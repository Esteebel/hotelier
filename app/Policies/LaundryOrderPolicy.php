<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\LaundryOrder;
use App\Models\User;

class LaundryOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewLaundry->value);
    }

    public function view(User $user, LaundryOrder $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewLaundry->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageLaundry->value);
    }

    public function update(User $user, LaundryOrder $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageLaundry->value);
    }

    public function delete(User $user, LaundryOrder $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageLaundry->value);
    }
}
