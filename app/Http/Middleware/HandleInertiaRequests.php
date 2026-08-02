<?php

namespace App\Http\Middleware;

use App\Domain\Announcements\Models\Announcement;
use App\Domain\Support\Models\SupportTicket;
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
            'announcement' => fn () => Announcement::where('is_active', true)->latest()->first(['id', 'message', 'color']),
            // Strict ">" is correct here (a read and the message that triggered it must never
            // tie) as long as these columns have real sub-second precision — see the
            // microsecond-precision timestamps on the support_tickets migration.
            'unreadSupportCount' => fn () => $tenantContext->hasSpa()
                ? SupportTicket::where('last_message_from', 'admin')->whereColumn('last_message_at', '>', 'spa_owner_read_at')->count()
                : 0,
            'openSupportTicketsCount' => fn () => $user?->hasRole('super_admin')
                ? SupportTicket::withoutGlobalScopes()
                    ->where(fn ($q) => $q->whereNull('admin_read_at')->orWhereColumn('last_message_at', '>', 'admin_read_at'))
                    ->count()
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'import_errors' => fn () => $request->session()->get('import_errors'),
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
