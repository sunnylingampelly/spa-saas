<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Announcements\Models\Announcement;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->admin->assignRole('super_admin');

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        Spa::withoutGlobalScopes()->firstOrFail();
    }

    public function test_a_published_announcement_is_shared_with_every_spa_owner_page(): void
    {
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'message' => 'Scheduled maintenance tonight.',
        ]);

        $response = $this->actingAs($this->owner)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page->where('announcement.message', 'Scheduled maintenance tonight.'));
    }

    public function test_publishing_a_new_announcement_retires_the_previous_one(): void
    {
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), ['message' => 'First message.']);
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), ['message' => 'Second message.']);

        $this->assertSame(1, Announcement::where('is_active', true)->count());

        $response = $this->actingAs($this->owner)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page->where('announcement.message', 'Second message.'));
    }

    public function test_a_published_announcement_defaults_to_the_indigo_color_when_none_is_chosen(): void
    {
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), ['message' => 'No color chosen.']);

        $this->assertSame('indigo', Announcement::firstOrFail()->color);
    }

    public function test_super_admin_can_choose_the_banners_color(): void
    {
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'message' => 'Scheduled maintenance tonight.',
            'color' => 'rose',
        ]);

        $this->assertSame('rose', Announcement::firstOrFail()->color);

        $response = $this->actingAs($this->owner)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page->where('announcement.color', 'rose'));
    }

    public function test_an_invalid_color_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.announcements.store'), [
            'message' => 'Bad color test.',
            'color' => 'not-a-real-color',
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_withdrawing_an_announcement_removes_it_from_shared_props(): void
    {
        $this->actingAs($this->admin)->post(route('admin.announcements.store'), ['message' => 'Temporary notice.']);
        $announcement = Announcement::firstOrFail();

        $this->actingAs($this->admin)->patch(route('admin.announcements.deactivate', $announcement->id));

        $response = $this->actingAs($this->owner)->get('/dashboard');
        $response->assertInertia(fn ($page) => $page->where('announcement', null));
    }
}
