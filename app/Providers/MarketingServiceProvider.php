<?php

namespace App\Providers;

use App\Domain\Marketing\Models\EmailCampaign;
use App\Domain\Marketing\Policies\EmailCampaignPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(EmailCampaign::class, EmailCampaignPolicy::class);
    }
}
