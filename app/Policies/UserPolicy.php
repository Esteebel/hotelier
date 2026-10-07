<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewUsers->value);
    }

    public function view(User $user, User $userBeingViewed): bool
    {
        return $this->viewAny($user)
            || $user->id === $userBeingViewed->id
            || ($this->withinScope($user, $userBeingViewed)
                && $user->hasPermissionTo(PermissionName::ManageUsers->value));
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageUsers->value);
    }

    public function update(User $user, User $userBeingViewed): bool
    {
        return ($this->create($user) && $this->withinScope($user, $userBeingViewed))
            || $user->id === $userBeingViewed->id;
    }

    public function delete(User $user, User $userBeingViewed): bool
    {
        if ($user->id === $userBeingViewed->id) {
            return false;
        }

        return $this->create($user) && $this->withinScope($user, $userBeingViewed);
    }

    /**
     * Organization-scoped users may only manage users of their own organization.
     */
    private function withinScope(User $user, User $userBeingViewed): bool
    {
        if (! $user->isOrgScoped()) {
            return true;
        }

        return $userBeingViewed->organization_id === $user->organization_id;
    }
}
