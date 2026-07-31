<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = 2000;

        return [
            'invoice_number' => 'INV/2026-27/'.$this->faker->unique()->numerify('####'),
            'public_token' => Str::random(40),
            'financial_year' => '2026-27',
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'taxable_amount' => $subtotal,
            'cgst_amount' => 180,
            'sgst_amount' => 180,
            'igst_amount' => 0,
            'tip_amount' => 0,
            'total_amount' => $subtotal + 360,
            'paid_amount' => 0,
            'balance_amount' => $subtotal + 360,
            'status' => 'unpaid',
        ];
    }
}
