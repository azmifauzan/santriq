<?php

use App\Models\Tenant;
use App\Models\User;

test('admin can view teachers list and create teacher', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $response = $this->actingAsStaff($admin)->post(route('teachers.store'), [
        'name' => 'Ustadzah Fatimah',
        'email' => 'fatimah@example.com',
        'password' => 'password123',
        'role' => 'pengajar',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'tenant_id' => $tenant->id,
        'email' => 'fatimah@example.com',
        'role' => 'pengajar',
    ]);
});

test('pengajar cannot create teacher', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);

    $response = $this->actingAsStaff($pengajar)->post(route('teachers.store'), [
        'name' => 'New Teacher',
        'email' => 'new@example.com',
        'password' => 'password123',
        'role' => 'pengajar',
    ]);

    $response->assertForbidden();
});

test('admin can update and delete teacher in their tenant', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $teacher = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);

    $response = $this->actingAsStaff($admin)->put(route('teachers.update', $teacher), [
        'name' => 'Ustadzah Fatimah Updated',
        'email' => $teacher->email,
        'role' => 'pengajar',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'id' => $teacher->id,
        'name' => 'Ustadzah Fatimah Updated',
    ]);

    $deleteResponse = $this->actingAsStaff($admin)->delete(route('teachers.destroy', $teacher));
    $deleteResponse->assertRedirect();
    $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
});
