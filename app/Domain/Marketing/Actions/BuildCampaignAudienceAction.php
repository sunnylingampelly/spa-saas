<?php

namespace App\Domain\Marketing\Actions;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

class BuildCampaignAudienceAction
{
    /**
     * Fixed segment types — deliberately not an open-ended filter builder for v1.
     *
     * @param  array{type?: string, tag?: string, days?: int}  $filter
     */
    public function query(array $filter): Builder
    {
        $query = Customer::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->where('marketing_opt_out', false);

        return match ($filter['type'] ?? 'all') {
            'vip' => $query->where('is_vip', true),
            'tag' => $query->whereJsonContains('tags', (string) ($filter['tag'] ?? '')),
            // "Win-back" segment — no non-cancelled bill in the last N days.
            'inactive_days' => $query->whereDoesntHave('invoices', fn ($q) => $q
                ->where('status', '!=', 'cancelled')
                ->where('created_at', '>=', now()->subDays((int) ($filter['days'] ?? 60)))),
            default => $query,
        };
    }

    public function count(array $filter): int
    {
        return $this->query($filter)->count();
    }
}
