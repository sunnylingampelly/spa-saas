<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Services\AppointmentConflictChecker;
use Illuminate\Validation\ValidationException;

class RescheduleAppointmentAction
{
    public function __construct(private readonly AppointmentConflictChecker $conflictChecker) {}

    public function execute(Appointment $appointment, string $startsAt): Appointment
    {
        $newStartsAt = now()->parse($startsAt);
        $durationMinutes = $appointment->starts_at->diffInMinutes($appointment->ends_at);
        $newEndsAt = $newStartsAt->copy()->addMinutes($durationMinutes);

        if ($this->conflictChecker->hasConflict($appointment->employee_id, $newStartsAt, $newEndsAt, $appointment->id)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This therapist already has an appointment at that time.',
            ]);
        }

        $appointment->update([
            'starts_at' => $newStartsAt,
            'ends_at' => $newEndsAt,
            'status' => 'booked',
        ]);

        return $appointment;
    }
}
