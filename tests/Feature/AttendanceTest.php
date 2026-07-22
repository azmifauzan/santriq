<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

test('first scan records checked_in_at', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'action' => 'check_in',
        ]);

    $this->assertDatabaseHas('attendances', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'date' => now()->format('Y-m-d'),
        'status' => 'hadir',
    ]);
});

test('second scan within deduplication window is ignored', function () {
    Carbon::setTestNow('2026-07-22 08:00:00');

    $tenant = Tenant::factory()->create(['settings' => ['dedup_minutes' => 5]]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    // First scan at 08:00
    $this->actingAs($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ])->assertOk();

    // Second scan at 08:02 (2 minutes later < 5 minutes)
    Carbon::setTestNow('2026-07-22 08:02:00');
    $response = $this->actingAs($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'action' => 'deduplicated',
        ]);

    $attendance = Attendance::where('student_id', $student->id)->first();
    expect($attendance->checked_out_at)->toBeNull();

    Carbon::setTestNow();
});

test('second scan after deduplication window records checked_out_at', function () {
    Carbon::setTestNow('2026-07-22 08:00:00');

    $tenant = Tenant::factory()->create(['settings' => ['dedup_minutes' => 5]]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    // First scan at 08:00
    $this->actingAs($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    // Second scan at 08:10 (10 minutes later > 5 minutes)
    Carbon::setTestNow('2026-07-22 08:10:00');
    $response = $this->actingAs($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'action' => 'check_out',
        ]);

    $attendance = Attendance::where('student_id', $student->id)->first();
    expect($attendance->checked_out_at)->not->toBeNull();

    Carbon::setTestNow();
});

test('scan with invalid qr_token returns 404', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAs($admin)->postJson(route('attendance.scan'), [
        'qr_token' => 'invalid-token-123',
    ]);

    $response->assertNotFound();
});

test('admin can update attendance status', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $attendance = Attendance::factory()->create(['tenant_id' => $tenant->id, 'status' => 'hadir']);

    $response = $this->actingAs($admin)->put(route('attendance.update', $attendance), [
        'status' => 'izin',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'status' => 'izin',
    ]);
});
