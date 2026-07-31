<?php

namespace App\Domain\Employees\Repositories;

use App\Domain\Employees\Models\EmployeeAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EloquentEmployeeAttendanceRepository implements EmployeeAttendanceRepositoryInterface
{
    public function summaryForEmployee(int $employeeId, Carbon $from, Carbon $to): array
    {
        return EmployeeAttendance::where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    public function summaryForSpa(int $spaId, Carbon $month): Collection
    {
        return EmployeeAttendance::withoutGlobalScopes()
            ->where('spa_id', $spaId)
            ->whereBetween('attendance_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('employee_id, status, count(*) as total')
            ->groupBy('employee_id', 'status')
            ->get();
    }
}
