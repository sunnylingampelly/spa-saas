<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\DTOs\CreateEmployeeData;
use App\Domain\Employees\Models\Employee;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\DB;

class CreateEmployeeAction
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function execute(CreateEmployeeData $data): Employee
    {
        return DB::transaction(function () use ($data) {
            return Employee::create([
                'employee_code' => $this->nextEmployeeCode(),
                'name' => $data->name,
                'gender' => $data->gender,
                'phone' => $data->phone,
                'email' => $data->email,
                'address_line_1' => $data->addressLine1,
                'address_line_2' => $data->addressLine2,
                'city' => $data->city,
                'state' => $data->state,
                'pincode' => $data->pincode,
                'emergency_contact_name' => $data->emergencyContactName,
                'emergency_contact_phone' => $data->emergencyContactPhone,
                'joining_date' => $data->joiningDate,
                'department' => $data->department,
                'designation' => $data->designation,
                'salary' => $data->salary,
                'commission_type' => $data->commissionType,
                'commission_value' => $data->commissionValue,
                'experience_years' => $data->experienceYears,
                'skills' => $data->skills,
                'specializations' => $data->specializations,
                'notes' => $data->notes,
            ]);
        });
    }

    private function nextEmployeeCode(): string
    {
        $spaId = $this->tenantContext->getCurrentSpaId();

        $count = Employee::withoutGlobalScopes()->where('spa_id', $spaId)->withTrashed()->count();

        do {
            $count++;
            $code = sprintf('EMP-%04d', $count);
        } while (Employee::withoutGlobalScopes()->withTrashed()->where('spa_id', $spaId)->where('employee_code', $code)->exists());

        return $code;
    }
}
