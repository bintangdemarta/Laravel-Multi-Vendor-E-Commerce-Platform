<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
| Routes for payment gateway webhooks and callbacks
*/

// Midtrans payment webhook (POST)
Route::post('/webhook/midtrans', [PaymentWebhookController::class, 'midtransNotification'])
    ->name('webhook.midtrans');

// Payment finish redirect (GET)
Route::get('/payment/finish', [PaymentWebhookController::class, 'paymentFinish'])
    ->name('payment.finish');
