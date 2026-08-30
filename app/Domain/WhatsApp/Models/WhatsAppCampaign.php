<?php

namespace App\Domain\WhatsApp\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WhatsAppCampaign extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    // See WhatsAppTemplate::$table for why this is spelled out explicitly.
    protected $table = 'whatsapp_campaigns';

    protected $fillable = [
        'spa_id', 'created_by_user_id', 'whatsapp_template_id', 'name',
        'variable_values', 'audience_filter', 'status', 'sent_at',
        'recipients_count', 'sent_count', 'delivered_count', 'read_count', 'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'variable_values' => 'array',
            'audience_filter' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'whatsapp_template_id');
    }

    public function recipients(): HasMany
    {
        // Explicit FK — see WhatsAppTemplate::campaigns() for why this can't be guessed.
        return $this->hasMany(WhatsAppCampaignRecipient::class, 'whatsapp_campaign_id');
    }

    public function deliveryRate(): float
    {
        return $this->sent_count > 0 ? round(($this->delivered_count / $this->sent_count) * 100, 1) : 0.0;
    }

    public function readRate(): float
    {
        return $this->sent_count > 0 ? round(($this->read_count / $this->sent_count) * 100, 1) : 0.0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
