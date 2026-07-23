<?php

namespace Tests\Support;

use Tests\TestCase;

/**
 * routes/tenant.php picks its domain-vs-prefix shape from config('tenancy.subdomain_active')
 * once, at boot. Flipping that with config()/putenv() from inside a test body is too late —
 * routes are already registered by the time the test's closure runs. Overriding
 * createApplication() (called from setUp(), before anything else) is the one hook early
 * enough to change which shape gets registered for a given test.
 */
class PathFallbackTestCase extends TestCase
{
    public function createApplication()
    {
        putenv('APP_TENANT_SUBDOMAIN_ACTIVE=false');

        try {
            return parent::createApplication();
        } finally {
            putenv('APP_TENANT_SUBDOMAIN_ACTIVE');
        }
    }
}
