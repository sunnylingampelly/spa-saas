<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Subscriptions\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Spa extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'owner_user_id', 'name', 'slug', 'legal_business_name', 'gst_number', 'pan_number',
        'business_registration_number', 'phone', 'email', 'address_line_1', 'address_line_2',
        'city', 'state', 'pincode', 'google_maps_link', 'opening_time', 'closing_time',
        'weekly_off_days', 'holiday_calendar', 'invoice_prefix', 'invoice_footer_note',
        'financial_year_start_month', 'timezone', 'currency', 'status', 'onboarding_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'weekly_off_days' => 'array',
            'holiday_calendar' => 'array',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'spa_user')
            ->withPivot(['role_label', 'is_default'])
            ->withTimestamps();
    }

    public function settings(): HasMany
    {
        return $this->hasMany(SpaSetting::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
        $this->addMediaCollection('banner')->singleFile();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
