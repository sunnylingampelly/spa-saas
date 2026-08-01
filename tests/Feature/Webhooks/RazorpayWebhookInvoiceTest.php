<?php

namespace Tests\Feature\Webhooks;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RazorpayWebhookInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private Spa $spa;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->spa = $owner->spas()->firstOrFail();
        $this->spa->update([
            'razorpay_key_id' => 'rzp_spa_fake',
            'razorpay_key_secret' => 'spa_secret_key',
            'razorpay_webhook_secret' => 'spa_webhook_secret',
        ]);

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

    private function postWebhook(string $token, string $payload, string $signature)
    {
        return $this->call('POST', "/api/webhooks/razorpay/{$token}", [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
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
        $signature = hash_hmac('sha256', $payload, 'spa_webhook_secret');

        $response = $this->postWebhook($this->spa->razorpay_webhook_token, $payload, $signature);

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

        $response = $this->postWebhook($this->spa->razorpay_webhook_token, $payload, 'not-a-real-signature');

        $response->assertStatus(400);
        $this->assertSame('unpaid', $this->invoice->fresh()->status);
    }

    public function test_a_valid_signature_from_a_different_spas_secret_is_rejected(): void
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
        // Signed with some other account's secret, not this spa's own.
        $signature = hash_hmac('sha256', $payload, 'someone_elses_webhook_secret');

        $response = $this->postWebhook($this->spa->razorpay_webhook_token, $payload, $signature);

        $response->assertStatus(400);
        $this->assertSame('unpaid', $this->invoice->fresh()->status);
    }

    public function test_the_platform_webhook_endpoint_never_resolves_invoice_payment_intents(): void
    {
        config([
            'services.razorpay.key_id' => 'rzp_platform_fake',
            'services.razorpay.key_secret' => 'platform_secret_key',
            'services.razorpay.webhook_secret' => 'platform_webhook_secret',
        ]);

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
        $signature = hash_hmac('sha256', $payload, 'platform_webhook_secret');

        $response = $this->call('POST', '/api/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertOk();
        $this->assertSame('unpaid', $this->invoice->fresh()->status);
    }
}
