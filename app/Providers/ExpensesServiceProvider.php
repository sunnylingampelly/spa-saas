<?php

namespace App\Providers;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Policies\ExpensePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ExpensesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Expense::class, ExpensePolicy::class);
    }
}
