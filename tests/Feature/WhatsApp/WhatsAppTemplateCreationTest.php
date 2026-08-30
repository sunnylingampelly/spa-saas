<?php

namespace Tests\Feature\WhatsApp;

use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTemplateCreationTest extends TestCase
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
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = $this->owner->spas()->firstOrFail();
        $this->spa->update([
            'whatsapp_phone_number_id' => '123456',
            'whatsapp_business_account_id' => 'waba-1',
            'whatsapp_access_token' => 'secret-token',
        ]);
    }

    private function validPayload(): array
    {
        return [
            'name' => 'festive_offer',
            'category' => 'marketing',
            'language' => 'en',
            'header_text' => 'A little something for you',
            'body_text' => 'Hi {{1}}, enjoy {{2}} off your next visit!',
            'footer_text' => 'Reply STOP to opt out',
            'variable_samples' => ['Priya', '20%'],
        ];
    }

    public function test_submitting_a_template_calls_meta_and_stores_it_locally_as_pending(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '999888777', 'status' => 'PENDING'], 200)]);

        $this->actingAs($this->owner)->post('/whatsapp-templates', $this->validPayload())->assertRedirect(route('whatsapp-templates.index'));

        $template = WhatsAppTemplate::firstOrFail();
        $this->assertSame('999888777', $template->meta_template_id);
        $this->assertSame('festive_offer', $template->name);
        $this->assertSame('pending', $template->status);
        $this->assertSame(2, $template->variableCount());

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v21.0/waba-1/message_templates'
            && $request['category'] === 'MARKETING');
    }

    public function test_a_non_snake_case_name_is_rejected_before_ever_calling_meta(): void
    {
        Http::fake();

        $this->actingAs($this->owner)->post('/whatsapp-templates', [
            ...$this->validPayload(),
            'name' => 'Festive Offer!',
        ])->assertSessionHasErrors('name');

        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppTemplate::count());
    }

    public function test_metas_own_rejection_is_flashed_back_and_nothing_is_stored(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Template content is disallowed'],
        ], 400)]);

        $response = $this->actingAs($this->owner)->post('/whatsapp-templates', $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Template content is disallowed', session('error'));
        $this->assertSame(0, WhatsAppTemplate::count());
    }

    public function test_a_spa_with_no_whatsapp_business_account_connected_is_stopped_before_calling_meta(): void
    {
        Http::fake();

        $this->spa->update(['whatsapp_business_account_id' => null]);

        $response = $this->actingAs($this->owner)->post('/whatsapp-templates', $this->validPayload());

        $response->assertSessionHas('error');
        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppTemplate::count());
    }
}
