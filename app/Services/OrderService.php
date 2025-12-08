<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Address;
use App\Services\CommissionService;
use App\Services\TaxService;
use App\Services\Shipping\RajaOngkirService;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CommissionService $commissionService,
        private TaxService $taxService,
        private RajaOngkirService $shippingService,
    ) {
    }

    /**
     * Create order from cart
     */
    public function createOrder(Cart $cart, Address $shippingAddress, array $shippingOptions): array
    {
        try {
            return DB::transaction(function () use ($cart, $shippingAddress, $shippingOptions) {
                // Calculate totals
                $subtotal = $cart->subtotal;
                $shippingCost = collect($shippingOptions)->sum('cost');

                // Calculate tax
                $taxData = $this->taxService->calculateOrderTax((object) ['subtotal' => $subtotal]);

                $total = $subtotal + $shippingCost + $taxData['total_tax'];

                // Create order
                $order = Order::create([
                    'order_number' => Order::generateOrderNumber(),
                    'user_id' => $cart->user_id,
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'tax_amount' => $taxData['total_tax'],
                    'vat_amount' => $taxData['vat_amount'],
                    'marketplace_withholding' => $taxData['marketplace_withholding'],
                    'total' => $total,
                    'status' => 'pending',
                    // Shipping address (snapshot)
                    'shipping_name' => $shippingAddress->recipient_name,
                    'shipping_phone' => $shippingAddress->phone,
                    'shipping_address' => $shippingAddress->address_line,
                    'shipping_city' => $shippingAddress->city->name,
                    'shipping_province' => $shippingAddress->province->name,
                    'shipping_postal_code' => $shippingAddress->postal_code,
                ]);

                // Create order items (grouped by vendor)
                foreach ($cart->getItemsByVendor() as $vendorId => $items) {
                    $vendorShipping = collect($shippingOptions)->firstWhere('vendor_id', $vendorId);

                    foreach ($items as $cartItem) {
                        // Calculate commission for this item
                        $itemSubtotal = $cartItem->sku->price * $cartItem->quantity;
                        $commission = $this->commissionService->calculateCommission((object) [
                            'vendor' => $cartItem->vendor,
                            'sku' => $cartItem->sku,
                            'subtotal' => $itemSubtotal,
                        ]);

                        // Calculate tax for this item
                        $itemTax = $this->taxService->calculateItemTax((object) ['subtotal' => $itemSubtotal]);

                        // Get variant details
                        $variantDetails = $cartItem->sku->attributeOptions->mapWithKeys(function ($option) {
                            return [$option->attribute->name => $option->value];
                        })->toArray();

                        // Create order item
                        $orderItem = $order->items()->create([
                            'sku_id' => $cartItem->sku_id,
                            'vendor_id' => $vendorId,
                            'product_name' => $cartItem->product->name,
                            'sku_code' => $cartItem->sku->sku_code,
                            'variant_details' => $variantDetails,
                            'price' => $cartItem->sku->price,
                            'quantity' => $cartItem->quantity,
                            'subtotal' => $itemSubtotal,
                            'commission_rate' => $commission['commission_rate'],
                            'commission_amount' => $commission['commission_amount'],
                            'vendor_earnings' => $commission['vendor_earnings'],
                            'tax_amount' => $itemTax,
                            'shipping_cost' => $vendorShipping['cost'] ?? 0,
                            'courier_name' => $vendorShipping['courier_name'] ?? null,
                            'courier_service' => $vendorShipping['service'] ?? null,
                            'status' => 'pending',
                        ]);

                        // Reserve stock
                        if (!$cartItem->sku->reserveStock($cartItem->quantity)) {
                            throw new \Exception("Failed to reserve stock for {$cartItem->product->name}");
                        }
                    }
                }

                // Record order creation in history
                $order->recordStatusChange('pending', 'Order created');

                // Clear cart
                $cart->items()->delete();

                return [
                    'success' => true,
                    'order' => $order->load('items'),
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Order creation failed', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate shipping for checkout
     */
    public function calculateShipping(Cart $cart, Address $destination): array
    {
        $itemsByVendor = $cart->getItemsByVendor();
        $shippingOptions = [];

        foreach ($itemsByVendor as $vendorId => $items) {
            $vendor = $items->first()->vendor;

            // Calculate total weight for this vendor
            $totalWeight = $items->sum(function ($item) {
                return ($item->sku->weight ?? 0) * $item->quantity;
            });

            // Get vendor's enabled couriers
            $couriers = $vendor->shippingSettings->enabled_couriers ?? ['jne', 'tiki', 'pos'];

            // Get shipping costs
            $costs = $this->shippingService->getMultipleCosts(
                $vendor->shippingSettings->origin_city_id,
                $destination->city->rajaongkir_id,
                max(1, $totalWeight), // Minimum 1 gram
                $couriers
            );

            if ($costs['success'] && !empty($costs['options'])) {
                // Use cheapest option by default
                $cheapest = $costs['options'][0];

                $shippingOptions[] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendor->shop_name,
                    'weight' => $totalWeight,
                    'selected' => $cheapest,
                    'options' => $costs['options'],
                ];
            }
        }

        return $shippingOptions;
    }

    /**
     * Get order summary for review before payment
     */
    public function getOrderSummary(Order $order): array
    {
        $itemsByVendor = $order->items->groupBy('vendor_id');

        return [
            'order_number' => $order->order_number,
            'subtotal' => $order->subtotal,
            'shipping_cost' => $order->shipping_cost,
            'tax' => [
                'vat' => $order->vat_amount,
                'withholding' => $order->marketplace_withholding,
                'total' => $order->tax_amount,
            ],
            'total' => $order->total,
            'status' => $order->status,
            'shipping_address' => [
                'name' => $order->shipping_name,
                'phone' => $order->shipping_phone,
                'address' => $order->shipping_address,
                'city' => $order->shipping_city,
                'province' => $order->shipping_province,
                'postal_code' => $order->shipping_postal_code,
            ],
            'vendors' => $itemsByVendor->map(function ($items, $vendorId) {
                return [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $items->first()->vendor->shop_name,
                    'items' => $items->map(function ($item) {
                        return [
                            'product_name' => $item->product_name,
                            'variant' => $item->variant_details,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'subtotal' => $item->subtotal,
                        ];
                    }),
                    'shipping' => [
                        'courier' => $items->first()->courier_name,
                        'service' => $items->first()->courier_service,
                        'cost' => $items->first()->shipping_cost,
                    ],
                ];
            })->values(),
        ];
    }
}
