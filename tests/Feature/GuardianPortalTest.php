<?php

use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;

test('guardian sees only their own children on the portal dashboard', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-portal']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $ownChild = Student::factory()->create(['tenant_id' => $tenant->id]);
    $otherChild = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian->students()->attach($ownChild->id, ['relation' => 'Ayah']);

    $this->actingAs($guardian, 'guardian')
        ->get("http://{$tenant->subdomain}.santriq.test/wali/portal")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/Portal')
            ->has('students', 1)
            ->where('students.0.id', $ownChild->id)
        );
});

test('guardian cannot open another guardians child detail page', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-portal2']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $otherGuardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $otherChild = Student::factory()->create(['tenant_id' => $tenant->id]);
    $otherGuardian->students()->attach($otherChild->id, ['relation' => 'Ibu']);

    $this->actingAs($guardian, 'guardian')
        ->get("http://{$tenant->subdomain}.santriq.test/wali/portal/anak/{$otherChild->id}")
        ->assertForbidden();
});

test('guardian sees attendance and achievements for their own child', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-portal3']);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id]);
    $child = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian->students()->attach($child->id, ['relation' => 'Ayah']);

    Attendance::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $child->id,
        'date' => now()->format('Y-m-d'),
        'status' => 'hadir',
    ]);

    $this->actingAs($guardian, 'guardian')
        ->get("http://{$tenant->subdomain}.santriq.test/wali/portal/anak/{$child->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('guardian/StudentDetail')
            ->has('attendances', 1)
        );
});
