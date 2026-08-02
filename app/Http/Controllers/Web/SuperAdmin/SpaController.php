<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Impersonation\Actions\StartImpersonationAction;
use App\Domain\Impersonation\Models\Impersonation;
use App\Domain\Subscriptions\Actions\AdminUpdateSubscriptionAction;
use App\Domain\Tenancy\Actions\ExportSpasAction;
use App\Domain\Tenancy\Models\Spa;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpaController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $spas = Spa::withoutGlobalScopes()
            ->with(['owner:id,name,email', 'subscription'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($o) => $o->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Spas', [
            'spas' => $spas,
            'filters' => ['search' => $search],
        ]);
    }

    public function export(Request $request, ExportSpasAction $action): StreamedResponse
    {
        return $action->execute($request->string('search')->toString() ?: null);
    }

    public function show(Spa $spa): Response
    {
        $spa->load(['owner:id,name,email,is_active', 'subscription.payments' => fn ($q) => $q->latest()]);

        return Inertia::render('SuperAdmin/SpaShow', [
            'spa' => $spa,
            'stats' => [
                'employees' => Employee::withoutGlobalScopes()->where('spa_id', $spa->id)->count(),
                'customers' => Customer::withoutGlobalScopes()->where('spa_id', $spa->id)->count(),
                'appointments' => Appointment::withoutGlobalScopes()->where('spa_id', $spa->id)->count(),
            ],
            'impersonations' => Impersonation::withoutGlobalScopes()
                ->where('spa_id', $spa->id)
                ->with('superAdmin:id,name')
                ->latest('started_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, Spa $spa): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $spa->update(['status' => $data['status']]);

        return back()->with('success', "Spa marked {$data['status']}.");
    }

    public function impersonate(Spa $spa, StartImpersonationAction $startImpersonation): RedirectResponse
    {
        $startImpersonation->execute($spa, auth()->user());

        return redirect()->route('dashboard');
    }

    public function updateSubscription(Request $request, Spa $spa, AdminUpdateSubscriptionAction $action): RedirectResponse
    {
        abort_unless($spa->subscription, 404, 'This spa has no subscription record.');

        $data = $request->validate([
            'plan_code' => ['required', Rule::in(['trial', 'monthly', 'lifetime'])],
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'cancelled'])],
            'current_period_ends_at' => ['nullable', 'date'],
        ]);

        $action->execute(
            $spa->subscription,
            $data['plan_code'],
            $data['status'],
            filled($data['current_period_ends_at'] ?? null) ? Carbon::parse($data['current_period_ends_at']) : null,
        );

        return back()->with('success', 'Subscription updated.');
    }

    public function updateOwner(Request $request, Spa $spa): RedirectResponse
    {
        $owner = $this->ownerOrAbort($spa);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($owner->id)],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $updates = ['name' => $data['name'], 'email' => $data['email']];

        // Blank means "leave the current password unchanged" — same convention as the
        // Razorpay secret fields on the spa-owner's own Payment Gateway settings.
        if (filled($data['password'] ?? null)) {
            $updates['password'] = $data['password'];
        }

        $owner->update($updates);

        return back()->with('success', 'Owner account updated.');
    }

    public function toggleOwnerStatus(Spa $spa): RedirectResponse
    {
        $owner = $this->ownerOrAbort($spa);

        $owner->update(['is_active' => ! $owner->is_active]);

        return back()->with('success', $owner->is_active ? 'Owner account reactivated.' : 'Owner account deactivated.');
    }

    public function deleteOwner(Spa $spa): RedirectResponse
    {
        $owner = $this->ownerOrAbort($spa);

        $owner->delete();

        return back()->with('success', 'Owner account deleted.');
    }

    private function ownerOrAbort(Spa $spa): User
    {
        $owner = $spa->owner;

        // Never allow acting on a super_admin-role user through this spa-scoped surface —
        // same guard as impersonation (StartImpersonationAction).
        abort_if(! $owner || $owner->hasRole('super_admin'), 403);

        return $owner;
    }
}
