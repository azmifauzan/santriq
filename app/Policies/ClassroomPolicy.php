<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return $user->tenant_id === $classroom->tenant_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $user->tenant_id === $classroom->tenant_id;
    }

    public function delete(User $user, Classroom $classroom): bool
    {
        return $user->isAdmin() && $user->tenant_id === $classroom->tenant_id;
    }
}
