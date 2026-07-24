# Desain: Panel Super Admin

Status: disetujui, siap diturunkan jadi rencana implementasi.

## 1. Ringkasan

Kapabilitas baru `is_super_admin` pada `users`, terpisah dari `role` (`admin`/`pengajar`) yang sudah scoped per tenant. Super admin bisa jadi admin/pengajar di lembaganya sendiri sekaligus punya akses ke panel lintas-tenant di domain apex: daftar semua lembaga + statistik dasar, detail satu lembaga, dan toggle suspend/aktifkan. Login tetap lewat form Fortify yang sama (tidak ada halaman login terpisah); user dengan `is_super_admin` melihat link tambahan "Panel Super Admin" di sidebar tenant yang mengarah ke domain apex.

## 2. Cakupan v1

- List lembaga + jumlah santri/pengajar/wali per lembaga.
- Detail satu lembaga.
- Suspend / aktifkan lembaga. Lembaga tersuspend tidak bisa diakses sama sekali (staff, wali, landing) di subdomain-nya.
- Provisioning super admin pertama: seeder/tinker manual, bukan UI. Tidak ada UI untuk memberi/mencabut status super admin di v1 — terlalu sensitif untuk self-serve tanpa audit trail.
- Di luar cakupan: billing, impersonate/login-as, edit data lembaga dari panel super admin, audit log, multi-level admin.

## 3. Data Model

Migrasi baru:

```
users.is_super_admin: boolean, default false, after role
tenants.suspended_at: timestamp, nullable, after settings
```

`is_super_admin` terpisah dari `role` karena super admin tetap admin/pengajar normal di lembaganya sendiri — ini kapabilitas tambahan, bukan pengganti role.

`suspended_at` dipakai (bukan `is_active` boolean) supaya kapan lembaga disuspend juga tersimpan, dan togglenya cuma set/null-kan kolom ini.

`User` model: tambah `is_super_admin` ke `@property` + method `isSuperAdmin(): bool`.
`Tenant` model: tambah `suspended_at` ke cast `datetime` + `@property` + method `isSuspended(): bool`.

## 4. Akses & Efek Suspend

### Middleware `EnsureSuperAdmin`

Baru, pola sama `EnsureStaffTenantMatchesSubdomain`: `abort_unless(auth()->user()?->isSuperAdmin(), 403)`.

### Efek suspend

`ResolveTenantFromDomain` (middleware global, sudah resolve `Tenant` dari `{subdomain}`) ditambah satu baris: kalau tenant resolve dan `isSuspended()`, `abort(403)`. Ini menutup akses ke seluruh rute tenant subdomain — landing, login staff, dashboard, portal wali, semuanya — sebelum request sampai ke controller manapun. Bukan flag baru di setiap controller.

Panel super admin sendiri ada di domain apex, jadi tidak kena efek ini.

## 5. Backend

### Routes (`routes/web.php`, di dalam `Route::domain(config('tenancy.domain'))` yang sudah ada)

```php
Route::middleware(['auth', 'verified', EnsureSuperAdmin::class])
    ->prefix('super-admin')->name('super-admin.')
    ->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        Route::get('{tenant}', [SuperAdminController::class, 'show'])->name('show');
        Route::patch('{tenant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('toggle-status');
    });
```

`{tenant}` route model binding lewat kolom `id` (default) — tidak lewat `subdomain` supaya tidak bentrok konsep dengan `{subdomain}` di rute tenant.

### `SuperAdminController`

- `index`: semua `Tenant`, `withCount` santri/pengajar+admin/wali (query terpisah per relasi, `Tenant` belum punya relasi ini — tambah `hasMany` ke `Student`, `User`, `Guardian` di model `Tenant` kalau belum ada).
- `show`: satu tenant + counts yang sama + daftar staff (`users` dengan `tenant_id` itu).
- `toggleStatus`: `suspended_at` null → `now()`, atau sebaliknya.

### `TenantPolicy`

Baru. `viewAny`/`view`/`update` semua gate ke `$user->isSuperAdmin()`. Controller pakai `$this->authorize(...)` sesuai konvensi proyek (otorisasi di Policy, bukan di controller langsung).

## 6. Frontend

- `resources/js/layouts/SuperAdminLayout.vue`: layout minimal terpisah dari `AppSidebar`/`AppLayout` tenant — beda konteks nav sama sekali (bukan per-lembaga), dan hidup di domain apex bukan subdomain.
- `resources/js/pages/SuperAdmin/Index.vue`: tabel lembaga (nama, subdomain, dibuat kapan, jumlah santri/pengajar/wali, status, tombol suspend/aktifkan).
- `resources/js/pages/SuperAdmin/Show.vue`: detail lembaga + daftar staff.
- `AppSidebar.vue` (tenant): satu item nav baru "Panel Super Admin", tampil hanya kalau `auth.user.is_super_admin`, `href` ke URL absolut (beda domain, apex vs subdomain — bukan rute Wayfinder biasa).

### `superAdminUrl` shared prop

`HandleInertiaRequests::share()` tambah key baru, dihitung server-side (bukan dirakit di client dari env) supaya konsisten dengan mode `subdomain_active` on/off yang sudah ada di `config('tenancy.domain')`:

```php
'superAdminUrl' => $user?->isSuperAdmin()
    ? url()->to('http://'.config('tenancy.domain').'/super-admin') // scheme ikut request()->getScheme() sebenarnya
    : null,
```

(Detail helper URL persis mengikuti pola yang sudah dipakai `GoogleOAuthToken`/route apex lain — diselaraskan saat implementasi, bukan didikte di sini.)

## 7. Testing

Feature test baru `tests/Feature/SuperAdminTest.php`:

- Guest → redirect login.
- Admin/pengajar biasa (bukan super admin) → 403.
- Super admin → `index` menampilkan semua tenant lintas-tenant (bukti global scope tidak menghalangi, karena `Tenant` model tidak pakai `BelongsToTenant`) dengan count yang benar.
- `toggleStatus` mengubah `suspended_at` dan membalik lagi.
- Tenant tersuspend: request ke rute tenant subdomain manapun (contoh `dashboard`, `tenant.landing`) → 403.
- Sidebar: assert prop `superAdminUrl` muncul untuk super admin, `null` untuk user biasa (test Inertia response props atau test terpisah kalau lebih cocok jadi unit test komponen).

## 8. Yang Sengaja Tidak Dibangun (v1)

- UI grant/revoke super admin — risikonya butuh audit trail, di luar scope.
- Impersonate/login-as lembaga.
- Edit data lembaga dari panel super admin (hanya lihat + suspend).
- Notifikasi ke tenant saat disuspend (Telegram dsb) — bisa ditambah nanti, tidak blocking v1.
