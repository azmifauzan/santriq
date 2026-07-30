# Import UX Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace default-English Laravel validation errors on the 4 Excel import features (Santri, Wali Santri, Kelas, Pengajar) with Indonesian messages, and replace the empty-header import template with a template that has an example row, locked/validated cell formats (date, text), and a reference sheet of valid values where applicable.

**Architecture:** Maatwebsite Excel's `RowValidator` already calls `customValidationMessages()` on the import class when present (`vendor/maatwebsite/excel/src/Validators/RowValidator.php:81`) — no library change needed, just add that method to each `App\Imports\*` class. For templates, add a new `App\Exports\Templates` namespace with per-entity template export classes (using `FromArray` + `WithEvents`/`AfterSheet` for styling and `PhpOffice\PhpSpreadsheet\Cell\DataValidation` for dropdowns/date validation), swapped in by each controller's `export()` method when `?template=1` is passed, replacing the current `new Collection` (empty) placeholder.

**Tech Stack:** Laravel 13, maatwebsite/excel ^3.1, phpoffice/phpspreadsheet (transitive dependency, already vendored), Pest v4.

## Global Constraints

- No new Composer dependencies — everything needed is already vendored (`maatwebsite/excel`, `phpoffice/phpspreadsheet`).
- Do not change `config/app.php` locale or add a `lang/` directory — fix is scoped to the 4 import classes via Maatwebsite's per-import message hook.
- Follow existing PHP conventions: curly braces always, explicit param/return types, PHPDoc array shapes, PSR property promotion where applicable.
- After every PHP file change, run `vendor/bin/pint --dirty --format agent`.
- Run the narrowest relevant test filter after each task; run full `composer test` as the last task.
- Do not touch `AchievementsExport`, `AttendancesExport`, `InvoicesExport`, `LeaveRequestsExport` — they have no import counterpart, out of scope.
- Do not modify the real (non-template) export classes (`StudentsExport`, `GuardiansExport`, `ClassroomsExport`, `TeachersExport`) — only the `?template=1` branch changes.
- Reference sheet ("Referensi") is only added to a template when it has at least one row of data (e.g. no classrooms yet → no Referensi sheet for Students) — confirmed product decision, do not add placeholder/fallback rows.

---

## Task 1: Indonesian validation messages — Students import

**Files:**
- Modify: `app/Imports/StudentsImport.php`
- Modify: `tests/Feature/StudentExportImportTest.php`

**Interfaces:**
- Produces: `StudentsImport::customValidationMessages(): array<string, string>` — keyed `"{field}.{rule}"`, consumed by Maatwebsite's `RowValidator` (no other app code calls this directly).

- [ ] **Step 1: Update the existing test to assert Indonesian error text**

In `tests/Feature/StudentExportImportTest.php`, extend the `'student import creates valid rows and skips invalid/duplicate ones, reporting both'` test (the failures are, in order: row 3 duplicate NIS `1001`, row 4 missing NIS) by adding after the existing `expect($summary['skipped'])->toBe(2);` line:

```php
    expect($summary['errors'][0])->toContain('NIS 1001 sudah terdaftar.');
    expect($summary['errors'][1])->toContain('NIS wajib diisi.');
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter="student import creates valid rows"`
Expected: FAIL — actual error text is still the default English Laravel messages (e.g. "The nis has already been taken.").

- [ ] **Step 3: Add `customValidationMessages()` to `StudentsImport`**

In `app/Imports/StudentsImport.php`, add this method right after the existing `rules()` method (before the closing `}` of the class):

```php
    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.unique' => 'NIS :input sudah terdaftar.',
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'jenis_kelamin.required' => 'Jenis Kelamin wajib diisi.',
            'jenis_kelamin.string' => 'Jenis Kelamin harus berupa teks.',
            'jenis_kelamin.in' => 'Jenis Kelamin harus L atau P.',
            'tanggal_lahir.date' => 'Tanggal Lahir harus tanggal yang valid, contoh: 2015-05-14.',
            'status.string' => 'Status harus berupa teks.',
            'status.in' => 'Status harus active atau inactive.',
        ];
    }
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter="student import creates valid rows"`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Imports/StudentsImport.php tests/Feature/StudentExportImportTest.php
git commit -m "feat: add Indonesian validation messages to student import"
```

---

## Task 2: Indonesian validation messages — Guardians import

**Files:**
- Modify: `app/Imports/GuardiansImport.php`
- Modify: `tests/Feature/GuardianExportImportTest.php`

**Interfaces:**
- Produces: `GuardiansImport::customValidationMessages(): array<string, string>`.

- [ ] **Step 1: Update the existing test to assert Indonesian error text**

In `tests/Feature/GuardianExportImportTest.php`, extend the `'guardian import creates rows and skips ones missing a name'` test by adding, after `$this->assertDatabaseCount('guardians', 1);`:

```php
    $response->assertSessionHas('import_summary', function (array $summary) {
        return str_contains($summary['errors'][0], 'Nama wajib diisi.');
    });
```

Note: `assertSessionHas` with a closure needs the raw session value — check the pattern used by `StudentExportImportTest.php` for reading `import_summary` from the session (`$response->getSession()->get(SessionKey::FLASH_DATA)['import_summary']`) and reuse it for consistency instead of inventing a new pattern:

```php
    $summary = $response->getSession()->get(SessionKey::FLASH_DATA)['import_summary'];
    expect($summary['errors'][0])->toContain('Nama wajib diisi.');
```

Add `use Inertia\Support\SessionKey;` to the top of the file (matches `StudentExportImportTest.php`).

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter="guardian import creates rows"`
Expected: FAIL — actual message is still English ("The nama field is required.").

- [ ] **Step 3: Add `customValidationMessages()` to `GuardiansImport`**

In `app/Imports/GuardiansImport.php`, add after `rules()`:

```php
    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'no_hp.max' => 'No. HP maksimal :max karakter.',
        ];
    }
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter="guardian import creates rows"`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Imports/GuardiansImport.php tests/Feature/GuardianExportImportTest.php
git commit -m "feat: add Indonesian validation messages to guardian import"
```

---

## Task 3: Indonesian validation messages — Classrooms import

**Files:**
- Modify: `app/Imports/ClassroomsImport.php`
- Modify: `tests/Feature/ClassroomExportImportTest.php`

**Interfaces:**
- Produces: `ClassroomsImport::customValidationMessages(): array<string, string>`.

- [ ] **Step 1: Update the existing test to assert Indonesian error text**

In `tests/Feature/ClassroomExportImportTest.php`, add `use Inertia\Support\SessionKey;` at the top, and extend `'classroom import creates rows and skips ones missing a name'` by adding after `$this->assertDatabaseCount('classrooms', 1);`:

```php
    $summary = $response->getSession()->get(SessionKey::FLASH_DATA)['import_summary'];
    expect($summary['errors'][0])->toContain('Nama wajib diisi.');
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter="classroom import creates rows"`
Expected: FAIL

- [ ] **Step 3: Add `customValidationMessages()` to `ClassroomsImport`**

In `app/Imports/ClassroomsImport.php`, add after `rules()`:

```php
    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'level.string' => 'Level harus berupa teks.',
            'level.max' => 'Level maksimal :max karakter.',
        ];
    }
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter="classroom import creates rows"`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Imports/ClassroomsImport.php tests/Feature/ClassroomExportImportTest.php
git commit -m "feat: add Indonesian validation messages to classroom import"
```

---

## Task 4: Indonesian validation messages — Teachers import

**Files:**
- Modify: `app/Imports/TeachersImport.php`
- Modify: `tests/Feature/TeacherExportImportTest.php`

**Interfaces:**
- Produces: `TeachersImport::customValidationMessages(): array<string, string>`.

- [ ] **Step 1: Update the existing test to assert Indonesian error text**

In `tests/Feature/TeacherExportImportTest.php`, add `use Inertia\Support\SessionKey;` at the top, and extend `'teacher import creates accounts with a generated password and skips duplicate emails'` by adding after `$this->assertDatabaseCount('users', 3);`:

```php
    $summary = $response->getSession()->get(SessionKey::FLASH_DATA)['import_summary'];
    expect($summary['errors'][0])->toContain('Email existing@example.com sudah terdaftar.');
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter="teacher import creates accounts"`
Expected: FAIL

- [ ] **Step 3: Add `customValidationMessages()` to `TeachersImport`**

In `app/Imports/TeachersImport.php`, add after `rules()`:

```php
    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email harus berupa teks.',
            'email.email' => 'Email harus berupa alamat email yang valid.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.unique' => 'Email :input sudah terdaftar.',
            'role.required' => 'Role wajib diisi.',
            'role.string' => 'Role harus berupa teks.',
            'role.in' => 'Role harus admin atau pengajar.',
        ];
    }
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter="teacher import creates accounts"`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Imports/TeachersImport.php tests/Feature/TeacherExportImportTest.php
git commit -m "feat: add Indonesian validation messages to teacher import"
```

---

## Task 5: Reusable `ReferenceSheet` export class

**Files:**
- Create: `app/Exports/Templates/ReferenceSheet.php`
- Test: `tests/Unit/Exports/Templates/ReferenceSheetTest.php`

**Interfaces:**
- Produces: `App\Exports\Templates\ReferenceSheet::__construct(array<string, array<int, string>> $columns)`, `->array(): array<int, array<int, string|null>>` (first row is column labels, taken from `array_keys($columns)`; subsequent rows are the transposed values, padded with `null` for short columns), `->title(): string` (`'Referensi'`).
- Consumes: nothing from other tasks — this is a leaf class used by Task 6 and Task 7.

- [ ] **Step 1: Write the failing unit test**

Create `tests/Unit/Exports/Templates/ReferenceSheetTest.php`:

```php
<?php

use App\Exports\Templates\ReferenceSheet;

test('array() puts column labels first, then transposes values, padding short columns with null', function () {
    $sheet = new ReferenceSheet([
        'Kelas' => ['Kelas A', 'Kelas B', 'Kelas C'],
        'Role' => ['admin', 'pengajar'],
    ]);

    expect($sheet->array())->toBe([
        ['Kelas', 'Role'],
        ['Kelas A', 'admin'],
        ['Kelas B', 'pengajar'],
        ['Kelas C', null],
    ]);
});

test('title() returns Referensi', function () {
    $sheet = new ReferenceSheet(['Role' => ['admin', 'pengajar']]);

    expect($sheet->title())->toBe('Referensi');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact tests/Unit/Exports/Templates/ReferenceSheetTest.php`
Expected: FAIL with "Class App\Exports\Templates\ReferenceSheet not found".

- [ ] **Step 3: Implement `ReferenceSheet`**

Create `app/Exports/Templates/ReferenceSheet.php`:

```php
<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lists valid values for enum-like import columns (e.g. daftar nama kelas,
 * daftar role) on their own sheet, so the user can see and copy the exact
 * values instead of guessing.
 */
class ReferenceSheet implements FromArray, WithStyles, WithTitle
{
    /**
     * @param  array<string, array<int, string>>  $columns  Column label => list of valid values.
     */
    public function __construct(private readonly array $columns) {}

    /**
     * @return array<int, array<int, string|null>>
     */
    public function array(): array
    {
        $rowCount = max(array_map('count', $this->columns));
        $rows = [array_keys($this->columns)];

        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = array_map(
                fn (array $values) => $values[$i] ?? null,
                array_values($this->columns)
            );
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Referensi';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
```

- [ ] **Step 4: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact tests/Unit/Exports/Templates/ReferenceSheetTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Exports/Templates/ReferenceSheet.php tests/Unit/Exports/Templates/ReferenceSheetTest.php
git commit -m "feat: add reusable ReferenceSheet export for import templates"
```

---

## Task 6: Students import template (example row, dropdowns, date validation, Referensi sheet)

**Files:**
- Create: `app/Exports/Templates/StudentsTemplateSheet.php`
- Create: `app/Exports/Templates/StudentsTemplateExport.php`
- Modify: `app/Http/Controllers/StudentController.php:13` (remove unused `Collection` import), `:42-51` (`export()` method)
- Modify: `tests/Feature/StudentExportImportTest.php`

**Interfaces:**
- Consumes: `App\Exports\Templates\ReferenceSheet` from Task 5 (`new ReferenceSheet(['Kelas' => $kelasOptions])`).
- Produces: `App\Exports\Templates\StudentsTemplateExport` — zero-arg constructor, implements `Maatwebsite\Excel\Concerns\WithMultipleSheets`, used by `StudentController::export()`.

- [ ] **Step 1: Write the failing feature tests**

Add to `tests/Feature/StudentExportImportTest.php`:

```php
test('student template download returns an xlsx file with an example row and a Referensi sheet for existing classrooms', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);

    $response = $this->actingAsStaff($admin)->get(route('students.export', ['template' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('student template download works even when the tenant has no classrooms yet', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('students.export', ['template' => 1]));

    $response->assertOk();
});
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --compact --filter="student template download"`
Expected: FAIL or error — `StudentController::export()` still returns the old empty-collection `StudentsExport`, which doesn't exist yet as the new template class (this step should still currently pass since the old code path already returns a valid xlsx; the important check is the NEXT task step where we swap the implementation — run this to confirm the baseline test at least executes, then proceed).

Note: since the *old* `?template=1` path already downloads a valid (if empty) xlsx, this specific test may pass before Step 3 too — that's fine, it's the visible contract (a valid xlsx download) that must never break. The behavior change (example row, dropdowns, Referensi sheet) is not asserted here since Pest/PhpSpreadsheet cell-level assertions are out of scope per the design doc; proceed to implement and re-run to confirm no regression.

- [ ] **Step 3: Implement `StudentsTemplateSheet`**

Create `app/Exports/Templates/StudentsTemplateSheet.php`:

```php
<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateSheet implements FromArray, WithEvents, WithTitle
{
    private const LAST_ROW = 500;

    /**
     * @param  array<int, string>  $kelasOptions
     */
    public function __construct(private readonly array $kelasOptions) {}

    /**
     * @return array<int, array<int, string|null>>
     */
    public function array(): array
    {
        return [
            ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'],
            ['2024001', 'Ahmad Fauzan', 'L', '2015-05-14', $this->kelasOptions[0] ?? '', 'active'],
        ];
    }

    public function title(): string
    {
        return 'Template';
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A2:F2')->getFont()->setItalic(true);
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->getStyle('A3:A'.self::LAST_ROW)->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle('D3:D'.self::LAST_ROW)->getNumberFormat()->setFormatCode('yyyy-mm-dd');

                $this->applyListValidation($sheet, 'C', '"L,P"', 'Jenis Kelamin', 'Pilih L atau P.');
                $this->applyDateValidation($sheet, 'D');

                if ($this->kelasOptions !== []) {
                    $lastKelasRow = count($this->kelasOptions) + 1;
                    $this->applyListValidation(
                        $sheet,
                        'E',
                        "Referensi!\$A\$2:\$A\${$lastKelasRow}",
                        'Kelas',
                        'Pilih kelas dari daftar di sheet Referensi.'
                    );
                }

                $this->applyListValidation($sheet, 'F', '"active,inactive"', 'Status', 'Pilih active atau inactive.');
            },
        ];
    }

    private function applyListValidation(Worksheet $sheet, string $column, string $formula, string $label, string $message): void
    {
        for ($row = 3; $row <= self::LAST_ROW; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle("{$label} tidak valid");
            $validation->setError($message);
            $validation->setFormula1($formula);
        }
    }

    private function applyDateValidation(Worksheet $sheet, string $column): void
    {
        for ($row = 3; $row <= self::LAST_ROW; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_DATE);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Tanggal Lahir tidak valid');
            $validation->setError('Isi tanggal lahir dengan format tanggal, contoh: 2015-05-14.');
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $validation->setFormula1('DATE(1900,1,1)');
        }
    }
}
```

- [ ] **Step 4: Implement `StudentsTemplateExport`**

Create `app/Exports/Templates/StudentsTemplateExport.php`:

```php
<?php

namespace App\Exports\Templates;

use App\Models\Classroom;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentsTemplateExport implements WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $kelasOptions = Classroom::orderBy('name')->pluck('name')->all();

        $sheets = [new StudentsTemplateSheet($kelasOptions)];

        if ($kelasOptions !== []) {
            $sheets[] = new ReferenceSheet(['Kelas' => $kelasOptions]);
        }

        return $sheets;
    }
}
```

- [ ] **Step 5: Wire it into `StudentController::export()`**

In `app/Http/Controllers/StudentController.php`:

Remove the now-unused import at line 13:
```php
use Illuminate\Database\Eloquent\Collection;
```

Add after the `App\Exports\StudentsExport` import:
```php
use App\Exports\Templates\StudentsTemplateExport;
```

Replace the `export()` method (lines 42-51):
```php
    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Student::class);

        if ($request->boolean('template')) {
            return Excel::download(new StudentsTemplateExport, 'template-data-santri.xlsx');
        }

        $students = $this->filteredQuery($request)->get();

        return Excel::download(new StudentsExport($students), 'data-santri.xlsx');
    }
```

- [ ] **Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=StudentExportImportTest`
Expected: PASS (all tests in the file, including the pre-existing ones — confirms no regression on the real export/import paths).

- [ ] **Step 8: Commit**

```bash
git add app/Exports/Templates/StudentsTemplateSheet.php app/Exports/Templates/StudentsTemplateExport.php app/Http/Controllers/StudentController.php tests/Feature/StudentExportImportTest.php
git commit -m "feat: add guided import template for students with dropdowns and Referensi sheet"
```

---

## Task 7: Teachers import template (example row, Role dropdown, Referensi sheet)

**Files:**
- Create: `app/Exports/Templates/TeachersTemplateSheet.php`
- Create: `app/Exports/Templates/TeachersTemplateExport.php`
- Modify: `app/Http/Controllers/TeacherController.php:11` (remove unused `Collection` import), `:36-45` (`export()` method)
- Modify: `tests/Feature/TeacherExportImportTest.php`

**Interfaces:**
- Consumes: `App\Exports\Templates\ReferenceSheet` from Task 5.
- Produces: `App\Exports\Templates\TeachersTemplateExport` — zero-arg constructor, implements `WithMultipleSheets`, used by `TeacherController::export()`.

- [ ] **Step 1: Write the failing feature test**

Add to `tests/Feature/TeacherExportImportTest.php`:

```php
test('teacher template download returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $response = $this->actingAsStaff($admin)->get(route('teachers.export', ['template' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --compact --filter="teacher template download"`
Expected: PASS already (old empty-collection path also downloads a valid xlsx) — this is the regression guard for the upcoming swap, same rationale as Task 6 Step 2.

- [ ] **Step 3: Implement `TeachersTemplateSheet`**

Create `app/Exports/Templates/TeachersTemplateSheet.php`:

```php
<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class TeachersTemplateSheet implements FromArray, WithEvents, WithTitle
{
    private const LAST_ROW = 500;

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Nama', 'Email', 'Role'],
            ['Ustadzah Aminah', 'aminah@contoh.sch.id', 'pengajar'],
        ];
    }

    public function title(): string
    {
        return 'Template';
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:C1')->getFont()->setBold(true);
                $sheet->getStyle('A2:C2')->getFont()->setItalic(true);
                foreach (range('A', 'C') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                for ($row = 3; $row <= self::LAST_ROW; $row++) {
                    $validation = $sheet->getCell("C{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Role tidak valid');
                    $validation->setError('Pilih admin atau pengajar.');
                    $validation->setFormula1('"admin,pengajar"');
                }
            },
        ];
    }
}
```

- [ ] **Step 4: Implement `TeachersTemplateExport`**

Create `app/Exports/Templates/TeachersTemplateExport.php`:

```php
<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TeachersTemplateExport implements WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new TeachersTemplateSheet,
            new ReferenceSheet(['Role' => ['admin', 'pengajar']]),
        ];
    }
}
```

- [ ] **Step 5: Wire it into `TeacherController::export()`**

In `app/Http/Controllers/TeacherController.php`:

Remove the now-unused import at line 11:
```php
use Illuminate\Database\Eloquent\Collection;
```

Add after the `App\Exports\TeachersExport` import:
```php
use App\Exports\Templates\TeachersTemplateExport;
```

Replace the `export()` method (lines 36-45):
```php
    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', User::class);

        if ($request->boolean('template')) {
            return Excel::download(new TeachersTemplateExport, 'template-data-pengajar.xlsx');
        }

        $teachers = $this->filteredQuery()->get();

        return Excel::download(new TeachersExport($teachers), 'data-pengajar.xlsx');
    }
```

- [ ] **Step 6: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=TeacherExportImportTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add app/Exports/Templates/TeachersTemplateSheet.php app/Exports/Templates/TeachersTemplateExport.php app/Http/Controllers/TeacherController.php tests/Feature/TeacherExportImportTest.php
git commit -m "feat: add guided import template for teachers with Role dropdown and Referensi sheet"
```

---

## Task 8: Guardians import template (example row, text-formatted phone column)

**Files:**
- Create: `app/Exports/Templates/GuardiansTemplateExport.php`
- Modify: `app/Http/Controllers/GuardianController.php:11` (remove unused `Collection` import), `:39-48` (`export()` method)
- Modify: `tests/Feature/GuardianExportImportTest.php`

**Interfaces:**
- Produces: `App\Exports\Templates\GuardiansTemplateExport` — zero-arg constructor, implements `FromArray`/`WithEvents`, used by `GuardianController::export()`.

- [ ] **Step 1: Write the failing feature test**

Add to `tests/Feature/GuardianExportImportTest.php`:

```php
test('guardian template download returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('guardians.export', ['template' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --compact --filter="guardian template download"`
Expected: PASS already (regression guard, same rationale as Task 6 Step 2).

- [ ] **Step 3: Implement `GuardiansTemplateExport`**

Create `app/Exports/Templates/GuardiansTemplateExport.php`:

```php
<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class GuardiansTemplateExport implements FromArray, WithEvents
{
    private const LAST_ROW = 500;

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Nama', 'No. HP'],
            ['Ibu Aisyah', '081234567890'],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:B1')->getFont()->setBold(true);
                $sheet->getStyle('A2:B2')->getFont()->setItalic(true);
                foreach (range('A', 'B') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->getStyle('B3:B'.self::LAST_ROW)->getNumberFormat()->setFormatCode('@');
            },
        ];
    }
}
```

- [ ] **Step 4: Wire it into `GuardianController::export()`**

In `app/Http/Controllers/GuardianController.php`:

Remove the now-unused import at line 11:
```php
use Illuminate\Database\Eloquent\Collection;
```

Add after the `App\Exports\GuardiansExport` import:
```php
use App\Exports\Templates\GuardiansTemplateExport;
```

Replace the `export()` method (lines 39-48):
```php
    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Guardian::class);

        if ($request->boolean('template')) {
            return Excel::download(new GuardiansTemplateExport, 'template-data-wali-santri.xlsx');
        }

        $guardians = Guardian::latest()->get();

        return Excel::download(new GuardiansExport($guardians), 'data-wali-santri.xlsx');
    }
```

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=GuardianExportImportTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Exports/Templates/GuardiansTemplateExport.php app/Http/Controllers/GuardianController.php tests/Feature/GuardianExportImportTest.php
git commit -m "feat: add guided import template for guardians with text-formatted phone column"
```

---

## Task 9: Classrooms import template (example row)

**Files:**
- Create: `app/Exports/Templates/ClassroomsTemplateExport.php`
- Modify: `app/Http/Controllers/ClassroomController.php:10` (remove unused `Collection` import), `:35-44` (`export()` method)
- Modify: `tests/Feature/ClassroomExportImportTest.php`

**Interfaces:**
- Produces: `App\Exports\Templates\ClassroomsTemplateExport` — zero-arg constructor, implements `FromArray`/`WithEvents`, used by `ClassroomController::export()`.

- [ ] **Step 1: Write the failing feature test**

Add to `tests/Feature/ClassroomExportImportTest.php`:

```php
test('classroom template download returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $response = $this->actingAsStaff($admin)->get(route('classrooms.export', ['template' => 1]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run the test**

Run: `php artisan test --compact --filter="classroom template download"`
Expected: PASS already (regression guard, same rationale as Task 6 Step 2).

- [ ] **Step 3: Implement `ClassroomsTemplateExport`**

Create `app/Exports/Templates/ClassroomsTemplateExport.php`:

```php
<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ClassroomsTemplateExport implements FromArray, WithEvents
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Nama', 'Level'],
            ['Kelas Iqra 1', 'Jilid 1'],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:B1')->getFont()->setBold(true);
                $sheet->getStyle('A2:B2')->getFont()->setItalic(true);
                foreach (range('A', 'B') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}
```

- [ ] **Step 4: Wire it into `ClassroomController::export()`**

In `app/Http/Controllers/ClassroomController.php`:

Remove the now-unused import at line 10:
```php
use Illuminate\Database\Eloquent\Collection;
```

Add after the `App\Exports\ClassroomsExport` import:
```php
use App\Exports\Templates\ClassroomsTemplateExport;
```

Replace the `export()` method (lines 35-44):
```php
    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Classroom::class);

        if ($request->boolean('template')) {
            return Excel::download(new ClassroomsTemplateExport, 'template-data-kelas.xlsx');
        }

        $classrooms = Classroom::latest()->get();

        return Excel::download(new ClassroomsExport($classrooms), 'data-kelas.xlsx');
    }
```

- [ ] **Step 5: Run pint**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --compact --filter=ClassroomExportImportTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Exports/Templates/ClassroomsTemplateExport.php app/Http/Controllers/ClassroomController.php tests/Feature/ClassroomExportImportTest.php
git commit -m "feat: add guided import template for classrooms with an example row"
```

---

## Task 10: Full verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full CI-equivalent check**

Run: `composer ci:check`
Expected: PASS — `config:clear`, `pint --test`, `phpstan` (level 7), and the full Pest suite all green. Fix anything phpstan flags about the new `App\Exports\Templates\*` classes (typed array shapes, `Worksheet` type hints) before proceeding.

- [ ] **Step 2: Manually verify one template in Excel/LibreOffice (not automatable)**

Run: `php artisan tinker --execute '$u = App\Models\User::first(); Auth::login($u);'` is not needed — instead, log into the app in a browser as an admin, open Santri → Import Excel → Unduh Template, and open the downloaded file: confirm the example row is present, the Jenis Kelamin/Kelas/Status columns show a dropdown arrow when clicked, and Tanggal Lahir shows a date picker/calendar icon when clicked (behavior varies slightly by Excel/LibreOffice version — confirm at least the dropdown lists are present). This step has no automated equivalent since Pest cannot drive Excel's UI; report what was observed.

- [ ] **Step 3: Commit if Step 1 required fixes**

Only if Step 1 required code changes:

```bash
git add -A
git commit -m "fix: address phpstan/pint findings in import template classes"
```
