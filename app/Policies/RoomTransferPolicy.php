<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\RoomTransfer;
use App\Models\User;

class RoomTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTransfers->value);
    }

    public function view(User $user, RoomTransfer $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTransfers->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTransfers->value);
    }

    public function update(User $user, RoomTransfer $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTransfers->value);
    }

    public function delete(User $user, RoomTransfer $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRoomTransfers->value);
    }
}
