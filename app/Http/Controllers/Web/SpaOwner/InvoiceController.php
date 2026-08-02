<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Actions\CancelInvoiceAction;
use App\Domain\Billing\Actions\CreateInvoiceAction;
use App\Domain\Billing\Actions\ExportInvoicesAction;
use App\Domain\Billing\DTOs\CreateInvoiceData;
use App\Domain\Billing\DTOs\InvoiceItemData;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        return Inertia::render('Invoices/Index', [
            'invoices' => Invoice::query()
                ->with('customer:id,name,phone')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['status' => $status],
        ]);
    }

    public function create(Request $request): Response
    {
        $appointment = null;

        if ($appointmentId = $request->integer('appointment_id')) {
            $appointment = Appointment::with(['customer:id,name,phone', 'service:id,name,price,offer_price,gst_rate,duration_minutes', 'employee:id,name'])
                ->find($appointmentId);
        }

        return Inertia::render('Invoices/Create', [
            ...$this->formOptions(),
            'appointment' => $appointment,
        ]);
    }

    public function store(Request $request, CreateInvoiceAction $action): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'guest_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'items.*.employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount_type' => ['nullable', 'in:percentage,flat'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tip_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $invoice = $action->execute(new CreateInvoiceData(
            items: array_map(fn ($item) => new InvoiceItemData(
                serviceId: $item['service_id'],
                quantity: $item['quantity'],
                employeeId: $item['employee_id'] ?? null,
            ), $data['items']),
            customerId: $data['customer_id'] ?? null,
            guestName: $data['guest_name'] ?? null,
            guestPhone: $data['guest_phone'] ?? null,
            appointmentId: $data['appointment_id'] ?? null,
            discountType: $data['discount_type'] ?? null,
            discountValue: $data['discount_value'] ?? 0,
            tipAmount: $data['tip_amount'] ?? 0,
            notes: $data['notes'] ?? null,
        ));

        return redirect()->route('invoices.show', $invoice)->with('success', 'Bill created.');
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['items.service', 'items.employee', 'payments', 'customer']),
            'payUrl' => $invoice->balance_amount > 0 ? route('public.invoices.show', $invoice->public_token) : null,
        ]);
    }

    public function cancel(Request $request, Invoice $invoice, CancelInvoiceAction $action): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $action->execute($invoice, $data['reason'] ?? null);

        return back()->with('success', 'Invoice cancelled.');
    }

    public function export(Request $request, ExportInvoicesAction $action): StreamedResponse
    {
        return $action->execute($request->string('status')->toString() ?: null);
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'services' => Service::query()->where('status', 'active')->orderBy('name')
                ->get(['id', 'name', 'price', 'offer_price', 'gst_rate', 'duration_minutes']),
        ];
    }
}
