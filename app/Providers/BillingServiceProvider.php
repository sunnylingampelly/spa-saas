<?php

namespace App\Providers;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Policies\InvoicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class BillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Invoice::class, InvoicePolicy::class);
    }
}
