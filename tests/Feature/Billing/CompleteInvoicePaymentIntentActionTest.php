<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Actions\CompleteInvoicePaymentIntentAction;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteInvoicePaymentIntentActionTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;

    private InvoicePaymentIntent $intent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($owner)->post('/customers', ['name' => 'Anjali Mehta', 'state' => 'Karnataka']);
        $customer = Customer::firstOrFail();

        $this->actingAs($owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        // total = 1180
        $this->actingAs($owner)->post('/invoices', [
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);
        $this->invoice = Invoice::firstOrFail();

        $this->intent = InvoicePaymentIntent::create([
            'spa_id' => $this->invoice->spa_id,
            'invoice_id' => $this->invoice->id,
            'status' => 'pending',
            'amount' => $this->invoice->balance_amount,
            'razorpay_order_id' => 'order_test123',
        ]);
    }

    public function test_completes_a_pending_intent_and_records_a_matching_payment(): void
    {
        app(CompleteInvoicePaymentIntentAction::class)->execute($this->intent, 'pay_test456', 'sig_test789');

        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertSame(0.0, (float) $this->invoice->fresh()->balance_amount);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'method' => 'razorpay',
            'reference_number' => 'pay_test456',
        ]);
        $this->assertSame('paid', $this->intent->fresh()->status);
    }

    public function test_is_idempotent_and_does_not_double_credit_when_run_twice(): void
    {
        $action = app(CompleteInvoicePaymentIntentAction::class);

        $action->execute($this->intent, 'pay_test456', 'sig_test789');
        $action->execute($this->intent->fresh(), 'pay_test456', 'sig_test789');

        $this->assertSame(1, $this->invoice->payments()->count());
        $this->assertSame(0.0, (float) $this->invoice->fresh()->balance_amount);
    }
}
