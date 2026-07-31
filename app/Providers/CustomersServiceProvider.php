<?php

namespace App\Providers;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Policies\CustomerPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CustomersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
