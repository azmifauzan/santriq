<?php

use App\Models\Tenant;
use App\Models\User;

test('new registration creates tenant and admin user', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPQ Nurul Huda',
        'name' => 'Ustadz Ahmad',
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('tenants', [
        'name' => 'TPQ Nurul Huda',
    ]);

    $tenant = Tenant::where('name', 'TPQ Nurul Huda')->first();

    $this->assertDatabaseHas('users', [
        'email' => 'ahmad@example.com',
        'tenant_id' => $tenant->id,
        'role' => 'admin',
    ]);
});

test('user from tenant A cannot see users from tenant B', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);
    $userB = User::factory()->create(['tenant_id' => $tenantB->id]);

    $response = $this->actingAs($adminA)->get(route('teachers.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teachers/Index')
        ->has('teachers', 1)
        ->where('teachers.0.id', $adminA->id)
    );
});
