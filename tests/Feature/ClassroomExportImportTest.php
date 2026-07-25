<?php

use App\Models\Classroom;
use App\Models\Tenant;
use App\Models\User;

test('classroom export returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);

    $response = $this->actingAsStaff($admin)->get(route('classrooms.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('classroom import creates rows and skips ones missing a name', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $file = makeXlsxUploadedFile(
        ['Nama', 'Level'],
        [
            ['Kelas Iqra 1', 'Jilid 1'],
            ['', 'Jilid 2'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('classrooms.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('classrooms', ['tenant_id' => $tenant->id, 'name' => 'Kelas Iqra 1', 'level' => 'Jilid 1']);
    $this->assertDatabaseCount('classrooms', 1);
});
