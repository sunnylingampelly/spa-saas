<?php

namespace App\Domain\Services\DTOs;

use Spatie\LaravelData\Data;

class CreateServiceData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly int $durationMinutes,
        public readonly float $price,
        public readonly ?int $serviceCategoryId = null,
        public readonly ?string $description = null,
        public readonly ?float $offerPrice = null,
        public readonly float $gstRate = 18.00,
        public readonly ?string $hsnSacCode = null,
        public readonly string $commissionType = 'percentage',
        public readonly float $commissionValue = 0,
        public readonly ?string $colorHex = null,
        public readonly string $status = 'active',
    ) {}
}
