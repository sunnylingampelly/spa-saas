<?php

namespace App\Domain\Subscriptions\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'spa_id',
        'subscription_id',
        'confirmed_by_user_id',
        'plan_code',
        'method',
        'status',
        'amount',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'proof_note',
        'raw_response',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
