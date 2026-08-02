<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\Models\Employee;
use App\Domain\Shared\Services\SpreadsheetExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportEmployeesAction
{
    private const HEADINGS = [
        'name', 'gender', 'phone', 'email', 'address_line_1', 'address_line_2', 'city', 'state',
        'pincode', 'emergency_contact_name', 'emergency_contact_phone', 'joining_date',
        'department', 'designation', 'salary', 'commission_type', 'commission_value',
        'experience_years', 'notes',
    ];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(): StreamedResponse
    {
        $rows = Employee::query()
            ->latest()
            ->get()
            ->map(fn (Employee $employee) => [
                $employee->name,
                $employee->gender,
                $employee->phone,
                $employee->email,
                $employee->address_line_1,
                $employee->address_line_2,
                $employee->city,
                $employee->state,
                $employee->pincode,
                $employee->emergency_contact_name,
                $employee->emergency_contact_phone,
                optional($employee->joining_date)->toDateString(),
                $employee->department,
                $employee->designation,
                $employee->salary,
                $employee->commission_type,
                $employee->commission_value,
                $employee->experience_years,
                $employee->notes,
            ]);

        return $this->exportService->download('employees.xlsx', self::HEADINGS, $rows);
    }

    public function template(): StreamedResponse
    {
        return $this->exportService->download('employees-import-template.xlsx', self::HEADINGS, []);
    }
}
