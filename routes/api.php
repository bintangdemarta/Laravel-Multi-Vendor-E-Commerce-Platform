<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/products/search', [ProductController::class, 'search']);

    // Cart (guest + auth)
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);
});

// Authenticated routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Cart validation
    Route::post('/cart/validate', [CartController::class, 'validate']);

    // Checkout
    Route::post('/checkout/shipping', [CheckoutController::class, 'calculateShipping']);
    Route::post('/checkout/summary', [CheckoutController::class, 'getSummary']);
    Route::post('/checkout/create-order', [CheckoutController::class, 'createOrder']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{orderNumber}', [OrderController::class, 'show']);
    Route::post('/orders/{orderNumber}/cancel', [OrderController::class, 'cancel']);
});
