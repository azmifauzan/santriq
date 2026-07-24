<?php

use App\Models\Tenant;
use App\Support\DemoTenant;

test('the login page shows demo credentials only on the demo tenant', function () {
    $regular = Tenant::factory()->create(['subdomain' => 'tpq-biasa']);

    $this->get("http://{$regular->subdomain}.santriq.test/login")
        ->assertInertia(fn ($page) => $page->where('demoHint', null));

    Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->get('http://'.DemoTenant::SUBDOMAIN.'.santriq.test/login')
        ->assertInertia(fn ($page) => $page
            ->where('demoHint.admin.email', 'admin@santriq.test')
            ->where('demoHint.admin.password', 'password')
            ->where('demoHint.pengajar.email', 'pengajar@santriq.test')
            ->where('demoHint.pengajar.password', 'password')
        );
});

test('the login page has no demo hint on the bare apex domain', function () {
    $this->get('http://santriq.test/login')
        ->assertInertia(fn ($page) => $page->where('demoHint', null));
});
