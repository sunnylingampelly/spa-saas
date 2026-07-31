<?php

namespace App\Domain\Billing\Models;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'customer_id', 'appointment_id', 'created_by_user_id', 'guest_name', 'guest_phone',
        'invoice_number', 'public_token', 'financial_year', 'subtotal', 'discount_type', 'discount_value',
        'discount_amount', 'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'tip_amount', 'total_amount', 'paid_amount', 'balance_amount', 'status', 'notes',
        'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'tip_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function billedToName(): string
    {
        return $this->customer?->name ?? $this->guest_name ?? 'Guest';
    }

    /**
     * invoice_number contains "/" (e.g. "INV/2026-27/0001"), which is invalid in a
     * filename / Content-Disposition header — this is the safe form for those.
     */
    public function filenameSafeInvoiceNumber(): string
    {
        return str_replace('/', '-', $this->invoice_number);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
