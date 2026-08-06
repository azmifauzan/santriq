<?php

use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CardPrintSettings;

test('user can perform CRUD on classrooms', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAsStaff($admin)->post(route('classrooms.store'), [
        'name' => 'Kelas Abu Bakar',
        'level' => 'Jilid 1',
    ])->assertRedirect();

    $this->assertDatabaseHas('classrooms', [
        'tenant_id' => $tenant->id,
        'name' => 'Kelas Abu Bakar',
    ]);

    $classroom = Classroom::firstWhere('name', 'Kelas Abu Bakar');

    $this->actingAsStaff($admin)->put(route('classrooms.update', $classroom), [
        'name' => 'Kelas Umar',
        'level' => 'Jilid 2',
    ])->assertRedirect();

    $this->assertDatabaseHas('classrooms', [
        'id' => $classroom->id,
        'name' => 'Kelas Umar',
    ]);
});

test('student creation generates unique qr_token and respects nis uniqueness', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAsStaff($admin)->post(route('students.store'), [
        'nis' => '1001',
        'name' => 'Ahmad Faiz',
        'gender' => 'L',
    ])->assertRedirect();

    $student = Student::firstWhere('nis', '1001');
    expect($student)->not->toBeNull();
    expect($student->qr_token)->not->toBeEmpty();

    // Duplicate NIS in same tenant should fail validation
    $response = $this->actingAsStaff($admin)->post(route('students.store'), [
        'nis' => '1001',
        'name' => 'Faiz Second',
        'gender' => 'L',
    ]);
    $response->assertSessionHasErrors(['nis']);
});

test('guardians can be created and linked to students', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAsStaff($admin)->post(route('guardians.store'), [
        'name' => 'Bapak Abdullah',
        'phone' => '08123456789',
        'student_ids' => [$student->id],
    ])->assertRedirect();

    $guardian = Guardian::firstWhere('name', 'Bapak Abdullah');
    expect($guardian)->not->toBeNull();
    expect($guardian->link_token)->not->toBeEmpty();
    expect($guardian->students)->toHaveCount(1);
});

test('print cards page renders SVG for students', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Students/PrintCards')
        ->has('students', 1)
        ->where('cardSettings', CardPrintSettings::defaults())
        ->where('logoPath', null)
    );
});

test('print cards page reflects saved card print settings', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'landing' => ['logo_path' => 'tenants/1/logo/logo.png'],
            'card_print' => [
                'columns_per_print_row' => 3,
                'accent_color' => '#059669',
                'show_nis' => false,
                'show_classroom' => true,
                'show_gender' => true,
                'show_logo' => true,
            ],
        ],
    ]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));

    $response->assertInertia(fn ($page) => $page
        ->component('Students/PrintCards')
        ->where('cardSettings.columns_per_print_row', 3)
        ->where('cardSettings.accent_color', '#059669')
        ->where('cardSettings.show_nis', false)
        ->where('logoPath', 'tenants/1/logo/logo.png')
    );
});

test('print cards page still returns logo path even when show_logo is off', function () {
    $tenant = Tenant::factory()->create([
        'settings' => [
            'landing' => ['logo_path' => 'tenants/1/logo/logo.png'],
            'card_print' => ['show_logo' => false],
        ],
    ]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.print-cards', ['ids' => $student->id]));

    $response->assertInertia(fn ($page) => $page
        ->where('logoPath', 'tenants/1/logo/logo.png')
    );
});
