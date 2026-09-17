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
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHotels->value);
    }

    public function update(User $user, HotelOrganization $hotel): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHotels->value);
    }

    public function delete(User $user, HotelOrganization $hotel): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageHotels->value);
    }
}