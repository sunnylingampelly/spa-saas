<?php

namespace App\Domain\Subscriptions\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'spa_id',
        'created_by_user_id',
        'plan_code',
        'status',
        'starts_at',
        'current_period_ends_at',
        'cancelled_at',
        'razorpay_customer_id',
        'razorpay_subscription_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function onTrial(): bool
    {
        return $this->status === 'trialing'
            && $this->current_period_ends_at !== null
            && now()->lt($this->current_period_ends_at);
    }

    public function hasAccess(): bool
    {
        return match ($this->status) {
            'active' => $this->current_period_ends_at === null || now()->lt($this->current_period_ends_at),
            'trialing' => $this->onTrial(),
            default => false,
        };
    }
}
