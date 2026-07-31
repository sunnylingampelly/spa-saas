<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceRefundTest extends TestCase
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

        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);
        $this->invoice = Invoice::firstOrFail();

        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1180]],
        ]);
    }

    public function test_a_full_refund_marks_the_invoice_refunded(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/refund", [
            'method' => 'cash',
            'amount' => 1180,
            'reason' => 'Customer unsatisfied',
        ])->assertRedirect();

        $fresh = $this->invoice->fresh();
        $this->assertSame('refunded', $fresh->status);
        $this->assertSame(0.0, (float) $fresh->paid_amount);
    }

    public function test_a_partial_refund_reopens_the_balance(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/refund", [
            'method' => 'cash',
            'amount' => 500,
        ]);

        $fresh = $this->invoice->fresh();
        $this->assertSame('partially_paid', $fresh->status);
        $this->assertSame(500.0, (float) $fresh->balance_amount);
    }

    public function test_refunding_more_than_was_paid_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/refund", [
            'method' => 'cash',
            'amount' => 5000,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertSame(1180.0, (float) $this->invoice->fresh()->paid_amount);
    }

    public function test_a_paid_invoice_cannot_be_cancelled_directly(): void
    {
        $response = $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/cancel");

        $response->assertSessionHasErrors('status');
        $this->assertSame('paid', $this->invoice->fresh()->status);
    }

    public function test_refunding_to_wallet_credits_the_customers_wallet_balance(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/refund", [
            'method' => 'wallet',
            'amount' => 500,
        ])->assertRedirect();

        $this->assertSame(500.0, (float) $this->customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('customer_wallet_transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 500,
        ]);
    }

    public function test_refunding_to_wallet_is_rejected_for_guest_bills(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'guest_name' => 'Walk-in Guest',
            'items' => [['service_id' => Service::first()->id, 'quantity' => 1]],
        ]);
        $guestInvoice = Invoice::latest('id')->first();
        $this->actingAs($this->owner)->post("/invoices/{$guestInvoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => (float) $guestInvoice->total_amount]],
        ]);

        $response = $this->actingAs($this->owner)->post("/invoices/{$guestInvoice->id}/refund", [
            'method' => 'wallet',
            'amount' => 100,
        ]);

        $response->assertSessionHasErrors('method');
    }
}
