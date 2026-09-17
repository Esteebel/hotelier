<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewPayments->value);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManagePayments->value);
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo(PermissionName::ManagePayments->value);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo(PermissionName::ManagePayments->value);
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo(PermissionName::ManagePayments->value);
    }
}
