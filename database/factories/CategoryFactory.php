<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'parent_id' => null,
            'name' => ucfirst($name),
            'slug' => \Illuminate\Support\Str::slug($name),
            'description' => fake()->sentence(),
            'commission_rate' => null,
            'is_active' => true,
            'lft' => 0,
            'rgt' => 0,
            'depth' => 0,
        ];
    }

    public function withCommission(float $rate = 0.12): static
    {
        return $this->state(fn(array $attributes) => [
            'commission_rate' => $rate,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
