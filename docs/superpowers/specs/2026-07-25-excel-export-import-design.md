# Export & Import Excel untuk List Data Panel

## Goal

Tambah export Excel (xlsx) di semua list-data admin panel, dan import Excel
untuk 4 entity master data (Students, Teachers, Classrooms, Guardians).
Entity transaksional (Invoices, Achievements, Attendance, LeaveRequests)
export-only.

Package baru: `maatwebsite/excel` (wrapper Laravel di atas PhpSpreadsheet).

## A. Export (semua 8 list)

- Satu class `{Entity}Export` per entity di `app/Exports/`, implement
  `FromCollection`, `WithHeadings`, `WithMapping`.
- Controller `index()` tiap entity: extract query+filter logic yang sudah ada
  ke method private `filteredQuery(Request $request)`, dipakai ulang oleh
  action `export()` baru — supaya export menghormati filter querystring aktif
  yang sama seperti tabel yang sedang dilihat user (pola sama seperti
  `ReportController::exportCsv()` yang sudah ada).
- Route baru per entity: `GET {resource}/export` → nama route
  `{resource}.export`, ditaruh di `routes/tenant.php` dekat resource route
  masing-masing. Authorize pakai `viewAny` Policy ability entity tsb (ability
  yang sama dipakai `index()`).
- Template download: `GET {resource}/export?template=1` menjalankan Export
  class yang sama terhadap collection kosong → file cuma berisi heading row.
  Tidak perlu class terpisah.
- Kolom export per entity:
  - **Students**: NIS, Nama, Jenis Kelamin, Tanggal Lahir, Kelas, Status.
  - **Teachers**: Nama, Email, Role.
  - **Classrooms**: Nama, Level.
  - **Guardians**: Nama, No. HP.
  - **Invoices**: Periode, Nama Santri, Kelas, Jumlah, Jatuh Tempo, Status.
  - **Achievements**: Tanggal, Nama Santri, Kategori, Judul, Nilai, Catatan.
  - **Attendance**: NIS, Nama Santri, Kelas, Masuk, Pulang, Status.
  - **LeaveRequests**: Nama Santri, Jenis Izin, Rentang Tanggal, Alasan,
    Status.

## B. Import (Students, Teachers, Classrooms, Guardians saja)

- Satu class `{Entity}Import` per entity di `app/Imports/`, implement
  `ToModel`, `WithHeadingRow`, `WithValidation`, `SkipsOnFailure`.
- `model()` set `tenant_id` eksplisit dari `Auth::user()->tenant_id` — jangan
  andalkan global scope buat write path.
- Route baru per entity: `POST {resource}/import` → nama route
  `{resource}.import`. Authorize pakai `create` Policy ability entity tsb
  (ability yang sama dipakai `store()` manual).
- Kolom import = kolom export entity tsb, dengan pengecualian:
  - **Students**: kolom "Kelas" berisi nama kelas, di-resolve ke
    `classroom_id` via `Classroom::where('name', $value)->first()` (otomatis
    tenant-scoped lewat global scope karena request datang dari user login).
    Kalau nama kelas tidak ketemu di tenant tsb → baris invalid, skip.
    Guardian linking TIDAK diimport (relasi M2M lintas entity, tetap manual
    lewat modal edit setelah import — sama seperti `store()` yang juga
    memisahkan concern ini).
  - **Teachers**: kolom Password TIDAK ada di file. Password digenerate
    otomatis (`Str::password(12)`), `email_verified_at` dan `onboarded_at` di-
    set `now()` (sama seperti `TeacherController::store()`). Admin
    menyampaikan password ke pengajar di luar sistem, atau pengajar pakai
    "Lupa Password".
  - **Guardians**: `telegram_chat_id` dan `link_token` TIDAK diimport (system-
    managed, diisi lewat flow link Telegram). Student linking tidak diimport,
    sama alasannya dengan Students/guardian.
- Validasi per-baris mirror `Store{Entity}Request` yang sudah ada (unique NIS
  per tenant, unique email Teacher, `Rule::in` buat gender/role/status), plus
  aturan tambahan khusus import di atas.

## C. Duplikat & error handling ("skip + report")

- Baris yang gagal validasi (`WithValidation`) atau duplikat (unique rule
  gagal) di-skip via `SkipsOnFailure`, tidak insert partial.
- Baris valid tetap ke-insert meski ada baris lain yang di-skip dalam file
  yang sama.
- Controller `import()` kumpulkan hasil, redirect back dengan
  `->with('import_summary', ['created' => int, 'skipped' => int, 'errors' =>
  string[]])`. `errors` dibatasi 20 baris pertama biar flash payload tidak
  membengkak di file besar.

## D. Frontend

- Tiap `Index.vue` (8 halaman): tombol **Export** (icon, di header dekat
  search/filter), `window.location.href` ke route `.export` dengan
  querystring filter aktif — pola sama seperti `Reports/Index.vue`.
- 4 halaman master data tambah tombol **Import** yang buka `Dialog` (reuse
  komponen `Dialog` yang sudah dipakai buat modal create/edit di halaman yang
  sama):
  - Input file (`accept=".xlsx,.xls,.csv"`).
  - Link "Unduh Template" → route `.export?template=1`.
  - Submit via `useForm().post(route, { forceFormData: true })`.
  - Setelah response, render `import_summary` dari flash (jumlah created/
    skipped + daftar error) di dalam dialog yang sama.

## E. Testing

Pest feature test per entity (di `tests/Feature/`):

- Export: request ke route `.export` return `200` dengan
  `Content-Type` xlsx yang benar, dan filter querystring memengaruhi isi
  file (baris yang tidak match filter tidak muncul).
- Import: file valid → row ke-insert dengan `tenant_id` benar; row dengan
  data invalid/duplikat → di-skip, masuk `import_summary.errors`, row lain
  di file yang sama tetap ke-insert; row yang refer ke resource tenant lain
  (misal nama kelas milik tenant lain) → tidak ketemu → skip (bukti tenant
  isolation).
- Import: role tanpa ability `create` (misal `pengajar` untuk Teachers) →
  403.

## Scope yang sengaja di luar

- Import untuk Invoices, Achievements, Attendance, LeaveRequests (data
  transaksional, generate/dicatat lewat flow aplikasi, bukan bulk-entry).
- Import guardian↔student dan student↔guardian linking (relasi M2M, tetap
  manual lewat modal existing).
- Update/upsert lewat import (scope ini cuma create baru + skip duplikat,
  sesuai keputusan user).
