<?php

namespace Tests\Unit\Domain\WhatsApp;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\WhatsApp\Exceptions\WhatsAppApiException;
use App\Domain\WhatsApp\Services\WhatsAppClient;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppClientTest extends TestCase
{
    use RefreshDatabase;

    private function createSpa(array $whatsappFields = []): Spa
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $spa = $owner->spas()->firstOrFail();
        if ($whatsappFields) {
            $spa->update($whatsappFields);
        }

        return $spa->fresh();
    }

    public function test_a_spa_with_no_credentials_is_not_configured(): void
    {
        $spa = $this->createSpa();

        $this->assertFalse(WhatsAppClient::forSpa($spa)->isConfigured());
    }

    public function test_a_spa_with_both_fields_set_is_configured(): void
    {
        $spa = $this->createSpa(['whatsapp_phone_number_id' => '123456', 'whatsapp_access_token' => 'secret-token']);

        $this->assertTrue(WhatsAppClient::forSpa($spa)->isConfigured());
    }

    public function test_send_template_message_posts_the_correct_payload_to_this_spas_phone_number_id(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.HBg']]], 200)]);

        $spa = $this->createSpa(['whatsapp_phone_number_id' => '123456', 'whatsapp_access_token' => 'secret-token']);

        $result = WhatsAppClient::forSpa($spa)->sendTemplateMessage(
            '919876543210',
            'festive_offer',
            'en',
            [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => 'Priya']]]],
        );

        $this->assertSame('wamid.HBg', $result['message_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/123456/messages'
                && $request->hasHeader('Authorization', 'Bearer secret-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '919876543210'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'festive_offer'
                && $request['template']['language']['code'] === 'en';
        });
    }

    public function test_send_template_message_throws_with_metas_own_error_message_on_failure(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'error' => ['message' => 'Invalid parameter', 'error_data' => ['details' => 'Template name does not exist']],
        ], 400)]);

        $spa = $this->createSpa(['whatsapp_phone_number_id' => '123456', 'whatsapp_access_token' => 'secret-token']);

        $this->expectException(WhatsAppApiException::class);
        $this->expectExceptionMessage('Invalid parameter (Template name does not exist)');

        WhatsAppClient::forSpa($spa)->sendTemplateMessage('919876543210', 'missing_template', 'en', []);
    }

    public function test_fetch_phone_number_details_returns_the_display_number_and_verified_name(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'display_phone_number' => '+91 98765 43210',
            'verified_name' => 'Radiance Day Spa',
        ], 200)]);

        $spa = $this->createSpa(['whatsapp_phone_number_id' => '123456', 'whatsapp_access_token' => 'secret-token']);

        $details = WhatsAppClient::forSpa($spa)->fetchPhoneNumberDetails();

        $this->assertSame('+91 98765 43210', $details['display_phone_number']);
        $this->assertSame('Radiance Day Spa', $details['verified_name']);
    }

    public function test_create_template_posts_to_the_wabas_message_templates_endpoint(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '999888777', 'status' => 'PENDING'], 200)]);

        $spa = $this->createSpa(['whatsapp_phone_number_id' => '123456', 'whatsapp_access_token' => 'secret-token']);

        $result = WhatsAppClient::forSpa($spa)->createTemplate('waba-1', [
            'name' => 'festive_offer',
            'category' => 'MARKETING',
            'language' => 'en',
            'components' => [['type' => 'BODY', 'text' => 'Hi {{1}}']],
        ]);

        $this->assertSame('999888777', $result['id']);
        $this->assertSame('PENDING', $result['status']);

        Http::assertSent(fn ($request) => $request->url() === 'https://graph.facebook.com/v21.0/waba-1/message_templates');
    }

    public function test_two_different_spas_send_with_their_own_credentials(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        $spaA = $this->createSpa(['whatsapp_phone_number_id' => '111', 'whatsapp_access_token' => 'token-a']);
        $spaB = $this->createSpa(['whatsapp_phone_number_id' => '222', 'whatsapp_access_token' => 'token-b']);

        WhatsAppClient::forSpa($spaA)->sendTemplateMessage('919876543210', 'promo', 'en', []);
        WhatsAppClient::forSpa($spaB)->sendTemplateMessage('919876543210', 'promo', 'en', []);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/111/messages') && $request->hasHeader('Authorization', 'Bearer token-a'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/222/messages') && $request->hasHeader('Authorization', 'Bearer token-b'));
    }
}
