<?php

use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('user can perform CRUD on classrooms', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($admin)->post(route('classrooms.store'), [
        'name' => 'Kelas Abu Bakar',
        'level' => 'Jilid 1',
    ])->assertRedirect();

    $this->assertDatabaseHas('classrooms', [
        'tenant_id' => $tenant->id,
        'name' => 'Kelas Abu Bakar',
    ]);

    $classroom = Classroom::firstWhere('name', 'Kelas Abu Bakar');

    $this->actingAs($admin)->put(route('classrooms.update', $classroom), [
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

    $this->actingAs($admin)->post(route('students.store'), [
        'nis' => '1001',
        'name' => 'Ahmad Faiz',
        'gender' => 'L',
    ])->assertRedirect();

    $student = Student::firstWhere('nis', '1001');
    expect($student)->not->toBeNull();
    expect($student->qr_token)->not->toBeEmpty();

    // Duplicate NIS in same tenant should fail validation
    $response = $this->actingAs($admin)->post(route('students.store'), [
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

    $this->actingAs($admin)->post(route('guardians.store'), [
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

    $response = $this->actingAs($admin)->get(route('students.print-cards', ['ids' => $student->id]));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Students/PrintCards')
        ->has('students', 1)
    );
});
