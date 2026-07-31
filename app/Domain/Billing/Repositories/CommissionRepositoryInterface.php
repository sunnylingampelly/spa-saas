<?php

namespace App\Domain\Billing\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CommissionRepositoryInterface
{
    /**
     * Per-employee commission summary for paid invoices billed in the given
     * range. Each row: employee_id, employee_name, revenue, commission.
     */
    public function summaryForSpa(int $spaId, Carbon $from, Carbon $to): Collection;
}
