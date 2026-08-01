<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SpaAdminManagementTest extends TestCase
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

        $this->owner = User::factory()->create(['password' => bcrypt('OldPassword123')]);
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = Spa::withoutGlobalScopes()->firstOrFail();
    }

    public function test_super_admin_can_edit_the_subscription_plan_status_and_period(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.subscription.update', $this->spa->id), [
            'plan_code' => 'lifetime',
            'status' => 'active',
            'current_period_ends_at' => null,
        ])->assertRedirect();

        $subscription = $this->spa->subscription->fresh();
        $this->assertSame('lifetime', $subscription->plan_code);
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->current_period_ends_at);
    }

    public function test_super_admin_can_set_a_concrete_renewal_date(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.subscription.update', $this->spa->id), [
            'plan_code' => 'monthly',
            'status' => 'active',
            'current_period_ends_at' => '2027-01-15',
        ])->assertRedirect();

        $subscription = $this->spa->subscription->fresh();
        $this->assertSame('2027-01-15', $subscription->current_period_ends_at->format('Y-m-d'));
    }

    public function test_setting_status_to_cancelled_stamps_cancelled_at_and_clearing_it_removes_the_stamp(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.subscription.update', $this->spa->id), [
            'plan_code' => 'monthly',
            'status' => 'cancelled',
            'current_period_ends_at' => null,
        ]);
        $this->assertNotNull($this->spa->subscription->fresh()->cancelled_at);

        $this->actingAs($this->admin)->patch(route('admin.spas.subscription.update', $this->spa->id), [
            'plan_code' => 'monthly',
            'status' => 'active',
            'current_period_ends_at' => null,
        ]);
        $this->assertNull($this->spa->subscription->fresh()->cancelled_at);
    }

    public function test_a_spa_owner_cannot_edit_a_subscription(): void
    {
        $this->actingAs($this->owner)->patch(route('admin.spas.subscription.update', $this->spa->id), [
            'plan_code' => 'lifetime',
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_super_admin_can_update_the_owners_name_and_email(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.owner.update', $this->spa->id), [
            'name' => 'Renamed Owner',
            'email' => 'renamed@example.com',
            'password' => '',
        ])->assertRedirect();

        $fresh = $this->owner->fresh();
        $this->assertSame('Renamed Owner', $fresh->name);
        $this->assertSame('renamed@example.com', $fresh->email);
    }

    public function test_updating_the_owner_rejects_an_email_already_used_by_someone_else(): void
    {
        $otherOwner = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->admin)->patch(route('admin.spas.owner.update', $this->spa->id), [
            'name' => $this->owner->name,
            'email' => $otherOwner->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_a_blank_password_leaves_the_owners_existing_password_unchanged(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.owner.update', $this->spa->id), [
            'name' => $this->owner->name,
            'email' => $this->owner->email,
            'password' => '',
        ]);

        $this->assertTrue(Hash::check('OldPassword123', $this->owner->fresh()->password));
    }

    public function test_a_non_blank_password_actually_changes_the_owners_password(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.owner.update', $this->spa->id), [
            'name' => $this->owner->name,
            'email' => $this->owner->email,
            'password' => 'BrandNewPass99',
        ]);

        $this->assertTrue(Hash::check('BrandNewPass99', $this->owner->fresh()->password));
    }

    public function test_toggling_owner_status_deactivates_and_reactivates(): void
    {
        $this->assertTrue($this->owner->fresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.spas.owner.toggle-status', $this->spa->id))->assertRedirect();
        $this->assertFalse($this->owner->fresh()->is_active);

        $this->actingAs($this->admin)->patch(route('admin.spas.owner.toggle-status', $this->spa->id))->assertRedirect();
        $this->assertTrue($this->owner->fresh()->is_active);
    }

    public function test_a_deactivated_owner_cannot_log_in(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.owner.toggle-status', $this->spa->id));
        Auth::logout();

        $this->post('/login', ['email' => $this->owner->email, 'password' => 'OldPassword123'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_a_reactivated_owner_can_log_in_again(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.spas.owner.toggle-status', $this->spa->id));
        $this->actingAs($this->admin)->patch(route('admin.spas.owner.toggle-status', $this->spa->id));
        Auth::logout();

        $this->post('/login', ['email' => $this->owner->email, 'password' => 'OldPassword123'])->assertRedirect();
        $this->assertAuthenticatedAs($this->owner->fresh());
    }

    public function test_deleting_the_owner_soft_deletes_and_disables_login(): void
    {
        $this->actingAs($this->admin)->delete(route('admin.spas.owner.delete', $this->spa->id))->assertRedirect();

        $this->assertSoftDeleted($this->owner);

        Auth::logout();
        $this->post('/login', ['email' => $this->owner->email, 'password' => 'OldPassword123'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_owner_actions_are_rejected_when_the_spas_owner_is_a_super_admin(): void
    {
        $otherAdmin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $otherAdmin->assignRole('super_admin');
        $this->spa->update(['owner_user_id' => $otherAdmin->id]);

        $this->actingAs($this->admin)->patch(route('admin.spas.owner.update', $this->spa->id), [
            'name' => 'x', 'email' => 'x@example.com', 'password' => '',
        ])->assertForbidden();

        $this->actingAs($this->admin)->patch(route('admin.spas.owner.toggle-status', $this->spa->id))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('admin.spas.owner.delete', $this->spa->id))->assertForbidden();
    }
}
