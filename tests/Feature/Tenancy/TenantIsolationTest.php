<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Models\SpaSetting;
use App\Domain\Tenancy\Policies\SpaPolicy;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Spa $spaA;

    private Spa $spaB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('spa_owner');
        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('spa_owner');

        $this->actingAs($this->ownerA)->post('/onboarding/create-spa', ['name' => 'Spa A', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spaA = Spa::withoutGlobalScopes()->where('name', 'Spa A')->firstOrFail();

        $this->actingAs($this->ownerB)->post('/onboarding/create-spa', ['name' => 'Spa B', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spaB = Spa::withoutGlobalScopes()->where('name', 'Spa B')->firstOrFail();

        SpaSetting::withoutGlobalScopes()->create([
            'spa_id' => $this->spaA->id,
            'group' => 'invoice',
            'key' => 'secret_note',
            'value' => ['note' => 'Spa A only'],
        ]);

        SpaSetting::withoutGlobalScopes()->create([
            'spa_id' => $this->spaB->id,
            'group' => 'invoice',
            'key' => 'secret_note',
            'value' => ['note' => 'Spa B only'],
        ]);
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_settings(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $visibleSettings = SpaSetting::all();

        $this->assertCount(1, $visibleSettings);
        $this->assertSame($this->spaA->id, $visibleSettings->first()->spa_id);
    }

    public function test_owner_a_cannot_view_spa_bs_settings_by_switching_context(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $this->assertFalse(
            SpaSetting::where('spa_id', $this->spaB->id)->exists(),
            'Owner A should not be able to see Spa B\'s settings even when filtering explicitly by its id.'
        );
    }

    public function test_owner_a_is_denied_policy_authorization_over_spa_b(): void
    {
        $policy = new SpaPolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->spaB));
        $this->assertFalse($policy->update($this->ownerA, $this->spaB));

        $this->assertTrue($policy->view($this->ownerA, $this->spaA));
        $this->assertTrue($policy->update($this->ownerA, $this->spaA));
    }
}
