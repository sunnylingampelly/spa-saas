<?php

namespace App\Domain\Expenses\Actions;

use App\Domain\Expenses\Models\Expense;

class UpdateExpenseAction
{
    public function execute(Expense $expense, array $data): Expense
    {
        $expense->update($data);

        return $expense;
    }
}
