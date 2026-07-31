<?php

namespace App\Domain\Employees\Models;

use App\Domain\Shared\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Employee extends Model implements HasMedia
{
    use BelongsToTenant, HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'employee_code', 'name', 'gender', 'phone', 'email',
        'address_line_1', 'address_line_2', 'city', 'state', 'pincode',
        'emergency_contact_name', 'emergency_contact_phone',
        'joining_date', 'department', 'designation',
        'salary', 'commission_type', 'commission_value',
        'experience_years', 'skills', 'specializations',
        'performance_rating', 'performance_notes',
        'notes', 'status',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'skills' => 'array',
            'specializations' => 'array',
            'salary' => 'decimal:2',
            'commission_value' => 'decimal:2',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(EmployeeLeave::class);
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
