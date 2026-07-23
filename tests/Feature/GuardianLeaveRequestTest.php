<?php

use App\Models\Guardian;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;

test('guardian can submit a leave request for their own child', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-izin']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $child = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian->students()->attach($child->id, ['relation' => 'Ayah']);

    $this->actingAs($guardian, 'guardian')
        ->post("http://{$tenant->subdomain}.santriq.test/wali/portal/izin", [
            'student_id' => $child->id,
            'type' => 'sakit',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
            'reason' => 'Demam',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('leave_requests', [
        'student_id' => $child->id,
        'type' => 'sakit',
        'status' => 'pending',
    ]);
});

test('guardian cannot submit a leave request for a child that is not theirs', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-izin2']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $notMyChild = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($guardian, 'guardian')
        ->post("http://{$tenant->subdomain}.santriq.test/wali/portal/izin", [
            'student_id' => $notMyChild->id,
            'type' => 'sakit',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-02',
        ])
        ->assertForbidden();

    expect(LeaveRequest::withoutGlobalScopes()->count())->toBe(0);
});
