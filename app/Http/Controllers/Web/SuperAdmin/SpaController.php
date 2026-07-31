<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Impersonation\Actions\StartImpersonationAction;
use App\Domain\Impersonation\Models\Impersonation;
use App\Domain\Tenancy\Models\Spa;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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

    public function show(Spa $spa): Response
    {
        $spa->load(['owner:id,name,email', 'subscription.payments' => fn ($q) => $q->latest()]);

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
}
