<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Appointments\Actions\CreateAppointmentAction;
use App\Domain\Appointments\Actions\DeleteAppointmentAction;
use App\Domain\Appointments\Actions\ExportAppointmentsAction;
use App\Domain\Appointments\Actions\RescheduleAppointmentAction;
use App\Domain\Appointments\Actions\UpdateAppointmentAction;
use App\Domain\Appointments\Actions\UpdateAppointmentStatusAction;
use App\Domain\Appointments\DTOs\CreateAppointmentData;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $date = $request->string('date')->toString() ?: now()->toDateString();
        $day = Carbon::parse($date);

        return Inertia::render('Appointments/Index', [
            'date' => $day->toDateString(),
            'appointments' => Appointment::query()
                ->with([
                    'customer:id,name,phone',
                    'employee:id,name',
                    'service:id,name,duration_minutes,color_hex',
                    'invoice:id,appointment_id,invoice_number,status',
                ])
                ->whereBetween('starts_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Appointments/Create', [
            ...$this->formOptions(),
            'initialDate' => $request->string('date')->toString() ?: now()->toDateString(),
        ]);
    }

    public function store(Request $request, CreateAppointmentAction $action): RedirectResponse
    {
        $data = $this->validated($request);

        $appointment = $action->execute(new CreateAppointmentData(
            customerId: $data['customer_id'],
            serviceId: $data['service_id'],
            startsAt: $data['starts_at'],
            employeeId: $data['employee_id'] ?? null,
            bookingType: $data['booking_type'] ?? 'advance',
            notes: $data['notes'] ?? null,
        ));

        return redirect()
            ->route('appointments.index', ['date' => $appointment->starts_at->toDateString()])
            ->with('success', 'Appointment booked.');
    }

    public function edit(Appointment $appointment): Response
    {
        $this->authorize('update', $appointment);

        return Inertia::render('Appointments/Edit', [
            'appointment' => $appointment,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Appointment $appointment, UpdateAppointmentAction $action): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $action->execute($appointment, $data);

        return redirect()
            ->route('appointments.index', ['date' => $appointment->starts_at->toDateString()])
            ->with('success', 'Appointment updated.');
    }

    public function destroy(Appointment $appointment, DeleteAppointmentAction $action): RedirectResponse
    {
        $this->authorize('delete', $appointment);

        $date = $appointment->starts_at->toDateString();
        $action->execute($appointment);

        return redirect()->route('appointments.index', ['date' => $date])->with('success', 'Appointment removed.');
    }

    public function updateStatus(Request $request, Appointment $appointment, UpdateAppointmentStatusAction $action): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'status' => ['required', 'in:booked,confirmed,in_progress,completed,cancelled,no_show'],
            'cancelled_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action->execute($appointment, $data['status'], $data['cancelled_reason'] ?? null);

        return back()->with('success', 'Appointment status updated.');
    }

    public function reschedule(Request $request, Appointment $appointment, RescheduleAppointmentAction $action): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate(['starts_at' => ['required', 'date']]);

        $action->execute($appointment, $data['starts_at']);

        return back()->with('success', 'Appointment rescheduled.');
    }

    public function export(Request $request, ExportAppointmentsAction $action): StreamedResponse
    {
        return $action->execute($request->string('date')->toString() ?: now()->toDateString());
    }

    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']),
            'employees' => Employee::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'services' => Service::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'duration_minutes', 'price']),
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'booking_type' => ['nullable', 'in:walk_in,advance'],
            'starts_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
