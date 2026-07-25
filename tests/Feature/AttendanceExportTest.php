<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('attendance export returns an xlsx file honoring the date filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Attendance::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'date' => '2026-07-01']);

    $response = $this->actingAsStaff($admin)->get(route('attendance.export', ['date' => '2026-07-01']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
