<?php

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('admin can create leave request', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($admin)->post(route('leave-requests.store'), [
        'student_id' => $student->id,
        'type' => 'sakit',
        'start_date' => '2026-07-20',
        'end_date' => '2026-07-21',
        'reason' => 'Demam tinggi',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('leave_requests', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'type' => 'sakit',
        'status' => 'pending',
    ]);
});

test('approving leave request populates attendances for date range and notifies guardian', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id, 'telegram_chat_id' => '999888']);
    $student->guardians()->attach($guardian->id);

    $leaveRequest = LeaveRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'type' => 'izin',
        'start_date' => '2026-07-22',
        'end_date' => '2026-07-23',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->put(route('leave-requests.review', $leaveRequest), [
        'status' => 'approved',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('leave_requests', [
        'id' => $leaveRequest->id,
        'status' => 'approved',
    ]);

    $this->assertDatabaseHas('attendances', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'date' => '2026-07-22',
        'status' => 'izin',
    ]);

    $this->assertDatabaseHas('attendances', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'date' => '2026-07-23',
        'status' => 'izin',
    ]);

    Queue::assertPushed(SendTelegramMessage::class);
});

test('rejecting leave request does not create attendance records', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $leaveRequest = LeaveRequest::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'type' => 'sakit',
        'start_date' => '2026-07-22',
        'end_date' => '2026-07-22',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->put(route('leave-requests.review', $leaveRequest), [
        'status' => 'rejected',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('leave_requests', [
        'id' => $leaveRequest->id,
        'status' => 'rejected',
    ]);

    $this->assertDatabaseMissing('attendances', [
        'student_id' => $student->id,
        'date' => '2026-07-22',
    ]);
});
