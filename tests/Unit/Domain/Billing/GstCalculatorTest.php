<?php

namespace Tests\Unit\Domain\Billing;

use App\Domain\Billing\Services\GstCalculator;
use Tests\TestCase;

class GstCalculatorTest extends TestCase
{
    public function test_intra_state_splits_tax_evenly_into_cgst_and_sgst(): void
    {
        $calculator = new GstCalculator;

        $result = $calculator->calculate([['lineTotal' => 1000, 'gstRate' => 18]], true);

        $this->assertSame(90.0, $result['cgst']);
        $this->assertSame(90.0, $result['sgst']);
        $this->assertSame(0.0, $result['igst']);
        $this->assertSame(180.0, $result['total']);
    }

    public function test_inter_state_uses_igst_only(): void
    {
        $calculator = new GstCalculator;

        $result = $calculator->calculate([['lineTotal' => 1000, 'gstRate' => 18]], false);

        $this->assertSame(0.0, $result['cgst']);
        $this->assertSame(0.0, $result['sgst']);
        $this->assertSame(180.0, $result['igst']);
    }

    public function test_multiple_line_items_with_different_rates_are_summed(): void
    {
        $calculator = new GstCalculator;

        $result = $calculator->calculate([
            ['lineTotal' => 1000, 'gstRate' => 18],
            ['lineTotal' => 500, 'gstRate' => 5],
        ], true);

        // 180 + 25 = 205 total tax, split 102.5 / 102.5
        $this->assertSame(205.0, $result['total']);
        $this->assertSame(102.5, $result['cgst']);
        $this->assertSame(102.5, $result['sgst']);
    }

    public function test_same_state_is_treated_as_intra_state_case_insensitively(): void
    {
        $calculator = new GstCalculator;

        $this->assertTrue($calculator->isIntraState('Karnataka', 'karnataka'));
        $this->assertTrue($calculator->isIntraState(' Karnataka ', 'Karnataka'));
    }

    public function test_different_states_are_inter_state(): void
    {
        $calculator = new GstCalculator;

        $this->assertFalse($calculator->isIntraState('Karnataka', 'Maharashtra'));
    }

    public function test_a_missing_customer_state_defaults_to_intra_state(): void
    {
        // Guest billing / customers without a recorded state — safer default than
        // silently applying IGST when we simply don't know the customer's state.
        $calculator = new GstCalculator;

        $this->assertTrue($calculator->isIntraState('Karnataka', null));
        $this->assertTrue($calculator->isIntraState(null, null));
    }
}
