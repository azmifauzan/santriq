<?php

namespace App\Policies;

use App\Models\Achievement;
use App\Models\User;

class AchievementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Achievement $achievement): bool
    {
        return $user->tenant_id === $achievement->tenant_id;
    }

    public function delete(User $user, Achievement $achievement): bool
    {
        return $user->tenant_id === $achievement->tenant_id;
    }
}
