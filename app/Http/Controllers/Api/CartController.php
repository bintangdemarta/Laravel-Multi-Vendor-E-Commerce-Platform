<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    /**
     * Get current cart
     * GET /api/cart
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user(),
            $request->session()->getId()
        );

        $summary = $this->cartService->getCartSummary($cart);

        return response()->json([
            'cart' => $summary,
        ]);
    }

    /**
     * Add item to cart
     * POST /api/cart/items
     */
    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku_id' => 'required|exists:skus,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->cartService->getCart(
            $request->user(),
            $request->session()->getId()
        );

        $result = $this->cartService->addItem(
            $cart,
            $validated['sku_id'],
            $validated['quantity']
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'cart' => $this->cartService->getCartSummary($cart->fresh()),
        ]);
    }

    /**
     * Update cart item quantity
     * PUT /api/cart/items/{id}
     */
    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $cart = $this->cartService->getCart(
            $request->user(),
            $request->session()->getId()
        );

        $result = $this->cartService->updateQuantity(
            $cart,
            $itemId,
            $validated['quantity']
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'message' => $result['message'],
            'cart' => $this->cartService->getCartSummary($cart->fresh()),
        ]);
    }

    /**
     * Remove item from cart
     * DELETE /api/cart/items/{id}
     */
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user(),
            $request->session()->getId()
        );

        $removed = $this->cartService->removeItem($cart, $itemId);

        if (!$removed) {
            return response()->json([
                'message' => 'Item not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => $this->cartService->getCartSummary($cart->fresh()),
        ]);
    }

    /**
     * Clear entire cart
     * DELETE /api/cart
     */
    public function clear(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user(),
            $request->session()->getId()
        );

        $this->cartService->clearCart($cart);

        return response()->json([
            'message' => 'Cart cleared',
        ]);
    }

    /**
     * Validate cart before checkout
     * POST /api/cart/validate
     */
    public function validate(Request $request): JsonResponse
    {
        $cart = $this->cartService->getCart(
            $request->user(),
            $request->session()->getId()
        );

        $validation = $this->cartService->validateCart($cart);

        return response()->json($validation);
    }
}
