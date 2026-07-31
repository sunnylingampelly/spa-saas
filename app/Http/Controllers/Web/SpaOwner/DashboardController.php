<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Dashboard\Services\DashboardMetricsService;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(DashboardMetricsService $metricsService, TenantContext $tenantContext): Response
    {
        return Inertia::render('Dashboard/Index', [
            'metrics' => $metricsService->forSpa($tenantContext->getCurrentSpa()),
        ]);
    }
}
