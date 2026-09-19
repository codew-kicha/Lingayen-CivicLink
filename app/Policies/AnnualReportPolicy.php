<?php

namespace App\Policies;

use App\Models\AnnualReport;
use App\Models\User;

class AnnualReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, AnnualReport $annualReport): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, AnnualReport $annualReport): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, AnnualReport $annualReport): bool
    {
        return $user->isAdmin();
    }
}
