<?php

namespace App\Domain\Employees\DTOs;

use Spatie\LaravelData\Data;

class MarkAttendanceData extends Data
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $attendanceDate,
        public readonly string $status = 'present',
        public readonly ?string $checkIn = null,
        public readonly ?string $checkOut = null,
        public readonly ?string $notes = null,
    ) {}
}
