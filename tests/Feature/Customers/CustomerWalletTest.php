<?php

namespace Tests\Feature\Customers;

use App\Domain\Customers\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWalletTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $this->customer = Customer::firstOrFail();
    }

    public function test_a_credit_increases_the_wallet_balance_and_records_a_transaction(): void
    {
        $this->actingAs($this->owner)->post("/customers/{$this->customer->id}/wallet", [
            'type' => 'credit',
            'amount' => 500,
            'reason' => 'Birthday bonus',
        ])->assertRedirect();

        $this->assertEquals(500, $this->customer->fresh()->wallet_balance);
        $this->assertDatabaseHas('customer_wallet_transactions', [
            'customer_id' => $this->customer->id,
            'type' => 'credit',
            'amount' => 500,
            'balance_after' => 500,
        ]);
    }

    public function test_a_debit_decreases_the_wallet_balance(): void
    {
        $this->actingAs($this->owner)->post("/customers/{$this->customer->id}/wallet", ['type' => 'credit', 'amount' => 500]);
        $this->actingAs($this->owner)->post("/customers/{$this->customer->id}/wallet", ['type' => 'debit', 'amount' => 200]);

        $this->assertEquals(300, $this->customer->fresh()->wallet_balance);
    }

    public function test_a_debit_cannot_take_the_balance_negative(): void
    {
        $response = $this->actingAs($this->owner)->post("/customers/{$this->customer->id}/wallet", [
            'type' => 'debit',
            'amount' => 100,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(0, $this->customer->fresh()->wallet_balance);
    }
}
