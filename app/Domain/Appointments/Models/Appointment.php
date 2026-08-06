<?php

namespace App\Domain\Appointments\Models;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Appointment extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity, SoftDeletes;

    public const ACTIVE_STATUSES = ['booked', 'confirmed', 'in_progress'];

    protected $fillable = [
        'customer_id', 'employee_id', 'service_id', 'booking_type',
        'starts_at', 'ends_at', 'status', 'notes', 'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'email_reminder_sent_at' => 'datetime',
            'sms_reminder_sent_at' => 'datetime',
            'whatsapp_reminder_sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    /**
     * Check if the appointment can be cancelled
     */
    public function canBeCancelled(): bool
    {
        if (in_array($this->status, ['cancelled', 'no_show', 'completed'], true)) {
            return false;
        }

        if ($this->invoice()->exists()) {
            $invoice = $this->invoice;
            if (in_array($invoice->status, ['paid', 'partially_paid'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the appointment can be rescheduled
     */
    public function canBeRescheduled(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        if ($this->invoice()->exists()) {
            $invoice = $this->invoice;
            if (in_array($invoice->status, ['paid', 'partially_paid'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the appointment can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->invoice()->exists() && $this->status !== 'completed';
    }

    /**
     * Check if appointment is active (not cancelled/no-show)
     */
    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}
