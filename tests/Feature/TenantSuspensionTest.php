<?php

use App\Models\Tenant;
use App\Models\User;

test('suspended tenant landing page is inaccessible', function () {
    $tenant = Tenant::factory()->create(['suspended_at' => now()]);

    $this->get("http://{$tenant->subdomain}.santriq.test/")->assertForbidden();
});

test('suspended tenant blocks staff dashboard access', function () {
    $tenant = Tenant::factory()->create(['suspended_at' => now()]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)
        ->get("http://{$tenant->subdomain}.santriq.test/dashboard")
        ->assertForbidden();
});

test('active tenant landing page is still reachable', function () {
    $tenant = Tenant::factory()->create();

    $this->get("http://{$tenant->subdomain}.santriq.test/")->assertOk();
});
