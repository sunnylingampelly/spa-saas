<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;

class UpdateAppointmentStatusAction
{
    public function execute(Appointment $appointment, string $status, ?string $cancelledReason = null): Appointment
    {
        $appointment->update([
            'status' => $status,
            'cancelled_reason' => $status === 'cancelled' ? $cancelledReason : null,
        ]);

        return $appointment;
    }
}
