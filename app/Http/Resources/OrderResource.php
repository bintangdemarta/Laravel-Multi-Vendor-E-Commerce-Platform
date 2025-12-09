<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'tax' => [
                'vat' => $this->tax_vat,
                'marketplace_withholding' => $this->tax_marketplace_withholding,
                'total' => $this->tax_total,
            ],
            'total' => $this->total,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping_address' => [
                'recipient_name' => $this->shipping_recipient_name,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
                'district' => $this->shipping_district,
                'city' => $this->shipping_city,
                'province' => $this->shipping_province,
                'postal_code' => $this->shipping_postal_code,
            ],
            'payment' => $this->whenLoaded('payment', function () {
                return [
                    'status' => $this->payment->status,
                    'method' => $this->payment->payment_method,
                    'paid_at' => $this->payment->paid_at?->toISOString(),
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
