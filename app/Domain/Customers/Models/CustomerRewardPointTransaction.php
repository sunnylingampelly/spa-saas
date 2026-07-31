<?php

namespace App\Domain\Customers\Models;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Shared\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRewardPointTransaction extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'customer_id', 'invoice_id', 'type', 'points', 'balance_after', 'reason', 'created_by_user_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
