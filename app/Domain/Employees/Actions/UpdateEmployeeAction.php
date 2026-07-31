<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\Models\Employee;

class UpdateEmployeeAction
{
    public function execute(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee;
    }
}
