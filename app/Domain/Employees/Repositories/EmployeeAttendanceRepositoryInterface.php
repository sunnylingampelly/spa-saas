<?php

namespace App\Domain\Employees\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface EmployeeAttendanceRepositoryInterface
{
    public function summaryForEmployee(int $employeeId, Carbon $from, Carbon $to): array;

    public function summaryForSpa(int $spaId, Carbon $month): Collection;
}
