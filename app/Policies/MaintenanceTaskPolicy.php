<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\MaintenanceTask;
use App\Models\User;

class MaintenanceTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewMaintenance->value);
    }

    public function view(User $user, MaintenanceTask $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewMaintenance->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageMaintenance->value);
    }

    public function update(User $user, MaintenanceTask $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageMaintenance->value);
    }

    public function delete(User $user, MaintenanceTask $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageMaintenance->value);
    }
}
