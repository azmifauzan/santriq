<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;

class GuardianPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Guardian $guardian): bool
    {
        return $user->tenant_id === $guardian->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Guardian $guardian): bool
    {
        return $user->tenant_id === $guardian->tenant_id;
    }

    public function delete(User $user, Guardian $guardian): bool
    {
        return $user->isAdmin() && $user->tenant_id === $guardian->tenant_id;
    }
}
