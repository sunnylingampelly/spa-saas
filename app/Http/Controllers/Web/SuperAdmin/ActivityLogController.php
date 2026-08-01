<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Models\EmployeeAttendance;
use App\Domain\Employees\Models\EmployeeLeave;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use App\Domain\Subscriptions\Models\Subscription;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Models\SpaSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Every model that belongs to a spa via App\Domain\Shared\Concerns\BelongsToTenant — used to
     * translate "show me this spa's activity" into a set of (subject_type, subject_id) matches,
     * since activity_log itself has no tenant column of its own.
     */
    private const TENANT_SCOPED_MODELS = [
        SpaSetting::class,
        Employee::class,
        EmployeeAttendance::class,
        EmployeeLeave::class,
        Service::class,
        ServiceCategory::class,
        Customer::class,
        Invoice::class,
        Payment::class,
        Expense::class,
        Appointment::class,
        Subscription::class,
        SupportTicket::class,
    ];

    public function index(Request $request): Response
    {
        $spaId = $request->integer('spa_id') ?: null;

        $activities = Activity::query()
            ->with(['causer:id,name', 'subject'])
            ->when($spaId, fn ($query) => $query->where(fn ($q) => $this->scopeToSpa($q, $spaId)))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('SuperAdmin/ActivityLog', [
            'activities' => $activities,
            'filters' => ['spa_id' => $spaId],
            'spaName' => $spaId ? Spa::withoutGlobalScopes()->find($spaId)?->name : null,
        ]);
    }

    private function scopeToSpa($query, int $spaId): void
    {
        $query->where(fn ($q) => $q->where('subject_type', Spa::class)->where('subject_id', $spaId));

        foreach (self::TENANT_SCOPED_MODELS as $modelClass) {
            $ids = $modelClass::withoutGlobalScopes()->where('spa_id', $spaId)->pluck('id');

            if ($ids->isNotEmpty()) {
                $query->orWhere(fn ($q) => $q->where('subject_type', $modelClass)->whereIn('subject_id', $ids));
            }
        }
    }
}
