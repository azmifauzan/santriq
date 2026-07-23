<?php

namespace App\Support;

use App\Models\Tenant;
use RuntimeException;

class CurrentTenant
{
    public static function get(): Tenant
    {
        if (! app()->bound(Tenant::class)) {
            throw new RuntimeException('No tenant has been resolved for this request.');
        }

        return app(Tenant::class);
    }

    public static function resolved(): bool
    {
        return app()->bound(Tenant::class);
    }
}
