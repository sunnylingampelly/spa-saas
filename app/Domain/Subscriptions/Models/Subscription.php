<?php

namespace App\Domain\Subscriptions\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscription extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity;

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

    // Guards against paying for a plan the spa already effectively has: Lifetime never
    // needs another payment, and renewing the *same* still-active plan far ahead of its
    // expiry would just discard the time already paid for. A plan *change* (upgrade to
    // Lifetime, or renewing within the window as it approaches expiry) is always allowed.
    public function blockingReasonForPurchase(string $planCode): ?string
    {
        if ($this->status !== 'active') {
            return null;
        }

        if ($this->plan_code === 'lifetime') {
            return 'You already have lifetime access — no further payment is needed.';
        }

        if ($this->plan_code !== $planCode || $this->current_period_ends_at === null) {
            return null;
        }

        if ($this->current_period_ends_at->isPast()) {
            return null;
        }

        $renewalWindowDays = config('subscriptions.renewal_window_days');
        $renewalOpensAt = $this->current_period_ends_at->copy()->subDays($renewalWindowDays);

        if (now()->lt($renewalOpensAt)) {
            $label = config("subscriptions.plans.{$planCode}.label", $planCode);
            $expiryDate = $this->current_period_ends_at->format('d M Y');

            return "Your {$label} plan is already active until {$expiryDate}. You can renew starting {$renewalWindowDays} days before it ends.";
        }

        return null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
