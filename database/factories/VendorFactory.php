<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'shop_name' => fake()->company() . ' Store',
            'slug' => fake()->unique()->slug(),
            'description' => fake()->paragraph(),
            'npwp' => $this->generateNPWP(),
            'bank_name' => fake()->randomElement(['BCA', 'Mandiri', 'BNI', 'BRI']),
            'bank_account_number' => fake()->numerify('##########'),
            'bank_account_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'status' => 'active',
            'commission_rate' => null,
            'balance' => 0,
        ];
    }

    private function generateNPWP(): string
    {
        return fake()->numerify('###############'); // 15 digits
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
