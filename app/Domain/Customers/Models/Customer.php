<?php

namespace App\Domain\Customers\Models;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Model implements HasMedia
{
    use BelongsToTenant, HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'customer_code', 'name', 'phone', 'whatsapp_number', 'date_of_birth', 'anniversary_date',
        'gender', 'email', 'address_line_1', 'address_line_2', 'city', 'state', 'pincode', 'occupation',
        'medical_notes', 'allergy_notes', 'preferred_service_id', 'preferred_employee_id',
        'wallet_balance', 'reward_points', 'referral_code', 'referred_by_customer_id',
        'tags', 'is_vip', 'customer_since', 'marketing_opt_out', 'marketing_opt_out_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'anniversary_date' => 'date',
            'customer_since' => 'date',
            'tags' => 'array',
            'is_vip' => 'boolean',
            'wallet_balance' => 'decimal:2',
            'marketing_opt_out' => 'boolean',
            'marketing_opt_out_at' => 'datetime',
        ];
    }

    public function preferredService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'preferred_service_id');
    }

    public function preferredEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'preferred_employee_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_customer_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_customer_id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(CustomerWalletTransaction::class);
    }

    public function rewardPointTransactions(): HasMany
    {
        return $this->hasMany(CustomerRewardPointTransaction::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
