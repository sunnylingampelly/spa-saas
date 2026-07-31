<?php

namespace Tests\Feature\Webhooks;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RazorpayWebhookInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        config([
            'services.razorpay.key_id' => 'rzp_test_fake',
            'services.razorpay.key_secret' => 'test_secret_key',
            'services.razorpay.webhook_secret' => 'test_webhook_secret',
        ]);

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($owner)->post('/customers', ['name' => 'Anjali Mehta', 'state' => 'Karnataka']);
        $customer = Customer::firstOrFail();

        $this->actingAs($owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        $this->actingAs($owner)->post('/invoices', [
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);
        $this->invoice = Invoice::firstOrFail();
    }

    public function test_a_payment_captured_event_completes_a_matching_invoice_payment_intent(): void
    {
        InvoicePaymentIntent::create([
            'spa_id' => $this->invoice->spa_id,
            'invoice_id' => $this->invoice->id,
            'status' => 'pending',
            'amount' => $this->invoice->balance_amount,
            'razorpay_order_id' => 'order_invoice_test',
        ]);

        $payload = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['order_id' => 'order_invoice_test', 'id' => 'pay_webhook_test']]],
        ]);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret');

        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $this->invoice->id,
            'method' => 'razorpay',
            'reference_number' => 'pay_webhook_test',
        ]);
    }

    public function test_an_invalid_webhook_signature_is_rejected(): void
    {
        InvoicePaymentIntent::create([
            'spa_id' => $this->invoice->spa_id,
            'invoice_id' => $this->invoice->id,
            'status' => 'pending',
            'amount' => $this->invoice->balance_amount,
            'razorpay_order_id' => 'order_invoice_test',
        ]);

        $payload = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['order_id' => 'order_invoice_test', 'id' => 'pay_webhook_test']]],
        ]);

        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'not-a-real-signature',
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(400);
        $this->assertSame('unpaid', $this->invoice->fresh()->status);
    }
}
