# Pindah Domain Tenant ke santriq.my.id — Rencana Implementasi

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Tujuan:** Setiap lembaga dilayani dari `{subdomain}.santriq.my.id`. `santriq.web.id` hanya melayani apex: landing, registrasi, login, verifikasi email, Google OAuth, panel super admin, dan webhook Telegram. Setelah cutover, tidak ada lagi subdomain lembaga di bawah `santriq.web.id`, termasuk redirect.

**Alasan:** GSC kembali menandai `santriq.web.id` dengan "Deceptive pages" (Safe Browsing merah per 4 Nov 2026, tanpa sample URL). Audit 5 Nov tidak menemukan konten berbahaya: 30 tenant bersih, storage publik kosong, link eksternal bersih, tidak ada open redirect. Ini sudah flag kedua. Selama siapa pun bisa mendaftar dan langsung punya halaman di bawah `*.santriq.web.id`, satu tenant saja bisa mencemari domain utama. Tenant dipindah ke registrable domain terpisah, dan wildcard `*.santriq.web.id` dihapus total. Redirect sengaja tidak dipertahankan, karena Safe Browsing juga menilai rantai redirect: subdomain `.web.id` yang memantul ke halaman bermasalah tetap mengaitkan `santriq.web.id`.

**Arsitektur:** Config `tenancy.domain` dipecah. `tenancy.domain` tetap jadi domain apex. Config baru `tenancy.tenant_domain` menjadi root subdomain lembaga dan defaultnya mengikuti `tenancy.domain`, jadi lokal dan test tidak berubah. Handoff signed-link yang sudah ada (`App\Support\TenantSessionHandoff`) tetap dipakai, karena `SESSION_DOMAIN` tidak bisa lintas registrable domain. Semua URL tenant dibentuk lewat `route()`/Wayfinder, sehingga otomatis memakai domain baru.

**Tech stack:** Laravel 13, Wayfinder, Pest 4, nginx (`jonasal/nginx-certbot`), Cloudflare DNS, certbot DNS-01.

## Keputusan final

| # | Keputusan | Alasan |
|---|---|---|
| 1 | **Pindah total, tanpa redirect.** Record `A *.santriq.web.id` dihapus saat cutover. Subdomain lama berhenti resolve (NXDOMAIN). | Tidak ada jalur apa pun di bawah `santriq.web.id` yang bisa mengantar ke konten tenant. |
| 2 | `www.santriq.web.id` mendapat record eksplisit `A` (proxied) dan di nginx di-301 ke `https://santriq.web.id`. | Selama ini `www` hanya hidup lewat wildcard (ada traffic referrer `http://www.santriq.web.id`). Ini apex, bukan tenant, jadi aman. |
| 3 | Apex `santriq.my.id` di-301 ke `https://santriq.web.id`. | Pengunjung yang mengetik domain tenant tanpa subdomain diarahkan ke landing. |
| 4 | User yang sedang login di subdomain lama harus login ulang. **Tidak diumumkan.** | Login di `santriq.web.id` langsung melakukan handoff ke subdomain baru. Dampaknya kecil (30 lembaga). |
| 5 | Link bertanda tangan yang terbit sebelum cutover dibiarkan kedaluwarsa. | Umurnya 5 menit (handoff), 15 menit (magic link wali), dan pendek (impersonasi super admin). |
| 6 | Tenant `santren` ("SIMSANTREN") ikut dipindah tanpa tindakan khusus. | Tidak ada bukti konten berbahaya. |
| 7 | Cert `santriq.web.id` yang ada (apex + wildcard) dibiarkan apa adanya. | Cert ini tetap valid untuk apex. Renewal DNS-01 tetap jalan karena token di server mencakup zona `santriq.web.id`. Tanpa record wildcard, SAN wildcard-nya tidak melayani apa pun. |
| 8 | Token Cloudflare di server (`nginx_secrets/cloudflare.ini`) tidak perlu diganti. | Hash-nya identik dengan `CLOUDFLARE_TOKEN` baru di `.env` lokal, dan token itu bisa mengakses zona `santriq.my.id` dan `santriq.web.id`. |

## Fakta yang sudah dicek (5 Nov 2026)

- **Zona `santriq.my.id`** aktif di Cloudflare (zone id `bdf40d132c14cb92e5d4b3843644ac07`). Record yang ada baru `A santriq.my.id → 43.129.52.206` (proxied).
- **Zona `santriq.web.id`** (zone id `4b30695459fba2d20bbc58ec456cc42d`) berisi:
  - `A *.santriq.web.id → 43.129.52.206` dns-only, id record diawali `d8e008d3`. **Dihapus.**
  - `A santriq.web.id` proxied. Tetap.
  - Record Brevo (DKIM, DMARC, TXT) dan `google-site-verification`. Tetap, tidak disentuh.
- **Pemakaian `tenancy.domain` di kode:**
  - `routes/web.php:14`: grup route apex. Tetap.
  - `routes/web.php:61`: redirect lokal 127.0.0.1. Tetap.
  - `routes/tenant.php:151`: `Route::domain('{subdomain?}.'.config('tenancy.domain'))`. **Ganti ke `tenant_domain`.**
  - `routes/tenant.php:155`: mode path-fallback di apex. Tetap.
  - `app/Http/Middleware/ResolveTenantFromDomain.php:54`: suffix host. **Ganti ke `tenant_domain`.**
  - `app/Support/DemoTenant.php:31`: URL tenant demo. **Ganti ke `tenant_domain`** untuk mode subdomain.

  Tidak ada tempat lain di `app/` yang merangkai URL tenant secara manual.
- **Teks domain di frontend:** hanya `resources/js/pages/auth/Register.vue:116` (`.santriq.web.id`). `SiteFooter.vue:43` (`utm_source=santriq.web.id`) adalah link apex, tetap.
- **Link di pesan Telegram:** hanya magic link wali (`GuardianAuthController`, lewat `URL::temporarySignedRoute`), jadi otomatis ikut domain baru. Notifikasi kehadiran, SPP, dan izin tidak berisi URL.
- **Link super admin** (`superAdminUrl`, `ownDashboardUrl` di `HandleInertiaRequests`): lewat `route()`, otomatis.
- **`Dockerfile`** menjalankan `npm run build` tanpa `.env`, dan Wayfinder membakukan domain route dari default config. Domain wajib di-set lewat `ARG/ENV` sebelum `npm run build`.
- **HTTP port 80** ditangani global oleh `/etc/nginx/conf.d/redirector.conf` (`listen 80 default_server`) di container nginx. Tidak perlu block port 80 per domain.
- **Payload QR kartu santri** berupa ULID (`students.qr_token`), bukan URL. Kartu yang sudah dicetak tidak terdampak.
- **Test yang menyentuh domain/tenant:** `ResolveTenantFromDomainTest`, `MultiTenancyTest`, `TenantSubdomainRegistrationTest`, `StaffTenantSubdomainGuardTest`, `Auth/RegistrationTest`, `Auth/AuthenticationTest`, `Auth/GoogleAuthenticationTest`, `Auth/EmailVerificationTest`, `GuardianMagicLinkTest`, `DemoLoginHintTest`, `SuperAdminTest`, `TenantLandingTest`, dan lainnya. Semua harus tetap hijau tanpa env baru.

## Global Constraints

- Jangan mengubah dependensi.
- `tenant_domain` defaultnya `tenancy.domain`. Lokal (`localhost`) dan seluruh test yang ada harus tetap hijau tanpa env baru.
- Setiap perubahan PHP lolos `vendor/bin/pint --dirty --format agent`, phpstan level 7, dan Pest (`composer test`).
- Urutan infra: DNS baru → cert baru → deploy app → nginx → hapus wildcard `.web.id` paling akhir.
- Server bersama aplikasi lain: cukup `nginx -s reload`, jangan recreate container nginx.
- Commit dan push hanya setelah user meminta.

---

### Task 1: Config `tenancy.tenant_domain`

**Files:** `config/tenancy.php`, `.env.example`

- [x] Tambah key:
  ```php
  'tenant_domain' => env('APP_TENANT_ROOT_DOMAIN') ?: env('APP_TENANT_DOMAIN', 'santriq.web.id'),
  ```
  Docblock-nya menjelaskan bahwa root subdomain lembaga sengaja dibuat registrable domain terpisah dari apex (isolasi reputasi Safe Browsing).
- [x] Docblock `'domain'`: domain apex (landing, auth, super admin, webhook).
- [x] `.env.example`: tambah `APP_TENANT_ROOT_DOMAIN=` dengan komentar "kosong = sama dengan APP_TENANT_DOMAIN".

### Task 2: Pakai `tenant_domain` di jalur resolusi tenant

**Files:** `routes/tenant.php`, `app/Http/Middleware/ResolveTenantFromDomain.php`, `app/Support/DemoTenant.php`, `app/Support/TenantSessionHandoff.php` (docblock)

- [x] `routes/tenant.php:151` → `Route::domain('{subdomain?}.'.config('tenancy.tenant_domain'))`.
- [x] `ResolveTenantFromDomain::subdomainFromHost` memakai suffix dari `tenancy.tenant_domain`.
- [x] `DemoTenant::url()`: cabang subdomain memakai `tenancy.tenant_domain`. Cabang path tetap memakai `tenancy.domain`.
- [x] Docblock `TenantSessionHandoff`: apex `santriq.web.id`, dashboard di `{subdomain}.santriq.my.id`.

### Task 3: Frontend registrasi

**Files:** `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.ts` (atau file type shared props yang dipakai), `resources/js/pages/auth/Register.vue`

- [x] Share prop `tenantDomain` => `config('tenancy.tenant_domain')`.
- [x] Tambah `tenantDomain: string` ke type shared props.
- [x] `Register.vue:116` menampilkan `.{{ page.props.tenantDomain }}`, bukan hardcode.

### Task 4: Dockerfile

**Files:** `Dockerfile`

- [x] Sebelum `RUN npm run build`, tambah:
  ```dockerfile
  ARG APP_TENANT_DOMAIN=santriq.web.id
  ARG APP_TENANT_ROOT_DOMAIN=santriq.my.id
  ENV APP_TENANT_DOMAIN=$APP_TENANT_DOMAIN APP_TENANT_ROOT_DOMAIN=$APP_TENANT_ROOT_DOMAIN
  ```
- [x] Verifikasi image: `grep -l "santriq.my.id" public/build/assets/*.js` ada hasilnya, dan route apex di aset tetap `santriq.web.id`.

### Task 5: Test

**Files:** `tests/Feature/ResolveTenantFromDomainTest.php` (tambah kasus), test baru `tests/Feature/SeparateTenantDomainTest.php` (dibuat dengan `php artisan make:test --pest SeparateTenantDomainTest`) memakai base test case yang men-set config sebelum boot, mengikuti pola `tests/Support/PathFallbackTestCase.php`.

- [x] Dengan `tenancy.domain=santriq.web.id` dan `tenancy.tenant_domain=santriq.my.id`:
  - `GET http://foo.santriq.my.id/` → landing tenant `foo`.
  - `GET http://foo.santriq.web.id/` → bukan halaman tenant (404).
  - `GET http://santriq.web.id/` → landing apex (`Welcome`).
  - `TenantSessionHandoff::link($user)` diawali `http://foo.santriq.my.id/auth/verify-session/`, dan mengikuti link itu login + render `auth/SigningIn`.
  - `DemoTenant::url('/login')` → `http://demo.santriq.my.id/login`.
  - Prop `tenantDomain` di halaman register = `santriq.my.id`.
- [x] Jalankan `composer test`. Semua test lama dan baru harus hijau.

### Task 6: Dokumentasi

**Files:** `docs/DEPLOY.md`, `CLAUDE.md` dan `AGENTS.md` (harus identik), `docs/RENCANA-IMPLEMENTASI.md`

- [x] `DEPLOY.md`:
  - Domain tenant `*.santriq.my.id` (wildcard DNS-only) dan apex `santriq.web.id`. Wildcard `.web.id` sudah tidak ada.
  - Env `APP_TENANT_ROOT_DOMAIN` dan `ARG` Dockerfile.
  - Cert kedua (`--cert-name santriq.my.id`) beserta perintah terbitnya.
  - Struktur server block nginx yang baru.
  - Catatan: `docker compose pull` dilewati karena `pull_policy: never`, jadi pakai `sudo docker pull <image>` dulu.
- [x] `CLAUDE.md`/`AGENTS.md` bagian "Keputusan arsitektur yang mengikat": tambah "Subdomain lembaga di registrable domain terpisah (`*.santriq.my.id`) dari apex (`santriq.web.id`) untuk isolasi reputasi Safe Browsing; jangan pernah menyajikan konten tenant atau redirect tenant di bawah apex."
- [x] `RENCANA-IMPLEMENTASI.md`: catat perubahan ini di bagian keputusan/fase rilis.

### Task 7: DNS baru

- [x] Cloudflare API zona `santriq.my.id`: buat `A *.santriq.my.id → 43.129.52.206`, **proxied: false**.
- [x] Biarkan `A santriq.my.id` proxied.
- [x] Cloudflare API zona `santriq.web.id`: buat `A www.santriq.web.id → 43.129.52.206`, **proxied: true**. Record eksplisit ini menang atas wildcard, jadi aman dibuat sebelum wildcard dihapus.
- [x] Verifikasi: `dig +short A foo.santriq.my.id` → `43.129.52.206`.

### Task 8: Sertifikat wildcard `santriq.my.id`

- [x] Terbitkan cert (token di `cloudflare.ini` sudah benar, tidak perlu diganti):
  ```bash
  sudo docker exec nginx certbot certonly \
    --dns-cloudflare --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
    --dns-cloudflare-propagation-seconds 30 \
    -d santriq.my.id -d "*.santriq.my.id" \
    --email mail2fauzan@gmail.com --agree-tos --non-interactive \
    --cert-name santriq.my.id
  ```
- [x] Verifikasi: `sudo ls /home/ubuntu/nginxconf/nginx_secrets/live/santriq.my.id/` berisi `fullchain.pem`, `privkey.pem`, `chain.pem`.

### Task 9: Deploy aplikasi

- [ ] Build image dari commit yang sudah di-review (`git archive <sha> | tar -x`), tag `YYYYMMDD-N`. Push hanya kalau build exit 0 (pola `BUILD_RC`/`PUSH_RC`).
- [ ] Server, `set -euo pipefail`:
  - Backup `.env` dan `docker-compose.yml`.
  - Tambah `APP_TENANT_ROOT_DOMAIN=santriq.my.id` ke `.env`.
  - Ganti tag dengan `sed -i "s|^\( *image: *\)azmifauzan/santriq:.*|\1azmifauzan/santriq:<TAG>|"`.
  - `sudo docker pull azmifauzan/santriq:<TAG>`, lalu `sudo docker compose up -d`, lalu `sudo docker exec santriq-app php artisan config:clear`.
- [ ] Digest yang di-pull harus sama dengan digest hasil push.
- [ ] Setelah langkah ini, tenant di `.web.id` untuk sementara 404, karena app sudah pindah sementara nginx dan DNS lama belum. Lanjutkan Task 10 segera.

### Task 10: nginx

**File:** `/home/ubuntu/nginxconf/user_conf.d/santriq.conf`. Backup dulu ke `santriq.conf.bak-domain-split`.

- [ ] Isi akhir file, dengan `location /` yang identik di dua block proxy (`proxy_pass http://santriq`, header Host/X-Real-IP/X-Forwarded-For/X-Forwarded-Proto, `proxy_buffer_size 16k`, `proxy_buffers 8 16k`, `proxy_busy_buffers_size 32k`) dan `client_max_body_size 20M`:
  1. `server_name santriq.web.id;`, cert `live/santriq.web.id`, proxy ke app (apex).
  2. `server_name www.santriq.web.id;`, cert `live/santriq.web.id` → `return 301 https://santriq.web.id$request_uri;`.
  3. `server_name *.santriq.my.id;`, cert `live/santriq.my.id`, proxy ke app (tenant).
  4. `server_name santriq.my.id;`, cert `live/santriq.my.id` → `return 301 https://santriq.web.id$request_uri;`.
- [ ] Tidak ada lagi `*.santriq.web.id` di `server_name` mana pun.
- [ ] `sudo docker exec nginx nginx -t` → `sudo docker exec nginx nginx -s reload`.

### Task 11: Hapus wildcard `santriq.web.id`

- [ ] Cloudflare API zona `santriq.web.id`: hapus record `A *.santriq.web.id` (id diawali `d8e008d3`; ambil id lengkap dari listing sebelum menghapus).
- [ ] Verifikasi: `dig +short A foo.santriq.web.id` kosong, `dig +short A www.santriq.web.id` berisi IP Cloudflare.

### Task 12: Verifikasi end-to-end

- [ ] `curl -sI https://demo.santriq.my.id/` → 200, `https://demo.santriq.my.id/login` → 200.
- [ ] `curl -sI https://santriq.web.id/` → 200.
- [ ] `curl -sI https://www.santriq.web.id/` → 301 ke `https://santriq.web.id/`.
- [ ] `curl -sI https://santriq.my.id/` → 301 ke `https://santriq.web.id/`.
- [ ] `curl -s https://santriq.web.id/register` memuat `santriq.my.id` di prop `tenantDomain`.
- [ ] Browser (Chrome):
  - Login password di `santriq.web.id/login` → halaman "signing in" → dashboard di `*.santriq.my.id`.
  - Login Google di apex → dashboard `.my.id`.
  - Logout.
  - Registrasi lembaga baru (akun test dengan domain email test, lalu hapus/suspend lewat super admin setelahnya) → onboarding di `.my.id`.
  - Portal wali `demo.santriq.my.id/wali/masuk` → login demo jalan.
  - Panel super admin `santriq.web.id/super-admin` → tautan kembali ke dashboard sendiri mengarah ke `.my.id`.
- [ ] Log scheduler menunjukkan `demo:reset` berikutnya sukses.
- [ ] `sudo docker logs santriq-app --since 10m` tanpa error 5xx baru.

### Task 13: Search Console

- [ ] Tambah domain property `santriq.my.id` di GSC. Verifikasi dengan record TXT `google-site-verification` yang dibuat lewat Cloudflare API di zona `santriq.my.id`.
- [ ] Request Review untuk `santriq.web.id` dengan teks:
  > Semua halaman yang dibuat pengguna (subdomain lembaga) sudah dipindah ke domain terpisah santriq.my.id, dan wildcard *.santriq.web.id sudah dihapus. santriq.web.id kini hanya menyajikan landing, registrasi, dan login milik kami sendiri. Audit tanggal 5 November 2026 tidak menemukan konten berbahaya.

---

## Rollback

Urutannya kebalikan dari cutover:

1. Cloudflare: buat ulang `A *.santriq.web.id → 43.129.52.206` dns-only.
2. nginx: kembalikan `santriq.conf.bak-domain-split` → `nginx -t` → `nginx -s reload`.
3. App: kembalikan tag `image:` ke versi sebelumnya dan hapus `APP_TENANT_ROOT_DOMAIN` dari `.env` → `docker compose up -d` → `config:clear`.
4. DNS `*.santriq.my.id`, `www.santriq.web.id`, dan cert `santriq.my.id` boleh dibiarkan, karena tidak mengganggu.
