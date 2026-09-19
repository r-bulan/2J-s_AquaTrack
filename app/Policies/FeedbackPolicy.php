<?php

namespace App\Policies;

use App\Models\Feedback;
use App\Models\User;

class FeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Feedback $feedback): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->isCustomer() && $feedback->customer_id === $user->customer?->id;
    }

    public function create(User $user): bool
    {
        return $user->isCustomer() || $user->isAdmin();
    }

    public function update(User $user, Feedback $feedback): bool
    {
        return $user->isAdmin();
    }
}
