<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->tenant_id === $attendance->tenant_id;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->isAdmin() && $user->tenant_id === $attendance->tenant_id;
    }
}
