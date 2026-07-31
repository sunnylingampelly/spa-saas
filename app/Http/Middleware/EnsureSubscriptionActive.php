<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $spa = $this->tenantContext->getCurrentSpa();

        if ($spa && ! $spa->subscription?->hasAccess()) {
            return redirect()->route('subscription.show');
        }

        return $next($request);
    }
}
