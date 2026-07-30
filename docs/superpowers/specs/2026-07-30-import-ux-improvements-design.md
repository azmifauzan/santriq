# Import UX Improvements — Design

## Problem

Fitur import Excel (Santri, Wali Santri, Kelas, Pengajar) punya dua masalah:

1. Pesan error validasi yang dilempar Maatwebsite Excel masih default Laravel bahasa Inggris (mis. "The nama field is required."), padahal seluruh UI berbahasa Indonesia.
2. Template unduhan (`?template=1`) cuma berisi header kolom kosong — tidak ada contoh pengisian, tidak ada daftar nilai referensi (kelas, role, dsb), dan tidak ada bantuan pengisian (format tanggal, dropdown) di Excel.

## Scope

Semua 4 entitas import: Santri (`StudentsImport`), Wali Santri (`GuardiansImport`), Kelas (`ClassroomsImport`), Pengajar (`TeachersImport`).

## Design

### 1. Pesan error Indonesia

Maatwebsite Excel's `RowValidator` sudah mengecek keberadaan method `customValidationMessages()` dan `customValidationAttributes()` pada class import (`vendor/maatwebsite/excel/src/Validators/RowValidator.php:81-93`) dan memakainya kalau ada — tidak perlu lib tambahan atau ubah `config/app.php` locale global.

Tambah kedua method ini ke 4 class di `app/Imports/`, isi pesan per rule per field, contoh (Students):

```php
public function customValidationAttributes(): array
{
    return [
        'nis' => 'NIS',
        'nama' => 'Nama',
        'jenis_kelamin' => 'Jenis Kelamin',
        'tanggal_lahir' => 'Tanggal Lahir',
        'status' => 'Status',
    ];
}

public function customValidationMessages(): array
{
    return [
        'nis.required' => 'NIS wajib diisi.',
        'nis.unique' => 'NIS :input sudah terdaftar.',
        'nama.required' => 'Nama wajib diisi.',
        'nama.string' => 'Nama harus berupa teks.',
        'nama.max' => 'Nama maksimal :max karakter.',
        'jenis_kelamin.required' => 'Jenis Kelamin wajib diisi.',
        'jenis_kelamin.in' => 'Jenis Kelamin harus L atau P.',
        'tanggal_lahir.date' => 'Tanggal Lahir harus tanggal yang valid, contoh: 2015-05-14.',
        'status.in' => 'Status harus active atau inactive.',
    ];
}
```

Sama pola untuk Guardians (`nama`, `no_hp`), Classrooms (`nama`, `level`), Teachers (`nama`, `email`, `role`).

### 2. Template yang membantu pengisian

Ganti export template (`?template=1`) dari `new Collection` kosong ke export class baru khusus template, di `app/Exports/Templates/`:

- `StudentsTemplateExport` (multi-sheet)
- `TeachersTemplateExport` (multi-sheet)
- `GuardiansTemplateExport` (single-sheet)
- `ClassroomsTemplateExport` (single-sheet)

**Sheet utama "Template"** (semua entitas): header (sama seperti export asli) + 1 baris contoh data valid. Styling: header bold, baris contoh italic/abu-abu supaya jelas itu contoh bukan data asli.

Kolom yang rawan kena auto-format Excel (angka dgn nol di depan) diformat sebagai teks (`@`): NIS (Students), No. HP (Guardians).

**Data Validation (dropdown/kalender) di sheet utama**, pakai `PhpOffice\PhpSpreadsheet\Cell\DataValidation` via `WithEvents`/`AfterSheet`:

| Entitas | Kolom | Tipe validation |
|---|---|---|
| Students | Jenis Kelamin | `TYPE_LIST` inline `"L,P"` |
| Students | Tanggal Lahir | `TYPE_DATE` + number format `yyyy-mm-dd` (Excel modern nampilin ikon kalender saat cell diklik) |
| Students | Kelas | `TYPE_LIST` formula merujuk sheet Referensi (kalau ada) |
| Students | Status | `TYPE_LIST` inline `"active,inactive"` |
| Teachers | Role | `TYPE_LIST` inline `"admin,pengajar"` |

Guardians & Classrooms: tidak ada kolom ber-enum/referensi → tidak ada data validation, cukup contoh baris + format teks No. HP.

Range data validation diterapkan ke ~500 baris ke bawah (cukup untuk kebutuhan import wajar, bukan batas keras — file tetap bisa diisi lebih banyak baris, cuma dropdown-nya gak ada di luar range itu).

**Sheet kedua "Referensi"** — hanya dibuat kalau datanya ada (sesuai keputusan: kalau kosong, sheet di-skip, bukan pakai placeholder):

- Students: kalau tenant sudah punya kelas (`Classroom::pluck('name')`), sheet Referensi berisi kolom "Kelas" (daftar nama kelas tenant saat ini).
- Teachers: sheet Referensi berisi kolom "Role" (`admin`, `pengajar`) — selalu ada karena ini nilai tetap aplikasi, bukan data tenant.

Reusable class `app/Exports/Templates/ReferenceSheet.php` implements `FromArray, WithTitle` menerima `array<string, array<int,string>>` (label kolom => daftar nilai) dan render sebagai sheet "Referensi".

### 3. Controller

Di 4 controller (`StudentController`, `GuardianController`, `ClassroomController`, `TeacherController`), method `export()`:

```php
if ($request->boolean('template')) {
    return Excel::download(new StudentsTemplateExport, 'template-data-santri.xlsx');
}

$students = $this->filteredQuery($request)->get();
return Excel::download(new StudentsExport($students), 'data-santri.xlsx');
```

Export asli (`StudentsExport`, dst) tidak diubah — tetap dipakai untuk unduh data riil.

### 4. Kompatibilitas dengan parsing import

Maatwebsite Excel `Row::toArray()` default `$formatData = true` (`vendor/maatwebsite/excel/src/Row.php:69`) — nilai cell dibaca sudah diformat sesuai number format cell-nya. Artinya cell tanggal dengan format `yyyy-mm-dd` yang diisi lewat date picker Excel akan terbaca sebagai string `"2015-05-14"` oleh `StudentsImport::model()`, sama seperti input teks manual sekarang. Tidak perlu ubah logic parsing tanggal di `StudentsImport`.

### 5. Testing

Extend 4 test `tests/Feature/*ExportImportTest.php`:

- Assert error di `import_summary.errors` mengandung teks Indonesia (bukan lagi "The ... field is required.") untuk kasus validasi gagal yang sudah ada di test.
- Assert `?template=1` tetap balikin response `xlsx` (`Content-Type` mengandung `spreadsheet`) — tidak perlu assert isi cell/dropdown secara detail (di luar kepraktisan test Pest untuk PhpSpreadsheet internals), cukup smoke test download-nya jalan tanpa error.

## Out of scope

- Ubah locale aplikasi Laravel secara global.
- Achievement/Attendance/Invoice/LeaveRequest export (tidak punya fitur import).
- Validasi format nomor HP Indonesia yang lebih ketat (di luar permintaan).
