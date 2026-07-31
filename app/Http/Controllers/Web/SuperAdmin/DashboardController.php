<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Subscriptions\Services\PlatformMetricsService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(PlatformMetricsService $metricsService): Response
    {
        return Inertia::render('SuperAdmin/Dashboard', [
            'metrics' => $metricsService->forPlatform(),
        ]);
    }
}
