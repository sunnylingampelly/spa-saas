<?php

namespace Database\Factories;

use App\Domain\Appointments\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $startsAt = $this->faker->dateTimeBetween('now', '+1 week');

        return [
            'booking_type' => 'advance',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+60 minutes'),
            'status' => 'booked',
        ];
    }
}
