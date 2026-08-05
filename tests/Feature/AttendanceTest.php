<?php

use App\Jobs\SendTelegramMessage;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

test('first scan records checked_in_at', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
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
    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ])->assertOk();

    // Second scan at 08:02 (2 minutes later < 5 minutes)
    Carbon::setTestNow('2026-07-22 08:02:00');
    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
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
    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    // Second scan at 08:10 (10 minutes later > 5 minutes)
    Carbon::setTestNow('2026-07-22 08:10:00');
    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
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

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => 'invalid-token-123',
    ]);

    $response->assertNotFound();
});

test('first scan by nis records checked_in_at', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'nis' => $student->nis,
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

test('scan with invalid nis returns 404', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'nis' => 'NIS-DOES-NOT-EXIST',
    ]);

    $response->assertNotFound();
});

test('scan without qr_token or nis returns validation error', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), []);

    $response->assertStatus(422);
});

test('scan by nis does not match a student from another tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenantA->id]);
    $studentInOtherTenant = Student::factory()->create(['tenant_id' => $tenantB->id, 'nis' => 'SHARED-NIS']);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'nis' => $studentInOtherTenant->nis,
    ]);

    $response->assertNotFound();
});

test('check-in telegram notification uses tenant timezone, not server UTC time', function () {
    Queue::fake();
    Carbon::setTestNow('2026-08-05 08:33:00'); // UTC -> 15:33 WIB

    $tenant = Tenant::factory()->create(['timezone' => 'Asia/Jakarta']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id, 'telegram_chat_id' => '12345678']);
    $student->guardians()->attach($guardian->id, ['relation' => 'Ayah']);

    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    $response->assertOk()->assertJson(['time' => '15:33']);

    Queue::assertPushed(SendTelegramMessage::class, fn ($job) => str_contains($job->messageText, '<b>15:33</b> WIB'));

    Carbon::setTestNow();
});

test('check-out telegram notification uses tenant timezone, not server UTC time', function () {
    Queue::fake();
    Carbon::setTestNow('2026-08-05 08:00:00');

    $tenant = Tenant::factory()->create(['timezone' => 'Asia/Jakarta', 'settings' => ['dedup_minutes' => 5]]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create(['tenant_id' => $tenant->id, 'telegram_chat_id' => '12345678']);
    $student->guardians()->attach($guardian->id, ['relation' => 'Ayah']);

    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ])->assertOk();

    Carbon::setTestNow('2026-08-05 08:33:00'); // 10 min later, UTC -> 15:33 WIB
    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    $response->assertOk()->assertJson(['action' => 'check_out', 'time' => '15:33']);

    Queue::assertPushed(SendTelegramMessage::class, function ($job) {
        return str_contains($job->messageText, 'PULANG') && str_contains($job->messageText, '<b>15:33</b> WIB');
    });

    Carbon::setTestNow();
});

test('deduplicated scan tells staff how long to wait before checkout is recorded', function () {
    Carbon::setTestNow('2026-07-22 08:00:00');

    $tenant = Tenant::factory()->create(['settings' => ['dedup_minutes' => 5]]);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    Carbon::setTestNow('2026-07-22 08:02:00');
    $response = $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ]);

    $response->assertOk()->assertJson(['action' => 'deduplicated']);
    expect($response->json('message'))->toContain('3 menit lagi');

    Carbon::setTestNow();
});

test('attendance date follows tenant timezone across the UTC day boundary', function () {
    // 2026-08-05 23:30 WIB = 2026-08-05 16:30 UTC. Still same WIB day.
    Carbon::setTestNow('2026-08-05 16:30:00');

    $tenant = Tenant::factory()->create(['timezone' => 'Asia/Jakarta']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ])->assertOk();

    $this->assertDatabaseHas('attendances', [
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'date' => '2026-08-05',
    ]);

    // 2026-08-06 00:30 WIB = 2026-08-05 17:30 UTC. New WIB day, but still same UTC day-1.
    Carbon::setTestNow('2026-08-05 17:30:00');

    $student2 = Student::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student2->qr_token,
    ])->assertOk();

    $this->assertDatabaseHas('attendances', [
        'tenant_id' => $tenant->id,
        'student_id' => $student2->id,
        'date' => '2026-08-06',
    ]);

    Carbon::setTestNow();
});

test('admin can update attendance status', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $attendance = Attendance::factory()->create(['tenant_id' => $tenant->id, 'status' => 'hadir']);

    $response = $this->actingAsStaff($admin)->put(route('attendance.update', $attendance), [
        'status' => 'izin',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'status' => 'izin',
    ]);
});
