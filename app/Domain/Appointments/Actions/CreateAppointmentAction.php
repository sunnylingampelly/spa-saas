<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\DTOs\CreateAppointmentData;
use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Services\AppointmentConflictChecker;
use App\Domain\Services\Models\Service;
use App\Domain\Tenancy\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAppointmentAction
{
    public function __construct(
        private readonly AppointmentConflictChecker $conflictChecker,
        private readonly TenantContext $tenantContext,
    ) {}

    public function execute(CreateAppointmentData $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $service = Service::findOrFail($data->serviceId);
            $startsAt = now()->parse($data->startsAt);
            $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

            // Prevent booking in the past
            if ($startsAt->isPast()) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Cannot create appointments in the past.',
                ]);
            }

            // Prevent booking too far in advance (e.g., more than 1 year)
            if ($startsAt->greaterThan(now()->addYear())) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Cannot book appointments more than 1 year in advance.',
                ]);
            }

            // Validate business hours if spa has them configured
            $spa = $this->tenantContext->getCurrentSpa();
            if ($spa->opening_time && $spa->closing_time) {
                $this->validateBusinessHours($startsAt, $endsAt, $spa->opening_time, $spa->closing_time);
            }

            // Validate weekly off days
            if ($spa->weekly_off_days && is_array($spa->weekly_off_days)) {
                $dayOfWeek = strtolower($startsAt->englishDayOfWeek);
                if (in_array($dayOfWeek, array_map('strtolower', $spa->weekly_off_days), true)) {
                    throw ValidationException::withMessages([
                        'starts_at' => "Cannot book on {$startsAt->englishDayOfWeek} (weekly off day).",
                    ]);
                }
            }

            // Validate holiday calendar
            if ($spa->holiday_calendar && is_array($spa->holiday_calendar)) {
                $dateString = $startsAt->toDateString();
                foreach ($spa->holiday_calendar as $holiday) {
                    if (isset($holiday['date']) && $holiday['date'] === $dateString) {
                        $reason = $holiday['name'] ?? 'Holiday';
                        throw ValidationException::withMessages([
                            'starts_at' => "Cannot book on {$startsAt->format('d M Y')} ({$reason}).",
                        ]);
                    }
                }
            }

            // Check employee conflict
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
