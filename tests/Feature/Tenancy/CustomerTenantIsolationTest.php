<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Policies\CustomerPolicy;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Customer $customerA;

    private Customer $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('spa_owner');
        $this->actingAs($this->ownerA)->post('/onboarding/create-spa', ['name' => 'Spa A', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerA)->post('/customers', ['name' => 'Customer A']);
        $this->customerA = Customer::withoutGlobalScopes()->where('name', 'Customer A')->firstOrFail();

        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('spa_owner');
        $this->actingAs($this->ownerB)->post('/onboarding/create-spa', ['name' => 'Spa B', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerB)->post('/customers', ['name' => 'Customer B']);
        $this->customerB = Customer::withoutGlobalScopes()->where('name', 'Customer B')->firstOrFail();
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_customers(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $visible = Customer::all();

        $this->assertCount(1, $visible);
        $this->assertSame('Customer A', $visible->first()->name);
    }

    public function test_owner_a_is_denied_policy_authorization_over_customer_b(): void
    {
        $policy = new CustomerPolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->customerB));
        $this->assertTrue($policy->view($this->ownerA, $this->customerA));
    }

    public function test_owner_a_cannot_view_customer_b_via_http(): void
    {
        // See EmployeeTenantIsolationTest for why this is 403 (policy denial) rather
        // than 404 (scope invisibility) — SubstituteBindings runs before spa.context.
        $this->actingAs($this->ownerA)->get("/customers/{$this->customerB->id}")->assertForbidden();
    }

    public function test_owner_a_cannot_adjust_customer_bs_wallet(): void
    {
        $this->actingAs($this->ownerA)->post("/customers/{$this->customerB->id}/wallet", [
            'type' => 'credit',
            'amount' => 100,
        ])->assertForbidden();

        $this->assertEquals(0, $this->customerB->fresh()->wallet_balance);
    }

    public function test_referral_lookup_cannot_cross_tenants(): void
    {
        // Owner A tries to refer a new customer using Owner B's referral code —
        // the lookup must not resolve across tenants.
        $this->actingAs($this->ownerA)->post('/customers', [
            'name' => 'Cross Tenant Referral',
            'referral_code' => $this->customerB->referral_code,
        ]);

        $newCustomer = Customer::where('name', 'Cross Tenant Referral')->first();

        $this->assertNull($newCustomer->referred_by_customer_id);
    }
}
