<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta', 'state' => 'Karnataka']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        // total = 1000 + 180 = 1180
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);
        $this->invoice = Invoice::firstOrFail();
    }

    public function test_a_full_payment_marks_the_invoice_paid(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1180]],
        ])->assertRedirect();

        $fresh = $this->invoice->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertSame(0.0, (float) $fresh->balance_amount);
    }

    public function test_a_partial_payment_leaves_a_balance_and_marks_partially_paid(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 500]],
        ]);

        $fresh = $this->invoice->fresh();
        $this->assertSame('partially_paid', $fresh->status);
        $this->assertSame(680.0, (float) $fresh->balance_amount);
    }

    public function test_a_split_payment_across_multiple_methods_is_recorded_as_separate_rows(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [
                ['method' => 'cash', 'amount' => 680],
                ['method' => 'upi', 'amount' => 500, 'reference_number' => 'UPI123'],
            ],
        ]);

        $this->assertSame(2, $this->invoice->payments()->count());
        $this->assertSame('paid', $this->invoice->fresh()->status);
    }

    public function test_overpaying_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 2000]],
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertSame(0.0, (float) $this->invoice->fresh()->paid_amount);
    }

    public function test_a_cancelled_invoice_cannot_accept_payments(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/cancel");

        $response = $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 100]],
        ]);

        $response->assertSessionHasErrors('status');
    }

    public function test_paying_by_wallet_actually_debits_the_customers_wallet_balance(): void
    {
        $this->actingAs($this->owner)->post("/customers/{$this->customer->id}/wallet", [
            'type' => 'credit', 'amount' => 2000,
        ]);

        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'wallet', 'amount' => 1180]],
        ])->assertRedirect();

        $this->assertSame(820.0, (float) $this->customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('customer_wallet_transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'debit',
            'amount' => 1180,
        ]);
        $this->assertSame('paid', $this->invoice->fresh()->status);
    }

    public function test_an_insufficient_wallet_balance_rolls_back_the_whole_payment(): void
    {
        // Customer's wallet starts at 0 — nowhere near the 1180 owed.
        $response = $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'wallet', 'amount' => 1180]],
        ]);

        $response->assertSessionHasErrors('method');
        $this->assertSame(0.0, (float) $this->invoice->fresh()->paid_amount);
        $this->assertSame(0, $this->invoice->payments()->count());
    }

    public function test_wallet_payment_is_rejected_for_guest_bills(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'guest_name' => 'Walk-in Guest',
            'items' => [['service_id' => Service::first()->id, 'quantity' => 1]],
        ]);
        $guestInvoice = Invoice::latest('id')->first();

        $response = $this->actingAs($this->owner)->post("/invoices/{$guestInvoice->id}/payments", [
            'payments' => [['method' => 'wallet', 'amount' => 100]],
        ]);

        $response->assertSessionHasErrors('method');
    }

    public function test_a_full_payment_awards_reward_points(): void
    {
        // total = 1180, default rate 100 rupees/point -> 11 points
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1180]],
        ]);

        $this->assertSame(11, $this->customer->fresh()->reward_points);
        $this->assertDatabaseHas('customer_reward_point_transactions', [
            'customer_id' => $this->customer->id,
            'invoice_id' => $this->invoice->id,
            'type' => 'earn',
            'points' => 11,
        ]);
    }

    public function test_a_partial_payment_does_not_award_reward_points(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 500]],
        ]);

        $this->assertSame(0, $this->customer->fresh()->reward_points);
    }

    public function test_reward_points_are_not_awarded_twice_for_a_split_payment_completed_over_two_calls(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 680]],
        ]);
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 500]],
        ]);

        $this->assertSame(11, $this->customer->fresh()->reward_points);
        $this->assertSame(1, $this->customer->fresh()->rewardPointTransactions()->count());
    }

    public function test_a_guest_bill_being_paid_in_full_does_not_error_on_missing_customer(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'guest_name' => 'Walk-in Guest',
            'items' => [['service_id' => Service::first()->id, 'quantity' => 1]],
        ]);
        $guestInvoice = Invoice::latest('id')->first();

        $this->actingAs($this->owner)->post("/invoices/{$guestInvoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => (float) $guestInvoice->total_amount]],
        ])->assertRedirect();

        $this->assertSame('paid', $guestInvoice->fresh()->status);
    }
}
