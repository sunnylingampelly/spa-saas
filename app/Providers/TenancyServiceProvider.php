<?php

namespace App\Providers;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Policies\SpaPolicy;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Gate::policy(Spa::class, SpaPolicy::class);
    }
}
