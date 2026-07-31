<?php

namespace App\Http\Middleware;

use App\Domain\Announcements\Models\Announcement;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $tenantContext = app(TenantContext::class);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name') ?? [],
            ],
            // A closure, not an eager value — resolved when Inertia builds the response, which is
            // after EnsureSpaContext (route middleware, runs later than this 'web'-group
            // middleware) has actually set the tenant context. Evaluating this eagerly here
            // would always see it unset and share null.
            'currentSpa' => fn () => $tenantContext->hasSpa() ? $tenantContext->getCurrentSpa() : null,
            'impersonating' => $this->impersonationBanner($request),
            'announcement' => fn () => Announcement::where('is_active', true)->latest()->first(['id', 'message']),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    private function impersonationBanner(Request $request): ?array
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return null;
        }

        return [
            'active' => true,
            'ownerName' => $request->user()?->name,
        ];
    }
}
