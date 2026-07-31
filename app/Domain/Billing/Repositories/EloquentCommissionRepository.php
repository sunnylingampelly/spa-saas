<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EloquentCommissionRepository implements CommissionRepositoryInterface
{
    public function summaryForSpa(int $spaId, Carbon $from, Carbon $to): Collection
    {
        $items = InvoiceItem::withoutGlobalScopes()
            ->where('invoice_items.spa_id', $spaId)
            ->whereHas('invoice', fn ($q) => $q
                ->where('status', 'paid')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]))
            ->whereNotNull('employee_id')
            ->with(['service:id,commission_type,commission_value', 'employee:id,name,commission_type,commission_value'])
            ->get();

        return $items
            ->groupBy('employee_id')
            ->map(function (Collection $employeeItems, $employeeId) {
                $employee = $employeeItems->first()->employee;

                $commission = $employeeItems->sum(fn (InvoiceItem $item) => $this->commissionFor($item));
                $revenue = $employeeItems->sum('line_total');

                return [
                    'employee_id' => $employeeId,
                    'employee_name' => $employee?->name ?? 'Unknown',
                    'revenue' => round((float) $revenue, 2),
                    'commission' => round($commission, 2),
                    'items_count' => $employeeItems->count(),
                ];
            })
            ->sortByDesc('commission')
            ->values();
    }

    /**
     * Service's commission is authoritative when configured (a spa's commission
     * plan is normally "X% on this service"); Employee's is only the fallback
     * for a personally-negotiated rate on services with none set.
     */
    private function commissionFor(InvoiceItem $item): float
    {
        $service = $item->service;
        $employee = $item->employee;

        if ($service && (float) $service->commission_value > 0) {
            return $this->calculate($service->commission_type, (float) $service->commission_value, $item);
        }

        if ($employee && (float) $employee->commission_value > 0) {
            return $this->calculate($employee->commission_type, (float) $employee->commission_value, $item);
        }

        return 0.0;
    }

    private function calculate(string $type, float $value, InvoiceItem $item): float
    {
        return $type === 'percentage'
            ? (float) $item->line_total * ($value / 100)
            : $value * $item->quantity;
    }
}
