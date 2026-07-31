<?php

namespace App\Domain\Employees\DTOs;

use Spatie\LaravelData\Data;

class RecordLeaveData extends Data
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $leaveType,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $reason = null,
        public readonly string $status = 'approved',
    ) {}
}
