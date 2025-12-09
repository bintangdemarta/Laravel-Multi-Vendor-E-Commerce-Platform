<?php

use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home
Route::get('/', function () {
    return Inertia::render('Home', [
        'categories' => [],
        'featuredProducts' => [],
        'topVendors' => [],
        'cartCount' => 0,
    ]);
})->name('home');

// Products
Route::get('/products', function () {
    return Inertia::render('Products/Index', [
        'products' => [
            'data' => [],
            'links' => [],
            'total' => 0,
        ],
        'categories' => [],
        'brands' => [],
        'filters' => [],
        'cartCount' => 0,
    ]);
})->name('products.index');

Route::get('/products/{slug}', function ($slug) {
    return Inertia::render('Products/Show', [
        'product' => [
            'name' => 'Sample Product',
            'slug' => $slug,
            'images' => ['/images/placeholder.jpg'],
            'vendor' => ['name' => 'Sample Vendor', 'city' => 'Jakarta'],
            'rating' => ['average' => 4.5, 'total' => 100],
            'price_range' => ['min' => 100000, 'max' => 150000],
            'skus' => [],
            'description' => 'Product description',
            'reviews' => [],
        ],
        'cartCount' => 0,
    ]);
})->name('products.show');

// Cart
Route::get('/cart', function () {
    return Inertia::render('Cart', [
        'cart' => [
            'vendors' => [],
            'subtotal' => 0,
            'total_items' => 0,
            'total_weight' => 0,
        ],
    ]);
})->name('cart');

// Checkout (authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', function () {
        return Inertia::render('Checkout/Index', [
            'cart' => [],
            'addresses' => [],
            'shippingOptions' => [],
        ]);
    })->name('checkout');

    Route::get('/orders', function () {
        return Inertia::render('Orders/Index', [
            'orders' => ['data' => [], 'links' => []],
        ]);
    })->name('orders.index');

    Route::get('/orders/{orderNumber}', function ($orderNumber) {
        return Inertia::render('Orders/Show', [
            'order' => [],
        ]);
    })->name('orders.show');
});

// Webhook routes
Route::post('/webhook/midtrans', [PaymentWebhookController::class, 'midtransNotification'])
    ->name('webhook.midtrans');

Route::get('/payment/finish', [PaymentWebhookController::class, 'paymentFinish'])
    ->name('payment.finish');

require __DIR__ . '/auth.php';
