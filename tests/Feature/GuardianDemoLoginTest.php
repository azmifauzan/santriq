<?php

use App\Models\Guardian;
use App\Models\Tenant;
use App\Support\DemoTenant;

test('demo login bypass is unreachable on a non-demo tenant', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-bukan-demo']);
    Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $this->post("http://{$tenant->subdomain}.santriq.test/wali/masuk-demo")
        ->assertNotFound();

    $this->assertGuest('guardian');
});

test('demo login bypass 404s when the demo tenant has no guardian yet', function () {
    Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->post('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/wali/masuk-demo')
        ->assertNotFound();
});

test('demo login bypass logs in as the demo tenant guardian without a signed link', function () {
    $tenant = Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);

    $this->post('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/wali/masuk-demo')
        ->assertRedirect(route('guardian.portal.index'));

    $this->assertAuthenticatedAs($guardian, 'guardian');
});

test('the guardian login page flags isDemo only for the demo tenant', function () {
    $regular = Tenant::factory()->create(['subdomain' => 'tpq-biasa']);

    $this->get("http://{$regular->subdomain}.santriq.test/wali/masuk")
        ->assertInertia(fn ($page) => $page->where('isDemo', false));

    $demo = Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->get('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/wali/masuk')
        ->assertInertia(fn ($page) => $page->where('isDemo', true));
});
