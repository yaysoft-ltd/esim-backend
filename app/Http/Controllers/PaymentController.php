<?php

namespace App\Http\Controllers;

use App\Jobs\OrderStoreJob;
use App\Jobs\TopupStoreJob;
use App\Mail\AllMail;
use App\Models\Currency;
use App\Models\EsimOrder;
use App\Models\PointTransaction;
use App\Models\UserNotification;
use App\Models\UserPoint;
use App\Models\Payment;
use App\Notifications\OrderPlacedNoti;
use App\Services\CashfreeService;
use App\Services\RazorpayService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Google_Client;
use Google_Service_AndroidPublisher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $cashfree;

    public function __construct(CashfreeService $cashfree)
    {
        $this->cashfree = $cashfree;
    }

    public function verifyRazorpayPayment(Request $request, RazorpayService $razorpayService, StripeService $stripeService)
    {
        $data = $request->all();
        if (systemflag('paymentMode') == 'Stripe') {
            $verification = $stripeService->verifyPayment($data);
        } elseif (systemflag('paymentMode') == 'Cashfree') {
            $orderId = $data['gateway_order_id'] ?? $data['order_id'] ?? null;
            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID missing for Cashfree verification'
                ], 400);
            }
            $verification = $this->cashfree->verifyPayment($orderId);
        } elseif (systemflag('paymentMode') == 'GpayInAppPurchase') {
            $verification = $this->verifyGooglePurchase($data['order_id'], $data['purchase_token'], $data['gateway_order_id'], $data['package_name'], $data['google_order_id']);
        } else {
            $verification = $razorpayService->verifyPayment($data);
        }

        if (!$verification['status']) {
            return response()->json([
                'success' => false,
                'message' => $verification['message']
            ], 400);
        }
        $order = EsimOrder::find($verification['order_id']);
        if ($order) {
            $order->status = 'paid';
            $order->save();
            $order->user->notify(new OrderPlacedNoti($order));
            UserNotification::create([
                'user_id' => $order->user_id,
                'title' => 'Order Placed',
                'type' => 1,
                'description' => 'Your order #' . $order->order_ref . ' has been placed successfully!',
            ]);
            $orderTemp = emailTemplate('orderPlaced');
            $companyName = systemflag('appName');
            $template = $orderTemp->description;
            $tempSubject = $orderTemp->subject;
            $data = [
                'orderId' => $order->order_ref,
                'packageName' => $order->package->name,
                'orderAmount' => $order->currency->symbol . $order->total_amount,
                'orderDate' => date('d M Y', strtotime($order->created_at)),
                'companyName' => $companyName,
                'date' => date('Y')
            ];

            Mail::to($order->user->email)->send(new AllMail($template, $data, $tempSubject));
            $points = PointTransaction::where('from_user_id', $order->user_id)->where('status', 0)->first();
            if ($points) {
                $creditRefUser = UserPoint::where('user_id', $points->user_id)->first();
                if (!$creditRefUser) {
                    $creditRefUser = UserPoint::create([
                        'user_id' => $points->user_id,
                        'balance' => 0,
                    ]);
                }
                $creditRefUser->increment('balance', $points->point);
                $points->balance = $creditRefUser->balance;
                $points->status = 1;
                $points->save();
            }
            if ($order->package->type == 'topup') {
                TopupStoreJob::dispatch($request->iccid, $order->package->airalo_package_id, $order->user->id, $order->id);
            } else {
                OrderStoreJob::dispatch($order->id, $order->package->airalo_package_id, $order->user);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment successful',
            'payment' => $order
        ]);
    }

    private function verifyGooglePurchase($orderId, $purchaseToken, $productId, $packageName, $googleOrderId)
    {
        try {
            // Google Service account
            $client = new \Google_Client();
            $client->setAuthConfig(storage_path('app/google/esim-firebase.json'));
            $client->addScope(\Google_Service_AndroidPublisher::ANDROIDPUBLISHER);

            $service = new \Google_Service_AndroidPublisher($client);
            $productId  = str_replace('-', '_', $productId);
            $purchase = $service->purchases_products->get(
                $packageName,
                $productId,
                $purchaseToken
            );

            if ($purchase->getPurchaseState() === 0) {
                $paymentRecord =  Payment::where('order_id', $orderId)->first();
                $paymentRecord->payment_status = 'paid';
                $paymentRecord->gateway_order_id = $productId;
                $paymentRecord->payment_id = $googleOrderId ?? null;
                $paymentRecord->payment_mode = 'GoogleBilling';
                $paymentRecord->payment_ref = $purchaseToken;
                $paymentRecord->save();
                return [
                    'status'  => true,
                    'order_id' => $orderId,
                    'data'    => [
                        'purchase'          => $purchase,
                        'order_id'         => $orderId
                    ]
                ];
            }

            Log::error('Google Purchase not completed');
            return [
                'success' => false,
                'message' => 'Google Purchase not completed'
            ];
        } catch (\Exception $e) {
            Log::error('Google API Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Google API Error: ' . $e->getMessage()
            ];
        }
    }


    public function cancelPayment(Request $request)
    {
        $request->validate([
            'esim_order_id' => 'required',
            'code'       => 'required',
        ]);
        try {
            $orderId = $request->input('esim_order_id');
            $order = EsimOrder::where('id', $orderId)->first();
            $order->status = 'cancelled';
            $order->save();
            $payment = Payment::where('order_id', $order->id)->first();
            $payment->payment_status = 'cancelled';
            $payment->payment_ref = $request->code;
            $payment->save();
            return response()->json(['success' => true, 'message' => 'Payment cancelled!'], 200);
        } catch (\Exception $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        $orderId = $request->query('order_id');
        $data    = $this->cashfree->verifyPayment($orderId);

        return response()->json([
            'data'    => $data,
        ]);
    }

    /**
     * Cashfree server webhook
     */
    public function webhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:100',
            'data' => 'required|array',
            'data.order' => 'sometimes|required|array',
            'data.order.order_id' => 'required_with:data.order|string|max:255',
            'data.order.order_amount' => 'required_with:data.order|numeric|min:0',
            'data.order.order_currency' => 'required_with:data.order|string|size:3',
            'data.order.order_status' => 'required_with:data.order|string|max:50',
            'data.payment' => 'sometimes|required|array',
            'data.payment.cf_payment_id' => 'required_with:data.payment|string|max:255',
            'data.payment.payment_status' => 'required_with:data.payment|string|max:50',
            'data.payment.payment_amount' => 'required_with:data.payment|numeric|min:0',
            'data.payment.payment_currency' => 'required_with:data.payment|string|size:3',
            'data.payment.payment_time' => 'required_with:data.payment|date',
            'data.payment.payment_method' => 'sometimes|string|max:100',
            'data.customer_details' => 'sometimes|array',
            'data.customer_details.customer_id' => 'sometimes|string|max:255',
            'data.customer_details.customer_email' => 'sometimes|email|max:255',
            'data.customer_details.customer_phone' => 'sometimes|string|max:15',
        ]);
        if ($validator->fails()) {
            Log::error('Cashfree webhook validation failed', [
                'errors' => $validator->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook payload',
                'errors' => $validator->errors()
            ], 400);
        }

        $payload = $request->all();

        $this->cashfree->handleWebhook($payload);

        return response()->json(['success' => true]);
    }
}
