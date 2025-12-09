<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Cart;
use App\Models\User;
use App\Models\Sku;
use App\Models\Product;
use App\Models\Vendor;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
    }

    public function test_can_create_guest_cart()
    {
        $cart = $this->cartService->getCart(null, 'test-session-123');

        $this->assertNotNull($cart);
        $this->assertEquals('test-session-123', $cart->session_id);
        $this->assertNull($cart->user_id);
    }

    public function test_can_create_user_cart()
    {
        $user = User::factory()->create();
        $cart = $this->cartService->getCart($user);

        $this->assertNotNull($cart);
        $this->assertEquals($user->id, $cart->user_id);
        $this->assertNull($cart->session_id);
    }

    public function test_can_add_item_to_cart()
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cart = $this->cartService->getCart(null, 'test-session');
        $result = $this->cartService->addItem($cart, $sku->id, 2);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $cart->fresh()->items()->first()->quantity);
    }

    public function test_cannot_add_item_with_insufficient_stock()
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 2,
            'is_active' => true,
        ]);

        $cart = $this->cartService->getCart(null, 'test-session');
        $result = $this->cartService->addItem($cart, $sku->id, 5);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Insufficient stock', $result['message']);
    }

    public function test_can_update_cart_item_quantity()
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cart = $this->cartService->getCart(null, 'test-session');
        $this->cartService->addItem($cart, $sku->id, 2);

        $cartItem = $cart->fresh()->items()->first();
        $result = $this->cartService->updateQuantity($cart, $cartItem->id, 5);

        $this->assertTrue($result['success']);
        $this->assertEquals(5, $cartItem->fresh()->quantity);
    }

    public function test_can_remove_cart_item()
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cart = $this->cartService->getCart(null, 'test-session');
        $this->cartService->addItem($cart, $sku->id, 2);

        $cartItem = $cart->fresh()->items()->first();
        $removed = $this->cartService->removeItem($cart, $cartItem->id);

        $this->assertTrue($removed);
        $this->assertEquals(0, $cart->fresh()->items()->count());
    }

    public function test_cart_summary_calculation()
    {
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => 100000,
            'weight' => 500,
            'stock' => 10,
            'is_active' => true,
        ]);

        $cart = $this->cartService->getCart(null, 'test-session');
        $this->cartService->addItem($cart, $sku->id, 3);

        $summary = $this->cartService->getCartSummary($cart->fresh());

        $this->assertEquals(300000, $summary['subtotal']);
        $this->assertEquals(3, $summary['total_items']);
        $this->assertEquals(1500, $summary['total_weight']);
        $this->assertCount(1, $summary['vendors']);
    }
}
