<?php

namespace Tests\Feature\Expenses;

use App\Domain\Expenses\Models\Expense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCrudTest extends TestCase
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

    public function test_an_owner_can_record_an_expense(): void
    {
        $response = $this->actingAs($this->owner)->post('/expenses', [
            'category' => 'Rent',
            'amount' => 15000,
            'expense_date' => '2026-08-01',
            'notes' => 'August rent',
        ]);

        $expense = Expense::first();

        $this->assertNotNull($expense);
        $this->assertSame('Rent', $expense->category);
        $this->assertEquals(15000, $expense->amount);
        $response->assertRedirect(route('expenses.index'));
    }

    public function test_an_invalid_category_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post('/expenses', [
            'category' => 'Not A Real Category',
            'amount' => 100,
            'expense_date' => '2026-08-01',
        ]);

        $response->assertSessionHasErrors('category');
        $this->assertSame(0, Expense::count());
    }

    public function test_an_owner_can_update_an_expense(): void
    {
        $this->actingAs($this->owner)->post('/expenses', ['category' => 'Rent', 'amount' => 15000, 'expense_date' => '2026-08-01']);
        $expense = Expense::first();

        $this->actingAs($this->owner)->put("/expenses/{$expense->id}", [
            'category' => 'Rent', 'amount' => 16000, 'expense_date' => '2026-08-01',
        ]);

        $this->assertEquals(16000, $expense->fresh()->amount);
    }

    public function test_an_owner_can_delete_an_expense(): void
    {
        $this->actingAs($this->owner)->post('/expenses', ['category' => 'Rent', 'amount' => 15000, 'expense_date' => '2026-08-01']);
        $expense = Expense::first();

        $this->actingAs($this->owner)->delete("/expenses/{$expense->id}");

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }
}
