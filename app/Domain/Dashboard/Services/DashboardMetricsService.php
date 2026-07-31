<?php

namespace App\Domain\Dashboard\Services;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceItem;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Services\InvoiceNumberGenerator;
use App\Domain\Customers\Models\Customer;
use App\Domain\Expenses\Models\Expense;
use App\Domain\Tenancy\Models\Spa;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardMetricsService
{
    public function __construct(private readonly InvoiceNumberGenerator $numberGenerator) {}

    public function forSpa(Spa $spa): array
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $trendStart = $today->copy()->subDays(29)->startOfDay();
        [$fyStart, $fyEnd] = $this->financialYearRange($spa, $today);

        return [
            'today' => $this->todayMetrics($spa, $today),
            'pendingPayments' => round((float) Invoice::withoutGlobalScopes()
                ->where('spa_id', $spa->id)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->sum('balance_amount'), 2),
            'customersThisMonth' => $this->repeatVsNewCustomers($spa, $monthStart),
            'monthlyRevenue' => $this->netRevenueBetween($spa, $monthStart, $today),
            'yearlyRevenue' => $this->netRevenueBetween($spa, $fyStart, $fyEnd),
            'financialYear' => $this->numberGenerator->financialYearFor($today, $spa->financial_year_start_month),
            'popularServices' => $this->popularServices($spa, $trendStart),
            'popularEmployees' => $this->popularEmployees($spa, $trendStart),
            'revenueTrend' => $this->revenueTrend($spa, $trendStart, $today),
            'customerGrowthTrend' => $this->customerGrowthTrend($spa, $trendStart, $today),
            'expensesThisMonth' => $expenses = round((float) Expense::withoutGlobalScopes()
                ->where('spa_id', $spa->id)
                ->whereBetween('expense_date', [$monthStart->toDateString(), $today->toDateString()])
                ->sum('amount'), 2),
            'profitThisMonth' => round($this->netRevenueBetween($spa, $monthStart, $today) - $expenses, 2),
        ];
    }

    private function todayMetrics(Spa $spa, Carbon $today): array
    {
        $dayRange = [$today->copy()->startOfDay(), $today->copy()->endOfDay()];

        $paymentsToday = Payment::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereBetween('paid_at', $dayRange)
            ->get(['type', 'amount']);

        $appointmentsToday = Appointment::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereBetween('starts_at', $dayRange)
            ->get(['booking_type']);

        $invoicesToday = Invoice::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereBetween('created_at', $dayRange)
            ->get(['cgst_amount', 'sgst_amount', 'igst_amount']);

        return [
            'revenue' => round(
                (float) $paymentsToday->where('type', 'payment')->sum('amount')
                    - (float) $paymentsToday->where('type', 'refund')->sum('amount'),
                2
            ),
            'appointments' => $appointmentsToday->count(),
            'walkIns' => $appointmentsToday->where('booking_type', 'walk_in')->count(),
            'newCustomers' => Customer::withoutGlobalScopes()
                ->where('spa_id', $spa->id)
                ->whereBetween('created_at', $dayRange)
                ->count(),
            'bills' => $invoicesToday->count(),
            'gst' => round((float) $invoicesToday->sum(fn ($i) => $i->cgst_amount + $i->sgst_amount + $i->igst_amount), 2),
        ];
    }

    private function repeatVsNewCustomers(Spa $spa, Carbon $monthStart): array
    {
        $customerIdsThisMonth = Invoice::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereNotNull('customer_id')
            ->where('created_at', '>=', $monthStart)
            ->pluck('customer_id')
            ->unique();

        if ($customerIdsThisMonth->isEmpty()) {
            return ['repeat' => 0, 'new' => 0];
        }

        $repeatCount = Invoice::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereIn('customer_id', $customerIdsThisMonth)
            ->where('created_at', '<', $monthStart)
            ->pluck('customer_id')
            ->unique()
            ->count();

        return [
            'repeat' => $repeatCount,
            'new' => $customerIdsThisMonth->count() - $repeatCount,
        ];
    }

    private function netRevenueBetween(Spa $spa, Carbon $from, Carbon $to): float
    {
        $payments = Payment::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereBetween('paid_at', [$from, $to])
            ->get(['type', 'amount']);

        return round(
            (float) $payments->where('type', 'payment')->sum('amount')
                - (float) $payments->where('type', 'refund')->sum('amount'),
            2
        );
    }

    private function popularServices(Spa $spa, Carbon $since): Collection
    {
        return InvoiceItem::withoutGlobalScopes()
            ->where('invoice_items.spa_id', $spa->id)
            ->whereHas('invoice', fn ($q) => $q->where('status', 'paid')->where('created_at', '>=', $since))
            ->selectRaw('service_id, sum(line_total) as revenue')
            ->groupBy('service_id')
            ->orderByDesc('revenue')
            ->with('service:id,name')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->service?->name ?? 'Unknown',
                'revenue' => round((float) $row->revenue, 2),
            ]);
    }

    private function popularEmployees(Spa $spa, Carbon $since): Collection
    {
        return InvoiceItem::withoutGlobalScopes()
            ->where('invoice_items.spa_id', $spa->id)
            ->whereNotNull('employee_id')
            ->whereHas('invoice', fn ($q) => $q->where('status', 'paid')->where('created_at', '>=', $since))
            ->selectRaw('employee_id, sum(line_total) as revenue')
            ->groupBy('employee_id')
            ->orderByDesc('revenue')
            ->with('employee:id,name')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->employee?->name ?? 'Unknown',
                'revenue' => round((float) $row->revenue, 2),
            ]);
    }

    private function revenueTrend(Spa $spa, Carbon $from, Carbon $to): array
    {
        $rows = Payment::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as day, type, SUM(amount) as total')
            ->groupBy('day', 'type')
            ->get();

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row->day] ??= 0;
            $byDay[$row->day] += $row->type === 'refund' ? -$row->total : $row->total;
        }

        return $this->fillDailySeries($from, $to, $byDay);
    }

    private function customerGrowthTrend(Spa $spa, Carbon $from, Carbon $to): array
    {
        $rows = Customer::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($from, $to, $rows->toArray());
    }

    private function fillDailySeries(Carbon $from, Carbon $to, array $valuesByDate): array
    {
        $series = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $series[] = ['date' => $key, 'value' => round((float) ($valuesByDate[$key] ?? 0), 2)];
            $cursor->addDay();
        }

        return $series;
    }

    private function financialYearRange(Spa $spa, Carbon $today): array
    {
        $startMonth = $spa->financial_year_start_month;

        $fyStart = $today->month >= $startMonth
            ? $today->copy()->startOfYear()->addMonths($startMonth - 1)
            : $today->copy()->subYear()->startOfYear()->addMonths($startMonth - 1);

        return [$fyStart, $fyStart->copy()->addYear()->subSecond()];
    }
}
