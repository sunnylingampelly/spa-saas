<?php

namespace App\Domain\Marketing\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EmailCampaign extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

    protected $fillable = [
        'spa_id', 'created_by_user_id', 'name', 'subject', 'preheader', 'body_html',
        'audience_filter', 'status', 'sent_at',
        'recipients_count', 'sent_count', 'opened_count', 'clicked_count', 'bounced_count', 'unsubscribed_count',
    ];

    protected function casts(): array
    {
        return [
            'audience_filter' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function openRate(): float
    {
        return $this->sent_count > 0 ? round(($this->opened_count / $this->sent_count) * 100, 1) : 0.0;
    }

    public function clickRate(): float
    {
        return $this->sent_count > 0 ? round(($this->clicked_count / $this->sent_count) * 100, 1) : 0.0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs()->logExcept(['body_html']);
    }
}
