<?php

namespace App\Domain\Auth\Services;

use Illuminate\Support\Str;

class DeviceFingerprintService
{
    public static function generate(string $userAgent, string $ipAddress): string
    {
        return hash('sha256', $userAgent.'|'.Str::before($ipAddress, ':'));
    }
}
