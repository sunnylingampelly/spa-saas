<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Billing\Repositories\CommissionRepositoryInterface;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CommissionReportController extends Controller
{
    public function index(Request $request, CommissionRepositoryInterface $repository, TenantContext $tenantContext): Response
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString()) : now();

        $summary = $repository->summaryForSpa($tenantContext->getCurrentSpaId(), $from, $to);

        return Inertia::render('Reports/Commissions', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'rows' => $summary,
            'totalCommission' => round((float) $summary->sum('commission'), 2),
            'totalRevenue' => round((float) $summary->sum('revenue'), 2),
        ]);
    }
}
