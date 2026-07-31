<?php

namespace Database\Factories;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'customer_code' => 'CUST-'.$this->faker->unique()->numerify('####'),
            'name' => $this->faker->name(),
            'phone' => $this->faker->numerify('9#########'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'referral_code' => strtoupper($this->faker->unique()->bothify('??????')),
            'wallet_balance' => 0,
            'reward_points' => 0,
            'is_vip' => false,
            'customer_since' => $this->faker->date(),
        ];
    }
}
