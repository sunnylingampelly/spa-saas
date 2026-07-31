<?php

namespace Tests\Feature\Onboarding;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSpaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_an_authenticated_user_can_create_their_spa(): void
    {
        $user = User::factory()->create();
        $user->assignRole('spa_owner');

        $response = $this->actingAs($user)->post('/onboarding/create-spa', [
            'name' => 'Serenity Spa',
            'phone' => '9876543210',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
        ]);

        $spa = Spa::withoutGlobalScopes()->where('name', 'Serenity Spa')->first();

        $this->assertNotNull($spa);
        $this->assertSame($user->id, $spa->owner_user_id);
        $this->assertTrue($spa->users()->where('users.id', $user->id)->wherePivot('role_label', 'owner')->exists());
        $response->assertRedirect(route('dashboard'));
    }

    public function test_after_onboarding_the_dashboard_is_accessible(): void
    {
        $user = User::factory()->create();
        $user->assignRole('spa_owner');

        $this->actingAs($user)->post('/onboarding/create-spa', ['name' => 'Serenity Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $response = $this->get('/dashboard');

        $response->assertOk();
    }

    public function test_phone_is_required(): void
    {
        $user = User::factory()->create();
        $user->assignRole('spa_owner');

        $this->actingAs($user)->post('/onboarding/create-spa', [
            'name' => 'Serenity Spa',
            'state' => 'Karnataka',
        ])->assertSessionHasErrors('phone');
    }

    public function test_state_is_required_since_it_drives_gst_calculation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('spa_owner');

        $this->actingAs($user)->post('/onboarding/create-spa', [
            'name' => 'Serenity Spa',
            'phone' => '9876543210',
        ])->assertSessionHasErrors('state');
    }
}
