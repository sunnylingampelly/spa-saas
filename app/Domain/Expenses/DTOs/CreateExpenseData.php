<?php

namespace App\Domain\Expenses\DTOs;

use Spatie\LaravelData\Data;

class CreateExpenseData extends Data
{
    public function __construct(
        public readonly string $category,
        public readonly float $amount,
        public readonly string $expenseDate,
        public readonly ?string $notes = null,
        public readonly ?int $createdByUserId = null,
    ) {}
}
