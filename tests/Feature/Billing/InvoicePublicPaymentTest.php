<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePublicPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta', 'state' => 'Karnataka']);
        $customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        // total = 1180
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);
        $this->invoice = Invoice::firstOrFail();

        config([
            'services.razorpay.key_id' => 'rzp_test_fake',
            'services.razorpay.key_secret' => 'test_secret_key',
            'services.razorpay.webhook_secret' => 'test_webhook_secret',
        ]);
    }

    public function test_the_public_pay_page_resolves_by_token_and_shows_invoice_details(): void
    {
        $response = $this->get("/pay/{$this->invoice->public_token}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/PayInvoice')
            ->where('invoice.invoice_number', $this->invoice->invoice_number)
            ->where('invoice.balance_amount', '1180.00')
            ->where('razorpayEnabled', true)
        );
    }

    public function test_the_public_pay_page_404s_for_an_unknown_token(): void
    {
        $this->get('/pay/not-a-real-token')->assertNotFound();
    }

    public function test_create_order_returns_503_when_razorpay_is_not_configured(): void
    {
        config(['services.razorpay.key_id' => null]);

        $this->post("/pay/{$this->invoice->public_token}/razorpay/order")->assertStatus(503);
    }

    public function test_create_order_rejects_an_invoice_thats_already_fully_paid(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1180]],
        ]);

        $this->post("/pay/{$this->invoice->public_token}/razorpay/order")->assertStatus(422);
    }

    public function test_create_order_rejects_a_cancelled_invoice(): void
    {
        $this->actingAs($this->owner)->post("/invoices/{$this->invoice->id}/cancel");

        $this->post("/pay/{$this->invoice->public_token}/razorpay/order")->assertStatus(422);
    }

    public function test_the_financial_rate_limiter_throttles_public_payment_attempts(): void
    {
        config(['services.razorpay.key_id' => null]);

        for ($i = 0; $i < 20; $i++) {
            $this->post("/pay/{$this->invoice->public_token}/razorpay/order")->assertStatus(503);
        }

        $this->post("/pay/{$this->invoice->public_token}/razorpay/order")->assertStatus(429);
    }
}
