<?php

namespace App\Domain\Billing\DTOs;

use Spatie\LaravelData\Data;

class CreateInvoiceData extends Data
{
    /**
     * @param  array<InvoiceItemData>  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly ?int $customerId = null,
        public readonly ?string $guestName = null,
        public readonly ?string $guestPhone = null,
        public readonly ?int $appointmentId = null,
        public readonly ?string $discountType = null,
        public readonly float $discountValue = 0,
        public readonly float $tipAmount = 0,
        public readonly ?string $notes = null,
    ) {}
}
