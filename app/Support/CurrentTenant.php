<?php

namespace App\Support;

use App\Models\Tenant;

class CurrentTenant
{
    /**
     * The {subdomain} route parameter is optional on tenant routes purely so
     * Wayfinder generates optional TS arguments for pages outside a tenant
     * context (see routes/tenant.php and resources/js/lib/tenantUrlDefaults.ts).
     * That means a tenant-only endpoint can still be *matched* with no
     * subdomain resolved (e.g. the bare apex domain) — treat that the same
     * as an unknown subdomain: a 404, not an unhandled exception.
     */
    public static function get(): Tenant
    {
        abort_unless(app()->bound(Tenant::class), 404);

        return app(Tenant::class);
    }

    public static function resolved(): bool
    {
        return app()->bound(Tenant::class);
    }
}
