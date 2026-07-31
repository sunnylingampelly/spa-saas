<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Customers\Actions\CreateCustomerAction;
use App\Domain\Customers\Actions\DeleteCustomerAction;
use App\Domain\Customers\Actions\UpdateCustomerAction;
use App\Domain\Customers\DTOs\CreateCustomerData;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Repositories\CustomerHistoryRepositoryInterface;
use App\Domain\Customers\Services\CustomerQrCodeService;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Customers/Index', [
            'customers' => Customer::query()
                ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");
                }))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create', $this->formOptions());
    }

    public function store(Request $request, CreateCustomerAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $customer = $action->execute(new CreateCustomerData(
            name: $data['name'],
            phone: $data['phone'] ?? null,
            whatsappNumber: $data['whatsapp_number'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            anniversaryDate: $data['anniversary_date'] ?? null,
            gender: $data['gender'] ?? null,
            email: $data['email'] ?? null,
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            occupation: $data['occupation'] ?? null,
            medicalNotes: $data['medical_notes'] ?? null,
            allergyNotes: $data['allergy_notes'] ?? null,
            preferredServiceId: $data['preferred_service_id'] ?? null,
            preferredEmployeeId: $data['preferred_employee_id'] ?? null,
            tags: $data['tags'] ?? [],
            isVip: $data['is_vip'] ?? false,
            referralCode: $data['referral_code'] ?? null,
        ));

        return redirect()->route('customers.show', $customer)->with('success', 'Customer added.');
    }

    public function show(Customer $customer, CustomerQrCodeService $qrCodeService, CustomerHistoryRepositoryInterface $historyRepository): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('Customers/Show', [
            'customer' => $customer->load([
                'preferredService', 'preferredEmployee', 'referredBy',
                'walletTransactions' => fn ($q) => $q->latest()->limit(20),
                'rewardPointTransactions' => fn ($q) => $q->latest()->limit(20),
            ]),
            'qrCodeSvg' => $qrCodeService->svgFor($customer->customer_code),
            'history' => [
                'stats' => $historyRepository->statsFor($customer->id),
                'recentAppointments' => $historyRepository->recentAppointments($customer->id),
                'recentInvoices' => $historyRepository->recentInvoices($customer->id),
            ],
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $this->authorize('update', $customer);

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Customer $customer, UpdateCustomerAction $action): RedirectResponse
    {
        $this->authorize('update', $customer);

        $data = $this->validated($request);
        unset($data['referral_code']);

        $action->execute($customer, $data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer, DeleteCustomerAction $action): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $action->execute($customer);

        return redirect()->route('customers.index')->with('success', 'Customer removed.');
    }

    private function formOptions(): array
    {
        return [
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'employees' => Employee::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * A minimal JSON create endpoint for inline "+ add new customer" pickers (e.g. booking an
     * appointment for a walk-in) — just enough to identify the customer, without leaving the
     * page the picker lives on. The full profile stays editable from Customers/Edit afterward.
     */
    public function quickCreate(Request $request, CreateCustomerAction $action)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $customer = $action->execute(new CreateCustomerData(
            name: $data['name'],
            phone: $data['phone'] ?? null,
        ));

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
        ], 201);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'anniversary_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'medical_notes' => ['nullable', 'string'],
            'allergy_notes' => ['nullable', 'string'],
            'preferred_service_id' => ['nullable', 'integer', 'exists:services,id'],
            'preferred_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'tags' => ['nullable', 'array'],
            'is_vip' => ['nullable', 'boolean'],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ]);
    }
}
