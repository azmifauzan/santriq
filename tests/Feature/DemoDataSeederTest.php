<?php

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\DemoTenant;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Hash;

test('seeding creates the demo tenant with staff, classrooms, students, guardians and history', function () {
    (new DemoDataSeeder)->run();

    $tenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();

    $admin = User::where('email', 'admin@santriq.test')->firstOrFail();
    expect($admin->tenant_id)->toBe($tenant->id)
        ->and($admin->role)->toBe('admin')
        ->and(Hash::check('password', $admin->password))->toBeTrue();

    $pengajar = User::where('email', 'pengajar@santriq.test')->firstOrFail();
    expect($pengajar->tenant_id)->toBe($tenant->id)
        ->and($pengajar->role)->toBe('pengajar');

    expect(Classroom::where('tenant_id', $tenant->id)->count())->toBe(2)
        ->and(Student::where('tenant_id', $tenant->id)->count())->toBe(10)
        ->and(Guardian::where('tenant_id', $tenant->id)->count())->toBe(10);

    $student = Student::where('tenant_id', $tenant->id)->firstOrFail();
    expect($student->guardians()->count())->toBe(1)
        ->and(Attendance::where('student_id', $student->id)->count())->toBeGreaterThan(0)
        ->and(Invoice::where('student_id', $student->id)->count())->toBe(1);
});

test('seeding twice does not duplicate classrooms or students but does refresh the admin password', function () {
    (new DemoDataSeeder)->run();

    $tenant = Tenant::where('subdomain', DemoTenant::SUBDOMAIN)->firstOrFail();
    $admin = User::where('email', 'admin@santriq.test')->firstOrFail();
    $admin->forceFill(['password' => Hash::make('changed-by-visitor')])->save();

    (new DemoDataSeeder)->run();

    expect(Student::where('tenant_id', $tenant->id)->count())->toBe(10);

    $admin->refresh();
    expect(Hash::check('password', $admin->password))->toBeTrue();
});
