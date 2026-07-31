<?php

namespace Database\Factories;

use App\Domain\Services\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Deep Tissue Massage', 'Swedish Massage', 'Thai Massage', 'Body Polish']).' '.$this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->sentence(),
            'duration_minutes' => $this->faker->randomElement([30, 45, 60, 90]),
            'price' => $this->faker->numberBetween(800, 4000),
            'gst_rate' => 18.00,
            'commission_type' => 'percentage',
            'commission_value' => $this->faker->numberBetween(5, 15),
            'color_hex' => $this->faker->hexColor(),
            'status' => 'active',
        ];
    }
}
