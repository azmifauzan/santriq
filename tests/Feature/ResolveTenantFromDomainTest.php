<?php

use App\Models\Tenant;

test('unknown subdomain returns 404', function () {
    $this->get('http://ghost.santriq.test/')->assertNotFound();
});

test('bare apex domain is left untouched', function () {
    $this->get('http://santriq.test/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

test('known subdomain resolves the matching tenant', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-cek']);

    $this->get("http://{$tenant->subdomain}.santriq.test/")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('tenant.id', $tenant->id)
        );
});
