<?php

namespace Tests\Feature\Marketing;

use App\Domain\Marketing\Actions\BuildCampaignAudienceAction;
use App\Domain\Services\Models\Service;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Real-world case: "Radiance Day Spa" wants to send a win-back email to customers who
 * haven't visited in a while, a VIP-only thank-you, and a tag-based segment for a specific
 * promotion — and never, ever email someone who's opted out, no matter which segment.
 */
class EmailCampaignAudienceTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private int $spaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spaId = Spa::withoutGlobalScopes()->firstOrFail()->id;
    }

    private function customer(array $attributes = [])
    {
        return CustomerFactory::new()->create(array_merge(['spa_id' => $this->spaId], $attributes));
    }

    public function test_all_segment_includes_every_customer_with_an_email(): void
    {
        $this->customer(['name' => 'Has Email', 'email' => 'has-email@example.com']);
        $this->customer(['name' => 'No Email', 'email' => null]);

        $matches = app(BuildCampaignAudienceAction::class)->query(['type' => 'all'])->pluck('name');

        $this->assertTrue($matches->contains('Has Email'));
        $this->assertFalse($matches->contains('No Email'));
    }

    public function test_vip_segment_only_includes_vip_customers(): void
    {
        $this->customer(['name' => 'VIP Priya', 'email' => 'vip@example.com', 'is_vip' => true]);
        $this->customer(['name' => 'Regular Anjali', 'email' => 'regular@example.com', 'is_vip' => false]);

        $matches = app(BuildCampaignAudienceAction::class)->query(['type' => 'vip'])->pluck('name');

        $this->assertSame(['VIP Priya'], $matches->all());
    }

    public function test_tag_segment_matches_customers_with_that_tag(): void
    {
        $this->customer(['name' => 'Tagged', 'email' => 'tagged@example.com', 'tags' => ['vip-guest', 'referral']]);
        $this->customer(['name' => 'Untagged', 'email' => 'untagged@example.com', 'tags' => ['referral']]);

        $matches = app(BuildCampaignAudienceAction::class)->query(['type' => 'tag', 'tag' => 'vip-guest'])->pluck('name');

        $this->assertSame(['Tagged'], $matches->all());
    }

    public function test_inactive_days_segment_excludes_customers_billed_recently(): void
    {
        $this->actingAs($this->owner)->post('/services', ['name' => 'Massage', 'duration_minutes' => 60, 'price' => 1000]);
        $service = Service::firstOrFail();

        $recent = $this->customer(['name' => 'Recent Visitor', 'email' => 'recent@example.com']);
        $this->customer(['name' => 'Long Gone', 'email' => 'stale@example.com']);

        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $recent->id,
            'items' => [['service_id' => $service->id, 'quantity' => 1]],
        ]);

        // "Long Gone" has no invoice at all — never visited recently either way.
        $matches = app(BuildCampaignAudienceAction::class)->query(['type' => 'inactive_days', 'days' => 60])->pluck('name');

        $this->assertFalse($matches->contains('Recent Visitor'));
        $this->assertTrue($matches->contains('Long Gone'));
    }

    public function test_opted_out_customers_are_excluded_from_every_segment(): void
    {
        $this->customer([
            'name' => 'Opted Out VIP', 'email' => 'optout@example.com', 'is_vip' => true, 'marketing_opt_out' => true,
        ]);

        $allMatches = app(BuildCampaignAudienceAction::class)->query(['type' => 'all'])->pluck('name');
        $vipMatches = app(BuildCampaignAudienceAction::class)->query(['type' => 'vip'])->pluck('name');

        $this->assertFalse($allMatches->contains('Opted Out VIP'));
        $this->assertFalse($vipMatches->contains('Opted Out VIP'));
    }
}
