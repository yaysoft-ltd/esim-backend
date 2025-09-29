<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class TopupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $adminIncludeTopup = systemflag('TopUpCommission');
        // Determine base net price
        $netPrice = $this['net_price'] ?? 0;
        // Add top-up percentage
        $includePrice = $netPrice * $adminIncludeTopup / 100;
        $netPriceWithTopup = $includePrice + $netPrice;
        $netPriceWithTopup = ceil($netPriceWithTopup / 5) * 5;

        return [
            "id" => $this['id'],
            "type" => $this['type'],
            "day" => $this['day'],
            "is_unlimited" => $this['is_unlimited'],
            "title" => $this['title'],
            "data" => $this['data'],
            "short_info" => $this['short_info'],
            "voice" => $this['voice'] ?? 0,
            "text" => $this['text'] ?? 0,
            "net_price" => $netPriceWithTopup,
        ];
    }
}
