# Notifikasi Telegram ke Super Admin Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Kirim notifikasi Telegram ke super admin saat tenant baru mendaftar, dan saat pengiriman Telegram ke wali santri gagal permanen setelah retry habis.

**Architecture:** Job queue baru `SendSuperAdminTelegramAlert` (tanpa outbox DB row, tidak seperti `SendTelegramMessage` yang scoped ke guardian) yang mengirim pesan bebas ke satu chat_id Telegram (grup/personal berisi semua super admin) yang dikonfigurasi lewat env. Dua titik dispatch: `CreateNewUser::create()` setelah tenant+admin dibuat, dan `SendTelegramMessage::failed()` setelah retry wali habis.

**Tech Stack:** Laravel 13 queued job (`ShouldQueue`), `Illuminate\Support\Facades\Http`, Pest 4 (`Http::fake`, `Queue::fake`).

## Global Constraints

- PHP 8.4: selalu curly braces, return type + param type hint eksplisit di semua method baru (dari CLAUDE.md proyek).
- Setelah edit PHP: jalankan `vendor/bin/pint --dirty --format agent` sebelum menganggap task selesai.
- Test: Pest, `php artisan test --compact --filter=<Nama>` per task, bukan full suite tiap task.
- Tidak ada migration/schema baru — chat_id super admin cukup satu nilai env (`TELEGRAM_SUPER_ADMIN_CHAT_ID`), bukan kolom per-user.
- Alert job **tidak boleh** menulis ke tabel `telegram_messages` (tabel itu punya `guardian_id` NOT NULL, scoped ke wali — tidak cocok untuk alert admin lintas-tenant).
- Kalau `bot_token` atau `super_admin_chat_id` kosong, job harus selesai tanpa exception (cukup `Log::warning`) — tidak boleh retry/gagal berisik di lingkungan tanpa Telegram dikonfigurasi.
- Alert kegagalan kirim ke wali hanya dipicu di `failed()` (setelah `$tries` habis), bukan di setiap percobaan `handle()` — supaya tidak spam per retry.
- Spec lengkap: `docs/superpowers/specs/2026-07-27-super-admin-telegram-alerts-design.md`.

---

### Task 1: Job `SendSuperAdminTelegramAlert` + config

**Files:**
- Modify: `config/services.php:38-41` (blok `telegram`)
- Create: `app/Jobs/SendSuperAdminTelegramAlert.php`
- Test: `tests/Feature/SendSuperAdminTelegramAlertTest.php`

**Interfaces:**
- Produces: `App\Jobs\SendSuperAdminTelegramAlert` — `ShouldQueue`, constructor `__construct(public string $messageText)`, method `handle(): void`. Dipakai Task 2 & Task 3 lewat `SendSuperAdminTelegramAlert::dispatch($message)`.
- Produces: config key `services.telegram.super_admin_chat_id` (env `TELEGRAM_SUPER_ADMIN_CHAT_ID`).

- [ ] **Step 1: Tambah config key**

Edit `config/services.php`, ubah blok `telegram` (baris 38-41) jadi:

```php
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'secret_token' => env('TELEGRAM_SECRET_TOKEN'),
        'super_admin_chat_id' => env('TELEGRAM_SUPER_ADMIN_CHAT_ID'),
    ],
```

- [ ] **Step 2: Tulis test job (gagal dulu karena job belum ada)**

Buat `tests/Feature/SendSuperAdminTelegramAlertTest.php`:

```php
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
```

- [ ] **Step 3: Jalankan test, pastikan gagal**

Run: `php artisan test --compact --filter=SendSuperAdminTelegramAlertTest`
Expected: FAIL — `Class "App\Jobs\SendSuperAdminTelegramAlert" not found`

- [ ] **Step 4: Buat job**

Buat `app/Jobs/SendSuperAdminTelegramAlert.php`:

```php
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendSuperAdminTelegramAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function __construct(public string $messageText) {}

    public function handle(): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.super_admin_chat_id');

        if (empty($botToken) || empty($chatId)) {
            Log::warning('SendSuperAdminTelegramAlert dilewati: bot_token/super_admin_chat_id belum dikonfigurasi.');

            return;
        }

        $response = Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $this->messageText,
            'parse_mode' => 'HTML',
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Telegram API error: '.$response->body());
        }
    }
}
```

- [ ] **Step 5: Jalankan test, pastikan lulus**

Run: `php artisan test --compact --filter=SendSuperAdminTelegramAlertTest`
Expected: PASS (4 test)

- [ ] **Step 6: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/services.php app/Jobs/SendSuperAdminTelegramAlert.php tests/Feature/SendSuperAdminTelegramAlertTest.php
git commit -m "feat: add SendSuperAdminTelegramAlert job"
```

---

### Task 2: Trigger saat tenant baru mendaftar

**Files:**
- Modify: `app/Actions/Fortify/CreateNewUser.php:1-79`
- Test: `tests/Feature/Auth/RegistrationTest.php`

**Interfaces:**
- Consumes: `App\Jobs\SendSuperAdminTelegramAlert::dispatch(string $messageText)` dari Task 1.

- [ ] **Step 1: Tulis test registrasi (gagal dulu)**

Tambahkan ke `tests/Feature/Auth/RegistrationTest.php`, di bagian `use` tambahkan:

```php
use App\Jobs\SendSuperAdminTelegramAlert;
use Illuminate\Support\Facades\Queue;
```

Lalu tambahkan test baru di akhir file:

```php
test('registering a new tenant dispatches a super admin telegram alert', function () {
    Queue::fake();

    $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Queue::assertPushed(SendSuperAdminTelegramAlert::class, function ($job) {
        return str_contains($job->messageText, 'TPA Nurul Huda')
            && str_contains($job->messageText, 'test@example.com');
    });
});
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --compact --filter="registering a new tenant dispatches a super admin telegram alert"`
Expected: FAIL — job tidak pernah di-dispatch

- [ ] **Step 3: Wire dispatch di `CreateNewUser`**

Tambahkan import di `app/Actions/Fortify/CreateNewUser.php` (urutan alfabetis di antara `use` yang ada, setelah `App\Concerns\ProfileValidationRules` sebelum `App\Models\Tenant`):

```php
use App\Jobs\SendSuperAdminTelegramAlert;
```

Ubah method `create()` (baris 62-79) dari:

```php
        return DB::transaction(function () use ($input, $google) {
            $tenant = Tenant::create([
                'name' => $input['institution_name'],
                'subdomain' => strtolower($input['subdomain']),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $google ? null : $input['password'],
                'google_id' => $google['sub'] ?? null,
                'email_verified_at' => $google ? now() : null,
                'role' => 'admin',
                'onboarded_at' => null,
            ]);
        });
    }
}
```

jadi:

```php
        $user = DB::transaction(function () use ($input, $google) {
            $tenant = Tenant::create([
                'name' => $input['institution_name'],
                'subdomain' => strtolower($input['subdomain']),
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $google ? null : $input['password'],
                'google_id' => $google['sub'] ?? null,
                'email_verified_at' => $google ? now() : null,
                'role' => 'admin',
                'onboarded_at' => null,
            ]);
        });

        SendSuperAdminTelegramAlert::dispatch(
            "🆕 Tenant baru terdaftar: {$user->tenant->name} ({$user->tenant->subdomain})\nAdmin: {$user->name} <{$user->email}>"
        );

        return $user;
    }
}
```

Dispatch diletakkan **setelah** `DB::transaction` selesai (bukan di dalam closure), supaya tidak terkirim kalau transaksi rollback.

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --compact --filter=RegistrationTest`
Expected: PASS (semua test di file, termasuk yang lama)

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/Fortify/CreateNewUser.php tests/Feature/Auth/RegistrationTest.php
git commit -m "feat: alert super admin via telegram on new tenant registration"
```

---

### Task 3: Trigger saat kirim Telegram ke wali gagal permanen

**Files:**
- Modify: `app/Jobs/SendTelegramMessage.php:97-103`
- Test: `tests/Feature/TelegramIntegrationTest.php`

**Interfaces:**
- Consumes: `App\Jobs\SendSuperAdminTelegramAlert::dispatch(string $messageText)` dari Task 1. Sama namespace (`App\Jobs`) dengan `SendTelegramMessage`, tidak perlu `use` statement tambahan.

- [ ] **Step 1: Tulis test (gagal dulu)**

Tambahkan ke `tests/Feature/TelegramIntegrationTest.php`, di bagian `use` tambahkan:

```php
use App\Jobs\SendSuperAdminTelegramAlert;
```

Lalu tambahkan test baru di akhir file:

```php
test('permanent telegram delivery failure dispatches a super admin alert', function () {
    Queue::fake();

    $tenant = Tenant::factory()->create();
    $guardian = Guardian::factory()->create([
        'tenant_id' => $tenant->id,
        'telegram_chat_id' => '12345678',
    ]);

    $job = new SendTelegramMessage($guardian, 'Halo wali santri');
    $job->failed(new RuntimeException('chat not found'));

    Queue::assertPushed(SendSuperAdminTelegramAlert::class, function ($alert) use ($guardian) {
        return str_contains($alert->messageText, $guardian->name)
            && str_contains($alert->messageText, 'chat not found');
    });
});
```

- [ ] **Step 2: Jalankan test, pastikan gagal**

Run: `php artisan test --compact --filter="permanent telegram delivery failure dispatches a super admin alert"`
Expected: FAIL — alert tidak pernah di-dispatch

- [ ] **Step 3: Wire dispatch di `SendTelegramMessage::failed()`**

Ubah `app/Jobs/SendTelegramMessage.php` baris 97-103 dari:

```php
    public function failed(?Throwable $exception): void
    {
        $this->log->update([
            'status' => 'failed',
            'error' => $exception?->getMessage() ?? 'Max retries reached',
        ]);
    }
```

jadi:

```php
    public function failed(?Throwable $exception): void
    {
        $error = $exception?->getMessage() ?? 'Max retries reached';

        $this->log->update([
            'status' => 'failed',
            'error' => $error,
        ]);

        SendSuperAdminTelegramAlert::dispatch(
            "⚠️ Gagal kirim Telegram ke wali {$this->guardian->name} (tenant {$this->guardian->tenant_id}) setelah retry: {$error}"
        );
    }
```

- [ ] **Step 4: Jalankan test, pastikan lulus**

Run: `php artisan test --compact --filter=TelegramIntegrationTest`
Expected: PASS (semua test di file, termasuk yang lama)

- [ ] **Step 5: Format & commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Jobs/SendTelegramMessage.php tests/Feature/TelegramIntegrationTest.php
git commit -m "feat: alert super admin when guardian telegram delivery permanently fails"
```

---

### Task 4: Full test suite check

**Files:** none (verification only)

- [ ] **Step 1: Jalankan seluruh suite**

Run: `composer test`
Expected: semua test lulus (config:clear + pint --test + phpstan + pest), tidak ada regresi di luar 3 task di atas.

- [ ] **Step 2: Kalau lulus, tidak ada commit tambahan** (task ini murni verifikasi, bukan perubahan kode).
