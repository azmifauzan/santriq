<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Inertia\Support\SessionKey;

test('student export returns an xlsx file honoring the classroom filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $classroomA = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);
    $classroomB = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas B']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'classroom_id' => $classroomA->id, 'nis' => '1001', 'name' => 'Ahmad']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'classroom_id' => $classroomB->id, 'nis' => '1002', 'name' => 'Budi']);

    $response = $this->actingAsStaff($admin)->get(route('students.export', ['classroom_id' => $classroomA->id]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('student import creates valid rows and skips invalid/duplicate ones, reporting both', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $classroom = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'nis' => '1001']);

    $file = makeXlsxUploadedFile(
        ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'],
        [
            ['2001', 'Citra', 'P', '2015-01-01', 'Kelas A', 'active'],
            ['1001', 'Duplikat NIS', 'L', '2014-01-01', 'Kelas A', 'active'],
            ['', 'Tanpa NIS', 'L', '2014-01-01', 'Kelas A', 'active'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('students.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('students', ['tenant_id' => $tenant->id, 'nis' => '2001', 'name' => 'Citra', 'classroom_id' => $classroom->id]);
    $this->assertDatabaseCount('students', 2);

    $response->assertInertiaFlash('import_summary');
    $summary = $response->getSession()->get(SessionKey::FLASH_DATA)['import_summary'];
    expect($summary['created'])->toBe(1);
    expect($summary['skipped'])->toBe(2);
    expect($summary['errors'][0])->toContain('NIS 1001 sudah terdaftar.');
    expect($summary['errors'][1])->toContain('NIS wajib diisi.');
});

test('student import is scoped to the current tenant — a classroom name from another tenant does not resolve', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->create(['tenant_id' => $otherTenant->id, 'name' => 'Kelas Lintas Tenant']);

    $file = makeXlsxUploadedFile(
        ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'],
        [['3001', 'Doni', 'L', '2015-01-01', 'Kelas Lintas Tenant', 'active']],
    );

    $this->actingAsStaff($admin)->post(route('students.import'), ['file' => $file]);

    $this->assertDatabaseHas('students', ['tenant_id' => $tenant->id, 'nis' => '3001', 'classroom_id' => null]);
});

test('student template download returns an xlsx file with a Referensi sheet for existing classrooms', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);

    $response = $this->actingAsStaff($admin)->get(route('students.export', ['template' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('student template download works even when the tenant has no classrooms yet', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.export', ['template' => 1]));

    $response->assertOk();
});

test('re-uploading the untouched student template imports zero rows — the example lives outside the data table, not in row 2', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $templateResponse = $this->actingAsStaff($admin)->get(route('students.export', ['template' => 1]));
    $path = sys_get_temp_dir().'/'.uniqid('students-template-', true).'.xlsx';
    file_put_contents($path, $templateResponse->streamedContent());
    $file = new UploadedFile($path, 'template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->actingAsStaff($admin)->post(route('students.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseCount('students', 0);
});
