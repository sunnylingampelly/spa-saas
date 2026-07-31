<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Impersonation\Models\Impersonation;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
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

    public function test_super_admin_can_impersonate_a_spa_owner(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.spas.impersonate', $this->spa->id))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->owner->fresh());

        $impersonation = Impersonation::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($this->admin->id, $impersonation->super_admin_user_id);
        $this->assertSame($this->spa->id, $impersonation->spa_id);
        $this->assertSame($this->owner->id, $impersonation->target_user_id);
        $this->assertNotNull($impersonation->started_at);
        $this->assertNull($impersonation->ended_at);
    }

    public function test_impersonating_another_super_admin_is_rejected(): void
    {
        $otherAdmin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $otherAdmin->assignRole('super_admin');

        // Force this spa's owner to also (incorrectly) be a super admin, simulating an attempt
        // to impersonate a platform admin rather than a genuine spa owner.
        $this->spa->update(['owner_user_id' => $otherAdmin->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.spas.impersonate', $this->spa->id))
            ->assertSessionHasErrors('spa');

        $this->assertAuthenticatedAs($this->admin);
        $this->assertSame(0, Impersonation::withoutGlobalScopes()->count());
    }

    public function test_stopping_impersonation_restores_the_original_admin_and_stamps_ended_at(): void
    {
        $this->actingAs($this->admin)->post(route('admin.spas.impersonate', $this->spa->id));

        $this->post('/stop-impersonating')->assertRedirect(route('admin.spas.index'));

        $this->assertAuthenticatedAs($this->admin->fresh());

        $impersonation = Impersonation::withoutGlobalScopes()->firstOrFail();
        $this->assertNotNull($impersonation->ended_at);
    }

    public function test_stop_impersonating_is_rejected_when_not_currently_impersonating(): void
    {
        $this->actingAs($this->owner)->post('/stop-impersonating')->assertForbidden();
    }
}
