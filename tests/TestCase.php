<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
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
