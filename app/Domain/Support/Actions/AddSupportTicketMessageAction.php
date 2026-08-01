<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\SupportTicket;
use App\Domain\Support\Models\SupportTicketMessage;
use Illuminate\Support\Facades\DB;

class AddSupportTicketMessageAction
{
    public function execute(SupportTicket $ticket, int $userId, bool $isFromAdmin, string $body): SupportTicketMessage
    {
        return DB::transaction(function () use ($ticket, $userId, $isFromAdmin, $body) {
            $message = $ticket->messages()->create([
                'user_id' => $userId,
                'is_from_admin' => $isFromAdmin,
                'body' => $body,
            ]);

            $updates = [
                'last_message_at' => now(),
                'last_message_from' => $isFromAdmin ? 'admin' : 'owner',
            ];

            // The author has obviously seen everything up to what they just wrote — the
            // OTHER side's read timestamp is left untouched, which is what makes it show as
            // unread for them.
            $updates[$isFromAdmin ? 'admin_read_at' : 'spa_owner_read_at'] = now();

            // Two conveniences matching how a real support desk behaves: an admin reply to a
            // still-untouched ticket signals work has started; an owner reply to a
            // resolved/closed ticket means the issue isn't actually done — reopen it rather
            // than letting a follow-up silently sit in a "closed" list.
            if ($isFromAdmin && $ticket->status === 'open') {
                $updates['status'] = 'in_progress';
            } elseif (! $isFromAdmin && in_array($ticket->status, ['resolved', 'closed'], true)) {
                $updates['status'] = 'open';
            }

            $ticket->update($updates);

            return $message;
        });
    }
}
