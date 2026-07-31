<?php

namespace App\Domain\Customers\Repositories;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;

class EloquentCustomerHistoryRepository implements CustomerHistoryRepositoryInterface
{
    public function recentAppointments(int $customerId, int $limit = 10): Collection
    {
        return Appointment::where('customer_id', $customerId)
            ->with(['service:id,name', 'employee:id,name'])
            ->latest('starts_at')
            ->limit($limit)
            ->get();
    }

    public function recentInvoices(int $customerId, int $limit = 10): Collection
    {
        return Invoice::where('customer_id', $customerId)
            ->latest()
            ->limit($limit)
            ->get(['id', 'invoice_number', 'total_amount', 'status', 'created_at']);
    }

    public function statsFor(int $customerId): array
    {
        $invoices = Invoice::where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->get(['paid_amount']);

        $lifetimeSpend = (float) $invoices->sum('paid_amount');
        $invoiceCount = $invoices->count();
        $averageBill = $invoiceCount > 0 ? round($lifetimeSpend / $invoiceCount, 2) : null;

        $completedVisits = Appointment::where('customer_id', $customerId)
            ->where('status', 'completed')
            ->orderBy('starts_at')
            ->pluck('starts_at');

        $visitCount = $completedVisits->count();
        $visitFrequencyDays = null;

        if ($visitCount >= 2) {
            $spanDays = $completedVisits->first()->diffInDays($completedVisits->last());
            $visitFrequencyDays = round($spanDays / ($visitCount - 1), 1);
        }

        return [
            'lifetimeSpend' => $lifetimeSpend,
            'averageBill' => $averageBill,
            'visitCount' => $visitCount,
            'visitFrequencyDays' => $visitFrequencyDays,
            'lastVisitAt' => $completedVisits->last()?->toIso8601String(),
        ];
    }
}
