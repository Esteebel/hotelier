<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewRoomTypes->value);
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTypes->value);
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTypes->value);
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTypes->value);
    }
}