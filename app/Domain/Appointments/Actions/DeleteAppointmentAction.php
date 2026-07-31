<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;

class DeleteAppointmentAction
{
    public function execute(Appointment $appointment): void
    {
        $appointment->delete();
    }
}
