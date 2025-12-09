<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Sku;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_cart()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'vendors',
                'subtotal',
                'total_items',
                'total_weight',
            ]);
    }

    public function test_can_add_item_to_cart()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/cart/items', [
            'sku_id' => $sku->id,
            'quantity' => 2,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_cannot_add_out_of_stock_item()
    {
        $user = User::factory()->create();
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 2,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/cart/items', [
            'sku_id' => $sku->id,
            'quantity' => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_cart_item_quantity()
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'stock' => 10,
        ]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'sku_id' => $sku->id,
            'quantity' => 2,
            'price' => $sku->price,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/cart/items/{$cartItem->id}", [
            'quantity' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_can_remove_cart_item()
    {
        $user = User::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        $vendor = Vendor::factory()->create();
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);
        $sku = Sku::factory()->create(['product_id' => $product->id]);
        $cartItem = CartItem::create([
            'cart_id' => $cart->id,
            'sku_id' => $sku->id,
            'quantity' => 2,
            'price' => $sku->price,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/cart/items/{$cartItem->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
