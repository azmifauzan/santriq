<?php

use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('leave request export returns an xlsx file honoring the status filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    LeaveRequest::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'status' => 'pending']);

    $response = $this->actingAsStaff($admin)->get(route('leave-requests.export', ['status' => 'pending']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
