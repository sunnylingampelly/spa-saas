<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Shared\Services\SpreadsheetExportService;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportAppointmentsAction
{
    private const HEADINGS = [
        'time', 'customer', 'service', 'employee', 'booking_type', 'lead_source', 'status',
    ];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(string $date): StreamedResponse
    {
        $day = Carbon::parse($date);

        $rows = Appointment::query()
            ->with(['customer:id,name', 'employee:id,name', 'service:id,name'])
            ->whereBetween('starts_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Appointment $appointment) => [
                $appointment->starts_at->format('H:i'),
                $appointment->customer?->name,
                $appointment->service?->name,
                $appointment->employee?->name ?? 'Unassigned',
                $appointment->booking_type,
                $appointment->lead_source,
                $appointment->status,
            ]);

        return $this->exportService->download("appointments-{$day->toDateString()}.xlsx", self::HEADINGS, $rows);
    }
}
