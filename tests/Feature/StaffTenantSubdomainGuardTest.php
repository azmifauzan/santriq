<?php

use App\Models\Tenant;
use App\Models\User;

test('a staff session for tenant A is logged out when it hits tenant B subdomain', function () {
    $tenantA = Tenant::factory()->create(['subdomain' => 'lembaga-a']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'lembaga-b']);
    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);

    $response = $this->actingAs($adminA)
        ->get("http://{$tenantB->subdomain}.santriq.test/dashboard");

    $response->assertRedirect("http://{$tenantB->subdomain}.santriq.test/login");
    $this->assertGuest();
});

test('a staff session for its own tenant subdomain is not disturbed', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'lembaga-c']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAs($admin)
        ->get("http://{$tenant->subdomain}.santriq.test/dashboard")
        ->assertOk();

    $this->assertAuthenticated();
});
