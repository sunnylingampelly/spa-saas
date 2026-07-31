<?php

namespace App\Providers;

use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use App\Domain\Services\Policies\ServiceCategoryPolicy;
use App\Domain\Services\Policies\ServicePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
    }
}
