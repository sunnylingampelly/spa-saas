<?php

namespace App\Domain\Auth\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceHistory extends Model
{
    protected $fillable = [
        'user_id', 'device_fingerprint', 'device_name', 'browser', 'platform',
        'is_trusted', 'first_seen_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
