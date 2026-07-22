<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('teacher or admin can manage achievements', function () {
    $tenant = Tenant::factory()->create();
    $teacher = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($teacher)->post(route('achievements.store'), [
        'student_id' => $student->id,
        'category' => 'Hafalan Qur\'an',
        'title' => 'Surah Al-Mulk 1-10',
        'score' => 90,
        'achieved_at' => now()->format('Y-m-d'),
        'note' => 'Lancar dan fasih',
    ])->assertRedirect();

    $this->assertDatabaseHas('achievements', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'title' => 'Surah Al-Mulk 1-10',
        'score' => 90,
    ]);
});

test('reports calculates aggregate stats correctly', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    Attendance::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'status' => 'hadir',
    ]);

    $response = $this->actingAs($admin)->get(route('reports.index', [
        'start_date' => now()->startOfMonth()->format('Y-m-d'),
        'end_date' => now()->endOfMonth()->format('Y-m-d'),
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reports/Index')
        ->has('rekap', 1)
        ->where('rekap.0.hadir', 1)
    );
});

test('reports csv export streams csv file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($admin)->get(route('reports.export-csv'));
    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
