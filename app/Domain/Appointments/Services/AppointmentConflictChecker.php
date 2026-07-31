<?php

namespace App\Domain\Appointments\Services;

use App\Domain\Appointments\Models\Appointment;
use Carbon\Carbon;

class AppointmentConflictChecker
{
    /**
     * True if the employee already has an active (non-cancelled/no-show) appointment
     * whose time range overlaps the given window.
     */
    public function hasConflict(?int $employeeId, Carbon $startsAt, Carbon $endsAt, ?int $excludingAppointmentId = null): bool
    {
        if (! $employeeId) {
            return false;
        }

        return Appointment::query()
            ->where('employee_id', $employeeId)
            ->whereIn('status', Appointment::ACTIVE_STATUSES)
            ->when($excludingAppointmentId, fn ($query) => $query->whereKeyNot($excludingAppointmentId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->exists();
    }
}
