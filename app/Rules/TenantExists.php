<?php

namespace App\Rules;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class TenantExists
{
    /**
     * Existence rule limited to the caller's lembaga.
     *
     * The plain "exists" rule queries the table directly and therefore ignores the
     * BelongsToTenant global scope, which would let a request reference another
     * tenant's row by id. Always use this for foreign keys coming from a request.
     */
    public static function in(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)->where('tenant_id', Auth::user()?->tenant_id);
    }
}
