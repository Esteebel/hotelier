<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\HousekeepingTask;
use App\Models\User;

class HousekeepingTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewHousekeeping->value);
    }

    public function view(User $user, HousekeepingTask $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewHousekeeping->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHousekeeping->value);
    }

    public function update(User $user, HousekeepingTask $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHousekeeping->value);
    }

    public function delete(User $user, HousekeepingTask $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHousekeeping->value);
    }
}
