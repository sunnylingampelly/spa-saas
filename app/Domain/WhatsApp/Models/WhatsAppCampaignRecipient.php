<?php

namespace App\Domain\WhatsApp\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppCampaignRecipient extends Model
{
    use BelongsToTenant, HasFactory;

    // See WhatsAppTemplate::$table for why this is spelled out explicitly.
    protected $table = 'whatsapp_campaign_recipients';

    protected $fillable = [
        'spa_id', 'whatsapp_campaign_id', 'customer_id', 'phone_number',
        'status', 'meta_message_id', 'error_message', 'sent_at', 'delivered_at', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppCampaign::class, 'whatsapp_campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
