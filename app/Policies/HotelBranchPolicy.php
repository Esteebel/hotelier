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
        return $this->viewAny($user) && $this->withinScope($user, $branch);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageBranches->value);
    }

    public function update(User $user, HotelBranch $branch): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageBranches->value)
            && $this->withinScope($user, $branch);
    }

    public function delete(User $user, HotelBranch $branch): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageBranches->value)
            && $this->withinScope($user, $branch);
    }

    private function withinScope(User $user, HotelBranch $branch): bool
    {
        if (! $user->isOrgScoped()) {
            return true;
        }

        return $user->organization_id === $branch->organization_id
            || $user->branches()->whereKey($branch->id)->exists();
    }
}
