<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isAdmin() || $organization->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isCsoRep() && ! $user->organization;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isAdmin() || $organization->user_id === $user->id;
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    public function moderate(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    // Admin record-keeping: create on behalf of a CSO, edit details, attach a login, revoke.
    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
