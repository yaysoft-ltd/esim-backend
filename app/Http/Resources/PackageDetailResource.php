<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $getPrice = packagePrice($this->id);
        $netPrice = $getPrice['totalAmount'];
        return [
            "id" => $this->id,
            "operator_id" => $this->operator_id,
            "airalo_package_id" => $this->airalo_package_id,
            "name" => $this->name,
            "type" => $this->type,
            "day" => $this->day,
            "is_unlimited" => (bool) $this->is_unlimited,
            "short_info" => $this->short_info,
            "net_price" => $netPrice,
            "data" => $this->data,
            "price" => $this->price,
            "is_active" => (bool) $this->is_active,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "is_fair_usage_policy" => (bool) $this->is_fair_usage_policy,
            "fair_usage_policy" => $this->fair_usage_policy,
            "qr_installation" => $this->qr_installation,
            "manual_installation" => $this->manual_installation,
            "country" => $this->country,
            "operator" => $this->operator
        ];
    }
}
