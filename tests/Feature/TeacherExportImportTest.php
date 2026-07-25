<?php

use App\Models\Tenant;
use App\Models\User;

test('teacher export returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'name' => 'Ustadz Fulan']);

    $response = $this->actingAsStaff($admin)->get(route('teachers.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('teacher export is denied for a non-admin', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);

    $this->actingAsStaff($pengajar)->get(route('teachers.export'))->assertForbidden();
});

test('teacher import creates accounts with a generated password and skips duplicate emails', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'existing@example.com']);

    $file = makeXlsxUploadedFile(
        ['Nama', 'Email', 'Role'],
        [
            ['Ustadzah Aminah', 'aminah@example.com', 'pengajar'],
            ['Duplikat Email', 'existing@example.com', 'pengajar'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('teachers.import'), ['file' => $file]);

    $response->assertRedirect();
    $newTeacher = User::firstWhere('email', 'aminah@example.com');
    expect($newTeacher)->not->toBeNull();
    expect($newTeacher->tenant_id)->toBe($tenant->id);
    expect($newTeacher->role)->toBe('pengajar');
    expect($newTeacher->password)->not->toBeNull();
    $this->assertDatabaseCount('users', 3); // admin + existing + new import
});

test('teacher import is denied for a non-admin', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);

    $file = makeXlsxUploadedFile(['Nama', 'Email', 'Role'], [['X', 'x@example.com', 'pengajar']]);

    $this->actingAsStaff($pengajar)->post(route('teachers.import'), ['file' => $file])->assertForbidden();
});
