<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\HotelBranch;
use App\Models\User;

class HotelBranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewBranches->value);
    }

    public function view(User $user, HotelBranch $branch): bool
    {
        return $this->viewAny($user)
            && ($user->hasRole('super-admin') || $user->branches()->whereKey($branch->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageBranches->value);
    }

    public function update(User $user, HotelBranch $branch): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageBranches->value);
    }

    public function delete(User $user, HotelBranch $branch): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageBranches->value);
    }
}