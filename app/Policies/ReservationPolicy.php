<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewReservations->value);
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::CreateReservations->value);
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageReservations->value);
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageReservations->value);
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $user->hasPermissionTo(PermissionName::CancelReservations->value);
    }
}
