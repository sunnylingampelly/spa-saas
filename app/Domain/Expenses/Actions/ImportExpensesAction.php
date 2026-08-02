<?php

namespace App\Domain\Expenses\Actions;

use App\Domain\Expenses\DTOs\CreateExpenseData;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Shared\DTOs\ImportResultData;
use App\Domain\Shared\Services\SpreadsheetImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportExpensesAction
{
    public function __construct(
        private readonly SpreadsheetImportService $spreadsheetImportService,
        private readonly CreateExpenseAction $createExpense,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'category' => ['required', 'string', 'in:'.implode(',', Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function execute(UploadedFile $file, int $createdByUserId): ImportResultData
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

            DB::transaction(function () use ($data, $createdByUserId) {
                $this->createExpense->execute(new CreateExpenseData(
                    category: $data['category'],
                    amount: (float) $data['amount'],
                    expenseDate: $data['expense_date'],
                    notes: $data['notes'] ?? null,
                    createdByUserId: $createdByUserId,
                ));
            });

            $importedCount++;
        }

        return new ImportResultData(importedCount: $importedCount, rowErrors: $rowErrors);
    }
}
