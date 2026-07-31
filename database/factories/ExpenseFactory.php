<?php

namespace Database\Factories;

use App\Domain\Expenses\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'category' => $this->faker->randomElement(Expense::CATEGORIES),
            'amount' => $this->faker->numberBetween(500, 20000),
            'expense_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }
}
