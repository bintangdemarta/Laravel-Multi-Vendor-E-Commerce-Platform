<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService)
    {
    }

    /**
     * List user's orders
     * GET /api/v1/orders
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with('items.vendor')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    /**
     * Get order detail
     * GET /api/v1/orders/{orderNumber}
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::with(['items.vendor', 'payment', 'statusHistories'])
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'order' => $this->orderService->getOrderSummary($order),
            'payment' => $order->payment ? [
                'status' => $order->payment->status,
                'method' => $order->payment->payment_method,
                'paid_at' => $order->payment->paid_at,
            ] : null,
            'history' => $order->statusHistories->map(fn($h) => [
                'status' => $h->status,
                'notes' => $h->notes,
                'created_at' => $h->occurred_at->format('Y-m-d H:i:s'),
            ]),
        ]);
    }

    /**
     * Cancel order
     * POST /api/v1/orders/{orderNumber}/cancel
     */
    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!$order->canBeCancelled()) {
            return response()->json([
                'message' => 'This order cannot be cancelled',
            ], 400);
        }

        $order->cancel('Cancelled by customer');

        return response()->json([
            'message' => 'Order cancelled successfully',
            'order' => $this->orderService->getOrderSummary($order->fresh()),
        ]);
    }
}
