<?php

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\TelegramMessage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('telegram webhook links guardian upon valid /start command', function () {
    $tenant = Tenant::factory()->create();
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'link_token' => 'VALID_LINK_TOKEN_123',
        'telegram_chat_id' => null,
    ]);

    $response = $this->postJson(route('telegram.webhook'), [
        'message' => [
            'text' => '/start VALID_LINK_TOKEN_123',
            'chat' => [
                'id' => 987654321,
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJson(['status' => 'linked']);

    $guardian->refresh();
    expect($guardian->telegram_chat_id)->toBe('987654321');
    expect($guardian->linked_at)->not->toBeNull();
});

test('telegram webhook rejects request when secret token does not match', function () {
    config(['services.telegram.secret_token' => 'my-secret']);

    $response = $this->postJson(route('telegram.webhook'), [
        'message' => [
            'text' => '/start TOKEN',
            'chat' => ['id' => 123],
        ],
    ], [
        'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
    ]);

    $response->assertForbidden();
});

test('attendance scan dispatches SendTelegramMessage job when guardian is linked', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '12345678',
    ]);

    $student->guardians()->attach($guardian->id, ['relation' => 'Ayah']);

    $this->actingAsStaff($admin)->postJson(route('attendance.scan'), [
        'qr_token' => $student->qr_token,
    ])->assertOk();

    Queue::assertPushed(SendTelegramMessage::class, function ($job) use ($guardian) {
        return $job->guardian->id === $guardian->id;
    });
});

test('telegram webhook is rejected outside local when no secret is configured', function () {
    config(['services.telegram.secret_token' => null]);
    $this->app->detectEnvironment(fn () => 'production');

    $this->postJson(route('telegram.webhook'), [
        'message' => ['text' => '/start TOKEN', 'chat' => ['id' => 123]],
    ])->assertForbidden();
});

test('guardian submits a leave request through the telegram bot', function () {
    $tenant = Tenant::factory()->create();
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '555000',
    ]);
    $student->guardians()->attach($guardian->id, ['relation' => 'Ibu']);

    $this->postJson(route('telegram.webhook'), [
        'message' => [
            'text' => '/izin sakit 2026-07-23 2026-07-24 demam tinggi',
            'chat' => ['id' => 555000],
        ],
    ])->assertOk()->assertJson(['status' => 'leave_created']);

    $leaveRequest = LeaveRequest::withoutGlobalScopes()->firstOrFail();

    expect($leaveRequest->student_id)->toBe($student->id);
    expect($leaveRequest->tenant_id)->toBe($tenant->id);
    expect($leaveRequest->type)->toBe('sakit');
    expect($leaveRequest->status)->toBe('pending');
    expect($leaveRequest->reason)->toBe('demam tinggi');
    expect($leaveRequest->start_date->format('Y-m-d'))->toBe('2026-07-23');
    expect($leaveRequest->end_date->format('Y-m-d'))->toBe('2026-07-24');
});

test('telegram leave request rejects a malformed date and creates nothing', function () {
    $tenant = Tenant::factory()->create();
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '555001',
    ]);
    $student->guardians()->attach($guardian->id, ['relation' => 'Ibu']);

    $this->postJson(route('telegram.webhook'), [
        'message' => [
            'text' => '/izin sakit kemarin besok',
            'chat' => ['id' => 555001],
        ],
    ])->assertOk()->assertJson(['status' => 'leave_invalid_date']);

    expect(LeaveRequest::withoutGlobalScopes()->count())->toBe(0);
});

test('telegram leave request asks for a NIS when the guardian has several children', function () {
    $tenant = Tenant::factory()->create();
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '555002',
    ]);

    $students = Student::factory()->count(2)->create(['tenant_id' => $tenant->id]);
    foreach ($students as $student) {
        $student->guardians()->attach($guardian->id, ['relation' => 'Ayah']);
    }

    $this->postJson(route('telegram.webhook'), [
        'message' => [
            'text' => '/izin sakit 2026-07-23 2026-07-24 demam',
            'chat' => ['id' => 555002],
        ],
    ])->assertOk()->assertJson(['status' => 'leave_needs_nis']);

    expect(LeaveRequest::withoutGlobalScopes()->count())->toBe(0);

    $target = $students->last();

    $this->postJson(route('telegram.webhook'), [
        'message' => [
            'text' => "/izin {$target->nis} izin 2026-07-23 2026-07-23 acara keluarga",
            'chat' => ['id' => 555002],
        ],
    ])->assertOk()->assertJson(['status' => 'leave_created']);

    expect(LeaveRequest::withoutGlobalScopes()->sole()->student_id)->toBe($target->id);
});

test('telegram job keeps a single outbox row across retries', function () {
    config(['services.telegram.bot_token' => 'test-token']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    $tenant = Tenant::factory()->create();
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '12345678',
    ]);

    $job = new SendTelegramMessage($guardian, 'Halo wali santri');
    $job->handle();
    $job->handle(); // a retry re-runs the same job instance payload

    expect(TelegramMessage::where('guardian_id', $guardian->id)->count())->toBe(1);
    expect($job->log->fresh()->status)->toBe('sent');
    expect($job->log->fresh()->sent_at)->not->toBeNull();
});

test('telegram job records the failure on the outbox row when the api rejects', function () {
    config(['services.telegram.bot_token' => 'test-token']);
    Http::fake(['api.telegram.org/*' => Http::response('chat not found', 400)]);

    $tenant = Tenant::factory()->create();
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '12345678',
    ]);

    $job = new SendTelegramMessage($guardian, 'Halo wali santri');

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);

    $log = $job->log->fresh();
    expect(TelegramMessage::where('guardian_id', $guardian->id)->count())->toBe(1);
    expect($log->status)->toBe('failed');
    expect($log->error)->toContain('chat not found');
});
