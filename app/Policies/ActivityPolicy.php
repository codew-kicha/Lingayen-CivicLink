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

    // Activities are the record of an accredited organization's work, so logging needs an active
    // accreditation. Pending activities stay editable even if the accreditation lapses meanwhile.
    public function create(User $user): bool
    {
        return $user->isCsoRep() && $user->organization && $user->organization->isAccredited();
    }

    public function update(User $user, Activity $activity): bool
    {
        return $activity->organization->user_id === $user->id && $activity->status === 'pending';
    }

    // A representative can take back an activity until PESO has reviewed it.
    public function withdraw(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
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
