<?php

namespace App\Domain\Appointments\Actions;

use App\Domain\Appointments\Models\Appointment;
use Illuminate\Validation\ValidationException;

class UpdateAppointmentStatusAction
{
    public function execute(Appointment $appointment, string $status, ?string $cancelledReason = null): Appointment
    {
        // Validate status transitions
        $this->validateStatusTransition($appointment->status, $status);

        // Prevent cancellation if invoice already exists and is paid/partially paid
        if (in_array($status, ['cancelled', 'no_show'], true) && $appointment->invoice()->exists()) {
            $invoice = $appointment->invoice;
            if (in_array($invoice->status, ['paid', 'partially_paid'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot cancel appointment with a paid invoice. Please refund the invoice first.',
                ]);
            }
        }

        // Require cancellation reason for cancelled and no_show
        if (in_array($status, ['cancelled', 'no_show'], true) && empty($cancelledReason)) {
            throw ValidationException::withMessages([
                'cancelled_reason' => 'A reason is required when cancelling or marking as no-show.',
            ]);
        }

        $appointment->update([
            'status' => $status,
            'cancelled_reason' => in_array($status, ['cancelled', 'no_show'], true) ? $cancelledReason : null,
        ]);

        return $appointment;
    }

    private function validateStatusTransition(string $currentStatus, string $newStatus): void
    {
        // Define valid transitions
        $validTransitions = [
            'booked' => ['confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'],
            'confirmed' => ['in_progress', 'completed', 'cancelled', 'no_show'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [], // Cannot change from completed
            'cancelled' => [], // Cannot change from cancelled
            'no_show' => [], // Cannot change from no_show
        ];

        if ($currentStatus === $newStatus) {
            return; // Allow same status (idempotent)
        }

        if (!isset($validTransitions[$currentStatus])) {
            throw ValidationException::withMessages([
                'status' => "Invalid current status: {$currentStatus}",
            ]);
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus], true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot transition from {$currentStatus} to {$newStatus}.",
            ]);
        }
    }
}
