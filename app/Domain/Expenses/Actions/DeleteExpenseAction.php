<?php

namespace App\Domain\Expenses\Actions;

use App\Domain\Expenses\Models\Expense;

class DeleteExpenseAction
{
    public function execute(Expense $expense): void
    {
        $expense->delete();
    }
}
