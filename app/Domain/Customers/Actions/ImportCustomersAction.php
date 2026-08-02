<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\DTOs\CreateCustomerData;
use App\Domain\Shared\DTOs\ImportResultData;
use App\Domain\Shared\Services\SpreadsheetImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportCustomersAction
{
    public function __construct(
        private readonly SpreadsheetImportService $spreadsheetImportService,
        private readonly CreateCustomerAction $createCustomer,
    ) {}

    /**
     * The single source of truth for what a customer row (form or spreadsheet) must look
     * like — CustomerController::validated() delegates here too, so the two paths can never
     * silently drift apart.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'anniversary_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'medical_notes' => ['nullable', 'string'],
            'allergy_notes' => ['nullable', 'string'],
            // Deliberately not a strict 'boolean' rule: this app's own export writes "yes"/"no"
            // (readable in a spreadsheet), and filter_var(..., FILTER_VALIDATE_BOOLEAN) below
            // already handles that plus true/false/1/0/blank — a file this feature exports must
            // always be re-importable by this same feature.
            'is_vip' => ['nullable'],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function execute(UploadedFile $file): ImportResultData
    {
        $rows = $this->spreadsheetImportService->readRows($file);

        $importedCount = 0;
        $rowErrors = [];

        foreach ($rows as $index => $row) {
            $spreadsheetRowNumber = $index + 2; // +1 for the header row, +1 for 1-based rows.

            $validator = Validator::make($row, self::validationRules());

            if ($validator->fails()) {
                $rowErrors[] = [
                    'row' => $spreadsheetRowNumber,
                    'errors' => $validator->errors()->all(),
                ];

                continue;
            }

            $data = $validator->validated();

            DB::transaction(function () use ($data) {
                $this->createCustomer->execute(new CreateCustomerData(
                    name: $data['name'],
                    phone: $data['phone'] ?? null,
                    whatsappNumber: $data['whatsapp_number'] ?? null,
                    dateOfBirth: $data['date_of_birth'] ?? null,
                    anniversaryDate: $data['anniversary_date'] ?? null,
                    gender: $data['gender'] ?? null,
                    email: $data['email'] ?? null,
                    city: $data['city'] ?? null,
                    state: $data['state'] ?? null,
                    occupation: $data['occupation'] ?? null,
                    medicalNotes: $data['medical_notes'] ?? null,
                    allergyNotes: $data['allergy_notes'] ?? null,
                    isVip: filter_var($data['is_vip'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    referralCode: $data['referral_code'] ?? null,
                ));
            });

            $importedCount++;
        }

        return new ImportResultData(importedCount: $importedCount, rowErrors: $rowErrors);
    }
}
