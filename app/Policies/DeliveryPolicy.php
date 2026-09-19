<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isRider();
    }

    public function view(User $user, Delivery $delivery): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isRider() && $delivery->rider_id === $user->rider?->id;
    }

    public function update(User $user, Delivery $delivery): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isRider() && $delivery->rider_id === $user->rider?->id;
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->isAdmin();
    }
}
