# Desain: Notifikasi Telegram ke Super Admin

Status: disetujui, siap diturunkan jadi rencana implementasi.

## 1. Ringkasan

Kirim notifikasi Telegram ke super admin untuk event penting lintas-tenant, mulai dari: (1) tenant baru + admin pertama mendaftar, dan (2) pengiriman pesan Telegram ke wali santri gagal permanen setelah retry habis (ops alert). Mekanisme terpisah dari alur Telegram wali santri yang sudah ada (`SendTelegramMessage`/`telegram_messages`), karena tabel itu di-scope ke `guardian_id` (NOT NULL FK) dan tidak cocok untuk alert operasional lintas-tenant.

## 2. Cakupan v1

- Job baru `SendSuperAdminTelegramAlert` — kirim pesan bebas ke satu chat Telegram (grup/personal) yang berisi semua super admin, dikonfigurasi lewat env, bukan per-user.
- Trigger 1: registrasi tenant baru (`CreateNewUser::create`).
- Trigger 2: kegagalan permanen pengiriman Telegram ke wali (`SendTelegramMessage::failed()`).
- Di luar cakupan v1: linking chat_id per-super-admin (kolom `telegram_chat_id` di `users`), notifikasi event lain (suspend/aktivasi tenant, pembayaran SPP, dll — bisa ditambah belakangan, bukan bagian request ini), retry/outbox log ke database untuk alert ini (cukup default retry queue + Laravel failed-job log).

## 3. Config

`config/services.php`, blok `telegram` yang sudah ada ditambah satu key:

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'secret_token' => env('TELEGRAM_SECRET_TOKEN'),
    'super_admin_chat_id' => env('TELEGRAM_SUPER_ADMIN_CHAT_ID'),
],
```

Bot yang sama dipakai untuk alert ke super admin dan pesan ke wali — cukup chat_id tujuan yang beda.

## 4. Job `App\Jobs\SendSuperAdminTelegramAlert`

Pola ikuti `SendTelegramMessage` (tries, backoff) tapi tanpa outbox row:

```php
class SendSuperAdminTelegramAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

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

Tidak ada `TelegramMessage` row: alert ini tidak terikat tenant/guardian manapun, dan kegagalan cukup terlihat lewat mekanisme failed-job bawaan Laravel (tabel `failed_jobs`) — tidak butuh outbox terpisah untuk kasus ini.

Kalau config kosong (default di `local`/`testing` tanpa `.env` diisi), job selesai tanpa efek — tidak boleh melempar exception supaya tidak retry/gagal berisik di lingkungan yang memang belum setup Telegram.

## 5. Trigger 1 — Registrasi Tenant Baru

`app/Actions/Fortify/CreateNewUser.php`, method `create()`: dispatch alert setelah `DB::transaction` commit (bukan di dalam closure), supaya tidak terkirim kalau transaksi rollback (mis. race unique subdomain).

```php
$user = DB::transaction(function () use ($input, $google) {
    // ...tetap sama seperti sekarang...
});

SendSuperAdminTelegramAlert::dispatch(
    "🆕 Tenant baru terdaftar: {$user->tenant->name} ({$user->tenant->subdomain})\nAdmin: {$user->name} <{$user->email}>"
);

return $user;
```

## 6. Trigger 2 — Kegagalan Permanen Kirim Telegram ke Wali

`app/Jobs/SendTelegramMessage.php`, method `failed()` (dipanggil sekali setelah `$tries` habis, bukan di setiap percobaan — supaya tidak spam alert per retry):

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

Cabang lain di `handle()` yang return lebih awal tanpa `throw` (guardian belum link chat_id, bot token kosong) **tidak** memicu `failed()` dan **tidak** memicu alert ini — itu kondisi normal/konfigurasi, bukan kegagalan pengiriman, dan berpotensi terlalu sering terjadi untuk layak jadi alert per kejadian.

## 7. Testing

- `tests/Feature/Auth/RegistrationTest.php` (atau file registrasi yang relevan): `Queue::fake()` + assert `SendSuperAdminTelegramAlert` di-dispatch dengan pesan yang mengandung nama tenant & email admin, setelah registrasi tenant baru berhasil.
- `tests/Feature/SendTelegramMessageJobTest.php` (atau tempat test job wali yang sudah ada): panggil `failed()` langsung / paksa retry habis lewat `Http::fake` respons gagal berulang, assert `SendSuperAdminTelegramAlert` di-dispatch.
- Test baru untuk job itu sendiri: `Http::fake` sukses → job selesai tanpa exception; config `super_admin_chat_id`/`bot_token` kosong → job selesai tanpa exception dan tanpa memanggil `Http::post` (assert `Http::fake` tidak menerima request).

## 8. Yang Sengaja Tidak Dibangun (v1)

- Linking chat_id per-super-admin individual — satu chat_id bersama (grup) sudah cukup untuk jumlah super admin saat ini, dan menghindari alur linking baru mirip guardian.
- Event lain (suspend/aktivasi tenant, SPP masuk, dsb.) — bisa ditambah sebagai trigger baru ke job yang sama nanti, tanpa perubahan desain.
- Outbox/log database untuk alert super admin — cukup `failed_jobs` bawaan Laravel.
