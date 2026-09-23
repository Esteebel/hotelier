<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Checkin;
use App\Models\User;

class CheckinPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageCheckins->value);
    }

    public function view(User $user, Checkin $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageCheckins->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageCheckins->value);
    }

    public function update(User $user, Checkin $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageCheckins->value);
    }

    public function delete(User $user, Checkin $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageCheckins->value);
    }
}
