<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Home', 'Office', 'Apartment']),
            'recipient_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'address_line' => fake()->streetAddress(),
            'district_id' => District::factory(),
            'postal_code' => fake()->postcode(),
            'notes' => fake()->optional()->sentence(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_default' => true,
        ]);
    }
}
