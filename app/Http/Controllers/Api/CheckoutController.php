<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\Payment\MidtransService;
use App\Services\CartService;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private MidtransService $midtransService,
    ) {
    }

    /**
     * Calculate shipping for checkout
     * POST /api/checkout/shipping
     */
    public function calculateShipping(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
        ]);

        $cart = $this->cartService->getCart($request->user());
        $address = Address::findOrFail($validated['address_id']);

        // Verify address belongs to user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shippingOptions = $this->orderService->calculateShipping($cart, $address);

        return response()->json([
            'shipping_options' => $shippingOptions,
        ]);
    }

    /**
     * Create order from cart
     * POST /api/checkout/create-order
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'shipping_options' => 'required|array',
            'shipping_options.*.vendor_id' => 'required|exists:vendors,id',
            'shipping_options.*.courier_name' => 'required|string',
            'shipping_options.*.service' => 'required|string',
            'shipping_options.*.cost' => 'required|numeric',
        ]);

        $cart = $this->cartService->getCart($request->user());
        $address = Address::findOrFail($validated['address_id']);

        // Validate address belongs to user
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate cart
        $validation = $this->cartService->validateCart($cart);
        if (!$validation['valid']) {
            return response()->json([
                'message' => 'Cart validation failed',
                'errors' => $validation['errors'],
            ], 400);
        }

        // Create order
        $result = $this->orderService->createOrder(
            $cart,
            $address,
            $validated['shipping_options']
        );

        if (!$result['success']) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $result['error'],
            ], 400);
        }

        $order = $result['order'];

        // Generate payment token
        $paymentResult = $this->midtransService->createTransaction($order);

        if (!$paymentResult['success']) {
            return response()->json([
                'message' => 'Payment initialization failed',
                'error' => $paymentResult['error'],
            ], 400);
        }

        return response()->json([
            'order' => $this->orderService->getOrderSummary($order),
            'snap_token' => $paymentResult['snap_token'],
        ], 201);
    }

    /**
     * Get checkout summary before creating order
     * POST /api/checkout/summary
     */
    public function getSummary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address_id' => 'required|exists:addresses,id',
        ]);

        $cart = $this->cartService->getCart($request->user());
        $address = Address::findOrFail($validated['address_id']);

        return response()->json([
            'cart' => $this->cartService->getCartSummary($cart),
            'address' => [
                'id' => $address->id,
                'label' => $address->label,
                'recipient' => $address->recipient_name,
                'phone' => $address->phone,
                'full_address' => $address->full_address,
            ],
        ]);
    }
}
