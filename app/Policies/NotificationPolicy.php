<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewNotifications->value);
    }

    public function view(User $user, Notification $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewNotifications->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageNotifications->value);
    }

    public function update(User $user, Notification $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageNotifications->value);
    }

    public function delete(User $user, Notification $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageNotifications->value);
    }
}
