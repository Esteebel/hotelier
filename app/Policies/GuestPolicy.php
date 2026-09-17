<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Guest;
use App\Models\User;

class GuestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewGuests->value);
    }

    public function view(User $user, Guest $guest): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageGuests->value);
    }

    public function update(User $user, Guest $guest): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageGuests->value);
    }

    public function delete(User $user, Guest $guest): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageGuests->value);
    }
}
