<?php

namespace App\Domain\Subscriptions\Services;

use App\Domain\Subscriptions\Models\Subscription;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Tenancy\Models\Spa;
use Carbon\Carbon;

/**
 * Platform-wide metrics for the Super Admin dashboard — mirrors the shape of
 * App\Domain\Dashboard\Services\DashboardMetricsService (the spa-owner equivalent), but every
 * query spans every tenant explicitly via withoutGlobalScopes() instead of a single spa.
 */
class PlatformMetricsService
{
    public function forPlatform(): array
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $trendStart = $today->copy()->subDays(29)->startOfDay();

        return [
            'totalSpas' => Spa::withoutGlobalScopes()->count(),
            'trialSpas' => Subscription::withoutGlobalScopes()->where('status', 'trialing')->count(),
            'activeSpas' => Subscription::withoutGlobalScopes()->where('status', 'active')->count(),
            'suspendedSpas' => Spa::withoutGlobalScopes()->where('status', 'suspended')->count(),
            'mrr' => $this->mrr(),
            'totalRevenueCollected' => round((float) SubscriptionPayment::withoutGlobalScopes()
                ->where('status', 'paid')->sum('amount'), 2),
            'revenueThisMonth' => round((float) SubscriptionPayment::withoutGlobalScopes()
                ->where('status', 'paid')->where('paid_at', '>=', $monthStart)->sum('amount'), 2),
            'revenueTrend' => $this->revenueTrend($trendStart, $today),
            'planDistribution' => $this->planDistribution(),
            'trialConversionRate' => $this->trialConversionRate(),
        ];
    }

    private function mrr(): float
    {
        $activeMonthlySubscriptions = Subscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('plan_code', 'monthly')
            ->count();

        // Monthly billing is retired (see config/subscriptions.php) — its price is no longer
        // in config, but existing monthly subscribers still exist and their real revenue must
        // keep showing up here rather than silently dropping to zero. ₹1,499 was always the
        // fixed monthly price for as long as the plan was sold.
        return round($activeMonthlySubscriptions * 1499.0, 2);
    }

    private function revenueTrend(Carbon $from, Carbon $to): array
    {
        $rows = SubscriptionPayment::withoutGlobalScopes()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return $this->fillDailySeries($from, $to, $rows->toArray());
    }

    private function planDistribution(): array
    {
        return Subscription::withoutGlobalScopes()
            ->where('status', 'active')
            ->selectRaw('plan_code, count(*) as total')
            ->groupBy('plan_code')
            ->pluck('total', 'plan_code')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    /**
     * (active + lifetime) ÷ every subscription that has ever left the trialing state — lifetime
     * plans carry status=active too (only current_period_ends_at differs), so a single
     * status='active' check already covers both cycles.
     */
    private function trialConversionRate(): float
    {
        $exitedTrial = Subscription::withoutGlobalScopes()->where('status', '!=', 'trialing')->count();

        if ($exitedTrial === 0) {
            return 0.0;
        }

        $converted = Subscription::withoutGlobalScopes()->where('status', 'active')->count();

        return round(($converted / $exitedTrial) * 100, 1);
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
}
