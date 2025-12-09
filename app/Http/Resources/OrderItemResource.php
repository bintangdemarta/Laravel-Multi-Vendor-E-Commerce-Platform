<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'sku_code' => $this->sku_code,
            'variant' => $this->variant,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'vendor' => [
                'id' => $this->vendor_id,
                'name' => $this->vendor->shop_name ?? null,
            ],
            'status' => $this->status,
            'tracking_number' => $this->tracking_number,
        ];
    }
}
