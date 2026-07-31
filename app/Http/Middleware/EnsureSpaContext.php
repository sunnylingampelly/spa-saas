<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSpaContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $spaIds = $user->spas()->pluck('spas.id');

        if ($spaIds->isEmpty()) {
            return redirect()->route('onboarding.create-spa.show');
        }

        $currentSpaId = $request->session()->get('current_spa_id');

        if (! $currentSpaId || ! $spaIds->contains($currentSpaId)) {
            if ($spaIds->count() === 1) {
                $currentSpaId = $spaIds->first();
                $request->session()->put('current_spa_id', $currentSpaId);
            } else {
                return redirect()->route('spa.switch');
            }
        }

        $this->tenantContext->setCurrentSpaId($currentSpaId);

        // A platform-level suspension is a stricter, deliberately different lock than the
        // subscription-lapsed one (EnsureSubscriptionActive) — it blocks everything, including
        // billing/profile, since suspension is a platform decision, not "please pay to continue".
        $spa = $this->tenantContext->getCurrentSpa();

        if ($spa && $spa->status === 'suspended' && ! $request->routeIs('suspended')) {
            return redirect()->route('suspended');
        }

        return $next($request);
    }
}
