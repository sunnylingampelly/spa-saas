<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;

class UpdateAppointmentAction
{
    public function execute(Appointment $appointment, array $data): Appointment
    {
        $appointment->update($data);

        return $appointment;
    }
}
