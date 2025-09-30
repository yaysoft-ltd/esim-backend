<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class EsimPackageResource extends JsonResource
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
            'id' => $this->id,
            'operator_id' => $this->operator_id,
            'airalo_package_id' => $this->airalo_package_id,
            'name' => $this->name,
            'type' => $this->type,
            'day' => $this->day,
            'is_unlimited' => $this->is_unlimited,
            'short_info' => $this->short_info,
            'data' => $this->data,
            'net_price' => $netPrice,
            'country' => $this->operator->country ?? null,
            'region' => $this->operator->region ?? null,
            'is_active' => $this->is_active,
            'is_popular' => $this->is_popular,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
