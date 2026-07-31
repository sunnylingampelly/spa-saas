<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Tenancy\Models\Spa;
use Carbon\Carbon;

class InvoiceNumberGenerator
{
    /**
     * Indian financial year runs April–March. Given the spa's configured start
     * month (default April = 4), returns e.g. "2026-27" for any date between
     * 01-Apr-2026 and 31-Mar-2027.
     */
    public function financialYearFor(Carbon $date, int $startMonth = 4): string
    {
        $startYear = $date->month >= $startMonth ? $date->year : $date->year - 1;

        return sprintf('%d-%s', $startYear, substr((string) ($startYear + 1), -2));
    }

    public function generate(Spa $spa, Carbon $date): array
    {
        $financialYear = $this->financialYearFor($date, $spa->financial_year_start_month);

        $count = Invoice::withoutGlobalScopes()
            ->where('spa_id', $spa->id)
            ->where('financial_year', $financialYear)
            ->withTrashed()
            ->count();

        do {
            $count++;
            $number = sprintf('%s/%s/%04d', $spa->invoice_prefix, $financialYear, $count);
        } while (Invoice::withoutGlobalScopes()->withTrashed()->where('spa_id', $spa->id)->where('invoice_number', $number)->exists());

        return ['invoice_number' => $number, 'financial_year' => $financialYear];
    }
}
