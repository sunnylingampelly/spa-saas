<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Expenses\Models\Expense;
use App\Domain\Expenses\Policies\ExpensePolicy;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Expense $expenseA;

    private Expense $expenseB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('spa_owner');
        $this->actingAs($this->ownerA)->post('/onboarding/create-spa', ['name' => 'Spa A', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerA)->post('/expenses', ['category' => 'Rent', 'amount' => 10000, 'expense_date' => '2026-08-01']);
        $this->expenseA = Expense::withoutGlobalScopes()->first();

        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('spa_owner');
        $this->actingAs($this->ownerB)->post('/onboarding/create-spa', ['name' => 'Spa B', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerB)->post('/expenses', ['category' => 'Rent', 'amount' => 20000, 'expense_date' => '2026-08-01']);
        $this->expenseB = Expense::withoutGlobalScopes()->latest('id')->first();
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_expenses(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $visible = Expense::all();

        $this->assertCount(1, $visible);
        $this->assertEquals(10000, $visible->first()->amount);
    }

    public function test_owner_a_is_denied_policy_authorization_over_expense_b(): void
    {
        $policy = new ExpensePolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->expenseB));
        $this->assertTrue($policy->view($this->ownerA, $this->expenseA));
    }

    public function test_owner_a_cannot_edit_expense_b_via_http(): void
    {
        $this->actingAs($this->ownerA)->get("/expenses/{$this->expenseB->id}/edit")->assertForbidden();
    }
}
