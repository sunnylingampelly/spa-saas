<?php

namespace App\Domain\Customers\DTOs;

use Spatie\LaravelData\Data;

class CreateCustomerData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $whatsappNumber = null,
        public readonly ?string $dateOfBirth = null,
        public readonly ?string $anniversaryDate = null,
        public readonly ?string $gender = null,
        public readonly ?string $email = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $occupation = null,
        public readonly ?string $medicalNotes = null,
        public readonly ?string $allergyNotes = null,
        public readonly ?int $preferredServiceId = null,
        public readonly ?int $preferredEmployeeId = null,
        public readonly array $tags = [],
        public readonly bool $isVip = false,
        public readonly ?string $referralCode = null,
    ) {}
}
