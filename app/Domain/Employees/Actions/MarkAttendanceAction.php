<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\DTOs\MarkAttendanceData;
use App\Domain\Employees\Models\EmployeeAttendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MarkAttendanceAction
{
    /**
     * @param  array<MarkAttendanceData>  $entries
     */
    public function execute(array $entries): Collection
    {
        return DB::transaction(function () use ($entries) {
            $records = new Collection;

            foreach ($entries as $entry) {
                $records->push(EmployeeAttendance::updateOrCreate(
                    [
                        'employee_id' => $entry->employeeId,
                        'attendance_date' => $entry->attendanceDate,
                    ],
                    [
                        'status' => $entry->status,
                        'check_in' => $entry->checkIn,
                        'check_out' => $entry->checkOut,
                        'notes' => $entry->notes,
                    ]
                ));
            }

            return $records;
        });
    }
}
