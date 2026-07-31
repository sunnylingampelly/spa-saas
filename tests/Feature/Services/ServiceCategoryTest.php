<?php

namespace Tests\Feature\Services;

use App\Domain\Services\Models\Service;
use App\Domain\Services\Models\ServiceCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCategoryTest extends TestCase
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

    public function test_an_owner_can_create_a_category(): void
    {
        $this->actingAs($this->owner)->post('/service-categories', ['name' => 'Massage Therapies'])
            ->assertRedirect();

        $this->assertDatabaseHas('service_categories', ['name' => 'Massage Therapies']);
    }

    public function test_a_service_can_be_assigned_to_a_category(): void
    {
        $this->actingAs($this->owner)->post('/service-categories', ['name' => 'Massage Therapies']);
        $category = ServiceCategory::first();

        $this->actingAs($this->owner)->post('/services', [
            'service_category_id' => $category->id,
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 2000,
        ]);

        $service = Service::first();
        $this->assertSame($category->id, $service->service_category_id);
    }

    public function test_deleting_a_category_nulls_out_the_services_category_reference(): void
    {
        $this->actingAs($this->owner)->post('/service-categories', ['name' => 'Massage Therapies']);
        $category = ServiceCategory::first();

        $this->actingAs($this->owner)->post('/services', [
            'service_category_id' => $category->id,
            'name' => 'Deep Tissue Massage',
            'duration_minutes' => 60,
            'price' => 2000,
        ]);
        $service = Service::first();

        $this->actingAs($this->owner)->delete("/service-categories/{$category->id}");

        $this->assertNull($service->fresh()->service_category_id);
    }
}
