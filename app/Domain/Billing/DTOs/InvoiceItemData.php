<?php

namespace App\Domain\Billing\DTOs;

use Spatie\LaravelData\Data;

class InvoiceItemData extends Data
{
    public function __construct(
        public readonly int $serviceId,
        public readonly int $quantity = 1,
        public readonly ?int $employeeId = null,
        public readonly ?float $unitPrice = null,
    ) {}
}
