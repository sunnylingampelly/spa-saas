<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaPaymentSettingsTest extends TestCase
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

    public function test_owner_can_save_their_own_razorpay_credentials(): void
    {
        $this->actingAs($this->owner)->put('/spa/payment-settings', [
            'razorpay_key_id' => 'rzp_live_owner_key',
            'razorpay_key_secret' => 'owner_super_secret',
            'razorpay_webhook_secret' => 'owner_webhook_secret',
        ])->assertRedirect();

        $fresh = $this->spa->fresh();
        $this->assertSame('rzp_live_owner_key', $fresh->razorpay_key_id);
        $this->assertSame('owner_super_secret', $fresh->razorpay_key_secret);
        $this->assertSame('owner_webhook_secret', $fresh->razorpay_webhook_secret);
    }

    public function test_secrets_never_appear_in_the_page_props_after_saving(): void
    {
        $this->actingAs($this->owner)->put('/spa/payment-settings', [
            'razorpay_key_id' => 'rzp_live_owner_key',
            'razorpay_key_secret' => 'owner_super_secret',
            'razorpay_webhook_secret' => 'owner_webhook_secret',
        ]);

        $response = $this->actingAs($this->owner)->get('/spa/profile');

        $response->assertInertia(fn ($page) => $page
            ->where('razorpayConfigured', true)
            ->where('razorpayKeyId', 'rzp_live_owner_key')
            ->missing('spa.razorpay_key_secret')
            ->missing('spa.razorpay_webhook_secret')
        );

        $response->assertDontSee('owner_super_secret');
        $response->assertDontSee('owner_webhook_secret');
    }

    public function test_submitting_a_blank_secret_leaves_the_existing_secret_untouched(): void
    {
        $this->actingAs($this->owner)->put('/spa/payment-settings', [
            'razorpay_key_id' => 'rzp_live_owner_key',
            'razorpay_key_secret' => 'owner_super_secret',
            'razorpay_webhook_secret' => 'owner_webhook_secret',
        ]);

        // Owner updates just the Key ID later, leaving both secret fields blank (as the form
        // always renders them) — the previously saved secrets must survive untouched.
        $this->actingAs($this->owner)->put('/spa/payment-settings', [
            'razorpay_key_id' => 'rzp_live_owner_key_rotated',
            'razorpay_key_secret' => '',
            'razorpay_webhook_secret' => '',
        ]);

        $fresh = $this->spa->fresh();
        $this->assertSame('rzp_live_owner_key_rotated', $fresh->razorpay_key_id);
        $this->assertSame('owner_super_secret', $fresh->razorpay_key_secret);
        $this->assertSame('owner_webhook_secret', $fresh->razorpay_webhook_secret);
    }

    public function test_disconnect_clears_all_three_credential_fields(): void
    {
        $this->actingAs($this->owner)->put('/spa/payment-settings', [
            'razorpay_key_id' => 'rzp_live_owner_key',
            'razorpay_key_secret' => 'owner_super_secret',
            'razorpay_webhook_secret' => 'owner_webhook_secret',
        ]);

        $this->actingAs($this->owner)->delete('/spa/payment-settings')->assertRedirect();

        $fresh = $this->spa->fresh();
        $this->assertNull($fresh->razorpay_key_id);
        $this->assertNull($fresh->razorpay_key_secret);
        $this->assertNull($fresh->razorpay_webhook_secret);
    }

    public function test_disconnect_does_not_rotate_the_webhook_token(): void
    {
        $tokenBefore = $this->spa->razorpay_webhook_token;

        $this->actingAs($this->owner)->put('/spa/payment-settings', [
            'razorpay_key_id' => 'rzp_live_owner_key',
            'razorpay_key_secret' => 'owner_super_secret',
            'razorpay_webhook_secret' => 'owner_webhook_secret',
        ]);
        $this->actingAs($this->owner)->delete('/spa/payment-settings');

        $this->assertSame($tokenBefore, $this->spa->fresh()->razorpay_webhook_token);
    }

    public function test_every_spa_gets_a_unique_webhook_token_on_creation(): void
    {
        $this->assertNotNull($this->spa->razorpay_webhook_token);
        $this->assertSame(40, strlen($this->spa->razorpay_webhook_token));
    }
}
