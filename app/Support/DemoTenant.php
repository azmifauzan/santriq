<?php

namespace App\Support;

use App\Models\Tenant;

class DemoTenant
{
    public const SUBDOMAIN = 'demo';

    public static function isActive(): bool
    {
        return CurrentTenant::resolved() && CurrentTenant::get()->subdomain === self::SUBDOMAIN;
    }

    public static function exists(): bool
    {
        return Tenant::where('subdomain', self::SUBDOMAIN)->exists();
    }

    /**
     * Absolute URL to the demo tenant, or null if it hasn't been seeded.
     * Respects `tenancy.subdomain_active` the same way routes/tenant.php does.
     */
    public static function url(string $path = ''): ?string
    {
        if (! self::exists()) {
            return null;
        }

        $domain = config('tenancy.domain');
        $scheme = request()->getScheme();
        $port = request()->getPort();
        $isDefaultPort = ($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80);
        $portSuffix = $isDefaultPort ? '' : ":{$port}";

        return config('tenancy.subdomain_active')
            ? "{$scheme}://".self::SUBDOMAIN.".{$domain}{$portSuffix}{$path}"
            : "{$scheme}://{$domain}{$portSuffix}/".self::SUBDOMAIN.$path;
    }
}
