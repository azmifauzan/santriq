<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\ReportCard;
use App\Models\User;
use App\Support\CurrentTenant;

class ReportCardPolicy
{
    public function create(User $user): bool
    {
        return $user->tenant_id === CurrentTenant::get()->id;
    }

    public function view(User|Guardian $user, ReportCard $reportCard): bool
    {
        if ($user->tenant_id !== $reportCard->tenant_id || $reportCard->tenant_id !== CurrentTenant::get()->id) {
            return false;
        }

        return $user instanceof User || $user->students()->where('students.id', $reportCard->student_id)->exists();
    }

    public function delete(User $user, ReportCard $reportCard): bool
    {
        return $this->view($user, $reportCard);
    }
}
