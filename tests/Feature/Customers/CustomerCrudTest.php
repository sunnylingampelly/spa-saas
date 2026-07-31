<?php

namespace Tests\Feature\Customers;

use App\Domain\Customers\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
    }

    public function test_an_owner_can_create_a_customer_with_generated_code_and_referral_code(): void
    {
        $response = $this->actingAs($this->owner)->post('/customers', [
            'name' => 'Anjali Mehta',
            'phone' => '9876500000',
        ]);

        $customer = Customer::where('name', 'Anjali Mehta')->first();

        $this->assertNotNull($customer);
        $this->assertSame('CUST-0001', $customer->customer_code);
        $this->assertNotNull($customer->referral_code);
        $this->assertNotNull($customer->customer_since);
        $response->assertRedirect(route('customers.show', $customer));
    }

    public function test_a_customer_can_be_created_via_a_referral_code(): void
    {
        $this->actingAs($this->owner)->post('/customers', ['name' => 'Referrer']);
        $referrer = Customer::first();

        $this->actingAs($this->owner)->post('/customers', [
            'name' => 'Referred Friend',
            'referral_code' => $referrer->referral_code,
        ]);

        $referred = Customer::where('name', 'Referred Friend')->first();

        $this->assertSame($referrer->id, $referred->referred_by_customer_id);
    }

    public function test_an_owner_can_update_a_customer(): void
    {
        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $customer = Customer::first();

        $this->actingAs($this->owner)->put("/customers/{$customer->id}", [
            'name' => 'Anjali Mehta',
            'city' => 'Mumbai',
        ]);

        $this->assertSame('Mumbai', $customer->fresh()->city);
    }

    public function test_an_owner_can_delete_a_customer(): void
    {
        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $customer = Customer::first();

        $this->actingAs($this->owner)->delete("/customers/{$customer->id}");

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
