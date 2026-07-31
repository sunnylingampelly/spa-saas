<?php

namespace Tests\Feature\Services;

use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
    }

    public function test_an_owner_can_create_a_service(): void
    {
        $response = $this->actingAs($this->owner)->post('/services', [
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 2000,
        ]);

        $service = Service::where('name', 'Deep Tissue Massage')->first();

        $this->assertNotNull($service);
        $this->assertEquals(18.00, $service->gst_rate);
        $this->assertSame('active', $service->status);
        $response->assertRedirect(route('services.index'));
    }

    public function test_an_owner_can_update_a_service(): void
    {
        $this->actingAs($this->owner)->post('/services', ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1800]);
        $service = Service::first();

        $this->actingAs($this->owner)->put("/services/{$service->id}", [
            'name' => 'Swedish Massage',
            'duration_minutes' => 60,
            'price' => 1900,
            'offer_price' => 1600,
        ]);

        $this->assertEquals(1600, $service->fresh()->offer_price);
    }

    public function test_an_owner_can_toggle_service_status(): void
    {
        $this->actingAs($this->owner)->post('/services', ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1800]);
        $service = Service::first();

        $this->actingAs($this->owner)->patch("/services/{$service->id}/status");

        $this->assertSame('inactive', $service->fresh()->status);
    }

    public function test_an_owner_can_delete_a_service(): void
    {
        $this->actingAs($this->owner)->post('/services', ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1800]);
        $service = Service::first();

        $this->actingAs($this->owner)->delete("/services/{$service->id}");

        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }

    public function test_sample_catalog_can_be_seeded_once(): void
    {
        $this->actingAs($this->owner)->post('/services/seed-sample-catalog');

        $this->assertGreaterThan(0, Service::count());
        $countAfterFirstSeed = Service::count();

        // Idempotent: seeding again while services already exist should not duplicate.
        $this->actingAs($this->owner)->post('/services/seed-sample-catalog');

        $this->assertSame($countAfterFirstSeed, Service::count());
    }
}
