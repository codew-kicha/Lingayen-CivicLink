<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    // An admin can never deactivate their own account, which also guarantees at least one
    // active admin always remains.
    public function deactivate(User $user, User $target): bool
    {
        return $user->isAdmin() && $target->isNot($user) && $target->is_active;
    }

    public function reactivate(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->is_active;
    }

    // Invitations only make sense until the person has set a password (which verifies them).
    public function invite(User $user, User $target): bool
    {
        return $user->isAdmin() && $target->is_active && $target->email_verified_at === null;
    }
}
