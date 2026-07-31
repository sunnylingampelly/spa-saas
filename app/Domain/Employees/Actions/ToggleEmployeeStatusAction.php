<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\Models\Employee;

class ToggleEmployeeStatusAction
{
    public function execute(Employee $employee, string $status): Employee
    {
        $employee->update(['status' => $status]);

        return $employee;
    }
}
