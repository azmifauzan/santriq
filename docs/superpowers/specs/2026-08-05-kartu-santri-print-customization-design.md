# Cetak Kartu Santri — Fix Sidebar & Kustomisasi Tampilan — Design

## Problem

1. Klik "Cetak Kartu QR" di Data Santri (`Students/Index.vue:125-128`) pakai `window.open(url, '_blank')` — buka tab baru. Di tab itu, `AppSidebar` tetap tampil (layout resolver `app.ts` gak nge-exclude `Students/PrintCards`), tapi menu "Data Santri" gak ke-highlight sebagai aktif, karena `NavMain.vue:27` cocokin URL exact match (`isCurrentUrl`) sedang path-nya `/students/print-cards`, bukan persis `/students`.
2. Tampilan layar halaman cetak (`PrintCards.vue`) dibatasi `max-w-6xl` + grid `grid-cols-2 md:grid-cols-3` — di layar lebar nyisain banyak ruang kosong kiri-kanan yang gak dipakai.
3. Gak ada cara admin kustomisasi tampilan kartu (jumlah kolom cetak, warna, field yang tampil, logo) — semua hardcode di `PrintCards.vue`.

## Scope

- Fix active-menu sidebar + ubah navigasi ke tab yang sama.
- Perbesar pemakaian ruang layar (bukan kertas) di halaman cetak.
- Halaman Settings baru "Cetak Kartu Santri" buat kustomisasi tampilan kartu, disimpan per-tenant.
- Tombol ke halaman kustomisasi itu dari halaman cetak kartu.

**Out of scope**: ubah default jumlah kolom di kertas (tetap 2 kecuali diubah admin lewat settings), upload logo baru khusus kartu (reuse logo Lembaga yang sudah ada), redesign kartu (bentuk/ukuran/QR tetap).

## Design

### 1. Fix sidebar active state + navigasi tab sama

- `resources/js/components/NavMain.vue:17,27`: ganti `isCurrentUrl` jadi `isCurrentOrParentUrl` (prefix match, sudah ada di `useCurrentUrl.ts` dan dipakai settings nav). Aman dipakai buat semua item `mainNavItems` (`AppSidebar.vue:37-102`) karena tidak ada href yang jadi prefix dari href lain (`/dashboard`, `/scan`, `/attendance`, `/students`, `/classrooms`, `/guardians`, `/achievements`, `/invoices`, `/leave-requests`, `/reports`, `/teachers` — semua unik, gak overlap).
- `resources/js/pages/Students/Index.vue:125-128`: ganti `window.open(url, '_blank')` jadi `router.visit(url)` dari `@inertiajs/vue3` — jadi navigasi Inertia biasa di tab yang sama (bisa balik pakai tombol back browser / klik menu sidebar lain).

### 2. Layout layar halaman cetak

Di `PrintCards.vue`, grid & container buat tampilan **layar** (bukan cetak) diperlebar biar makin banyak kartu per baris di layar lebar, independen dari setting kolom cetak (poin 3):

- Container: `max-w-6xl` → `max-w-[1600px]` (atau `max-w-full` dengan padding, dipilih saat implementasi berdasar hasil visual).
- Grid layar: `grid-cols-2 md:grid-cols-3` → tambah `lg:grid-cols-4 xl:grid-cols-5`.
- Grid cetak (`print:grid-cols-2`) **tidak diubah** di sini — jumlah kolom cetak jadi dinamis dari setting (poin 4).

### 3. Penyimpanan setting kustomisasi kartu

Ikut pola `settings['landing']` yang sudah ada di kolom JSON `tenants.settings` (lihat `UpdatesTenantLandingSettings`). Key baru: `settings['card_print']`:

```php
[
    'columns_per_print_row' => 2,       // int: 2 | 3 | 4
    'accent_color' => '#1e293b',        // hex string, default = warna border slate-800 sekarang
    'show_nis' => true,
    'show_classroom' => true,
    'show_gender' => false,
    'show_logo' => false,               // pakai settings['landing']['logo_path'] yang sudah ada
]
```

Nama santri & QR selalu tampil (gak bisa dimatikan — itu inti kartu).

Default value dipakai kalau `settings['card_print']` belum ada / key tertentu belum diset (tenant lama).

### 4. Backend

- **Trait** `app/Concerns/UpdatesTenantCardPrintSettings.php` — method `updateCardPrintSettings(Request $request, Tenant $tenant): array`, merge non-destruktif ke `tenants.settings['card_print']` (pola sama persis `UpdatesTenantLandingSettings::updateLandingSettings`).
- **Form Request** `app/Http/Requests/Settings/UpdateCardPrintSettingsRequest.php`:
  - `columns_per_print_row`: `required|integer|in:2,3,4`
  - `accent_color`: `required|string|regex:/^#[0-9A-Fa-f]{6}$/`
  - `show_nis`, `show_classroom`, `show_gender`, `show_logo`: `required|boolean`
- **Controller** `app/Http/Controllers/Settings/CardPrintController.php` (pola sama `LembagaController`):
  - `edit()`: `Inertia::render('settings/CardPrint', ['cardPrint' => ..., 'hasLogo' => bool])` — `hasLogo` dari `!empty($tenant->settings['landing']['logo_path'] ?? null)`, dipakai buat disable toggle "tampilkan logo" kalau belum ada logo.
  - `update(UpdateCardPrintSettingsRequest $request)`: panggil trait, `to_route('card-print.edit')->with('success', ...)`.
- **Route** di `routes/tenant.php`, di grup settings deket `lembaga` (baris ~104-129):
  ```php
  Route::get('settings/cetak-kartu', [CardPrintController::class, 'edit'])->name('card-print.edit');
  Route::put('settings/cetak-kartu', [CardPrintController::class, 'update'])->name('card-print.update');
  ```
- **`StudentController::printCards`** (baris 139-170): tambah baca `$tenant->settings['card_print'] ?? []` di-merge sama default array, kirim sebagai prop `cardSettings`. Tambah `logo_url` (null kecuali `show_logo` true & ada `logo_path` → `Storage::url($path)`) dikirim sekali di root props (bukan per-kartu, karena logo tenant sama buat semua kartu).

### 5. Frontend — halaman Settings baru

- `resources/js/layouts/settings/Layout.vue`: tambah item nav baru "Cetak Kartu Santri" → `cardPrintEdit()` (Wayfinder).
- `resources/js/pages/settings/CardPrint.vue` (pola sama `Lembaga.vue` — Inertia `<Form>` + Wayfinder):
  - Select jumlah kolom cetak (2/3/4).
  - Color input warna aksen.
  - Checkbox: tampilkan NIS, tampilkan kelas, tampilkan jenis kelamin.
  - Toggle tampilkan logo — disabled + hint "Upload logo dulu di Settings → Lembaga" kalau `hasLogo` false.
  - Preview kartu kecil (reaktif ke form state saat ini, sebelum submit) — pakai markup kartu yang disederhanakan supaya admin langsung lihat efeknya sebelum simpan.

### 6. Frontend — halaman cetak

`PrintCards.vue`:

- Props tambahan: `cardSettings: CardSettings`, `logoUrl: string | null`.
- Tombol baru "Kustomisasi Kartu" (`print:hidden`) di header, `<Link>` Inertia ke `cardPrintEdit()`.
- Kolom cetak dinamis: karena Tailwind `print:grid-cols-N` harus kelas statis, kolom cetak pakai CSS custom property + scoped media-print rule:
  ```html
  <div class="grid ..." :style="{ '--print-cols': cardSettings.columns_per_print_row }">
  ```
  ```css
  @media print {
      .card-grid { grid-template-columns: repeat(var(--print-cols), minmax(0, 1fr)); }
  }
  ```
- Warna aksen per kartu via CSS var (`style="--accent: cardSettings.accent_color"`), dipakai di border & garis header/footer kartu lewat scoped CSS (`border-color: var(--accent)`).
- Field NIS/kelas/gender: `v-if="cardSettings.show_nis"` dst.
- Logo: `v-if="cardSettings.show_logo && logoUrl"`, tampil kecil di header sebelah nama tenant.

## Data flow

Admin isi form di Settings → Cetak Kartu → submit → `CardPrintController::update` → trait merge ke `tenants.settings['card_print']` → tersimpan di DB. Saat admin buka halaman cetak kartu → `StudentController::printCards` baca settings itu (merge default) → kirim ke `PrintCards.vue` sebagai prop → kartu dirender sesuai setting.

## Testing

- `tests/Feature/Settings/CardPrintSettingsTest.php` (baru): `edit()` render page dengan default kalau belum ada setting; `update()` simpan & persist correctly ke `tenants.settings['card_print']`; validasi gagal untuk `columns_per_print_row` di luar 2/3/4 dan `accent_color` bukan hex valid. Struktur test (login sebagai admin tenant, assert response Inertia, assert DB) ikut pola `tests/Feature/MasterDataTest.php`.
- Extend/tambah test `StudentController::printCards`: assert prop `cardSettings` ada dengan default value pas tenant belum pernah set apa-apa, dan reflect nilai tersimpan setelah setting diubah.

## Out of scope (reminder)

- Upload logo baru khusus kartu.
- Ubah default kolom cetak (kertas tetap 2 kecuali admin ubah).
- Redesign bentuk/ukuran kartu atau QR.
