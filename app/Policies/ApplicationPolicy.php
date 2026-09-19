<?php

namespace App\Policies;

use App\Models\ApplicationModel;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isCsoRep();
    }

    public function view(User $user, ApplicationModel $application): bool
    {
        return $user->isAdmin() || $application->organization->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCsoRep() && $user->organization;
    }

    public function update(User $user, ApplicationModel $application): bool
    {
        return $user->isAdmin()
            || ($application->organization->user_id === $user->id && $application->status === 'draft');
    }

    public function review(User $user, ApplicationModel $application): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ApplicationModel $application): bool
    {
        return $user->isAdmin();
    }
}
