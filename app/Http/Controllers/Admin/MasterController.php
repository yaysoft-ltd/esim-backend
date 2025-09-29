<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CreateInAppProductJob;
use App\Models\Country;
use App\Models\Currency;
use App\Models\EsimPackage;
use App\Models\Operator;
use App\Models\Region;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function regions(Request $request)
    {
        try {
            $regions = Region::get();
            return view('admin.masters.region', compact('regions'));
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function countries(Request $request)
    {
        try {
            $countries = Country::get();
            return view('admin.masters.countries', compact('countries'));
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function operators(Request $request)
    {
        try {
            $operators = Operator::get();
            return view('admin.masters.operators', compact('operators'));
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function packages(Request $request)
    {
        try {
            $limit = $request->input('limit', 15);

            $packages = EsimPackage::with(['operator', 'operator.country', 'operator.region'])
                ->when($request->filled('location_id'), function ($query) use ($request) {
                    $query->whereHas('operator', function ($q) use ($request) {
                        $q->where('country_id', $request->location_id)
                            ->orWhere('region_id', $request->location_id);
                    });
                })
                ->when($request->filled('operator_id'), function ($query) use ($request) {
                    $query->where('operator_id', $request->operator_id);
                })
                ->when($request->filled('package_id'), function ($query) use ($request) {
                    $query->where('id', $request->package_id);
                })
                ->when($request->filled('is_unlimited'), function ($query) use ($request) {
                    $query->where('is_unlimited', $request->is_unlimited);
                })
                ->paginate($limit)
                ->appends($request->only([
                    'limit',
                    'location_id',
                    'operator_id',
                    'package_id',
                    'is_unlimited'
                ]));

            // Collect all country and region IDs referenced by operators
            $countryIds = Operator::whereNotNull('country_id')->pluck('country_id')->unique();
            $regionIds = Operator::whereNotNull('region_id')->pluck('region_id')->unique();

            $countries = Country::whereIn('id', $countryIds)->get();
            $regions = Region::whereIn('id', $regionIds)->get();

            // Merge countries and regions into one list for the dropdown
            $locations = $countries->merge($regions);

            $operators =  Operator::select('id', 'name')->get();
            $packageNames = EsimPackage::select('id', 'name')->orderBy('name')->get();

            return view('admin.masters.packages', compact(
                'packages',
                'limit',
                'locations',
                'operators',
                'packageNames'
            ));
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }


    public function packageUpdate(Request $request)
    {
        try {
            $package = EsimPackage::findOrFail($request->id);
            $allowed = ['is_active', 'is_popular', 'is_recommend', 'is_best_value'];

            if (in_array($request->field, $allowed)) {
                $package->{$request->field} = $request->value;
                $package->save();

                return response()->json(['success' => true, 'message' => ucfirst(str_replace('_', ' ', $request->field)) . ' updated successfully.']);
            }
        } catch (\Exception $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }
    public function getPackagesByAjax(Request $request)
    {
        try {
            $packages = EsimPackage::with('operator')
                ->when($request->country_id, function ($query) use ($request) {
                    $query->whereHas('operator', function ($q) use ($request) {
                        $q->where('country_id', $request->country_id);
                    });
                })
                ->when($request->region_id, function ($query) use ($request) {
                    $query->whereHas('operator', function ($q) use ($request) {
                        $q->where('region_id', $request->region_id);
                    });
                })
                ->get();

            return response()->json($packages);
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function currencies(Request $request)
    {
        try {
            $currencies = Currency::get();
            return view('admin.masters.currency', compact('currencies'));
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }
    public function updatePoints(Request $request, $id)
    {
        try {
            $currency = Currency::find($id);
            $currency->referral_point = $request->points;
            $currency->save();
            return redirect()->back()->with('success', 'Update successfully!');
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    public function syncFromAiralo()
    {
        try {

            EsimPackage::where('is_active', 1)
                ->where('airalo_active', 1)
                ->select('airalo_package_id', 'name', 'short_info', 'prices', 'is_active','airalo_active')
                ->chunk(100, function ($packages) {
                    foreach ($packages as $pkg) {
                        $adminInclude = (float) systemflag('PackageCommission');
                        $airaloPrice  = (float) ($pkg->prices['net_price']['INR'] ?? 0);

                        // Add commission
                        $includePrice = ($airaloPrice * $adminInclude) / 100;
                        $netPrice = $airaloPrice + $includePrice;

                        // Round up to nearest 5
                        $netPrice = ceil($netPrice / 5) * 5;

                        // Dispatch job for each package
                        CreateInAppProductJob::dispatch(
                            $pkg->airalo_package_id,
                            $pkg->name,
                            $pkg->short_info ?? 'eSIM package',
                            $netPrice,
                            'INR'
                        );
                    }
                });

            return redirect()->back()->with('success', 'Sync To Google Play Started Successfully!');
        } catch (\Exception $th) {
            return back()->with('error', $th->getMessage());
        }
    }

}
