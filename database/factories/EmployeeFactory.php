<?php

namespace Database\Factories;

use App\Domain\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-'.$this->faker->unique()->numerify('####'),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'phone' => $this->faker->numerify('9#########'),
            'email' => $this->faker->safeEmail(),
            'joining_date' => $this->faker->date(),
            'department' => $this->faker->randomElement(['Therapy', 'Front Desk', 'Management']),
            'designation' => $this->faker->jobTitle(),
            'salary' => $this->faker->numberBetween(15000, 60000),
            'commission_type' => 'percentage',
            'commission_value' => $this->faker->numberBetween(5, 20),
            'experience_years' => $this->faker->numberBetween(0, 15),
            'status' => 'active',
        ];
    }
}
