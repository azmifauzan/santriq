# Desain: Landing Page & Login Wali per Subdomain Lembaga

Status: disetujui, siap diturunkan jadi rencana implementasi.

## 1. Ringkasan

Tiap lembaga yang mendaftar di SantriQ mendapat subdomain sendiri (`{subdomain}.santriq.web.id`), dipilih permanen saat registrasi. Subdomain itu menghosting:

1. Landing page publik lembaga — konten bisa diubah admin lembaga.
2. Portal login wali santri — tanpa password, lewat magic-link Telegram, sesi persisten.
3. Seluruh aplikasi staf yang sudah ada (login, dashboard, absensi, dst) — pindah dari domain utama ke subdomain lembaga.

Domain utama (`santriq.web.id`) jadi murni halaman marketing platform + form registrasi lembaga baru.

## 2. Perubahan Keputusan Arsitektur

Dua keputusan di `docs/RENCANA-IMPLEMENTASI.md` § 1 berubah:

| Topik | Keputusan lama | Keputusan baru | Alasan |
|---|---|---|---|
| Identitas wali | Bukan user aplikasi; hanya Telegram + tautan bertanda tangan sekali pakai | Tetap tanpa password, tapi jadi "app user" ringan lewat guard `guardian` terpisah — login via magic-link Telegram, sesi persisten (cookie) | Wali perlu portal web yang bisa dibuka berulang tanpa balik ke bot tiap kali; tetap tanpa beban dukungan password |
| Lingkup subdomain | (belum ada) | Seluruh app (marketing tetap di domain utama; landing lembaga + login staf + dashboard + portal wali pindah ke `{subdomain}.santriq.web.id`) | Branding konsisten per lembaga, dipilih eksplisit oleh user saat brainstorming |

Dokumen `RENCANA-IMPLEMENTASI.md` § 1 dan § 2 (model data) harus diperbarui saat implementasi mendarat.

## 3. Data Model

- **Rename** `tenants.slug` → `tenants.subdomain`. Kolom ini sebelumnya auto-generated dan tidak dipakai di luar internal (`CreateNewUser`, seeder, factory) — aman di-rename tanpa migrasi data tambahan (nilai lama seperti `tpq-demo` sudah valid sebagai subdomain).
  - Format: `^[a-z0-9-]{3,63}$`, unique, permanen (tidak bisa diubah setelah registrasi).
  - Daftar kata terlarang (reserved): `www`, `api`, `admin`, `app`, `mail`, `webhook`, `assets`, `static`, dll — daftar final ditentukan saat implementasi.
- **Tidak ada migrasi kolom baru** untuk konten landing — disimpan di `tenants.settings->landing` (kolom `settings` JSON sudah ada):
  ```
  landing: {
    tagline: string|null,
    description: string|null,      // plain text (textarea), bukan rich text di V1
    logo_path: string|null,        // disk "public"
    accent_color: string|null,     // hex, dipakai header/tombol landing
    operating_hours: string|null,  // teks bebas, mis. "Senin-Jumat 15.00-17.00"
    gallery: string[],             // maks 6 path foto, disk "public"
  }
  ```
- Statistik publik (jumlah santri aktif, pengajar, kelas) **dihitung live** dari DB saat landing di-load, bukan field tersimpan.
- `Guardian` model: implement `Illuminate\Contracts\Auth\Authenticatable` (pakai trait `Illuminate\Auth\Authenticatable`) supaya bisa dipakai provider guard `guardian`. Tidak ada kolom password baru.

## 4. Resolusi Tenant dari Subdomain

- `config('app.tenant_domain')` (dari env `APP_TENANT_DOMAIN`, mis. `santriq.web.id`).
- Middleware baru `ResolveTenantFromDomain`:
  - Ambil subdomain dari host request.
  - `Tenant::where('subdomain', $sub)->firstOrFail()` — 404 kalau tidak ada.
  - Bind ke container / helper `currentTenant()`.
  - Panggil `URL::defaults(['subdomain' => $sub])` supaya `route()`/Wayfinder tidak perlu inject subdomain manual.
- **Global scope tenant** (`BelongsToTenant`) tetap berbasis `Auth::user()->tenant_id` (guard `web`) — tidak diganti berbasis subdomain, supaya sesi staf yang valid tidak otomatis dianggap benar hanya karena berada di subdomain yang salah.
  - Middleware tambahan pada grup rute staf: kalau `currentTenant()->id !== Auth::user()->tenant_id` → logout paksa + redirect ke `/login` subdomain tsb. Mencegah sesi staf lembaga A "menempel" saat membuka subdomain lembaga B.
- Rute publik (landing) dan portal wali tidak punya `Auth::user()` guard `web` → semua query di-scope manual pakai `currentTenant()->id`, pola yang sama dengan `TelegramWebhookController` saat ini.
- Sesi: `SESSION_DOMAIN` tetap `null` — cookie otomatis ter-scope per-host, sesi lembaga A tidak terbaca di lembaga B tanpa konfigurasi tambahan.
- Dev lokal: `{subdomain}.localhost:8000` — browser modern resolve `*.localhost` ke loopback tanpa edit `/etc/hosts`.

## 5. Routing

- `routes/web.php` (domain utama, tanpa constraint domain): `GET /` (marketing, `Welcome.vue` — tidak berubah), `GET/POST /register`.
- `routes/tenant.php` (baru), dibungkus `Route::domain('{subdomain}.'.config('app.tenant_domain'))` + middleware `ResolveTenantFromDomain`:
  - Semua rute staf yang sekarang ada di `routes/web.php` dan `routes/settings.php` (dashboard, students, guardians, attendance, achievements, reports, invoices, leave-requests, settings) — dipindah ke sini apa adanya.
  - Landing publik: `GET /`.
  - Login wali & portal (lihat § 6).

## 6. Registrasi

- Form daftar (`CreateNewUser` action) tambah field `subdomain`:
  - Validasi format + unique + tidak masuk reserved list.
  - Endpoint cek ketersediaan real-time (throttled), dipanggil dari form saat user mengetik.
- Alur: submit → buat `tenant` (dengan `subdomain`) + user admin pertama, seperti sekarang.
- Override Fortify `RegisterResponse`: redirect ke `https://{subdomain}.{tenant_domain}/login?registered=1`, bukan auto-masuk dashboard — sesi yang terbentuk di domain utama saat register tidak terpakai di subdomain (beda host), jadi tidak perlu dipertahankan.

## 7. Landing Page & Pengelolaan Konten

- `GET /` di subdomain → Inertia `Tenant/Landing.vue` (publik, tanpa auth): tampilkan profil (nama, logo, tagline, deskripsi, alamat, telepon, jam operasional), galeri foto, statistik live, warna aksen, CTA ke `/login` (staf) dan `/wali/masuk` (wali).
- Halaman pengelolaan: `settings/lembaga` (pola sama `ProfileController`/`SecurityController`), khusus role `admin` (Policy, `pengajar` ditolak):
  - Form request validasi teks + upload (logo maks 1, galeri maks 6, mime/ukuran dibatasi) → simpan ke `tenants.settings->landing` + file ke disk `public`.

## 8. Login & Portal Wali

- Guard baru `guardian` (session, provider eloquent → model `Guardian`).
- Rute di subdomain:
  - `GET/POST /wali/masuk` — wali input nomor HP → cari `Guardian` di-scope `currentTenant()`.
    - Kalau `telegram_chat_id` kosong → pesan "tautkan akun Telegram dulu" (pakai `link_token` yang sudah ada).
    - Kalau ada → `URL::temporarySignedRoute` (kadaluarsa 15 menit) dikirim via job `SendTelegramMessage` yang sudah ada.
    - Throttle ketat per-IP dan per-nomor (cegah enumerasi/spam).
  - `GET /wali/masuk/verifikasi/{guardian}` (signed) — validasi signature + `currentTenant()->id === $guardian->tenant_id` → `Auth::guard('guardian')->login($guardian, remember: true)` → redirect `/wali/portal`.
  - Grup `middleware(['auth:guardian'])`:
    - `GET /wali/portal` — daftar anak + ringkasan kehadiran/prestasi terbaru.
    - `GET /wali/portal/anak/{student}` — detail laporan kehadiran & prestasi; otorisasi lewat pivot `guardian_student` (bukan `TenantExists`, karena ini kepemilikan wali↔anak, bukan sekadar tenant).
    - `GET+POST /wali/portal/izin` — lihat & ajukan izin (reuse logic dari command `/izin` bot Telegram; ekstrak jadi service bersama kalau perlu).
    - `POST /wali/keluar` — logout guard `guardian`.

## 9. Keamanan

- Subdomain: format ketat + reserved list + unique, dicek di form request registrasi.
- Semua query wali/landing di-scope manual via `currentTenant()->id` (tidak ada global scope aktif tanpa `Auth::user()` guard `web`).
- FK baru dari request (kalau ada) pakai `App\Rules\TenantExists::in()`, konsisten dengan konvensi yang sudah dipatenkan di `docs/RENCANA-IMPLEMENTASI.md`.
- Upload logo/galeri: validasi mime + ukuran maksimum.
- Signed link magic-link: kadaluarsa 15 menit, tidak ada kolom `consumed_at` (YAGNI — sudah dibatasi waktu, dan hanya terkirim lewat chat Telegram pribadi wali). Bisa ditambah revoke-on-use nanti kalau ada kebutuhan konkret.
- Sesi staf dipaksa logout kalau `currentTenant()` tidak cocok `Auth::user()->tenant_id` (lihat § 4).

## 10. Testing (Pest, feature test)

- Subdomain tidak dikenal → 404.
- Kata terlarang / format invalid ditolak saat registrasi; subdomain duplikat ditolak.
- Registrasi berhasil → redirect ke `/login` subdomain baru.
- Staf dengan sesi valid lembaga A dipaksa logout saat mengakses subdomain lembaga B.
- Landing page hanya menampilkan profil/statistik/galeri milik tenant yang sesuai subdomain.
- Request magic-link ditolak kalau wali belum tertaut Telegram atau bukan milik tenant subdomain tsb.
- Verifikasi magic-link: signature kadaluarsa/dipalsu ditolak; signature valid → sesi guard `guardian` terbentuk.
- Portal wali hanya menampilkan anak miliknya sendiri (403 untuk anak wali lain / lembaga lain).
- `settings/lembaga` hanya bisa diakses & diubah oleh role `admin` (403 untuk `pengajar`).

## 11. Rollout

- Wildcard DNS `*.santriq.web.id` + wildcard TLS — masuk ke item "deploy produksi" yang sudah ada di backlog Fase 7 (`RENCANA-IMPLEMENTASI.md` § 0).
- Env baru: `APP_TENANT_DOMAIN`.
- Migrasi rename `tenants.slug` → `tenants.subdomain` (data seed demo `tpq-demo` tetap valid tanpa perubahan nilai).
- Update `TenantFactory`, `DatabaseSeeder`, dan test yang mereferensikan `slug`.

## 12. Eksplisit di Luar Scope

- Custom domain milik lembaga sendiri (di luar `*.santriq.web.id`).
- Ganti subdomain setelah registrasi (permanen sesuai keputusan).
- Rich text/WYSIWYG untuk deskripsi landing (plain text dulu di V1).
- Console super-admin platform (kolom `users.tenant_id` nullable untuk ini baru sebatas catatan skema, belum ada fitur — tidak terkait pekerjaan ini).
- 2FA untuk wali (tidak relevan, tidak ada password).
