<?php

namespace App\Providers;

use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Policies\SupportTicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(SupportTicket::class, SupportTicketPolicy::class);
    }
}
