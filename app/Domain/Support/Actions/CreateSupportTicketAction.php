<?php

namespace App\Domain\Support\Actions;

use App\Domain\Support\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class CreateSupportTicketAction
{
    public function execute(int $spaId, int $userId, string $subject, string $body): SupportTicket
    {
        return DB::transaction(function () use ($spaId, $userId, $subject, $body) {
            $ticket = SupportTicket::create([
                'spa_id' => $spaId,
                'created_by_user_id' => $userId,
                'subject' => $subject,
                'status' => 'open',
                'last_message_at' => now(),
                'last_message_from' => 'owner',
                'spa_owner_read_at' => now(),
                'admin_read_at' => null,
            ]);

            $ticket->messages()->create([
                'user_id' => $userId,
                'is_from_admin' => false,
                'body' => $body,
            ]);

            return $ticket;
        });
    }
}
