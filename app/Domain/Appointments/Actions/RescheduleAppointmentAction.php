<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Services\AppointmentConflictChecker;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Validation\ValidationException;

class RescheduleAppointmentAction
{
    public function __construct(
        private readonly AppointmentConflictChecker $conflictChecker,
        private readonly TenantContext $tenantContext,
    ) {}

    public function execute(Appointment $appointment, string $startsAt): Appointment
    {
        // Prevent rescheduling completed appointments
        if ($appointment->status === 'completed') {
            throw ValidationException::withMessages([
                'status' => 'Cannot reschedule a completed appointment.',
            ]);
        }

        // Prevent rescheduling if invoice is paid
        if ($appointment->invoice()->exists()) {
            $invoice = $appointment->invoice;
            if (in_array($invoice->status, ['paid', 'partially_paid'], true)) {
                throw ValidationException::withMessages([
                    'appointment' => 'Cannot reschedule appointment with a paid invoice. Please refund and create a new appointment.',
                ]);
            }
        }

        $newStartsAt = now()->parse($startsAt);
        $durationMinutes = $appointment->starts_at->diffInMinutes($appointment->ends_at);
        $newEndsAt = $newStartsAt->copy()->addMinutes($durationMinutes);

        // Prevent rescheduling to the past
        if ($newStartsAt->isPast()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Cannot reschedule to a past date/time.',
            ]);
        }

        // Validate business hours if spa has them configured
        $spa = $this->tenantContext->getCurrentSpa();
        if ($spa->opening_time && $spa->closing_time) {
            $this->validateBusinessHours($newStartsAt, $newEndsAt, $spa->opening_time, $spa->closing_time);
        }

        // Validate weekly off days
        if ($spa->weekly_off_days && is_array($spa->weekly_off_days)) {
            $dayOfWeek = strtolower($newStartsAt->englishDayOfWeek);
            if (in_array($dayOfWeek, array_map('strtolower', $spa->weekly_off_days), true)) {
                throw ValidationException::withMessages([
                    'starts_at' => "Cannot reschedule to {$newStartsAt->englishDayOfWeek} (weekly off day).",
                ]);
            }
        }

        // Check employee conflict
        if ($this->conflictChecker->hasConflict($appointment->employee_id, $newStartsAt, $newEndsAt, $appointment->id)) {
            throw ValidationException::withMessages([
                'starts_at' => 'This therapist already has an appointment at that time.',
            ]);
        }

        $appointment->update([
            'starts_at' => $newStartsAt,
            'ends_at' => $newEndsAt,
            'status' => 'booked', // Reset to booked when rescheduled
        ]);

        return $appointment;
    }

    private function validateBusinessHours(
        \Carbon\Carbon $startsAt,
        \Carbon\Carbon $endsAt,
        string $openingTime,
        string $closingTime
    ): void {
        $appointmentStart = $startsAt->format('H:i:s');
        $appointmentEnd = $endsAt->format('H:i:s');

        if ($appointmentStart < $openingTime) {
            throw ValidationException::withMessages([
                'starts_at' => "Appointment starts before business hours (opens at {$openingTime}).",
            ]);
        }

        if ($appointmentEnd > $closingTime) {
            throw ValidationException::withMessages([
                'starts_at' => "Appointment ends after business hours (closes at {$closingTime}).",
            ]);
        }
    }
}
