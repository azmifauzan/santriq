<?php

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('teacher or admin can manage achievements', function () {
    $tenant = Tenant::factory()->create();
    $teacher = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAsStaff($teacher)->post(route('achievements.store'), [
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

    $response = $this->actingAsStaff($admin)->get(route('reports.index', [
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

    $response = $this->actingAsStaff($admin)->get(route('reports.export-csv'));
    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
});

test('structured learning progress can be created updated and cleared', function () {
    $tenant = Tenant::factory()->create();
    $teacher = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $data = ['student_id' => $student->id, 'category' => 'Bacaan Iqra', 'title' => 'Latihan bacaan', 'achieved_at' => '2026-11-04',
        'learning_method' => 'Iqra', 'level' => '3', 'page_start' => 12, 'page_end' => 15, 'activity_type' => 'bacaan', 'fluency' => 'perlu_latihan', 'practice_note' => 'Ulangi panjang pendek.'];
    $this->actingAsStaff($teacher)->post(route('achievements.store'), $data)->assertRedirect();
    $achievement = Achievement::firstOrFail();
    expect($achievement->page_start)->toBe(12)->and($achievement->practice_note)->toBe('Ulangi panjang pendek.');
    $this->put(route('achievements.update', $achievement), [...$data, 'page_end' => 16, 'fluency' => 'lancar', 'practice_note' => null])->assertRedirect();
    expect($achievement->fresh()->page_end)->toBe(16)->and($achievement->fresh()->practice_note)->toBeNull();
});

test('invalid learning ranges and choices are rejected', function (array $fields, string $error) {
    $tenant = Tenant::factory()->create();
    $teacher = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAsStaff($teacher)->post(route('achievements.store'), [
        'student_id' => $student->id, 'category' => 'Hafalan', 'title' => 'Setoran', 'achieved_at' => '2026-11-04', ...$fields,
    ])->assertSessionHasErrors($error);
    $this->assertDatabaseCount('achievements', 0);
})->with([
    'reversed pages' => [['page_start' => 15, 'page_end' => 12], 'page_end'],
    'missing page start' => [['page_end' => 12], 'page_start'],
    'reversed verses' => [['surah' => 'An-Naba', 'ayah_start' => 15, 'ayah_end' => 12], 'ayah_end'],
    'missing surah' => [['ayah_start' => 2], 'surah'],
    'missing verse start' => [['surah' => 'An-Naba', 'ayah_end' => 12], 'ayah_start'],
    'out of bounds' => [['surah' => 'Al-Baqarah', 'ayah_start' => 287], 'ayah_start'],
    'invalid activity' => [['activity_type' => 'anything'], 'activity_type'],
    'invalid fluency' => [['fluency' => 'anything'], 'fluency'],
]);
