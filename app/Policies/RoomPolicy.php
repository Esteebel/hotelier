<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewRooms->value);
    }

    public function view(User $user, Room $room): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRooms->value);
    }

    public function update(User $user, Room $room): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRooms->value);
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRooms->value);
    }
}