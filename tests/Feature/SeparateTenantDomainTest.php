<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use App\Support\TenantSessionHandoff;

test('tenant subdomain is served on tenant root domain', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'foo']);

    $this->get('http://foo.santriq.my.id/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('tenant.id', $tenant->id)
        );
});

test('subdomain under apex domain is not served as tenant', function () {
    Tenant::factory()->create(['subdomain' => 'foo']);

    $this->get('http://foo.santriq.web.id/')->assertNotFound();
});

test('bare apex domain serves marketing welcome page', function () {
    $this->get('http://santriq.web.id/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

test('tenant session handoff link points to tenant domain and verifies successfully', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'foo']);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $link = TenantSessionHandoff::link($user);

    expect($link)->toStartWith('http://foo.santriq.my.id/auth/verify-session/');

    $this->get($link)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/SigningIn'));

    $this->assertAuthenticatedAs($user);
});

test('demo tenant url points to tenant domain for subdomain mode', function () {
    Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    expect(DemoTenant::url('/login'))->toBe('http://demo.santriq.my.id/login');
});

test('register page shares tenantDomain prop set to tenant root domain', function () {
    $this->get('http://santriq.web.id/register')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Register')
            ->where('tenantDomain', 'santriq.my.id')
        );
});
