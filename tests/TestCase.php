<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $this->bootingApplication($app);

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function bootingApplication(Application $app): void
    {
        if (str_ends_with(static::class, 'SeparateTenantDomainTest')) {
            $app->booting(function () use ($app) {
                $app['config']->set('app.url', 'http://santriq.web.id');
                $app['config']->set('tenancy.domain', 'santriq.web.id');
                $app['config']->set('tenancy.tenant_domain', 'santriq.my.id');
                $app['config']->set('tenancy.subdomain_active', true);
                $app->instance('request', Request::create('http://santriq.web.id'));
            });
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Log in as a staff user AND register their tenant's subdomain as the
     * default `route()` parameter, mirroring what ResolveTenantFromDomain
     * does for a real request to {subdomain}.{tenant_domain}.
     */
    protected function actingAsStaff(User $user): static
    {
        $this->actingAs($user);

        URL::defaults(['subdomain' => $user->tenant->subdomain]);

        return $this;
    }
}
