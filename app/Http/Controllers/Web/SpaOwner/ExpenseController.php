<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Expenses\Actions\CreateExpenseAction;
use App\Domain\Expenses\Actions\DeleteExpenseAction;
use App\Domain\Expenses\Actions\ExportExpensesAction;
use App\Domain\Expenses\Actions\ImportExpensesAction;
use App\Domain\Expenses\Actions\UpdateExpenseAction;
use App\Domain\Expenses\DTOs\CreateExpenseData;
use App\Domain\Expenses\Models\Expense;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Expenses/Index', [
            'expenses' => Expense::query()->latest('expense_date')->paginate(15),
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create', ['categories' => Expense::CATEGORIES]);
    }

    public function store(Request $request, CreateExpenseAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $action->execute(new CreateExpenseData(
            category: $data['category'],
            amount: $data['amount'],
            expenseDate: $data['expense_date'],
            notes: $data['notes'] ?? null,
            createdByUserId: $request->user()->id,
        ));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense): Response
    {
        $this->authorize('update', $expense);

        return Inertia::render('Expenses/Edit', ['expense' => $expense, 'categories' => Expense::CATEGORIES]);
    }

    public function update(Request $request, Expense $expense, UpdateExpenseAction $action): RedirectResponse
    {
        $this->authorize('update', $expense);

        $action->execute($expense, $this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense, DeleteExpenseAction $action): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $action->execute($expense);

        return back()->with('success', 'Expense removed.');
    }

    public function export(ExportExpensesAction $action): StreamedResponse
    {
        return $action->execute();
    }

    public function importTemplate(ExportExpensesAction $action): StreamedResponse
    {
        return $action->template();
    }

    public function import(Request $request, ImportExpensesAction $action): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv']]);

        $result = $action->execute($request->file('file'), $request->user()->id);

        return back()
            ->with('success', "Imported {$result->importedCount} expense(s).")
            ->with('import_errors', $result->rowErrors);
    }

    private function validated(Request $request): array
    {
        return $request->validate(ImportExpensesAction::validationRules());
    }
}
