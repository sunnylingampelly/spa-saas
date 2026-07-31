<?php

namespace App\Domain\Expenses\Actions;

use App\Domain\Expenses\DTOs\CreateExpenseData;
use App\Domain\Expenses\Models\Expense;

class CreateExpenseAction
{
    public function execute(CreateExpenseData $data): Expense
    {
        return Expense::create([
            'category' => $data->category,
            'amount' => $data->amount,
            'expense_date' => $data->expenseDate,
            'notes' => $data->notes,
            'created_by_user_id' => $data->createdByUserId,
        ]);
    }
}
