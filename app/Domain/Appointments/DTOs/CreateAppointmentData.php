<?php

namespace App\Domain\Appointments\DTOs;

use Spatie\LaravelData\Data;

class CreateAppointmentData extends Data
{
    public function __construct(
        public readonly int $customerId,
        public readonly int $serviceId,
        public readonly string $startsAt,
        public readonly ?int $employeeId = null,
        public readonly string $bookingType = 'advance',
        public readonly ?string $notes = null,
    ) {}
}
