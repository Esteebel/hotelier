<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\MenuItem;
use App\Models\User;

class MenuItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRestaurantOrders->value)
            || $user->hasPermissionTo(PermissionName::ManageMenuItems->value);
    }

    public function view(User $user, MenuItem $menuItem): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageMenuItems->value);
    }

    public function update(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageMenuItems->value);
    }

    public function delete(User $user, MenuItem $menuItem): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageMenuItems->value);
    }
}
