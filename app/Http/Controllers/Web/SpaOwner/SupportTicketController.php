<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Support\Actions\AddSupportTicketMessageAction;
use App\Domain\Support\Actions\CreateSupportTicketAction;
use App\Domain\Support\Models\SupportTicket;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function index(): Response
    {
        $tickets = SupportTicket::latest('last_message_at')->get()->map(fn (SupportTicket $ticket) => [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'last_message_at' => $ticket->last_message_at,
            'unread' => $ticket->last_message_from === 'admin'
                && $ticket->last_message_at?->gt($ticket->spa_owner_read_at),
        ]);

        return Inertia::render('Support/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Support/Create');
    }

    public function store(Request $request, TenantContext $tenantContext, CreateSupportTicketAction $action): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = $action->execute($tenantContext->getCurrentSpaId(), $request->user()->id, $data['subject'], $data['body']);

        return redirect()->route('support.tickets.show', $ticket)->with('success', 'Support ticket created.');
    }

    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->update(['spa_owner_read_at' => now()]);

        return Inertia::render('Support/Show', [
            'ticket' => $ticket->load(['messages.author:id,name']),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, AddSupportTicketMessageAction $action): RedirectResponse
    {
        $this->authorize('view', $ticket);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $action->execute($ticket, $request->user()->id, false, $data['body']);

        return back()->with('success', 'Reply sent.');
    }
}
