<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\CommissionService;

/**
 * Midtrans Payment Gateway Service
 * 
 * Handles Snap token generation and webhook notifications
 * Requires: composer require midtrans/midtrans-php
 */
class MidtransService
{
    private CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
        $this->configureMidtrans();
    }

    /**
     * Configure Midtrans settings
     */
    private function configureMidtrans(): void
    {
        // Configuration will work after installing midtrans/midtrans-php package
        if (class_exists('\Midtrans\Config')) {
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
            \Midtrans\Config::$isSanitized = config('services.midtrans.is_sanitized');
            \Midtrans\Config::$is3ds = config('services.midtrans.is_3ds');
        }
    }

    /**
     * Create Snap payment token for order
     */
    public function createTransaction(Order $order): array
    {
        // Prepare transaction details
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email' => $order->user->email,
                'phone' => $order->shipping_phone,
            ],
            'item_details' => $this->buildItemDetails($order),
            'callbacks' => [
                'finish' => route('payment.finish'),
            ],
        ];

        try {
            // Generate Snap token
            if (class_exists('\Midtrans\Snap')) {
                $snapToken = \Midtrans\Snap::getSnapToken($params);
            } else {
                // For development without package installed
                $snapToken = 'SNAP_TOKEN_' . $order->order_number;
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'transaction_id' => $order->order_number,
                'payment_method' => 'midtrans',
                'amount' => $order->total,
                'status' => 'pending',
            ]);

            return [
                'success' => true,
                'snap_token' => $snapToken,
                'order_number' => $order->order_number,
            ];
        } catch (\Exception $e) {
            \Log::error('Midtrans token generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build item details for Midtrans
     */
    private function buildItemDetails(Order $order): array
    {
        $items = [];

        // Add order items
        foreach ($order->items as $item) {
            $items[] = [
                'id' => $item->sku_id,
                'price' => (int) $item->price,
                'quantity' => $item->quantity,
                'name' => substr($item->product_name, 0, 50), // Midtrans limit
            ];
        }

        // Add shipping as separate item
        if ($order->shipping_cost > 0) {
            $items[] = [
                'id' => 'SHIPPING',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => 'Shipping Cost',
            ];
        }

        // Add tax as separate item
        if ($order->tax_amount > 0) {
            $items[] = [
                'id' => 'TAX',
                'price' => (int) $order->tax_amount,
                'quantity' => 1,
                'name' => 'Tax (VAT 11%)',
            ];
        }

        return $items;
    }

    /**
     * Handle Midtrans webhook notification (idempotent)
     */
    public function handleNotification(array $notificationData): array
    {
        try {
            // Verify signature if Midtrans package is available
            if (class_exists('\Midtrans\Notification')) {
                $notification = new \Midtrans\Notification();
                $orderNumber = $notification->order_id;
                $transactionStatus = $notification->transaction_status;
                $fraudStatus = $notification->fraud_status;
                $transactionId = $notification->transaction_id;
            } else {
                // For development
                $orderNumber = $notificationData['order_id'] ?? null;
                $transactionStatus = $notificationData['transaction_status'] ?? null;
                $fraudStatus = $notificationData['fraud_status'] ?? 'accept';
                $transactionId = $notificationData['transaction_id'] ?? null;
            }

            if (!$orderNumber) {
                throw new \Exception('Invalid notification: missing order_id');
            }

            // Find order
            $order = Order::where('order_number', $orderNumber)->firstOrFail();

            // Idempotency check: if already paid, skip processing
            if ($order->isPaid()) {
                return [
                    'success' => true,
                    'message' => 'Order already paid (idempotent)',
                    'order_id' => $order->id,
                ];
            }

            // Find or create payment record
            $payment = Payment::firstOrCreate(
                ['order_id' => $order->id],
                [
                    'transaction_id' => $transactionId,
                    'payment_method' => 'midtrans',
                    'amount' => $order->total,
                    'status' => 'pending',
                ]
            );

            // Update payment with gateway response
            $payment->update([
                'gateway_response' => $notificationData,
                'transaction_id' => $transactionId,
            ]);

            // Process payment based on status
            $result = $this->processPaymentStatus(
                $order,
                $payment,
                $transactionStatus,
                $fraudStatus
            );

            return $result;

        } catch (\Exception $e) {
            \Log::error('Midtrans notification handling failed', [
                'data' => $notificationData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process payment based on Midtrans status
     */
    private function processPaymentStatus(
        Order $order,
        Payment $payment,
        string $transactionStatus,
        string $fraudStatus
    ): array {
        $statusMessage = '';

        // Handle successful payment
        if (in_array($transactionStatus, ['capture', 'settlement'])) {
            if ($fraudStatus == 'accept') {
                $payment->markAsSuccess();
                $order->markAsPaid();

                // Process commissions
                $this->commissionService->processOrderCommissions($order);

                $statusMessage = 'Payment successful';
            } else {
                $payment->markAsFailed('Fraud detected: ' . $fraudStatus);
                $statusMessage = 'Payment failed: fraud check';
            }
        }
        // Handle pending payment
        elseif ($transactionStatus == 'pending') {
            $payment->update(['status' => 'pending']);
            $statusMessage = 'Payment pending';
        }
        // Handle failed/cancelled payment
        elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $payment->markAsFailed('Transaction ' . $transactionStatus);
            $order->cancel('Payment ' . $transactionStatus);
            $statusMessage = 'Payment ' . $transactionStatus;
        }

        return [
            'success' => true,
            'message' => $statusMessage,
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment_status' => $payment->status,
        ];
    }

    /**
     * Check transaction status from Midtrans API
     */
    public function checkTransactionStatus(string $orderNumber): array
    {
        try {
            if (class_exists('\Midtrans\Transaction')) {
                $status = \Midtrans\Transaction::status($orderNumber);

                return [
                    'success' => true,
                    'status' => $status->transaction_status,
                    'data' => $status,
                ];
            }

            return [
                'success' => false,
                'error' => 'Midtrans package not installed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel transaction on Midtrans
     */
    public function cancelTransaction(string $orderNumber): array
    {
        try {
            if (class_exists('\Midtrans\Transaction')) {
                $result = \Midtrans\Transaction::cancel($orderNumber);

                return [
                    'success' => true,
                    'message' => 'Transaction cancelled',
                    'data' => $result,
                ];
            }

            return [
                'success' => false,
                'error' => 'Midtrans package not installed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
