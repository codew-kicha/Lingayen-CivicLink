<?php

namespace App\Policies;

use App\Models\NewsPost;
use App\Models\User;

class NewsPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, NewsPost $newsPost): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, NewsPost $newsPost): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, NewsPost $newsPost): bool
    {
        return $user->isAdmin();
    }
}
