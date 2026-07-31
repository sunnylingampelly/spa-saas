<?php

namespace App\Providers;

use App\Domain\Auth\Listeners\LoginHistoryListener;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::subscribe(LoginHistoryListener::class);

        $this->configurePasswordPolicy();
        $this->configureRateLimiting();
    }

    private function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers();

            // ->uncompromised() calls the (Have I Been Pwned) API over HTTP — skip it in
            // testing so the suite stays fast and doesn't depend on network access.
            return app()->environment('testing') ? $rule : $rule->uncompromised();
        });
    }

    private function configureRateLimiting(): void
    {
        // Applied to routes that move real money: subscription checkout, invoice
        // payments/refunds, wallet adjustments.
        RateLimiter::for('financial', function ($request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });
    }
}
