<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\Models\Employee;

class DeleteEmployeeAction
{
    public function execute(Employee $employee): void
    {
        $employee->delete();
    }
}
