<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Customers\Models\Customer;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mirrors App\Domain\Marketing\Actions\BuildCampaignAudienceAction, targeting a WhatsApp number
 * instead of an email address. whatsapp_number falls back to phone — most customers' WhatsApp
 * number *is* their contact phone, and forcing every spa to redundantly re-enter it would be
 * needless friction. The same marketing_opt_out flag governs both channels: a customer who
 * opted out of marketing shouldn't get marketing on a different channel just because the send
 * path changed.
 */
class BuildWhatsAppAudienceAction
{
    /**
     * @param  array{type?: string, tag?: string, days?: int}  $filter
     */
    public function query(array $filter): Builder
    {
        $query = Customer::query()
            ->where(fn ($q) => $q->whereNotNull('whatsapp_number')->where('whatsapp_number', '!=', '')
                ->orWhere(fn ($q) => $q->whereNotNull('phone')->where('phone', '!=', '')))
            ->where('marketing_opt_out', false);

        return match ($filter['type'] ?? 'all') {
            'vip' => $query->where('is_vip', true),
            'tag' => $query->whereJsonContains('tags', (string) ($filter['tag'] ?? '')),
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
