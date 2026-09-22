<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Checkout;
use App\Models\User;

class CheckoutPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewTransfers->value);
    }

    public function view(User $user, Checkout $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewTransfers->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageTransfers->value);
    }

    public function update(User $user, Checkout $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageTransfers->value);
    }

    public function delete(User $user, Checkout $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageTransfers->value);
    }
}
