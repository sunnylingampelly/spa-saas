<?php

namespace App\Domain\Appointments\Policies;

use App\Domain\Appointments\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->hasRole('super_admin') || $appointment->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }
}
