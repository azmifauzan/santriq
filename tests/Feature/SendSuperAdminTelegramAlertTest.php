<?php

use App\Jobs\SendSuperAdminTelegramAlert;
use Illuminate\Support\Facades\Http;

test('sends the alert to the configured chat when bot token and chat id are set', function () {
    config([
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.super_admin_chat_id' => '999888777',
    ]);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    (new SendSuperAdminTelegramAlert('Halo super admin'))->handle();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
            && $request['chat_id'] === '999888777'
            && $request['text'] === 'Halo super admin';
    });
});

test('skips sending when bot token is missing', function () {
    config([
        'services.telegram.bot_token' => null,
        'services.telegram.super_admin_chat_id' => '999888777',
    ]);
    Http::fake();

    (new SendSuperAdminTelegramAlert('Halo super admin'))->handle();

    Http::assertNothingSent();
});

test('skips sending when super admin chat id is missing', function () {
    config([
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.super_admin_chat_id' => null,
    ]);
    Http::fake();

    (new SendSuperAdminTelegramAlert('Halo super admin'))->handle();

    Http::assertNothingSent();
});

test('throws when telegram api rejects so the queue retries', function () {
    config([
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.super_admin_chat_id' => '999888777',
    ]);
    Http::fake(['api.telegram.org/*' => Http::response('chat not found', 400)]);

    expect(fn () => (new SendSuperAdminTelegramAlert('Halo super admin'))->handle())
        ->toThrow(RuntimeException::class);
});
