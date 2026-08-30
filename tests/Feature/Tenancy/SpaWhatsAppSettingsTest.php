<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SpaWhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Spa $spa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = $this->owner->spas()->firstOrFail();
    }

    private function validPayload(): array
    {
        return [
            'whatsapp_phone_number_id' => '123456789',
            'whatsapp_business_account_id' => 'waba-1',
            'whatsapp_access_token' => 'owner_super_secret_token',
        ];
    }

    public function test_owner_can_save_their_own_whatsapp_credentials(): void
    {
        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', $this->validPayload())->assertRedirect();

        $fresh = $this->spa->fresh();
        $this->assertSame('123456789', $fresh->whatsapp_phone_number_id);
        $this->assertSame('waba-1', $fresh->whatsapp_business_account_id);
        $this->assertSame('owner_super_secret_token', $fresh->whatsapp_access_token);
    }

    public function test_the_access_token_never_appears_in_the_page_props_after_saving(): void
    {
        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', $this->validPayload());

        $response = $this->actingAs($this->owner)->get('/spa/profile');

        $response->assertInertia(fn ($page) => $page
            ->where('whatsappConfigured', true)
            ->missing('spa.whatsapp_access_token')
        );

        $response->assertDontSee('owner_super_secret_token');
    }

    public function test_submitting_a_blank_token_leaves_the_existing_token_untouched(): void
    {
        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', $this->validPayload());

        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', [
            ...$this->validPayload(),
            'whatsapp_phone_number_id' => '999999999',
            'whatsapp_access_token' => '',
        ]);

        $fresh = $this->spa->fresh();
        $this->assertSame('999999999', $fresh->whatsapp_phone_number_id);
        $this->assertSame('owner_super_secret_token', $fresh->whatsapp_access_token);
    }

    public function test_disconnect_clears_every_whatsapp_field(): void
    {
        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', $this->validPayload());

        $this->actingAs($this->owner)->delete('/spa/whatsapp-settings')->assertRedirect();

        $fresh = $this->spa->fresh();
        $this->assertNull($fresh->whatsapp_phone_number_id);
        $this->assertNull($fresh->whatsapp_business_account_id);
        $this->assertNull($fresh->whatsapp_access_token);
        $this->assertNull($fresh->whatsapp_display_phone_number);
        $this->assertNull($fresh->whatsapp_verified_name);
    }

    public function test_a_successful_test_connection_caches_the_display_number_and_verified_name(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'display_phone_number' => '+91 98765 43210',
            'verified_name' => 'Test Spa',
        ], 200)]);

        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', $this->validPayload());

        $response = $this->actingAs($this->owner)->post('/spa/whatsapp-settings/test');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $this->spa->fresh();
        $this->assertSame('+91 98765 43210', $fresh->whatsapp_display_phone_number);
        $this->assertSame('Test Spa', $fresh->whatsapp_verified_name);
    }

    public function test_a_failed_test_connection_flashes_the_real_meta_error_instead_of_pretending_it_worked(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 401)]);

        $this->actingAs($this->owner)->put('/spa/whatsapp-settings', $this->validPayload());

        $response = $this->actingAs($this->owner)->post('/spa/whatsapp-settings/test');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Invalid OAuth access token', session('error'));
    }
}
