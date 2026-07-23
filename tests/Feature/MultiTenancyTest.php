<?php

use App\Models\Achievement;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('new registration creates tenant and admin user', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPQ Nurul Huda',
        'subdomain' => 'tpq-nurul-huda',
        'name' => 'Ustadz Ahmad',
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $tenant = Tenant::where('name', 'TPQ Nurul Huda')->first();

    $response->assertRedirect(route('login', ['registered' => 1]));

    $this->assertDatabaseHas('tenants', [
        'name' => 'TPQ Nurul Huda',
        'subdomain' => 'tpq-nurul-huda',
    ]);

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

    $response = $this->actingAsStaff($adminA)->get(route('teachers.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teachers/Index')
        ->has('teachers', 1)
        ->where('teachers.0.id', $adminA->id)
    );
});

test('foreign tenant ids are rejected on every request that accepts one', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);

    $foreignClassroom = Classroom::factory()->create(['tenant_id' => $tenantB->id]);
    $foreignStudent = Student::factory()->create(['tenant_id' => $tenantB->id]);
    $foreignGuardian = Guardian::factory()->create(['tenant_id' => $tenantB->id]);

    $this->actingAsStaff($adminA)
        ->post(route('students.store'), [
            'nis' => '99999',
            'name' => 'Santri Uji',
            'gender' => 'L',
            'classroom_id' => $foreignClassroom->id,
            'guardian_ids' => [$foreignGuardian->id],
        ])
        ->assertSessionHasErrors(['classroom_id', 'guardian_ids.0']);

    $this->actingAsStaff($adminA)
        ->post(route('guardians.store'), [
            'name' => 'Wali Uji',
            'student_ids' => [$foreignStudent->id],
        ])
        ->assertSessionHasErrors('student_ids.0');

    $this->actingAsStaff($adminA)
        ->post(route('achievements.store'), [
            'student_id' => $foreignStudent->id,
            'category' => 'Hafalan',
            'title' => 'Juz 30',
            'achieved_at' => '2026-07-22',
        ])
        ->assertSessionHasErrors('student_id');

    $this->actingAsStaff($adminA)
        ->post(route('invoices.store'), [
            'student_id' => $foreignStudent->id,
            'period' => '2026-07',
            'amount' => 50000,
            'due_date' => '2026-07-31',
        ])
        ->assertSessionHasErrors('student_id');

    $this->actingAsStaff($adminA)
        ->post(route('leave-requests.store'), [
            'student_id' => $foreignStudent->id,
            'type' => 'sakit',
            'start_date' => '2026-07-22',
            'end_date' => '2026-07-23',
        ])
        ->assertSessionHasErrors('student_id');

    expect(Student::withoutGlobalScopes()->where('nis', '99999')->count())->toBe(0);
    expect(Achievement::withoutGlobalScopes()->count())->toBe(0);
    expect(Invoice::withoutGlobalScopes()->count())->toBe(0);
    expect(LeaveRequest::withoutGlobalScopes()->count())->toBe(0);
    expect($foreignGuardian->students()->count())->toBe(0);
});
