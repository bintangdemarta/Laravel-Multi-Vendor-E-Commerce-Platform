<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'vendor_id' => Vendor::factory(),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'name' => ucfirst($name),
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(),
            'meta_title' => ucfirst($name),
            'meta_description' => fake()->sentence(),
            'meta_keywords' => implode(',', fake()->words(5)),
            'status' => 'published',
            'featured' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'published',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn(array $attributes) => [
            'featured' => true,
        ]);
    }
}
