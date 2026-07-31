<?php

namespace App\Providers;

use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Policies\EmployeePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class EmployeesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}
