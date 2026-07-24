<?php

namespace App\Support;

class DemoTenant
{
    public const SUBDOMAIN = 'demo';

    public static function isActive(): bool
    {
        return CurrentTenant::resolved() && CurrentTenant::get()->subdomain === self::SUBDOMAIN;
    }
}
