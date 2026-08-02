<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Services\SpreadsheetExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportCustomersAction
{
    private const HEADINGS = [
        'name', 'phone', 'whatsapp_number', 'date_of_birth', 'anniversary_date', 'gender',
        'email', 'city', 'state', 'occupation', 'medical_notes', 'allergy_notes', 'is_vip',
        'referral_code',
    ];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(?string $search): StreamedResponse
    {
        $rows = Customer::query()
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
            }))
            ->latest()
            ->get()
            ->map(fn (Customer $customer) => [
                $customer->name,
                $customer->phone,
                $customer->whatsapp_number,
                optional($customer->date_of_birth)->toDateString(),
                optional($customer->anniversary_date)->toDateString(),
                $customer->gender,
                $customer->email,
                $customer->city,
                $customer->state,
                $customer->occupation,
                $customer->medical_notes,
                $customer->allergy_notes,
                $customer->is_vip ? 'yes' : 'no',
                $customer->referral_code,
            ]);

        return $this->exportService->download('customers.xlsx', self::HEADINGS, $rows);
    }

    public function template(): StreamedResponse
    {
        return $this->exportService->download('customers-import-template.xlsx', self::HEADINGS, []);
    }
}
