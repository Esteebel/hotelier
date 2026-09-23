<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewServices->value);
    }

    public function view(User $user, ServiceRequest $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewServices->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageServices->value);
    }

    public function update(User $user, ServiceRequest $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageServices->value);
    }

    public function delete(User $user, ServiceRequest $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageServices->value);
    }
}
