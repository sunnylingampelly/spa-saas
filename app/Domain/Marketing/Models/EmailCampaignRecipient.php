<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'spa_id', 'email_campaign_id', 'customer_id', 'email', 'tracking_token',
        'status', 'sent_at', 'error_message',
        'opened_at', 'open_count', 'clicked_at', 'click_count', 'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
