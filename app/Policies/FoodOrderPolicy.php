<?php

namespace App\Policies;

use App\Enums\PermissionName;
use App\Models\FoodOrder;
use App\Models\User;

class FoodOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRestaurantOrders->value);
    }

    public function view(User $user, FoodOrder $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRestaurantOrders->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRestaurantOrders->value);
    }

    public function update(User $user, FoodOrder $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRestaurantOrders->value);
    }

    public function delete(User $user, FoodOrder $transfer): bool
    {
        return $user->hasPermissionTo(PermissionName::ManageRestaurantOrders->value);
    }
}
