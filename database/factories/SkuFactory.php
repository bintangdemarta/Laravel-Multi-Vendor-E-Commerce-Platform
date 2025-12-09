<?php

namespace Database\Factories;

use App\Models\Sku;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkuFactory extends Factory
{
    protected $model = Sku::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku_code' => 'SKU-' . fake()->unique()->numerify('########'),
            'price' => fake()->numberBetween(10000, 1000000),
            'compare_at_price' => null,
            'cost_price' => fake()->numberBetween(5000, 500000),
            'stock' => fake()->numberBetween(0, 100),
            'reserved_stock' => 0,
            'sold_count' => 0,
            'weight' => fake()->numberBetween(100, 5000), // grams
            'length' => fake()->numberBetween(10, 100), // cm
            'width' => fake()->numberBetween(10, 100),
            'height' => fake()->numberBetween(10, 100),
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => 0,
        ]);
    }

    public function inStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'stock' => fake()->numberBetween(10, 100),
        ]);
    }

    public function withDiscount(): static
    {
        return $this->state(function (array $attributes) {
            $price = $attributes['price'];
            return [
                'compare_at_price' => $price * 1.3, // 30% discount
            ];
        });
    }
}
