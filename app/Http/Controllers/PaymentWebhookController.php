<?php

namespace App\Http\Controllers;

use App\Services\Payment\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    private MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Handle Midtrans payment notification webhook
     * 
     * Route: POST /webhook/midtrans
     */
    public function midtransNotification(Request $request)
    {
        // Log incoming webhook for debugging
        Log::info('Midtrans webhook received', [
            'data' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            // Process notification (idempotent)
            $result = $this->midtransService->handleNotification($request->all());

            if ($result['success']) {
                Log::info('Midtrans webhook processed successfully', $result);

                return response()->json([
                    'status' => 'success',
                    'message' => $result['message'],
                ], 200);
            }

            Log::error('Midtrans webhook processing failed', $result);

            return response()->json([
                'status' => 'error',
                'message' => $result['error'] ?? 'Processing failed',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Midtrans webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Handle payment finish redirect from Midtrans
     * 
     * Route: GET /payment/finish
     */
    public function paymentFinish(Request $request)
    {
        $orderNumber = $request->query('order_id');
        $statusCode = $request->query('status_code');
        $transactionStatus = $request->query('transaction_status');

        // Redirect to order detail page
        return redirect()->route('orders.show', ['order' => $orderNumber])
            ->with('payment_status', $transactionStatus);
    }
}
