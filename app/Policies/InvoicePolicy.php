<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() && $user->tenant_id === $invoice->tenant_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->isAdmin() && $user->tenant_id === $invoice->tenant_id;
    }
}
