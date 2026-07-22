<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->tenant_id === $leaveRequest->tenant_id;
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isAdmin() && $user->tenant_id === $leaveRequest->tenant_id;
    }
}
