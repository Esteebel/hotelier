<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewStaff->value);
    }

    public function view(User $user, Staff $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewStaff->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageStaff->value);
    }

    public function update(User $user, Staff $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageStaff->value);
    }

    public function delete(User $user, Staff $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageStaff->value);
    }
}
