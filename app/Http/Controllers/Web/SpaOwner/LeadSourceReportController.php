<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LeadSourceReportController extends Controller
{
    private const PAID_STATUSES = ['paid', 'partially_paid'];

    public function index(Request $request): Response
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString()) : now();

        $bySource = Invoice::query()
            ->join('appointments', 'invoices.appointment_id', '=', 'appointments.id')
            ->whereBetween('invoices.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereIn('invoices.status', self::PAID_STATUSES)
            ->selectRaw('appointments.lead_source as lead_source, count(*) as invoice_count, sum(invoices.paid_amount) as revenue')
            ->groupBy('appointments.lead_source')
            ->get();

        // Invoices billed without ever going through the Appointments flow (e.g. a direct
        // walk-in bill) have no lead_source to attribute — kept as its own bucket so the
        // report's total always reconciles to the spa's actual total revenue.
        $direct = Invoice::query()
            ->whereNull('appointment_id')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereIn('status', self::PAID_STATUSES)
            ->selectRaw('count(*) as invoice_count, sum(paid_amount) as revenue')
            ->first();

        $rows = $bySource->map(fn ($row) => [
            'lead_source' => $row->lead_source,
            'invoice_count' => (int) $row->invoice_count,
            'revenue' => round((float) $row->revenue, 2),
        ]);

        if ((int) ($direct->invoice_count ?? 0) > 0) {
            $rows->push([
                'lead_source' => null,
                'invoice_count' => (int) $direct->invoice_count,
                'revenue' => round((float) $direct->revenue, 2),
            ]);
        }

        return Inertia::render('Reports/LeadSources', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $rows->sortByDesc('revenue')->values(),
            'totalRevenue' => round((float) $rows->sum('revenue'), 2),
            'leadSources' => Appointment::LEAD_SOURCES,
        ]);
    }
}
