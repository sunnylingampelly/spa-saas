<?php

namespace App\Domain\Expenses\Actions;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Shared\Services\SpreadsheetExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportExpensesAction
{
    private const HEADINGS = ['category', 'amount', 'expense_date', 'notes'];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(): StreamedResponse
    {
        $rows = Expense::query()
            ->latest('expense_date')
            ->get()
            ->map(fn (Expense $expense) => [
                $expense->category,
                $expense->amount,
                optional($expense->expense_date)->toDateString(),
                $expense->notes,
            ]);

        return $this->exportService->download('expenses.xlsx', self::HEADINGS, $rows);
    }

    public function template(): StreamedResponse
    {
        return $this->exportService->download('expenses-import-template.xlsx', self::HEADINGS, []);
    }
}
