<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\OrderService;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sku;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    public function test_can_create_order_from_cart()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
            'weight' => 500,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'sku_id' => $sku->id,
            'quantity' => 2,
            'price' => $sku->price,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $orderData = [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'shipping_options' => [
                [
                    'vendor_id' => $vendor->id,
                    'courier_name' => 'JNE',
                    'service' => 'REG',
                    'cost' => 20000,
                ]
            ],
        ];

        $order = $this->orderService->createOrder($cart, $orderData);

        $this->assertNotNull($order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertNotEmpty($order->order_number);
        $this->assertEquals(200000, $order->subtotal); // 2 items * 100000
        $this->assertEquals(20000, $order->shipping_cost);
    }

    public function test_order_reduces_stock()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'sku_id' => $sku->id,
            'quantity' => 3,
            'price' => $sku->price,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $orderData = [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'shipping_options' => [
                [
                    'vendor_id' => $vendor->id,
                    'courier_name' => 'JNE',
                    'service' => 'REG',
                    'cost' => 15000,
                ]
            ],
        ];

        $this->orderService->createOrder($cart, $orderData);

        $this->assertEquals(7, $sku->fresh()->stock); // 10 - 3 = 7
    }

    public function test_order_calculates_tax_correctly()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => 100000,
            'stock' => 10,
        ]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create([
            'cart_id' => $cart->id,
            'sku_id' => $sku->id,
            'quantity' => 1,
            'price' => $sku->price,
        ]);

        $address = Address::factory()->create(['user_id' => $user->id]);

        $orderData = [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'shipping_options' => [
                [
                    'vendor_id' => $vendor->id,
                    'courier_name' => 'JNE',
                    'service' => 'REG',
                    'cost' => 10000,
                ]
            ],
        ];

        $order = $this->orderService->createOrder($cart, $orderData);

        $this->assertEquals(11000, $order->tax_vat); // 11% of 100000
        $this->assertEquals(2500, $order->tax_marketplace_withholding); // 2.5% of 100000
        $this->assertEquals(13500, $order->tax_total);
    }
}
