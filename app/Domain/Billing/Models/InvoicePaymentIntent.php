<?php

namespace App\Domain\Billing\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePaymentIntent extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'spa_id', 'invoice_id', 'status', 'amount',
        'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature', 'raw_response',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'raw_response' => 'array',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
