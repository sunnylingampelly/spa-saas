<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta', 'state' => 'Karnataka']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', [
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 2000,
            'gst_rate' => 18,
        ]);
        $this->service = Service::firstOrFail();
    }

    public function test_a_bill_can_be_created_for_an_existing_customer(): void
    {
        $response = $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);

        $invoice = Invoice::first();

        $this->assertNotNull($invoice);
        $this->assertSame(2000.0, (float) $invoice->subtotal);
        $this->assertSame(180.0, (float) $invoice->cgst_amount);
        $this->assertSame(180.0, (float) $invoice->sgst_amount);
        $this->assertSame(0.0, (float) $invoice->igst_amount);
        $this->assertSame(2360.0, (float) $invoice->total_amount);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertStringStartsWith('INV/', $invoice->invoice_number);
        $response->assertRedirect(route('invoices.show', $invoice));
    }

    public function test_a_bill_can_be_created_for_a_guest_with_no_customer_record(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'guest_name' => 'Walk-in Guest',
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);

        $invoice = Invoice::first();

        $this->assertNull($invoice->customer_id);
        $this->assertSame('Walk-in Guest', $invoice->guest_name);
    }

    public function test_quantity_multiplies_the_line_total(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 2]],
        ]);

        $invoice = Invoice::first();

        $this->assertSame(4000.0, (float) $invoice->subtotal);
    }

    public function test_a_flat_discount_reduces_the_taxable_amount(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
            'discount_type' => 'flat',
            'discount_value' => 200,
        ]);

        $invoice = Invoice::first();

        $this->assertSame(200.0, (float) $invoice->discount_amount);
        $this->assertSame(1800.0, (float) $invoice->taxable_amount);
        // Tax is charged on the discounted value, not the original subtotal.
        $this->assertSame(1800 * 0.18, (float) $invoice->cgst_amount + (float) $invoice->sgst_amount);
    }

    public function test_a_percentage_discount_is_capped_at_the_subtotal(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
            'discount_type' => 'percentage',
            'discount_value' => 150, // nonsensical >100% input
        ]);

        $invoice = Invoice::first();

        $this->assertSame(2000.0, (float) $invoice->discount_amount);
        $this->assertSame(0.0, (float) $invoice->taxable_amount);
    }

    public function test_an_invoice_cannot_be_created_with_no_items(): void
    {
        $response = $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Invoice::count());
    }

    public function test_billing_a_customer_in_a_different_state_uses_igst(): void
    {
        $this->actingAs($this->owner)->post('/customers', ['name' => 'Out of State Customer', 'state' => 'Maharashtra']);
        $outOfStateCustomer = Customer::where('name', 'Out of State Customer')->first();

        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $outOfStateCustomer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);

        $invoice = Invoice::latest('id')->first();

        $this->assertSame(0.0, (float) $invoice->cgst_amount);
        $this->assertSame(0.0, (float) $invoice->sgst_amount);
        $this->assertSame(360.0, (float) $invoice->igst_amount);
    }

    public function test_the_pdf_can_be_downloaded_even_though_the_invoice_number_contains_slashes(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);
        $invoice = Invoice::first();

        $this->assertStringContainsString('/', $invoice->invoice_number);

        $response = $this->actingAs($this->owner)->get("/invoices/{$invoice->id}/download");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
