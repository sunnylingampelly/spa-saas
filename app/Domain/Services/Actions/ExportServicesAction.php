<?php

namespace App\Domain\Services\Actions;

use App\Domain\Services\Models\Service;
use App\Domain\Shared\Services\SpreadsheetExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportServicesAction
{
    private const HEADINGS = [
        'category', 'name', 'description', 'duration_minutes', 'price', 'offer_price',
        'gst_rate', 'hsn_sac_code', 'commission_type', 'commission_value', 'status',
    ];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(): StreamedResponse
    {
        $rows = Service::query()
            ->with('category')
            ->latest()
            ->get()
            ->map(fn (Service $service) => [
                $service->category?->name,
                $service->name,
                $service->description,
                $service->duration_minutes,
                $service->price,
                $service->offer_price,
                $service->gst_rate,
                $service->hsn_sac_code,
                $service->commission_type,
                $service->commission_value,
                $service->status,
            ]);

        return $this->exportService->download('services.xlsx', self::HEADINGS, $rows);
    }

    public function template(): StreamedResponse
    {
        return $this->exportService->download('services-import-template.xlsx', self::HEADINGS, []);
    }
}
