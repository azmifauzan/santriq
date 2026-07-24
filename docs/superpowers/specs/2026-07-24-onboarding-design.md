# Desain: Onboarding Setelah Registrasi/Login Pertama

Status: disetujui, siap diturunkan jadi rencana implementasi.

## 1. Ringkasan

Setelah registrasi (dan verifikasi email) atau login pertama kali setelah verifikasi, admin lembaga (user pertama tiap tenant) diarahkan ke halaman onboarding tersendiri — bukan langsung ke dashboard — untuk mengisi info lembaga (alamat, telepon) dan konten landing page (tagline, deskripsi, jam operasional, warna aksen, logo, galeri). Onboarding bisa dilewati (skip). Pengajar (diundang admin) tidak pernah melihat onboarding.

## 2. Cakupan

- Hanya role `admin`. Pengajar dibuat oleh admin lewat `TeacherController::store` dan langsung ditandai onboarded — mereka tidak self-register, tidak mengelola landing page.
- Gate berbasis kolom `users.onboarded_at` (nullable timestamp), bukan session flag — persisten lintas sesi/perangkat.
- Field yang diisi: `tenants.address`, `tenants.phone` (kolom sudah ada, sebelumnya tak punya form sama sekali) + field landing yang sudah ada di `tenants.settings->landing` (`tagline`, `description`, `operating_hours`, `accent_color`, `logo_path`, `gallery`).
- Semua field nullable — submit boleh kosong. Tombol "Lewati" tersedia di kedua step, langsung set `onboarded_at` tanpa menyentuh field lain.
- `address`/`phone` juga ditambahkan ke halaman `settings/lembaga` yang sudah ada (bukan cuma onboarding), supaya data ini tetap bisa diedit ulang kapan saja, bukan cuma sekali saat onboarding.

## 3. Efek Samping yang Diperbaiki (dalam scope)

Fortify default `VerifyEmailResponse` redirect ke path tetap `/dashboard` (dari `config('fortify.home')`). Rute `dashboard` cuma terdaftar di dalam domain group tenant subdomain (lihat `routes/tenant.php`), sedangkan rute verifikasi email (`verification.verify`) terdaftar apex/global (tanpa constraint domain, didaftarkan otomatis oleh Fortify). Akibatnya klik link verifikasi email saat ini redirect ke URL yang 404 di mode subdomain aktif.

Diperbaiki dengan override `VerifyEmailResponse` (pola sama seperti `LoginResponse`/`RegisterResponse` yang sudah ada), redirect ke `route('dashboard', ['subdomain' => $user->tenant->subdomain])`. Ini sekaligus jadi titik gate onboarding yang benar untuk alur registrasi manual.

## 4. Data Model

Migrasi baru:

```
users.onboarded_at: timestamp, nullable, after email_verified_at
```

- Diisi `now()` saat: (a) user submit form onboarding, (b) user klik "Lewati", (c) `TeacherController::store` membuat user pengajar baru (langsung onboarded, tidak pernah melihat halaman ini).
- `CreateNewUser` (registrasi admin, manual maupun Google) TIDAK mengisi `onboarded_at` — sengaja `null`, supaya gate onboarding aktif untuk kedua jalur registrasi.

`User` model: tambah `onboarded_at` ke cast `datetime` dan PHPDoc `@property`.

## 5. Gate: Middleware `EnsureOnboardingComplete`

Baru: `app/Http/Middleware/EnsureOnboardingComplete.php`, pola serupa `EnsureStaffTenantMatchesSubdomain`:

```php
$user = Auth::guard('web')->user();

if ($user !== null && $user->isAdmin() && $user->onboarded_at === null && ! $request->routeIs('onboarding.*')) {
    return redirect()->route('onboarding.show');
}

return $next($request);
```

Ditambahkan ke middleware array pada dua group route tenant yang sudah pakai `['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class]` di `routes/tenant.php` (group dashboard/fitur inti, dan group settings/security/lembaga). Group `settings/profile` (yang cuma `auth`, tanpa `verified`) TIDAK ditambah — profil dasar tetap bisa diakses sebelum verifikasi email, tidak relevan dengan onboarding.

Rute onboarding sendiri (`onboarding.*`) ditaruh di group `['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class]` **tanpa** `EnsureOnboardingComplete`, supaya tidak redirect loop.

`route('onboarding.show')` tanpa parameter `subdomain` eksplisit — `ResolveTenantFromDomain` (middleware global) sudah memanggil `URL::defaults(['subdomain' => ...])` untuk tiap request tenant-domain, jadi Wayfinder/route() otomatis terisi.

## 6. Backend

### 6.1 `LembagaUpdateRequest` (diperluas)

Tambah ke `rules()`:

```php
'address' => ['nullable', 'string', 'max:255'],
'phone' => ['nullable', 'string', 'max:30'],
```

`authorize()` tetap `$this->user('web')?->isAdmin()`.

### 6.2 Trait `App\Concerns\UpdatesTenantLandingSettings`

Ekstrak logika update yang sekarang ada di `LembagaController::update` jadi method reusable:

```php
trait UpdatesTenantLandingSettings
{
    private function applyLandingUpdate(LembagaUpdateRequest $request, Tenant $tenant): void
    {
        $landing = $tenant->settings['landing'] ?? [];

        foreach (['tagline', 'description', 'operating_hours', 'accent_color'] as $field) {
            if ($request->filled($field)) {
                $landing[$field] = $request->string($field)->toString();
            }
        }

        if ($request->hasFile('logo')) {
            $landing['logo_path'] = $request->file('logo')->store('tenants/'.$tenant->id.'/logo', 'public');
        }

        if ($request->hasFile('gallery')) {
            $landing['gallery'] = collect($request->file('gallery'))
                ->map(fn ($file) => $file->store('tenants/'.$tenant->id.'/gallery', 'public'))
                ->all();
        }

        $tenant->update([
            'address' => $request->filled('address') ? $request->string('address')->toString() : $tenant->address,
            'phone' => $request->filled('phone') ? $request->string('phone')->toString() : $tenant->phone,
            'settings' => [...$tenant->settings ?? [], 'landing' => $landing],
        ]);
    }
}
```

`LembagaController::update` dan `OnboardingController::update` sama-sama `use UpdatesTenantLandingSettings;` dan memanggil `$this->applyLandingUpdate($request, $tenant);`.

### 6.3 `OnboardingController` (baru)

```php
class OnboardingController extends Controller
{
    use UpdatesTenantLandingSettings;

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user?->isAdmin(), 403);

        if ($user->onboarded_at !== null) {
            return to_route('dashboard');
        }

        $tenant = CurrentTenant::get();

        return Inertia::render('Onboarding', [
            'tenant' => ['address' => $tenant->address, 'phone' => $tenant->phone],
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $tenant = CurrentTenant::get();
        $this->applyLandingUpdate($request, $tenant);
        $request->user('web')->update(['onboarded_at' => now()]);

        return to_route('dashboard')->with('success', 'Onboarding selesai.');
    }

    public function skip(Request $request): RedirectResponse
    {
        abort_unless($request->user('web')?->isAdmin(), 403);
        $request->user('web')->update(['onboarded_at' => now()]);

        return to_route('dashboard');
    }
}
```

### 6.4 Routes (`routes/tenant.php`)

Dalam group `['auth', 'verified', EnsureStaffTenantMatchesSubdomain::class]` (grup baru, terpisah dari grup dashboard supaya tidak kena `EnsureOnboardingComplete`):

```php
Route::get('onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
Route::put('onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');
Route::post('onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
```

`EnsureOnboardingComplete::class` ditambahkan ke middleware array grup dashboard-fitur-inti dan grup settings/security/lembaga.

### 6.5 `App\Http\Responses\VerifyEmailResponse` (baru)

```php
class VerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        return redirect()->intended(route('dashboard', ['subdomain' => $user->tenant->subdomain]));
    }
}
```

Dibind di `FortifyServiceProvider::register()`, pola sama seperti `LoginResponse`/`RegisterResponse`.

### 6.6 `TeacherController::store`

Tambah `'onboarded_at' => now()` saat membuat user pengajar baru, supaya pengajar tak pernah tersangkut gate onboarding.

## 7. Frontend

### 7.1 `resources/js/pages/Onboarding.vue` (baru)

- Halaman berdiri sendiri, tanpa `AppLayout` (tidak ada sidebar staf) — pola sama `Tenant/Landing.vue` untuk halaman yang bukan bagian dashboard biasa.
- `useForm()` Inertia menampung semua field sekaligus: `address, phone, tagline, description, operating_hours, accent_color, logo, gallery`. `step` ref lokal (`1 | 2`) cuma mengatur field mana yang tampil — satu form, satu submit di step akhir (state tak hilang saat pindah step).
  - **Step 1 — Info Lembaga**: `address`, `phone`. Tombol "Lanjut" (client-side, pindah `step.value = 2`, tanpa request).
  - **Step 2 — Landing Page**: `tagline`, `description`, `operating_hours`, `accent_color`, `logo`, `gallery` (field & styling reuse dari `settings/Lembaga.vue`). Tombol "Kembali" (`step.value = 1`) dan "Simpan & Selesai" (`form.put(update.url())`).
- Tombol "Lewati" tampil di kedua step → `router.post(skip.url())`, tanpa dialog konfirmasi (reversibel — field tetap bisa diisi nanti lewat Settings → Lembaga).
- Indikator progres 2 titik sederhana, tanpa dependency baru.

### 7.2 `resources/js/pages/settings/Lembaga.vue` (diperluas)

Tambah input `address` dan `phone` (pola input teks sama seperti `operating_hours`), dikirim lewat `<Form>` yang sudah ada — tidak ada perubahan struktur.

## 8. Testing (Pest, feature test)

- Admin baru (manual), belum verifikasi → akses `dashboard` → redirect `verification.notice` (perilaku lama, tidak berubah).
- Admin baru (manual) klik link verifikasi → `onboarded_at` masih null → landing di `onboarding.show` (bukan 404, bukan langsung dashboard).
- Admin baru via Google (pre-verified) → login pertama → landing di `onboarding.show`.
- `PUT onboarding` dengan data lengkap → `tenants.address/phone` + `settings->landing` tersimpan, `onboarded_at` terisi, redirect ke dashboard.
- `PUT onboarding` dengan field kosong → tidak error (semua nullable), `onboarded_at` tetap terisi.
- `POST onboarding/skip` → `onboarded_at` terisi, `tenants.address/phone`/`settings` tidak berubah, redirect dashboard.
- Admin yang sudah onboarded akses `onboarding.show` lagi → redirect dashboard (idempoten).
- Pengajar (dibuat via `TeacherController::store`) → `onboarded_at` terisi otomatis, tidak pernah lihat onboarding walau login pertama kali.
- `settings/lembaga` (existing test) tetap lulus dengan field `address`/`phone` baru.
- Pengajar mengakses `onboarding.show` langsung → 403.

## 9. Eksplisit di Luar Scope

- Multi-tenant super-admin flow (tidak relevan, `users.tenant_id` nullable itu catatan lain).
- Wizard step tambahan di luar 2 step ini (mis. undang pengajar, import santri) — bisa jadi iterasi berikutnya, tidak diminta sekarang.
- Analytics/tracking progres onboarding.
- Reminder/nudge untuk admin yang skip (mis. banner "lengkapi profil lembaga" di dashboard) — tidak diminta.
