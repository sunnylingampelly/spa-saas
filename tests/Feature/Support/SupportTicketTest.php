<?php

namespace Tests\Feature\Support;

use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    private Spa $spa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->admin->assignRole('super_admin');

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = $this->owner->spas()->firstOrFail();
    }

    public function test_owner_can_create_a_ticket_and_it_is_visible_to_super_admin(): void
    {
        $this->actingAs($this->owner)->post(route('support.tickets.store'), [
            'subject' => "Can't record a UPI payment",
            'body' => 'The payment form throws an error when I select UPI.',
        ])->assertRedirect();

        $ticket = SupportTicket::firstOrFail();
        $this->assertSame($this->spa->id, $ticket->spa_id);
        $this->assertSame('open', $ticket->status);
        $this->assertSame(1, $ticket->messages()->count());

        $response = $this->actingAs($this->admin)->get(route('admin.support-tickets.index'));
        $response->assertInertia(fn ($page) => $page->where('tickets.data.0.subject', "Can't record a UPI payment"));
    }

    public function test_an_owner_from_a_different_spa_cannot_view_someone_elses_ticket(): void
    {
        $ticket = $this->createTicket();

        $otherOwner = User::factory()->create();
        $otherOwner->assignRole('spa_owner');
        $this->actingAs($otherOwner)->post('/onboarding/create-spa', ['name' => 'Other Spa', 'phone' => '9876500000', 'state' => 'Karnataka']);

        $this->actingAs($otherOwner)->get(route('support.tickets.show', $ticket->id))->assertForbidden();
    }

    public function test_owner_and_admin_replies_update_last_message_fields(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->admin)->post(route('admin.support-tickets.reply', $ticket->id), ['body' => 'Looking into it now.']);
        $fresh = $ticket->fresh();
        $this->assertSame('admin', $fresh->last_message_from);
        $this->assertSame(2, $fresh->messages()->count());

        $this->actingAs($this->owner)->post(route('support.tickets.reply', $ticket->id), ['body' => 'Thanks, still broken though.']);
        $fresh = $ticket->fresh();
        $this->assertSame('owner', $fresh->last_message_from);
        $this->assertSame(3, $fresh->messages()->count());
    }

    public function test_an_admin_reply_to_an_open_ticket_advances_it_to_in_progress(): void
    {
        $ticket = $this->createTicket();
        $this->assertSame('open', $ticket->status);

        $this->actingAs($this->admin)->post(route('admin.support-tickets.reply', $ticket->id), ['body' => 'On it.']);

        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_an_owner_reply_to_a_closed_ticket_reopens_it(): void
    {
        $ticket = $this->createTicket();
        $ticket->update(['status' => 'closed']);

        $this->actingAs($this->owner)->post(route('support.tickets.reply', $ticket->id), ['body' => 'This is happening again.']);

        $this->assertSame('open', $ticket->fresh()->status);
    }

    public function test_unread_counts_are_correct_through_the_full_lifecycle(): void
    {
        $ticket = $this->createTicket();

        // A brand new ticket is unread for the admin, read for the owner (they just wrote it).
        $adminHome = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $adminHome->assertInertia(fn ($page) => $page->where('openSupportTicketsCount', 1));
        $ownerHome = $this->actingAs($this->owner)->get(route('dashboard'));
        $ownerHome->assertInertia(fn ($page) => $page->where('unreadSupportCount', 0));

        // Admin views it -> admin unread drops to 0.
        $this->travel(1)->second();
        $this->actingAs($this->admin)->get(route('admin.support-tickets.show', $ticket->id));
        $adminHome = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $adminHome->assertInertia(fn ($page) => $page->where('openSupportTicketsCount', 0));

        // Admin replies -> owner unread becomes 1. Time must actually move forward here: the
        // unread check compares last_message_at against spa_owner_read_at with a strict ">",
        // and both are plain second-precision timestamps — without traveling, a fast test
        // run could tie them in the same second and this would wrongly read as "read".
        $this->travel(1)->second();
        $this->actingAs($this->admin)->post(route('admin.support-tickets.reply', $ticket->id), ['body' => 'Fixed, please retry.']);
        $ownerHome = $this->actingAs($this->owner)->get(route('dashboard'));
        $ownerHome->assertInertia(fn ($page) => $page->where('unreadSupportCount', 1));

        // Owner views it -> owner unread drops back to 0.
        $this->travel(1)->second();
        $this->actingAs($this->owner)->get(route('support.tickets.show', $ticket->id));
        $ownerHome = $this->actingAs($this->owner)->get(route('dashboard'));
        $ownerHome->assertInertia(fn ($page) => $page->where('unreadSupportCount', 0));
    }

    public function test_status_update_rejects_an_invalid_value(): void
    {
        $ticket = $this->createTicket();

        $this->actingAs($this->admin)->patch(route('admin.support-tickets.update-status', $ticket->id), [
            'status' => 'not-a-real-status',
        ])->assertSessionHasErrors('status');
    }

    private function createTicket(): SupportTicket
    {
        $this->actingAs($this->owner)->post(route('support.tickets.store'), [
            'subject' => 'Test issue',
            'body' => 'Something is not working.',
        ]);

        return SupportTicket::firstOrFail();
    }
}
