<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Tenancy\Models\Spa;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentValidator
{
    /**
     * Validate that appointment time respects business hours
     */
    public function validateBusinessHours(Carbon $startsAt, Carbon $endsAt, Spa $spa): void
    {
        if (!$spa->opening_time || !$spa->closing_time) {
            return;
        }

        $appointmentStart = $startsAt->format('H:i:s');
        $appointmentEnd = $endsAt->format('H:i:s');

        if ($appointmentStart < $spa->opening_time) {
            throw ValidationException::withMessages([
                'starts_at' => "Appointment starts before business hours (opens at {$spa->opening_time}).",
            ]);
        }

        if ($appointmentEnd > $spa->closing_time) {
            throw ValidationException::withMessages([
                'starts_at' => "Appointment ends after business hours (closes at {$spa->closing_time}).",
            ]);
        }
    }

    /**
     * Validate that appointment is not on a weekly off day
     */
    public function validateWeeklyOffDays(Carbon $startsAt, Spa $spa): void
    {
        if (!$spa->weekly_off_days || !is_array($spa->weekly_off_days)) {
            return;
        }

        $dayOfWeek = strtolower($startsAt->englishDayOfWeek);
        if (in_array($dayOfWeek, array_map('strtolower', $spa->weekly_off_days), true)) {
            throw ValidationException::withMessages([
                'starts_at' => "Cannot book on {$startsAt->englishDayOfWeek} (weekly off day).",
            ]);
        }
    }

    /**
     * Validate that appointment is not on a holiday
     */
    public function validateHolidayCalendar(Carbon $startsAt, Spa $spa): void
    {
        if (!$spa->holiday_calendar || !is_array($spa->holiday_calendar)) {
            return;
        }

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

    /**
     * Validate that appointment is not in the past
     */
    public function validateNotInPast(Carbon $startsAt): void
    {
        if ($startsAt->isPast()) {
            throw ValidationException::withMessages([
                'starts_at' => 'Cannot create appointments in the past.',
            ]);
        }
    }

    /**
     * Validate that appointment is not too far in the future
     */
    public function validateBookingWindow(Carbon $startsAt, int $maxDaysInAdvance = 365): void
    {
        if ($startsAt->greaterThan(now()->addDays($maxDaysInAdvance))) {
            throw ValidationException::withMessages([
                'starts_at' => "Cannot book appointments more than {$maxDaysInAdvance} days in advance.",
            ]);
        }
    }
}
