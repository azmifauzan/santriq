<?php

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Tests\TestCase;

/**
 * routes/tenant.php picks its domain-vs-prefix shape from config('tenancy.subdomain_active')
 * once, at boot. Flipping that with config()/putenv() from inside a test body is too late —
 * routes are already registered by the time the test's closure runs.
 *
 * putenv()/$_ENV/$_SERVER don't work either, even set before boot: bootstrapping re-runs
 * Dotenv's safeLoad(), and its repository is a process-wide static singleton (Illuminate\
 * Support\Env::$repository) whose ImmutableWriter only protects a key the FIRST time it's
 * loaded in the process. Once any earlier test has booted normally, that first load already
 * marked APP_TENANT_SUBDOMAIN_ACTIVE as "loaded by us" — so every later safeLoad() (i.e. every
 * later test's boot) freely overwrites it straight back to .env's value, silently discarding
 * whatever we set beforehand. Reproduced by instrumenting routes/tenant.php: in an isolated
 * run (first boot in the process) the override stuck; inside the full suite (many boots
 * already happened) getenv() read back 'true' moments after we'd putenv()'d 'false'.
 *
 * The one hook that runs after config is loaded but before routes are registered is
 * Application::booting() — providers (including route registration) boot right after
 * booting callbacks fire, per Illuminate\Foundation\Testing\TestCase::createApplication()
 * (see its own use of $app->booting() for WithCachedRoutes). Replicating that method here
 * instead of calling parent::createApplication() is what makes the override land in time.
 */
class PathFallbackTestCase extends TestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->booting(fn () => $app['config']->set('tenancy.subdomain_active', false));

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
