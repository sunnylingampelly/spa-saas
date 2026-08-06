<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;
use Illuminate\Validation\ValidationException;

class DeleteAppointmentAction
{
    public function execute(Appointment $appointment): void
    {
        // Prevent deletion if invoice exists
        if ($appointment->invoice()->exists()) {
            $invoice = $appointment->invoice;
            throw ValidationException::withMessages([
                'appointment' => "Cannot delete appointment with linked invoice #{$invoice->invoice_number}. Cancel the invoice first.",
            ]);
        }

        // Prevent deletion of completed appointments (use soft delete instead)
        if ($appointment->status === 'completed') {
            throw ValidationException::withMessages([
                'appointment' => 'Cannot delete a completed appointment. Use cancellation instead to maintain history.',
            ]);
        }

        $appointment->delete();
    }
}
