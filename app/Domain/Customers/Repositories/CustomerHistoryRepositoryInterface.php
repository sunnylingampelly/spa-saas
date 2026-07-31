<?php

namespace App\Domain\Customers\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface CustomerHistoryRepositoryInterface
{
    public function recentAppointments(int $customerId, int $limit = 10): Collection;

    public function recentInvoices(int $customerId, int $limit = 10): Collection;

    /**
     * @return array{lifetimeSpend: float, averageBill: ?float, visitCount: int, visitFrequencyDays: ?float, lastVisitAt: ?string}
     */
    public function statsFor(int $customerId): array;
}
