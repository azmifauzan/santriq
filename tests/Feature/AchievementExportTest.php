<?php

use App\Models\Achievement;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('achievement export returns an xlsx file honoring the category filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Achievement::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'category' => 'hafalan']);

    $response = $this->actingAsStaff($admin)->get(route('achievements.export', ['category' => 'hafalan']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
