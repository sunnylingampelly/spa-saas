<?php

namespace Tests\Unit\Domain\Billing;

use App\Domain\Billing\Services\InvoiceNumberGenerator;
use Carbon\Carbon;
use Tests\TestCase;

class InvoiceNumberGeneratorTest extends TestCase
{
    public function test_a_date_after_the_financial_year_start_month_belongs_to_that_year(): void
    {
        $generator = new InvoiceNumberGenerator;

        $this->assertSame('2026-27', $generator->financialYearFor(Carbon::parse('2026-04-01'), 4));
        $this->assertSame('2026-27', $generator->financialYearFor(Carbon::parse('2027-03-31'), 4));
    }

    public function test_a_date_before_the_financial_year_start_month_belongs_to_the_previous_year(): void
    {
        $generator = new InvoiceNumberGenerator;

        $this->assertSame('2025-26', $generator->financialYearFor(Carbon::parse('2026-03-31'), 4));
    }
}
