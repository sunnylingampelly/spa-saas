<?php

namespace App\Domain\Tenancy\DTOs;

use Spatie\LaravelData\Data;

class CreateSpaData extends Data
{
    public function __construct(
        public readonly int $ownerUserId,
        public readonly string $name,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $gstNumber = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
    ) {}
}
