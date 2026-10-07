<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\HotelOrganization;
use App\Models\User;

class HotelOrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewHotels->value);
    }

    public function view(User $user, HotelOrganization $hotel): bool
    {
        return $this->viewAny($user)
            && (! $user->isOrgScoped() || $user->organization_id === $hotel->id);
    }

    public function create(User $user): bool
    {
        // Creating new organizations is a platform-level action for super admins only.
        return $user->hasRole('super-admin')
            && $user->hasPermissionTo(PermissionName::ManageHotels->value);
    }

    public function update(User $user, HotelOrganization $hotel): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHotels->value)
            && (! $user->isOrgScoped() || $user->organization_id === $hotel->id);
    }

    public function delete(User $user, HotelOrganization $hotel): bool
    {
        return $user->hasRole('super-admin')
            && $user->hasPermissionTo(PermissionName::ManageHotels->value);
    }
}
