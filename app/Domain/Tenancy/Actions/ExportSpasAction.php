<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Shared\Services\SpreadsheetExportService;
use App\Domain\Tenancy\Models\Spa;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportSpasAction
{
    private const HEADINGS = [
        'name', 'owner_name', 'owner_email', 'plan', 'subscription_status',
        'current_period_ends_at', 'platform_status', 'created_at',
    ];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(?string $search): StreamedResponse
    {
        $rows = Spa::withoutGlobalScopes()
            ->with(['owner:id,name,email', 'subscription'])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('owner', fn ($o) => $o->where('email', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->get()
            ->map(fn (Spa $spa) => [
                $spa->name,
                $spa->owner?->name,
                $spa->owner?->email,
                $spa->subscription?->plan_code,
                $spa->subscription?->status,
                optional($spa->subscription?->current_period_ends_at)->toDateString(),
                $spa->status,
                optional($spa->created_at)->toDateString(),
            ]);

        return $this->exportService->download('spas.xlsx', self::HEADINGS, $rows);
    }
}
