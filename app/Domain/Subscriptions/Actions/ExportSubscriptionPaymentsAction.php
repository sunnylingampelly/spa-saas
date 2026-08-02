<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Shared\Services\SpreadsheetExportService;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportSubscriptionPaymentsAction
{
    private const HEADINGS = ['spa', 'plan', 'method', 'amount', 'status', 'date'];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(?string $status): StreamedResponse
    {
        $rows = SubscriptionPayment::withoutGlobalScopes()
            ->with('subscription.spa:id,name')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->get()
            ->map(fn (SubscriptionPayment $payment) => [
                $payment->subscription?->spa?->name,
                $payment->plan_code,
                $payment->method,
                $payment->amount,
                $payment->status,
                optional($payment->created_at)->toDateString(),
            ]);

        return $this->exportService->download('subscription-payments.xlsx', self::HEADINGS, $rows);
    }
}
