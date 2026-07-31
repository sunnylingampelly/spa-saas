<?php

namespace Tests\Unit\Domain\Tenancy;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Models\SpaSetting;
use App\Domain\Tenancy\Services\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToTenantTraitTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_auto_fills_the_tenant_column_on_create_when_context_is_set(): void
    {
        $owner = User::factory()->create();
        $spa = Spa::create(['owner_user_id' => $owner->id, 'name' => 'Auto Fill Spa', 'slug' => 'auto-fill-spa']);

        $this->app->make(TenantContext::class)->setCurrentSpaId($spa->id);

        $setting = SpaSetting::create(['group' => 'general', 'key' => 'foo', 'value' => ['bar' => 'baz']]);

        $this->assertSame($spa->id, $setting->spa_id);
    }

    public function test_the_global_scope_excludes_rows_belonging_to_other_tenants(): void
    {
        $ownerA = User::factory()->create();
        $spaA = Spa::create(['owner_user_id' => $ownerA->id, 'name' => 'Scope Spa A', 'slug' => 'scope-spa-a']);

        $ownerB = User::factory()->create();
        $spaB = Spa::create(['owner_user_id' => $ownerB->id, 'name' => 'Scope Spa B', 'slug' => 'scope-spa-b']);

        SpaSetting::withoutGlobalScopes()->create(['spa_id' => $spaA->id, 'group' => 'g', 'key' => 'k', 'value' => []]);
        SpaSetting::withoutGlobalScopes()->create(['spa_id' => $spaB->id, 'group' => 'g', 'key' => 'k', 'value' => []]);

        $this->app->make(TenantContext::class)->setCurrentSpaId($spaA->id);

        $this->assertCount(1, SpaSetting::all());
        $this->assertSame($spaA->id, SpaSetting::first()->spa_id);
    }
}
