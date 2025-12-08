<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    /**
     * Get or create cart for user/guest
     */
    public function getCart(?User $user = null, ?string $sessionId = null): Cart
    {
        if ($user) {
            return Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['expires_at' => now()->addDays(30)]
            );
        }

        if (!$sessionId) {
            $sessionId = Str::random(40);
        }

        return Cart::firstOrCreate(
            ['session_id' => $sessionId],
            ['expires_at' => now()->addDays(7)]
        );
    }

    /**
     * Add item to cart
     */
    public function addItem(Cart $cart, int $skuId, int $quantity = 1): array
    {
        $sku = Sku::with('product')->findOrFail($skuId);

        // Check if SKU is active
        if (!$sku->is_active) {
            return [
                'success' => false,
                'message' => 'Product is not available',
            ];
        }

        // Check stock availability
        if (!$sku->hasStock($quantity)) {
            return [
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $sku->getAvailableStock(),
            ];
        }

        // Check if item already in cart
        $cartItem = $cart->items()->where('sku_id', $skuId)->first();

        if ($cartItem) {
            // Update existing item
            $newQuantity = $cartItem->quantity + $quantity;

            if (!$sku->hasStock($newQuantity)) {
                return [
                    'success' => false,
                    'message' => 'Cannot add more. Available: ' . $sku->getAvailableStock(),
                ];
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            $cartItem = $cart->items()->create([
                'sku_id' => $skuId,
                'quantity' => $quantity,
            ]);
        }

        return [
            'success' => true,
            'message' => 'Item added to cart',
            'cart_item' => $cartItem->load('sku.product'),
        ];
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(Cart $cart, int $cartItemId, int $quantity): array
    {
        $cartItem = $cart->items()->findOrFail($cartItemId);

        if ($quantity <= 0) {
            $cartItem->delete();
            return [
                'success' => true,
                'message' => 'Item removed from cart',
            ];
        }

        if (!$cartItem->sku->hasStock($quantity)) {
            return [
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $cartItem->sku->getAvailableStock(),
            ];
        }

        $cartItem->update(['quantity' => $quantity]);

        return [
            'success' => true,
            'message' => 'Quantity updated',
            'cart_item' => $cartItem->fresh(),
        ];
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Cart $cart, int $cartItemId): bool
    {
        return $cart->items()->where('id', $cartItemId)->delete() > 0;
    }

    /**
     * Clear entire cart
     */
    public function clearCart(Cart $cart): bool
    {
        return $cart->items()->delete() > 0;
    }

    /**
     * Merge guest cart to user cart (after login)
     */
    public function mergeGuestCart(string $guestSessionId, User $user): Cart
    {
        $guestCart = Cart::where('session_id', $guestSessionId)->first();

        if (!$guestCart) {
            return $this->getCart($user);
        }

        $userCart = $this->getCart($user);

        DB::transaction(function () use ($guestCart, $userCart) {
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()->where('sku_id', $guestItem->sku_id)->first();

                if ($existingItem) {
                    // Merge quantities
                    $newQty = $existingItem->quantity + $guestItem->quantity;
                    $existingItem->update(['quantity' => min($newQty, $guestItem->sku->stock)]);
                } else {
                    // Move item to user cart
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }

            // Delete guest cart
            $guestCart->delete();
        });

        return $userCart->fresh('items.sku.product');
    }

    /**
     * Get cart summary for checkout
     */
    public function getCartSummary(Cart $cart): array
    {
        $itemsByVendor = $cart->getItemsByVendor();

        $summary = [
            'subtotal' => 0,
            'total_items' => 0,
            'total_weight' => 0,
            'vendors' => [],
        ];

        foreach ($itemsByVendor as $vendorId => $items) {
            $vendor = $items->first()->vendor;

            $vendorSubtotal = $items->sum(function ($item) {
                return $item->subtotal;
            });

            $vendorWeight = $items->sum(function ($item) {
                return ($item->sku->weight ?? 0) * $item->quantity;
            });

            $summary['vendors'][] = [
                'vendor_id' => $vendorId,
                'vendor_name' => $vendor->shop_name,
                'subtotal' => $vendorSubtotal,
                'weight' => $vendorWeight,
                'items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name,
                        'sku_code' => $item->sku->sku_code,
                        'price' => $item->sku->price,
                        'quantity' => $item->quantity,
                        'subtotal' => $item->subtotal,
                        'weight' => $item->sku->weight ?? 0,
                    ];
                }),
            ];

            $summary['subtotal'] += $vendorSubtotal;
            $summary['total_weight'] += $vendorWeight;
        }

        $summary['total_items'] = $cart->items->sum('quantity');

        return $summary;
    }

    /**
     * Validate cart before checkout
     */
    public function validateCart(Cart $cart): array
    {
        $errors = [];

        foreach ($cart->items as $item) {
            // Check if SKU still active
            if (!$item->sku->is_active) {
                $errors[] = "{$item->product->name} is no longer available";
                continue;
            }

            // Check stock availability
            if (!$item->canFulfill()) {
                $available = $item->sku->getAvailableStock();
                $errors[] = "{$item->product->name} - Only {$available} available, you have {$item->quantity} in cart";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}
