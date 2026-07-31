<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\DTOs\CreateAppointmentData;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Services\AppointmentConflictChecker;
use App\Domain\Services\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAppointmentAction
{
    public function __construct(private readonly AppointmentConflictChecker $conflictChecker) {}

    public function execute(CreateAppointmentData $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data->serviceId);
            $startsAt = now()->parse($data->startsAt);
            $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

            if ($this->conflictChecker->hasConflict($data->employeeId, $startsAt, $endsAt)) {
                throw ValidationException::withMessages([
                    'employee_id' => 'This therapist already has an appointment at that time.',
                ]);
            }

            return Appointment::create([
                'customer_id' => $data->customerId,
                'employee_id' => $data->employeeId,
                'service_id' => $data->serviceId,
                'booking_type' => $data->bookingType,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $data->notes,
            ]);
        });
    }
}
