<?php

namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Models\DeviceHistory;
use App\Domain\Auth\Models\LoginHistory;
use App\Domain\Auth\Services\DeviceFingerprintService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Request as RequestFacade;
use Jenssegers\Agent\Agent;

class LoginHistoryListener
{
    public function handleLogin(Login $event): void
    {
        $agent = $this->makeAgent();

        LoginHistory::create([
            'user_id' => $event->user->id,
            'login_method' => 'password',
            'status' => 'success',
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device_type' => $this->deviceType($agent),
            'session_id' => RequestFacade::session()->getId(),
            'login_at' => now(),
        ]);

        $this->recordDevice($event->user->id, $agent);

        $event->user->forceFill(['last_login_at' => now()])->save();
    }

    public function handleFailed(Failed $event): void
    {
        $agent = $this->makeAgent();

        LoginHistory::create([
            'user_id' => $event->user?->id,
            'login_method' => 'password',
            'status' => 'failed',
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::userAgent(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device_type' => $this->deviceType($agent),
            'login_at' => now(),
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        LoginHistory::where('user_id', $event->user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first()
            ?->update(['logout_at' => now()]);
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
        ];
    }

    private function recordDevice(int $userId, Agent $agent): void
    {
        $fingerprint = DeviceFingerprintService::generate(
            RequestFacade::userAgent() ?? '',
            RequestFacade::ip() ?? ''
        );

        $device = DeviceHistory::firstOrNew([
            'user_id' => $userId,
            'device_fingerprint' => $fingerprint,
        ]);

        $device->fill([
            'device_name' => $agent->device() ?: 'Unknown device',
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'first_seen_at' => $device->exists ? $device->first_seen_at : now(),
            'last_seen_at' => now(),
        ])->save();
    }

    private function makeAgent(): Agent
    {
        $agent = new Agent;
        $agent->setUserAgent(RequestFacade::userAgent());

        return $agent;
    }

    private function deviceType(Agent $agent): string
    {
        return match (true) {
            $agent->isPhone() => 'phone',
            $agent->isTablet() => 'tablet',
            $agent->isDesktop() => 'desktop',
            default => 'unknown',
        };
    }

}
