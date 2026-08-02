<?php

namespace App\Domain\Employees\Actions;

use App\Domain\Employees\DTOs\CreateEmployeeData;
use App\Domain\Shared\DTOs\ImportResultData;
use App\Domain\Shared\Services\SpreadsheetImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportEmployeesAction
{
    public function __construct(
        private readonly SpreadsheetImportService $spreadsheetImportService,
        private readonly CreateEmployeeAction $createEmployee,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'department' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'commission_type' => ['nullable', 'in:percentage,flat'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function execute(UploadedFile $file): ImportResultData
    {
        $rows = $this->spreadsheetImportService->readRows($file);

        $importedCount = 0;
        $rowErrors = [];

        foreach ($rows as $index => $row) {
            $spreadsheetRowNumber = $index + 2;

            $validator = Validator::make($row, self::validationRules());

            if ($validator->fails()) {
                $rowErrors[] = ['row' => $spreadsheetRowNumber, 'errors' => $validator->errors()->all()];

                continue;
            }

            $data = $validator->validated();

            DB::transaction(function () use ($data) {
                $this->createEmployee->execute(new CreateEmployeeData(
                    name: $data['name'],
                    gender: $data['gender'] ?? null,
                    phone: $data['phone'] ?? null,
                    email: $data['email'] ?? null,
                    addressLine1: $data['address_line_1'] ?? null,
                    addressLine2: $data['address_line_2'] ?? null,
                    city: $data['city'] ?? null,
                    state: $data['state'] ?? null,
                    pincode: $data['pincode'] ?? null,
                    emergencyContactName: $data['emergency_contact_name'] ?? null,
                    emergencyContactPhone: $data['emergency_contact_phone'] ?? null,
                    joiningDate: $data['joining_date'] ?? null,
                    department: $data['department'] ?? null,
                    designation: $data['designation'] ?? null,
                    salary: isset($data['salary']) ? (float) $data['salary'] : null,
                    commissionType: $data['commission_type'] ?? 'percentage',
                    commissionValue: isset($data['commission_value']) ? (float) $data['commission_value'] : 0,
                    experienceYears: isset($data['experience_years']) ? (int) $data['experience_years'] : 0,
                    notes: $data['notes'] ?? null,
                ));
            });

            $importedCount++;
        }

        return new ImportResultData(importedCount: $importedCount, rowErrors: $rowErrors);
    }
}
