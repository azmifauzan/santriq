# Rencana Implementasi SantriQ

Dokumen kerja untuk mengubah [SantriQ-PRD.md](SantriQ-PRD.md) menjadi urutan pengerjaan, sekaligus catatan status pengerjaannya.

Domain produksi: **santriq.web.id**

## 0. Status (diverifikasi 26 Juli 2026)

| Fase                     | Status                                                                         |
| ------------------------ | ------------------------------------------------------------------------------ |
| 0 — Fondasi multi-tenant | Selesai                                                                        |
| 1 — Master data santri   | Selesai (termasuk impor & ekspor Excel santri)                                 |
| 2 — Absensi QR           | Selesai                                                                        |
| 3 — Integrasi Telegram   | Selesai                                                                        |
| 4 — Pencapaian & laporan | Selesai                                                                        |
| 5 — SPP                  | Selesai                                                                        |
| 6 — Perizinan mandiri    | Selesai                                                                        |
| 7 — Rilis                | Sebagian: landing page & portal wali per subdomain, deploy produksi (santriq.web.id, wildcard TLS), dan webhook Telegram produksi selesai; tersisa backup terjadwal |

Verifikasi: `composer ci:check` hijau (ESLint, Prettier, vue-tsc, Pint, PHPStan level 7, Pest). `php artisan migrate:fresh --seed` berjalan bersih dan menghasilkan 1 lembaga demo, 2 akun, 2 kelas, 10 santri — semuanya punya `qr_token`, semua wali punya `link_token`.
Fitur landed pada 23 Juli 2026: Subdomain per-lembaga (`{subdomain}.santriq.web.id`), landing page publik lembaga, setting profil landing page admin, magic-link Telegram login wali tanpa password dengan guard `guardian`, portal wali (status kehadiran, prestasi, pengajuan izin). Rencana detail di `docs/2026-07-23-landing-wali-login-design.md` & `docs/superpowers/plans/2026-07-23-landing-wali-login.md`.

Deploy produksi (23 Juli 2026): favicon diganti agar serupa mark landing page (emerald + graduation cap, ganti favicon.ico/svg/apple-touch-icon.png default Laravel), dan halaman error 403/404/419/429/500/503 kini dirender lewat `Inertia::handleExceptionsUsing` (`ErrorPage.vue`) alih-alih halaman default Laravel — aktif di semua environment kecuali `local`/`testing`. Prosedur redeploy lengkap di `docs/DEPLOY.md`.

Tenant demo publik (23 Juli 2026): subdomain `demo` (`App\Support\DemoTenant`) berisi data contoh yang bisa dijajal tanpa registrasi. Halaman masuk staf menampilkan kredensial demo (`admin@santriq.test` / `pengajar@santriq.test`, lihat `FortifyServiceProvider::loginView`) hanya saat `DemoTenant::isActive()`; portal wali punya tombol "Masuk sebagai wali demo" (`GuardianAuthController::loginDemo`) yang login otomatis ke wali contoh pertama tanpa lewat alur Telegram. Perintah `php artisan demo:reset` menghapus lalu menabur ulang santri/kelas/wali tenant ini lewat `DemoDataSeeder`, dijadwalkan tiap jam lewat `Schedule::command(ResetDemoTenant::class)->hourly()` di `routes/console.php` — dieksekusi produksi oleh proses `schedule:work` di `docker/supervisord.conf` (lihat § Fase 7 dan `docs/DEPLOY.md`).

Onboarding admin pertama kali (24 Juli 2026): admin yang baru registrasi (manual maupun Google) diarahkan ke wizard dua langkah (`Onboarding.vue`) sebelum menyentuh dashboard — isi info lembaga (alamat, telepon) dan konten landing page (tagline, deskripsi, jam operasional, warna aksen, logo, galeri), atau lewati. Gate memakai kolom `users.onboarded_at` (nullable timestamp, bukan flag sesi) lewat middleware baru `EnsureOnboardingComplete` yang dipasang setelah `auth`+`verified` di `routes/tenant.php`; `onboarded_at` diisi otomatis untuk pengajar yang dibuatkan admin (`TeacherController::store`, mereka tidak pernah melihat wizard ini) dan untuk backfill user lama. Detail: `docs/superpowers/specs/2026-07-24-onboarding-design.md`.

Panel Super Admin (24 Juli 2026): kapabilitas lintas-tenant lewat kolom `users.is_super_admin` (boolean, terpisah dari `role` — super admin tetap admin/pengajar biasa di lembaganya sendiri). `SuperAdminController` (`/super-admin` di domain utama, digerbang `TenantPolicy` lewat `Gate::authorize`, pola sama `TeacherController`) menampilkan daftar seluruh lembaga dengan jumlah santri/pengajar/wali, detail per lembaga, dan tombol suspend/aktifkan (`tenants.suspended_at`, nullable timestamp). Lembaga yang disuspend langsung 403 di seluruh rute subdomainnya — ditegakkan sekali di `ResolveTenantFromDomain` (middleware global), bukan per-controller. Sidebar tenant menampilkan tautan "Panel Super Admin" (lintas domain, `<a>` biasa bukan Inertia `<Link>`) hanya untuk user dengan `is_super_admin=true`, lewat prop `superAdminUrl` yang dibagikan `HandleInertiaRequests`. Belum ada UI untuk memberi/mencabut status super admin — sengaja manual lewat `tinker` (lihat `docs/superpowers/specs/2026-07-24-super-admin-design.md`), supaya kapabilitas sensitif ini tidak self-serve tanpa audit trail.

Login & registrasi dengan Google (23 Juli 2026, disempurnakan 24 Juli 2026): tombol "Masuk/Daftar dengan Google" di atas form `auth/Login.vue`/`auth/Register.vue`, lewat `laravel/socialite`. `GoogleAuthController` (`auth/google/redirect`, `auth/google/callback`) selalu ada di domain utama karena `GOOGLE_REDIRECT_URI` tetap (satu redirect URI terdaftar di Google Cloud Console, tidak bisa per-subdomain) — konteks tenant/intent dibawa lewat `state` yang ditandatangani (`App\Support\GoogleOAuthToken`), bukan session, karena `SESSION_DOMAIN` kosong (cookie tidak lintas subdomain↔domain utama). Registrasi via Google tetap mengharuskan mengisi `institution_name`/`subdomain` (institusi baru butuh itu, Google cuma memberi identitas) — `CreateNewUser` menerima `google_token` sebagai pengganti password dan **selalu memakai email dari token, bukan dari form** (mencegah token Google milik penyerang dipasangkan dengan email korban); nama tetap bisa diedit user karena hanya label tampilan, bukan klaim identitas. Login via Google mencocokkan berdasarkan email (unik global) + `tenant_id` cocok dengan subdomain saat ini; auto-link `google_id` ke akun password yang sudah ada hanya jika Google melaporkan `email_verified=true`. Kalau subdomain kosong (login dari domain utama) atau lembaga tidak ditemukan, `handleLogin` mencoba mencocokkan `google_id` lintas tenant dulu (login pusat) sebelum jatuh ke `handleRegister` — form registrasi tampil dengan email/nama sudah terisi dari identitas Google. **Prasyarat operasional**: redirect URI produksi (`https://santriq.web.id/auth/google/callback`) harus terdaftar di Google Cloud Console sebelum fitur ini jalan di domain produksi — kalau belum, Google akan menolak dengan `redirect_uri_mismatch`.

Verifikasi email fungsional (24 Juli 2026): `App\Models\User` kini `implements MustVerifyEmail` (method-nya sudah ada dari trait bawaan `Illuminate\Foundation\Auth\User`, tinggal kontraknya yang belum dipasang) — ini yang membuat middleware `verified` (sudah lama terpasang di `routes/tenant.php` pada rute dashboard) benar-benar mulai menegakkan aturan, dan listener `Registered => SendEmailVerificationNotification` didaftarkan manual di `FortifyServiceProvider::boot()` (tidak ada `app/Listeners` yang di-scan otomatis di `bootstrap/app.php`). Konsekuensinya ditangani di tiga tempat: (1) `App\Http\Responses\RegisterResponse` sekarang bercabang — pendaftar Google (`email_verified_at` sudah terisi oleh `CreateNewUser`) langsung masuk lewat handoff bertanda tangan `App\Support\TenantSessionHandoff` (registrasi selalu berjalan di domain utama, sementara dashboard ada di subdomain, dan `SESSION_DOMAIN` kosong — redirect biasa tiba di subdomain sebagai tamu lalu memantul ke `/login`), pendaftar manual tetap login tapi diarahkan ke `verification.notice` sampai mengklik tautan di emailnya; (2) `TeacherController::store` men-set `email_verified_at => now()` saat admin menambah pengajar, karena akun itu tidak lewat alur registrasi (tidak ada event `Registered`, tidak ada email verifikasi yang bisa diklik) — admin yang mengetik emailnya dianggap menjaminnya; (3) migrasi `backfill_email_verified_at_for_existing_users` menandai semua user lama (yang dibuat sebelum perubahan ini) sebagai terverifikasi, supaya tidak ada admin/pengajar produksi yang tiba-tiba terkunci dari dashboard saat deploy.

Presensi manual via NIS (5 Agustus 2026): halaman `/scan` kini punya tab "Token QR" / "NIS" di kartu input manual — staf bisa mencatat presensi dengan mengetik NIS santri, bukan cuma token QR. `AttendanceController::scan` menerima `qr_token` **atau** `nis` (validasi `required_without` silang, salah satu wajib); pencarian santri tetap ter-scope `tenant_id` di kedua jalur, jadi tidak ada IDOR lintas lembaga lewat NIS. Sisa logic (masuk/pulang, dedup, notifikasi Telegram) tidak berubah karena tetap keyed off `$student` yang sama. Rencana: `docs/superpowers/plans/2026-08-05-manual-attendance-nis.md`.

Kustomisasi kartu cetak & fix sidebar (5 Agustus 2026): klik "Cetak Kartu QR" di halaman Data Santri sekarang navigasi Inertia di tab yang sama (`router.visit`, bukan `window.open`) supaya sidebar tetap menyorot "Data Santri" — perbaikan `NavMain.vue` dari exact-match (`isCurrentUrl`) ke prefix-match (`isCurrentOrParentUrl`) yang sudah ada di `useCurrentUrl` tapi belum dipakai di sana. Halaman cetak diperlebar (`max-w-6xl` → `max-w-[1600px]`) dan kolom layar bertambah (`lg:grid-cols-4 xl:grid-cols-5`) — jumlah kolom kertas saat print tidak berubah (default 2) kecuali diatur admin, dua hal ini sengaja dipisah. Tampilan kartu kini dikustomisasi lewat halaman baru Settings → Cetak Kartu Santri (`/settings/cetak-kartu`, admin-only): jumlah kolom cetak (2/3/4), warna aksen, toggle field NIS/kelas/jenis kelamin, dan toggle logo lembaga (memakai ulang logo yang sudah diunggah di Settings → Lembaga, tidak ada upload baru). Disimpan di `tenants.settings['card_print']` (namespace baru, pola sama `settings['landing']`) lewat `App\Support\CardPrintSettings::resolve()` + `App\Concerns\UpdatesTenantCardPrintSettings`. Checkbox Reka UI (`<Checkbox name="...">`) tidak mengirim field saat tidak dicentang seperti checkbox native, jadi validasi 4 field boolean di `UpdateCardPrintSettingsRequest` pakai `sometimes|boolean` (bukan `required`) — `$request->boolean()` sudah default `false` untuk key yang tidak ada. Desain & rencana: `docs/superpowers/specs/2026-08-05-kartu-santri-print-customization-design.md`, `docs/superpowers/plans/2026-08-05-kartu-santri-print-customization.md`.

Temuan yang sudah diperbaiki saat verifikasi:

- `bacon/bacon-qr-code` dipakai langsung oleh `QrCodeService` tetapi hanya ikut sebagai dependensi transitif Fortify — sekarang dideklarasikan eksplisit di `composer.json`.
- `SendTelegramMessage` membuat baris `telegram_messages` baru pada setiap percobaan ulang, dan `failed()` menandai gagal semua baris `pending` milik wali tersebut. Sekarang baris outbox dibuat sekali saat dispatch dan dipakai ulang oleh seluruh retry.
- Webhook Telegram bersifat publik dan dikecualikan dari CSRF, tetapi lolos begitu saja bila `TELEGRAM_SECRET_TOKEN` kosong — bot bisa dipakai mengirim pesan ke chat id sembarangan. Sekarang permintaan ditolak di semua environment kecuali `testing` bila secret kosong (termasuk `local`, karena Telegram hanya bisa menjangkau webhook lewat tunnel publik), perbandingan memakai `hash_equals`, dan endpoint dibatasi `throttle:120,1`.
- **IDOR lintas lembaga**: aturan `exists:students,id` dan sejenisnya bertanya langsung ke tabel sehingga melewati global scope tenant. Admin lembaga A bisa mengirim id milik lembaga B pada `students.store` (`classroom_id`, `guardian_ids`), `guardians.store` (`student_ids`), `achievements.store`, `invoices.store`, dan `leave-requests.store` — pengajuan izin yang disetujui bahkan menulis baris `attendances` untuk santri lembaga lain. Semua aturan tersebut kini memakai `App\Rules\TenantExists::in()`.
- `ReportController` tidak memanggil otorisasi sama sekali (datanya tetap ter-scope tenant, tapi tanpa gerbang policy); ditambahkan `Gate::authorize('viewAny', Attendance::class)` pada `index` dan `exportCsv`.
- `TELEGRAM_BOT_TOKEN` dan `TELEGRAM_SECRET_TOKEN` belum ada di `.env.example`.
- Perizinan mandiri baru tersedia dari sisi staf; perintah `/izin` untuk wali santri di bot ditambahkan.
- `DatabaseSeeder` masih bawaan starter kit; diganti seeder lembaga demo. Catatan: seeder sengaja tidak memakai `WithoutModelEvents` karena `qr_token` dan `link_token` dibuat di hook `creating`.
- Review lanjutan atas fitur subdomain (23 Juli 2026): redirect setelah daftar/masuk sebelumnya membangun URL subdomain dengan string manual — sekarang lewat `route()` supaya konsisten dengan mode fallback baru. `CurrentTenant::get()` melempar `RuntimeException` mentah (500) kalau tenant belum ter-resolve, misalnya saat rute wali diakses lewat domain utama tanpa subdomain — sekarang `abort(404)`. Ditambah mode fallback path (`{domain}/{subdomain}/...`) untuk dipakai sebelum wildcard DNS aktif, teruji di `tests/PathFallback/`.

Export/Import Excel di panel admin (25 Juli 2026): export xlsx (`maatwebsite/excel`) tersedia di semua 8 halaman list admin, import di 4 entity master data (Students, Teachers, Classrooms, Guardians). Detail: `docs/superpowers/specs/2026-07-25-excel-export-import-design.md`. Build stage Docker sempat gagal karena `phpoffice/phpspreadsheet` platform-check `ext-gd`/`ext-zip` pada image `php:8.5-cli` yang dipakai hanya untuk `composer install` (vendor/ di-copy ke stage app yang extension-nya lengkap) — diperbaiki dengan `--ignore-platform-reqs` khusus di stage tsb (lihat `Dockerfile`).

Ikon Excel asli (SVG inline, `resources/js/components/icons/ExcelIcon.vue`) ditambahkan di tombol Export/Import Excel semua 8 panel (26 Juli 2026), menggantikan teks polos. Footer sidebar panel admin (`AppSidebar.vue`) menambahkan kredit "Managed by SatsetOps" bertaut ke `https://satsetops.com`.

Flaky test ditemukan & diperbaiki saat scan menyeluruh (26 Juli 2026): 2 test di `tests/PathFallback/TenantPathFallbackTest` gagal hanya saat suite penuh dijalankan (bukan saat file itu dijalankan sendiri), sudah ada sejak sebelum perubahan hari ini (direproduksi juga di commit `fde5ffe`, jadi bukan regresi fitur Excel). Akar masalah: `PathFallbackTestCase` mencoba mem-flip `APP_TENANT_SUBDOMAIN_ACTIVE` lewat `putenv()`/`$_ENV`/`$_SERVER` sebelum boot, tapi `Illuminate\Support\Env::$repository` adalah singleton proses — `ImmutableWriter`-nya cuma melindungi sebuah key pada load PERTAMA di proses tsb; begitu test lain sudah boot duluan (menandai key itu "sudah dimuat"), setiap `safeLoad()` berikutnya (yaitu setiap boot test berikutnya) menimpanya balik ke nilai `.env` tanpa peduli override kita. Diperbaiki dengan pindah dari env-flipping ke `$app->booting()` (hook yang jalan setelah config termuat tapi sebelum provider/route ter-boot, pola yang sama dipakai `Illuminate\Foundation\Testing\TestCase` sendiri untuk `WithCachedRoutes`) yang langsung men-set `config(['tenancy.subdomain_active' => false])` pada instance app test tsb — reliabel terlepas dari urutan/riwayat boot proses.

Catatan lain: 2FA punya scaffolding (`TwoFactorAuthenticationRequest`, `RequirePassword` pada halaman keamanan) tetapi belum diaktifkan di `config/fortify.php` dan belum ada UI-nya. Akibatnya 4 test di `tests/Feature/Settings/SecurityTest.php` otomatis di-skip (`skipUnlessFortifyHas`) — inilah 4 skip yang muncul saat `composer test`.

## 1. Keputusan Arsitektur

Keputusan diambil sekali di depan supaya tidak diperdebatkan ulang tiap fase.

| Topik              | Keputusan                                                                                                         | Alasan                                                                                            |
| ------------------ | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Multi-tenancy      | Satu database, kolom `tenant_id` di setiap tabel milik lembaga + global scope Eloquent                            | Tanpa dependensi baru; cukup untuk skala TPA/TPQ. Database-per-tenant hanya jika terbukti perlu   |
| Peran pengguna     | Kolom `role` (enum: `admin`, `pengajar`) pada `users` + Policy Laravel                                            | Paket permission penuh belum dibutuhkan untuk dua peran                                           |
| Identitas wali     | Wali **bukan** user aplikasi dengan password, tapi punya guard `guardian` sendiri — login lewat magic-link Telegram, sesi persisten | Portal bisa dibuka berulang tanpa kembali ke bot tiap kali, tetap tanpa beban dukungan password |
| Lingkup subdomain | Setiap lembaga punya `{subdomain}.santriq.web.id`: landing publik, login staf, dashboard, dan portal wali semuanya di subdomain; domain utama murni marketing + registrasi | Branding konsisten per lembaga, dipilih eksplisit saat registrasi |
| Fallback tanpa wildcard DNS | `config('tenancy.subdomain_active')` (`APP_TENANT_SUBDOMAIN_ACTIVE`) memilih SATU bentuk rute saat boot: `{subdomain}.santriq.web.id/...` (default) atau `santriq.web.id/{subdomain}/...`. Rute `login`/`register`/reset password tetap di domain utama di kedua mode — `LoginResponse` mengarahkan ke dashboard lewat tenant milik user yang login, bukan lewat tenant hasil resolusi request | Wildcard DNS + TLS untuk `*.santriq.web.id` belum tersedia (lihat § 0); lembaga tetap bisa jalan dengan satu A record di domain utama sampai itu beres. Ganti mode butuh restart app + `npm run build` (Wayfinder membakukan bentuk rute ke JS saat build) |
| Login staf via Google | `laravel/socialite`, redirect/callback selalu di domain utama (redirect URI Google tetap, tidak bisa per-subdomain); state ditandatangani (`GoogleOAuthToken`) membawa intent+subdomain lintas domain karena session tidak ikut | `SESSION_DOMAIN` kosong → cookie tidak lintas subdomain↔domain utama; Socialite `stateless()` + state sendiri menghindari itu tanpa mengubah scope cookie aplikasi |
| Payload QR         | ULID acak per santri disimpan di kolom `qr_token` (unik), bukan ID berurutan                                      | Tidak bisa ditebak, bisa dicabut per santri tanpa mengubah ID                                     |
| Pemindai QR        | `BarcodeDetector` API bawaan browser, fallback pesan "gunakan Chrome/Android" bila tidak tersedia                 | Tanpa library JS tambahan; wajib HTTPS untuk akses kamera                                         |
| Generate gambar QR | `bacon/bacon-qr-code` (SVG, dirender di server oleh `QrCodeService`), dideklarasikan eksplisit di `composer.json` | Tidak ada cara wajar membuat QR tanpa library; jangan bergantung pada instalasi transitif Fortify |
| Telegram           | HTTP Client Laravel langsung ke Bot API, dibungkus queued job                                                     | SDK pihak ketiga tidak diperlukan untuk beberapa endpoint                                         |
| Retry notifikasi   | Queue `database` + `$tries`/`backoff` pada job, log status kirim di tabel `telegram_messages`                     | Memenuhi syarat retry di PRD tanpa infrastruktur tambahan                                         |
| Timezone           | Disimpan UTC, ditampilkan sesuai `tenants.timezone`                                                               | Lembaga tersebar di WIB/WITA/WIT                                                                  |
| Tema antarmuka     | Terang/gelap memakai class `dark`; default `prefers-color-scheme`, pilihan disimpan di browser                    | Mengikuti sistem tanpa dependensi atau pengaturan akun tambahan                                   |
| Super admin        | Kolom `users.is_super_admin` (boolean), terpisah dari `role` per-tenant — bukan `tenant_id` nullable            | Super admin tetap admin/pengajar biasa di lembaganya sendiri, plus kapabilitas lintas-tenant; menghindari kasus khusus "user tanpa tenant" di kode yang sudah asumsi `tenant_id` selalu ada |
| Suspend lembaga    | `tenants.suspended_at` (nullable timestamp), ditegakkan sekali di `ResolveTenantFromDomain`                       | Satu titik penegakan untuk seluruh rute subdomain (staf, wali, landing) tanpa flag per-controller |
| Onboarding admin   | Gate via `users.onboarded_at` (nullable timestamp, persisten), bukan flag sesi                                   | Bertahan lintas sesi/perangkat; admin yang belum lengkapi profil lembaga tetap diarahkan ulang lain kali login |
| Export/Import Excel | `maatwebsite/excel` (wrapper Laravel di atas PhpSpreadsheet)                                                    | Tidak ada cara wajar generate/parse xlsx tanpa library; format Excel eksplisit diminta pengguna, bukan CSV |

## 2. Model Data

Semua tabel lembaga memakai `tenant_id` + index. Nama tabel Inggris, label UI Bahasa Indonesia.

```
tenants          id, name, subdomain, address, phone, timezone, settings(json), suspended_at
```
Catatan: `settings.landing` menyimpan konten landing page per-lembaga (`tagline`, `description`, `logo_path`, `accent_color`, `operating_hours`, `gallery`).
users            + tenant_id, role, is_super_admin, onboarded_at
classrooms       tenant_id, name, level
students         tenant_id, classroom_id, nis, name, gender, birth_date, qr_token, status
guardians        tenant_id, name, phone, telegram_chat_id, link_token, linked_at
guardian_student guardian_id, student_id, relation
attendances      tenant_id, student_id, date, checked_in_at, checked_out_at,
                 status(hadir|izin|sakit|alpa), recorded_by
achievements     tenant_id, student_id, category, title, note, score, achieved_at, recorded_by
invoices         tenant_id, student_id, period, amount, due_date, status(unpaid|paid|void)
payments         invoice_id, amount, method, paid_at, verified_by, note
leave_requests   tenant_id, student_id, type(sakit|izin), start_date, end_date, reason,
                 status(pending|approved|rejected), reviewed_by, reviewed_at
telegram_messages tenant_id, guardian_id, payload, status, attempts, sent_at, error
```

Constraint penting:

- `students.qr_token` unik global.
- `attendances` unik pada (`student_id`, `date`) — satu baris per santri per hari.
- `invoices` unik pada (`student_id`, `period`).
- Foreign key `tenant_id` on delete cascade.

Semua constraint di atas sudah ada di migrasi, ditambah unik (`tenant_id`, `nis`) pada `students` dan unik (`guardian_id`, `student_id`) pada pivot.

## 3. Fase Pengerjaan

Tiap fase menghasilkan sesuatu yang bisa dipakai, dan ditutup dengan `composer ci:check` hijau. Status ringkas ada di bagian 0.

### Fase 0 — Fondasi Multi-Tenant — selesai

1. Migrasi `tenants`, tambah `tenant_id` + `role` pada `users`.
2. Trait `BelongsToTenant`: global scope filter `tenant_id`, auto-isi saat create.
3. Alur registrasi: pendaftar pertama membuat lembaga sekaligus jadi admin (sesuaikan `CreateNewUser`).
4. Halaman undang pengajar (admin menambah user pada lembaganya).
5. Policy dasar `admin` vs `pengajar`, dan navigasi sidebar per peran.
6. Factory + seeder demo satu lembaga.

**Test:** user lembaga A tidak bisa membaca/mengubah data lembaga B; pengajar ditolak pada aksi khusus admin.

### Fase 1 — Master Data Santri — selesai

1. CRUD `classrooms`.
2. CRUD `students` (validasi NIS unik per lembaga), generate `qr_token` saat create.
3. CRUD `guardians` + relasi ke santri.
4. Export Excel (xlsx, via `maatwebsite/excel`) di semua 8 halaman list admin (Students, Teachers, Classrooms, Guardians, Invoices, Achievements, Attendance, LeaveRequests), menghormati filter querystring aktif. Import Excel hanya untuk 4 entity master data (Students, Teachers, Classrooms, Guardians), dengan template, validasi per baris, skip + laporkan baris invalid/duplikat via `Inertia::flash`. Import mencocokkan kelas ke `classroom_id` lewat nama (tidak auto-create); penautan wali santri ke santri tetap manual lewat modal edit. Detail: `docs/superpowers/specs/2026-07-25-excel-export-import-design.md`.
5. Halaman cetak kartu QR: layout siap print (CSS `@media print`), beberapa kartu per halaman. Tampilan (jumlah kolom saat cetak, warna aksen, field yang ditampilkan, logo lembaga) bisa dikustomisasi admin lewat Settings → Cetak Kartu Santri (`tenants.settings['card_print']`), lihat § 0 (5 Agustus 2026).

**Test:** create santri menghasilkan `qr_token` unik; halaman cetak menampilkan santri terpilih saja.

### Fase 2 — Absensi QR — selesai

1. Halaman `/scan`: akses kamera, `BarcodeDetector`, umpan balik suara/visual berhasil-gagal.
2. Endpoint `POST /attendance/scan` (auth, throttle) menerima `qr_token` atau `nis` (salah satu wajib), menentukan aksi masuk/pulang.
3. Aturan dedup: scan ulang dalam ≤ N menit (setting lembaga, default 5) diabaikan dan direspons "sudah tercatat".
4. Daftar kehadiran harian + koreksi manual oleh admin.

**Test:** scan pertama isi `checked_in_at`; scan kedua di luar jeda isi `checked_out_at`; scan dalam jeda tidak membuat baris baru; token tidak dikenal ditolak.

### Fase 3 — Integrasi Telegram — selesai

1. Config `services.telegram`, bot token lewat env.
2. Penautan wali: sistem membuat `link_token`, wali kirim `/start <token>` ke bot, webhook menyimpan `telegram_chat_id`.
3. Webhook `POST /telegram/webhook` diverifikasi dengan `secret_token` header dari Telegram.
4. Job `SendTelegramMessage` (queued, `$tries = 3`, backoff naik) + pencatatan di `telegram_messages`.
5. Event kehadiran memicu notifikasi masuk/pulang.
6. Perintah bot: `/kehadiran`, `/prestasi`, `/tagihan` menampilkan data anak dari wali tersebut.

**Test:** absensi mengantre satu job per wali; webhook tanpa secret ditolak; token tidak dikenal tidak menautkan apa pun.

### Fase 4 — Pencapaian & Laporan — selesai

1. CRUD `achievements` oleh pengajar (form: kategori, judul, catatan, nilai, tanggal).
2. Riwayat pencapaian per santri.
3. Rekap kehadiran per santri/periode dan rekap lembaga.
4. Rekap pencapaian per santri/periode.
5. Ekspor CSV rekap.

**Test:** rekap menghitung hadir/izin/sakit/alpa dengan benar untuk rentang tanggal.

### Fase 5 — SPP — selesai

1. CRUD `invoices`, penerbitan massal per kelas/periode.
2. Pencatatan `payments` + verifikasi admin, status tagihan mengikuti.
3. Notifikasi Telegram saat tagihan terbit dan saat pembayaran terverifikasi.
4. Riwayat tagihan untuk wali via bot/portal.

**Test:** verifikasi pembayaran mengubah status jadi `paid` dan mengirim satu notifikasi; tagihan ganda pada periode sama ditolak.

### Fase 6 — Perizinan Mandiri — selesai

1. Pengajuan izin dari wali lewat bot: `/izin [nis] <sakit|izin> <mulai> <selesai> [alasan]`. NIS hanya diperlukan bila satu wali punya lebih dari satu santri.
2. Antrean peninjauan untuk admin/pengajar: setujui/tolak.
3. Persetujuan menulis `attendances` berstatus `izin`/`sakit` pada rentang tanggal terkait.
4. Notifikasi hasil peninjauan ke wali.

**Test:** izin disetujui membuat baris kehadiran per hari dalam rentang; izin ditolak tidak mengubah kehadiran; perintah bot dengan tanggal salah tidak membuat pengajuan; wali dengan dua anak diminta menyebutkan NIS.

### Fase 7 — Rilis — sebagian

1. ~~Rate limit endpoint publik~~ — sudah: `throttle:60,1` pada scan, `throttle:120,1` pada webhook, `throttle:6,1` pada ganti password.
2. ~~Landing page publik, tampilan login/registrasi, dan tema terang/gelap responsif~~ — sudah.
3. Backup database terjadwal + retensi — belum, lihat § Backup di `docs/DEPLOY.md`.
4. ~~Deploy ke santriq.web.id~~ — sudah (23 Juli 2026): image `azmifauzan/santriq` (PHP 8.5-apache, multi-stage build) lewat Docker Compose di server produksi, wildcard TLS `*.santriq.web.id` (certbot DNS-01 via Cloudflare), queue worker `database` dan scheduler (`schedule:work`, dipakai untuk reset tenant demo tiap jam) jalan sebagai daemon lewat `supervisord`. Detail & prosedur redeploy di `docs/DEPLOY.md`.
5. ~~Set webhook Telegram ke domain produksi~~ — sudah, `TELEGRAM_SECRET_TOKEN` terpasang.
6. ~~Seeder demo + dokumentasi self-hosting di README~~ — sudah. Tenant demo publik (subdomain `demo`, reset otomatis tiap jam) ditambahkan 23 Juli 2026, lihat § 0.
7. ~~Onboarding admin pertama kali~~ — sudah (24 Juli 2026), lihat § 0.
8. ~~Panel super admin (lintas-tenant: list lembaga, suspend/aktifkan)~~ — sudah (24 Juli 2026), lihat § 0. Provisioning super admin pertama masih manual lewat `tinker`, sengaja belum ada UI.
9. ~~Impor & Ekspor Excel santri~~ — sudah, diperluas ke semua 8 panel admin (25 Juli 2026): export di semua 8, impor di 4 entity master data. Lihat § 0.

## 4. Konvensi Pengerjaan

- Setiap perubahan disertai test Pest; jalankan `php artisan test --compact --filter=...` untuk iterasi cepat.
- Route frontend memakai fungsi Wayfinder (`@/routes`, `@/actions`), bukan URL hardcode.
- Validasi di Form Request, otorisasi di Policy — bukan di controller.
- Query lintas tenant hanya lewat scope; jangan pernah `withoutGlobalScopes()` di jalur permintaan pengguna biasa. Webhook Telegram berjalan tanpa sesi login sehingga scope tidak aktif — di sana `tenant_id` diambil dari data santri/wali yang bersangkutan, bukan dari user. Satu-satunya jalur permintaan pengguna yang sengaja memakai `withoutGlobalScopes()` adalah `SuperAdminController` (dijaga `TenantPolicy`, lihat § 0) — global scope `BelongsToTenant` mengikuti `tenant_id` user yang login, dan super admin tetap terikat ke lembaganya sendiri, jadi tanpa ini hitungan santri/wali di panel lintas-lembaga akan salah (ter-filter ke lembaganya sendiri).
- Jangan menambah dependensi tanpa persetujuan (kecuali library QR yang sudah dicatat di atas).

## 5. Yang Sengaja Ditunda

| Ditunda                                                                                                             | Tambahkan bila                                                             |
| ------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- |
| Database per tenant                                                                                                 | Ada lembaga dengan kebutuhan isolasi/regulasi khusus                       |
| Paket roles & permissions                                                                                           | Peran bertambah melewati admin/pengajar                                    |
| Payment gateway                                                                                                     | Lembaga meminta pembayaran otomatis, bukan verifikasi manual               |
| Aplikasi mobile / PWA offline                                                                                       | Koneksi di lokasi terbukti jadi penghambat absensi                         |
| Kanal notifikasi selain Telegram (WA)                                                                               | Data menunjukkan banyak wali tidak memakai Telegram                        |
| SSR Inertia                                                                                                         | SEO halaman publik jadi kebutuhan nyata                                    |
| 2FA (scaffolding Fortify sudah ada, `Features::twoFactorAuthentication()` belum diaktifkan di `config/fortify.php`) | Akun admin lembaga memegang data banyak santri dan butuh proteksi tambahan |
| UI untuk memberi/mencabut status super admin (`users.is_super_admin`)                                              | Lebih dari satu orang perlu mengelola lembaga di platform dan butuh audit trail siapa memberi akses ke siapa |
| Impersonate/login-as lembaga dari panel super admin                                                                | Dukungan/debugging lintas lembaga jadi kebutuhan rutin, bukan sesekali |
