<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCsoRep();
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->isAdmin() || $activity->organization->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCsoRep() && $user->organization;
    }

    public function update(User $user, Activity $activity): bool
    {
        return $activity->organization->user_id === $user->id && $activity->status === 'pending';
    }

    public function verify(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }
}
