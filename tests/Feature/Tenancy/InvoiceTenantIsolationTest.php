<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Policies\InvoicePolicy;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Invoice $invoiceA;

    private Invoice $invoiceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerA = $this->billOneInvoice('Spa A');
        $this->invoiceA = Invoice::withoutGlobalScopes()->first();

        $this->ownerB = $this->billOneInvoice('Spa B');
        $this->invoiceB = Invoice::withoutGlobalScopes()->latest('id')->first();
    }

    private function billOneInvoice(string $spaName): User
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => $spaName, 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($owner)->post('/customers', ['name' => "$spaName Customer", 'state' => 'Karnataka']);
        $customer = Customer::firstOrFail();

        $this->actingAs($owner)->post('/services', ['name' => "$spaName Service", 'duration_minutes' => 60, 'price' => 1000]);
        $service = Service::firstOrFail();

        $this->actingAs($owner)->post('/invoices', [
            'customer_id' => $customer->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        return $owner;
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_invoices(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $this->assertCount(1, Invoice::all());
    }

    public function test_invoice_numbering_is_independent_per_spa(): void
    {
        // Both spas' first invoice of the financial year should be sequence 0001 —
        // numbering must not leak across tenants.
        $this->assertStringContainsString('/0001', $this->invoiceA->invoice_number);
        $this->assertStringContainsString('/0001', $this->invoiceB->invoice_number);
    }

    public function test_owner_a_is_denied_policy_authorization_over_invoice_b(): void
    {
        $policy = new InvoicePolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->invoiceB));
        $this->assertTrue($policy->view($this->ownerA, $this->invoiceA));
    }

    public function test_owner_a_cannot_record_a_payment_against_invoice_b(): void
    {
        $this->actingAs($this->ownerA)->post("/invoices/{$this->invoiceB->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 100]],
        ])->assertForbidden();

        $this->assertSame(0.0, (float) $this->invoiceB->fresh()->paid_amount);
    }

    public function test_owner_a_cannot_download_invoice_bs_pdf(): void
    {
        $this->actingAs($this->ownerA)->get("/invoices/{$this->invoiceB->id}/download")->assertForbidden();
    }
}
