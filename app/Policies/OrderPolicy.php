<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isCustomer() && $order->customer_id === $user->customer?->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
