<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Support\Actions\AddSupportTicketMessageAction;
use App\Domain\Support\Models\SupportTicket;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $tickets = SupportTicket::withoutGlobalScopes()
            ->with(['spa:id,name', 'creator:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest('last_message_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SupportTicket $ticket) => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'last_message_at' => $ticket->last_message_at,
                'spa' => $ticket->spa,
                'creator' => $ticket->creator,
                'unread' => $ticket->admin_read_at === null || $ticket->last_message_at?->gt($ticket->admin_read_at),
            ]);

        return Inertia::render('SuperAdmin/SupportTickets', [
            'tickets' => $tickets,
            'filters' => ['status' => $status],
        ]);
    }

    public function show(SupportTicket $ticket): Response
    {
        $ticket->update(['admin_read_at' => now()]);

        return Inertia::render('SuperAdmin/SupportTicketShow', [
            'ticket' => $ticket->load(['messages.author:id,name', 'spa:id,name', 'creator:id,name']),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, AddSupportTicketMessageAction $action): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $action->execute($ticket, $request->user()->id, true, $data['body']);

        return back()->with('success', 'Reply sent.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
        ]);

        $ticket->update(['status' => $data['status']]);

        return back()->with('success', 'Ticket status updated.');
    }
}
