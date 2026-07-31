<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaSuspensionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private Spa $spa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->admin->assignRole('super_admin');

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = Spa::withoutGlobalScopes()->firstOrFail();
    }

    public function test_super_admin_can_suspend_a_spa(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.spas.update-status', $this->spa->id), ['status' => 'suspended'])
            ->assertRedirect();

        $this->assertSame('suspended', $this->spa->fresh()->status);
    }

    public function test_a_suspended_spas_owner_is_redirected_everywhere_except_logout(): void
    {
        $this->spa->update(['status' => 'suspended']);

        $this->actingAs($this->owner)->get('/dashboard')->assertRedirect(route('suspended'));
        $this->actingAs($this->owner)->get('/subscription')->assertRedirect(route('suspended'));
        $this->actingAs($this->owner)->get('/spa/profile')->assertRedirect(route('suspended'));

        // The suspended page itself and logout must stay reachable.
        $this->actingAs($this->owner)->get(route('suspended'))->assertOk();
        $this->actingAs($this->owner)->post('/logout')->assertRedirect();
    }

    public function test_reactivating_a_suspended_spa_restores_access(): void
    {
        $this->spa->update(['status' => 'suspended']);

        $this->actingAs($this->admin)
            ->patch(route('admin.spas.update-status', $this->spa->id), ['status' => 'active']);

        $this->actingAs($this->owner)->get('/dashboard')->assertOk();
    }

    public function test_status_can_only_be_set_to_active_or_suspended(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.spas.update-status', $this->spa->id), ['status' => 'trial'])
            ->assertSessionHasErrors('status');
    }
}
