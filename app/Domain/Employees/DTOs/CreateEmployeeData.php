<?php

namespace App\Domain\Employees\DTOs;

use Spatie\LaravelData\Data;

class CreateEmployeeData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $gender = null,
        public readonly ?string $phone = null,
        public readonly ?string $email = null,
        public readonly ?string $addressLine1 = null,
        public readonly ?string $addressLine2 = null,
        public readonly ?string $city = null,
        public readonly ?string $state = null,
        public readonly ?string $pincode = null,
        public readonly ?string $emergencyContactName = null,
        public readonly ?string $emergencyContactPhone = null,
        public readonly ?string $joiningDate = null,
        public readonly ?string $department = null,
        public readonly ?string $designation = null,
        public readonly ?float $salary = null,
        public readonly string $commissionType = 'percentage',
        public readonly float $commissionValue = 0,
        public readonly int $experienceYears = 0,
        public readonly array $skills = [],
        public readonly array $specializations = [],
        public readonly ?string $notes = null,
    ) {}
}
