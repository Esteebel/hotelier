<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewExpenses->value);
    }

    public function view(User $user, Expense $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ViewExpenses->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageExpenses->value);
    }

    public function update(User $user, Expense $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageExpenses->value);
    }

    public function delete(User $user, Expense $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageExpenses->value);
    }
}
