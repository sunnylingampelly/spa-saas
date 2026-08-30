<?php

namespace App\Providers;

use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\WhatsApp\Policies\WhatsAppCampaignPolicy;
use App\Domain\WhatsApp\Policies\WhatsAppTemplatePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WhatsAppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(WhatsAppTemplate::class, WhatsAppTemplatePolicy::class);
        Gate::policy(WhatsAppCampaign::class, WhatsAppCampaignPolicy::class);
    }
}
