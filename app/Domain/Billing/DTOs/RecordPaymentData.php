<?php

namespace App\Domain\Billing\DTOs;

use Spatie\LaravelData\Data;

class RecordPaymentData extends Data
{
    public function __construct(
        public readonly string $method,
        public readonly float $amount,
        public readonly ?string $referenceNumber = null,
    ) {}
}
