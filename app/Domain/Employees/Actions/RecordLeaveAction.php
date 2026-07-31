<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\DTOs\RecordLeaveData;
use App\Domain\Employees\Models\EmployeeLeave;

class RecordLeaveAction
{
    public function execute(RecordLeaveData $data): EmployeeLeave
    {
        return EmployeeLeave::create([
            'employee_id' => $data->employeeId,
            'leave_type' => $data->leaveType,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'reason' => $data->reason,
            'status' => $data->status,
        ]);
    }
}
