<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\DTOs\CreateInvoiceData;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Services\GstCalculator;
use App\Domain\Billing\Services\InvoiceNumberGenerator;
use App\Domain\Customers\Models\Customer;
use App\Domain\Services\Models\Service;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateInvoiceAction
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly InvoiceNumberGenerator $numberGenerator,
        private readonly GstCalculator $gstCalculator,
    ) {}

    public function execute(CreateInvoiceData $data): Invoice
    {
        if (empty($data->items)) {
            throw ValidationException::withMessages(['items' => 'Add at least one service to the bill.']);
        }

        return DB::transaction(function () use ($data) {
            $spa = $this->tenantContext->getCurrentSpa();
            $customer = $data->customerId ? Customer::findOrFail($data->customerId) : null;

            $lines = $this->buildLines($data);
            $subtotal = round(array_sum(array_column($lines, 'line_total')), 2);
            $discountAmount = $this->calculateDiscount($data->discountType, $data->discountValue, $subtotal);

            $taxInput = [];
            foreach ($lines as &$line) {
                $proportionalDiscount = $subtotal > 0 ? round(($line['line_total'] / $subtotal) * $discountAmount, 2) : 0;
                $line['taxable_value'] = $line['line_total'] - $proportionalDiscount;
                $taxInput[] = ['lineTotal' => $line['taxable_value'], 'gstRate' => $line['gst_rate']];
            }
            unset($line);

            $isIntraState = $this->gstCalculator->isIntraState($spa->state, $customer?->state);
            $tax = $this->gstCalculator->calculate($taxInput, $isIntraState);

            $taxableAmount = $subtotal - $discountAmount;
            $totalAmount = round($taxableAmount + $tax['total'] + $data->tipAmount, 2);

            ['invoice_number' => $invoiceNumber, 'financial_year' => $financialYear] = $this->numberGenerator->generate($spa, now());

            $invoice = Invoice::create([
                'customer_id' => $customer?->id,
                'appointment_id' => $data->appointmentId,
                'created_by_user_id' => $spa->owner_user_id,
                'guest_name' => $customer ? null : $data->guestName,
                'guest_phone' => $customer ? null : $data->guestPhone,
                'invoice_number' => $invoiceNumber,
                'public_token' => Str::random(40),
                'financial_year' => $financialYear,
                'subtotal' => $subtotal,
                'discount_type' => $data->discountType,
                'discount_value' => $data->discountValue,
                'discount_amount' => $discountAmount,
                'taxable_amount' => $taxableAmount,
                'cgst_amount' => $tax['cgst'],
                'sgst_amount' => $tax['sgst'],
                'igst_amount' => $tax['igst'],
                'tip_amount' => $data->tipAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance_amount' => $totalAmount,
                'status' => 'unpaid',
                'notes' => $data->notes,
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'service_id' => $line['service_id'],
                    'employee_id' => $line['employee_id'],
                    'description' => $line['description'],
                    'hsn_sac_code' => $line['hsn_sac_code'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'gst_rate' => $line['gst_rate'],
                    'line_total' => $line['line_total'],
                ]);
            }

            return $invoice->load('items');
        });
    }

    private function buildLines(CreateInvoiceData $data): array
    {
        return array_map(function ($item) {
            $service = Service::findOrFail($item->serviceId);
            $unitPrice = $item->unitPrice ?? (float) ($service->offer_price ?? $service->price);
            $lineTotal = round($unitPrice * $item->quantity, 2);

            return [
                'service_id' => $service->id,
                'employee_id' => $item->employeeId,
                'description' => $service->name,
                'hsn_sac_code' => $service->hsn_sac_code,
                'quantity' => $item->quantity,
                'unit_price' => $unitPrice,
                'gst_rate' => (float) $service->gst_rate,
                'line_total' => $lineTotal,
            ];
        }, $data->items);
    }

    private function calculateDiscount(?string $type, float $value, float $subtotal): float
    {
        if (! $type || $value <= 0) {
            return 0.0;
        }

        $amount = $type === 'percentage' ? round($subtotal * ($value / 100), 2) : $value;

        return min($amount, $subtotal);
    }
}
