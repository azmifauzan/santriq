# Excel Export & Import Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Excel (xlsx) export to all 8 admin-panel list pages, and Excel import to the 4 master-data list pages (Students, Teachers, Classrooms, Guardians).

**Architecture:** One `{Entity}Export` class per entity (`app/Exports/`, `FromCollection`+`WithHeadings`+`WithMapping`) and one `{Entity}Import` class per master-data entity (`app/Imports/`, `ToModel`+`WithHeadingRow`+`WithValidation`+`SkipsOnFailure`), driven by `maatwebsite/excel`. Each list controller gets a `filteredQuery()` extraction (reused by `index()` and the new `export()` action) and, for master data, an `import()` action. Import results ("created"/"skipped"/error list) go back to the browser via `Inertia::flash('import_summary', [...])`, read by a new shared `ImportDialog.vue` component mounted on the 4 master-data pages.

**Tech Stack:** Laravel 13, `maatwebsite/excel` ^3.1 (PhpSpreadsheet wrapper), Inertia v3 + Vue 3, Pest 4.

**Spec:** `docs/superpowers/specs/2026-07-25-excel-export-import-design.md`

---

## Notes for the implementer

- **Route paths are hardcoded, not Wayfinder.** Every one of the 8 target `Index.vue` files already calls its backend with hardcoded paths (`/students`, `/teachers`, ...) — none of them use `@/actions` or `@/routes`. Mixing Wayfinder into only the new export/import buttons inside these specific files would be a worse inconsistency than following the pattern the file already has 10+ times over. New code in this plan matches that local convention.
- **`Inertia::flash()`, not `->with()`, for the import summary.** Most controllers in this app use plain `redirect()->back()->with('success', ...)` for simple strings, but `SecurityController`/`ProfileController` already use `Inertia::flash('toast', [...])` for structured array data — that's the pattern this plan follows for `import_summary` (an array of counts + error strings), because it's automatically exposed as `page.props.flash.import_summary` on the next Inertia response (see `vendor/inertiajs/inertia-laravel/src/Response.php:241-243`).
- **Heading-row key conversion.** `WithHeadingRow` slugs headings to snake_case keys (`Str::slug($heading, '_')`), e.g. "Jenis Kelamin" → `jenis_kelamin`, "No. HP" → `no_hp`. The `rules()` and `model()` array keys in every Import class below rely on this.
- **Classroom names are not required unique** (no `Rule::unique` in `StoreClassroomRequest` today), so `ClassroomsImport` doesn't add one either — importing the same classroom twice creates two rows, matching existing manual-create behavior. This is a pre-existing app characteristic, not something this plan changes.
- Run `vendor/bin/pint --dirty --format agent` after each backend task before committing (per this repo's binding convention).

---

### Task 1: Install `maatwebsite/excel`

**Files:**
- Modify: `composer.json`, `composer.lock`

- [ ] **Step 1: Require the package**

Run: `composer require maatwebsite/excel`
Expected: Installs `maatwebsite/excel ^3.1` (already dry-run verified compatible with `laravel/framework ^13.17` and PHP `^8.3`), plus `phpoffice/phpspreadsheet`.

- [ ] **Step 2: Verify the service provider registered (auto-discovery)**

Run: `php artisan package:discover --ansi`
Expected: Output includes a line for `Maatwebsite\Excel\ExcelServiceProvider`.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: add maatwebsite/excel for panel export/import"
```

---

### Task 2: Add a shared xlsx-fixture test helper

**Files:**
- Modify: `tests/Pest.php`

Every import feature test needs a real `.xlsx` file to upload. Add one helper, once, instead of rebuilding a spreadsheet in every test file.

- [ ] **Step 1: Add `makeXlsxUploadedFile()` to `tests/Pest.php`**

Add this function in the "Functions" section (after the existing helper functions, before the closing of the file):

```php
/**
 * Build a real .xlsx file on disk and wrap it as an UploadedFile, for
 * posting to import endpoints in feature tests. $rows is a list of
 * arrays, one per data row (no need to pass headings separately if
 * $headings already leads the sheet).
 *
 * @param  array<int, string>  $headings
 * @param  array<int, array<int, mixed>>  $rows
 */
function makeXlsxUploadedFile(array $headings, array $rows, string $filename = 'import.xlsx'): \Illuminate\Http\UploadedFile
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($headings, null, 'A1');
    $sheet->fromArray($rows, null, 'A2');

    $path = sys_get_temp_dir().'/'.uniqid('test-import-', true).'.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new \Illuminate\Http\UploadedFile($path, $filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}
```

- [ ] **Step 2: Commit**

```bash
git add tests/Pest.php
git commit -m "test: add makeXlsxUploadedFile helper for import feature tests"
```

---

### Task 3: Students — export

**Files:**
- Create: `app/Exports/StudentsExport.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/StudentExportImportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/StudentExportImportTest.php`:

```php
<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('student export returns an xlsx file honoring the classroom filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $classroomA = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);
    $classroomB = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas B']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'classroom_id' => $classroomA->id, 'nis' => '1001', 'name' => 'Ahmad']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'classroom_id' => $classroomB->id, 'nis' => '1002', 'name' => 'Budi']);

    $response = $this->actingAsStaff($admin)->get(route('students.export', ['classroom_id' => $classroomA->id]));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=StudentExportImportTest`
Expected: FAIL — route `students.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/StudentsExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Student>  $students
     */
    public function __construct(private readonly Collection $students) {}

    public function collection(): Collection
    {
        return $this->students;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'];
    }

    /**
     * @param  Student  $student
     * @return array<int, string|null>
     */
    public function map($student): array
    {
        return [
            $student->nis,
            $student->name,
            $student->gender,
            $student->birth_date,
            $student->classroom?->name,
            $student->status,
        ];
    }
}
```

- [ ] **Step 4: Refactor `StudentController` to extract `filteredQuery()` and add `export()`**

Modify `app/Http/Controllers/StudentController.php` — replace the `index()` method and add `filteredQuery()`/`export()`:

```php
<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

        $students = $this->filteredQuery($request)->get();
        $classrooms = Classroom::all();
        $guardians = Guardian::all();

        return Inertia::render('Students/Index', [
            'students' => $students,
            'classrooms' => $classrooms,
            'guardians' => $guardians,
            'filters' => $request->only(['classroom_id', 'search']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Student::class);

        $students = $request->boolean('template')
            ? new Collection
            : $this->filteredQuery($request)->get();

        return Excel::download(new StudentsExport($students), 'data-santri.xlsx');
    }

    /**
     * @return Builder<Student>
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = Student::with(['classroom', 'guardians'])
            ->latest();

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $guardianIds = $validated['guardian_ids'] ?? [];
        unset($validated['guardian_ids']);

        $student = Student::create($validated);

        if (! empty($guardianIds)) {
            $student->guardians()->sync($guardianIds);
        }

        return redirect()->back()->with('success', 'Data santri berhasil ditambahkan.');
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();
        $guardianIds = $validated['guardian_ids'] ?? [];
        unset($validated['guardian_ids']);

        $student->update($validated);
        $student->guardians()->sync($guardianIds);

        return redirect()->back()->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        $student->delete();

        return redirect()->back()->with('success', 'Data santri berhasil dihapus.');
    }

    public function printCards(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

        $studentIds = array_filter(explode(',', $request->input('ids', '')));

        $query = Student::with('classroom');
        if (! empty($studentIds)) {
            $query->whereIn('id', $studentIds);
        } elseif ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $user = auth()->user();
        $tenantName = ($user && $user->tenant) ? $user->tenant->name : 'SantriQ';

        $students = $query->get()->map(function (Student $student) use ($tenantName) {
            return [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
                'classroom_name' => $student->classroom ? $student->classroom->name : 'Tanpa Kelas',
                'qr_svg' => QrCodeService::generateSvg($student->qr_token, 180),
                'tenant_name' => $tenantName,
            ];
        });

        return Inertia::render('Students/PrintCards', [
            'students' => $students,
        ]);
    }
}
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, add right after the `students` resource line:

```php
        Route::get('students/print-cards', [StudentController::class, 'printCards'])->name('students.print-cards');
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::resource('students', StudentController::class)->except(['create', 'edit', 'show']);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=StudentExportImportTest`
Expected: PASS

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/StudentsExport.php app/Http/Controllers/StudentController.php routes/tenant.php tests/Feature/StudentExportImportTest.php
git commit -m "feat: add Excel export for students list"
```

---

### Task 4: Students — import

**Files:**
- Create: `app/Imports/StudentsImport.php`
- Modify: `app/Http/Controllers/StudentController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/StudentExportImportTest.php`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/StudentExportImportTest.php`:

```php
test('student import creates valid rows and skips invalid/duplicate ones, reporting both', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $classroom = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);
    Student::factory()->create(['tenant_id' => $tenant->id, 'nis' => '1001']);

    $file = makeXlsxUploadedFile(
        ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'],
        [
            ['2001', 'Citra', 'P', '2015-01-01', 'Kelas A', 'active'],
            ['1001', 'Duplikat NIS', 'L', '2014-01-01', 'Kelas A', 'active'],
            ['', 'Tanpa NIS', 'L', '2014-01-01', 'Kelas A', 'active'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('students.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('students', ['tenant_id' => $tenant->id, 'nis' => '2001', 'name' => 'Citra', 'classroom_id' => $classroom->id]);
    $this->assertDatabaseCount('students', 2);

    $response->assertInertiaFlash('import_summary');
    $summary = $response->session()->get(\Inertia\Support\SessionKey::FLASH_DATA)['import_summary'];
    expect($summary['created'])->toBe(1);
    expect($summary['skipped'])->toBe(2);
});

test('student import is scoped to the current tenant — a classroom name from another tenant does not resolve', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->create(['tenant_id' => $otherTenant->id, 'name' => 'Kelas Lintas Tenant']);

    $file = makeXlsxUploadedFile(
        ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'],
        [['3001', 'Doni', 'L', '2015-01-01', 'Kelas Lintas Tenant', 'active']],
    );

    $this->actingAsStaff($admin)->post(route('students.import'), ['file' => $file]);

    $this->assertDatabaseHas('students', ['tenant_id' => $tenant->id, 'nis' => '3001', 'classroom_id' => null]);
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=StudentExportImportTest`
Expected: FAIL — route `students.import` doesn't exist.

- [ ] **Step 3: Create the import class**

Create `app/Imports/StudentsImport.php`:

```php
<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    public function model(array $row): Student
    {
        $classroomId = null;
        if (! empty($row['kelas'])) {
            $classroomId = Classroom::where('name', $row['kelas'])->value('id');
        }

        $this->createdCount++;

        return new Student([
            'tenant_id' => Auth::user()->tenant_id,
            'classroom_id' => $classroomId,
            'nis' => (string) $row['nis'],
            'name' => $row['nama'],
            'gender' => strtoupper((string) $row['jenis_kelamin']),
            'birth_date' => $row['tanggal_lahir'] ?? null,
            'status' => $row['status'] ?: 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;

        return [
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nis')->where('tenant_id', $tenantId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'string', 'in:L,P,l,p'],
            'tanggal_lahir' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
```

- [ ] **Step 4: Add `import()` to `StudentController`**

In `app/Http/Controllers/StudentController.php`, add `use App\Imports\StudentsImport;` and `use Maatwebsite\Excel\Validators\Failure;` to the imports, then add this method right after `export()`:

```php
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new StudentsImport;
        Excel::import($import, $request->file('file'));

        $errors = collect($import->failures())
            ->map(fn (Failure $failure) => "Baris {$failure->row()}: ".implode(', ', $failure->errors()))
            ->take(20)
            ->all();

        Inertia::flash('import_summary', [
            'created' => $import->createdCount,
            'skipped' => count($import->failures()),
            'errors' => $errors,
        ]);

        return redirect()->back()->with('success', 'Import santri selesai diproses.');
    }
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right after the `students.export` line added in Task 3:

```php
        Route::get('students/export', [StudentController::class, 'export'])->name('students.export');
        Route::post('students/import', [StudentController::class, 'import'])->name('students.import');
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=StudentExportImportTest`
Expected: PASS (3 tests)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Imports/StudentsImport.php app/Http/Controllers/StudentController.php routes/tenant.php tests/Feature/StudentExportImportTest.php
git commit -m "feat: add Excel import for students list"
```

---

### Task 5: Shared `ImportDialog.vue` component

**Files:**
- Create: `resources/js/components/ImportDialog.vue`

Used by the 4 master-data pages (Students, Teachers, Classrooms, Guardians) — one file instead of duplicating the same modal 4 times.

- [ ] **Step 1: Create the component**

Create `resources/js/components/ImportDialog.vue`:

```vue
<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';

type ImportSummary = {
    created: number;
    skipped: number;
    errors: string[];
};

const props = defineProps<{
    importUrl: string;
    templateUrl: string;
    title: string;
}>();

const isOpen = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm<{ file: File | null }>({
    file: null,
});

const summary = computed(() => {
    const page = usePage().props as {
        flash?: { import_summary?: ImportSummary };
    };

    return page.flash?.import_summary ?? null;
});

function open() {
    form.reset();
    form.clearErrors();
    isOpen.value = true;
}

function close() {
    isOpen.value = false;
}

function onFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    form.file = target.files?.[0] ?? null;
}

function submit() {
    form.post(props.importUrl, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}
</script>

<template>
    <Button variant="outline" @click="open">Import Excel</Button>

    <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4 sm:items-center"
    >
        <div
            class="max-h-[calc(100dvh-2rem)] w-full max-w-md overflow-y-auto rounded-lg border bg-background p-6 shadow-lg"
        >
            <h2 class="mb-4 text-lg font-semibold">{{ title }}</h2>

            <a
                :href="templateUrl"
                class="mb-4 inline-block text-sm text-primary underline"
            >
                Unduh Template
            </a>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="block w-full text-sm"
                        @change="onFileChange"
                    />
                    <p
                        v-if="form.errors.file"
                        class="mt-1 text-sm text-destructive"
                    >
                        {{ form.errors.file }}
                    </p>
                </div>

                <div
                    v-if="summary"
                    class="rounded-md border bg-muted/30 p-3 text-sm"
                >
                    <p>
                        Berhasil ditambahkan:
                        <strong>{{ summary.created }}</strong>
                    </p>
                    <p>Dilewati: <strong>{{ summary.skipped }}</strong></p>
                    <ul
                        v-if="summary.errors.length > 0"
                        class="mt-2 list-disc space-y-1 pl-4 text-destructive"
                    >
                        <li v-for="(err, i) in summary.errors" :key="i">
                            {{ err }}
                        </li>
                    </ul>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="outline" @click="close">
                        Tutup
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !form.file"
                    >
                        Import
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/ImportDialog.vue
git commit -m "feat: add shared ImportDialog component"
```

---

### Task 6: Students — frontend

**Files:**
- Modify: `resources/js/pages/Students/Index.vue`

- [ ] **Step 1: Import `ImportDialog` and add an export button + the dialog**

In `resources/js/pages/Students/Index.vue`, add the import at the top of `<script setup>`:

```ts
import ImportDialog from '@/components/ImportDialog.vue';
```

Add this function next to `filterStudents`/`printSelectedCards`:

```ts
function exportStudents() {
    const params = new URLSearchParams({
        classroom_id: selectedClassroom.value,
        search: searchInput.value,
    });
    window.location.href = `/students/export?${params.toString()}`;
}
```

In the template, in the header actions block (next to the "Cetak Kartu QR" / "Tambah Santri" buttons), add:

```html
<Button variant="outline" @click="exportStudents">
    Export Excel
</Button>
<ImportDialog
    import-url="/students/import"
    template-url="/students/export?template=1"
    title="Import Data Santri"
/>
```

placed so the header block reads:

```html
<div
    class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
>
    <Button variant="outline" @click="printSelectedCards">
        🖨️ Cetak Kartu QR
    </Button>
    <Button variant="outline" @click="exportStudents"> Export Excel </Button>
    <ImportDialog
        import-url="/students/import"
        template-url="/students/export?template=1"
        title="Import Data Santri"
    />
    <Button @click="openCreateModal"> + Tambah Santri </Button>
</div>
```

- [ ] **Step 2: Build and manually verify**

Run: `npm run build`
Then start the app (`composer dev` or equivalent already-running dev server) and visit the Students list page. Confirm:
- "Export Excel" downloads an `.xlsx` file matching the current filter.
- "Import Excel" opens the dialog, "Unduh Template" downloads a headings-only file, and uploading a filled-in copy of that template shows the created/skipped summary.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Students/Index.vue
git commit -m "feat: wire Excel export/import buttons into Students list"
```

---

### Task 7: Teachers — export

**Files:**
- Create: `app/Exports/TeachersExport.php`
- Modify: `app/Http/Controllers/TeacherController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/TeacherExportImportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/TeacherExportImportTest.php`:

```php
<?php

use App\Models\Tenant;
use App\Models\User;

test('teacher export returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'name' => 'Ustadz Fulan']);

    $response = $this->actingAsStaff($admin)->get(route('teachers.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('teacher export is denied for a non-admin', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);

    $this->actingAsStaff($pengajar)->get(route('teachers.export'))->assertForbidden();
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=TeacherExportImportTest`
Expected: FAIL — route `teachers.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/TeachersExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TeachersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, User>  $teachers
     */
    public function __construct(private readonly Collection $teachers) {}

    public function collection(): Collection
    {
        return $this->teachers;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'Email', 'Role'];
    }

    /**
     * @param  User  $user
     * @return array<int, string|null>
     */
    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->role,
        ];
    }
}
```

- [ ] **Step 4: Refactor `TeacherController`**

Replace `app/Http/Controllers/TeacherController.php` in full:

```php
<?php

namespace App\Http\Controllers;

use App\Exports\TeachersExport;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', User::class);

        $teachers = $this->filteredQuery()->get();

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', User::class);

        $teachers = $request->boolean('template')
            ? new Collection
            : $this->filteredQuery()->get();

        return Excel::download(new TeachersExport($teachers), 'data-pengajar.xlsx');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    private function filteredQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return User::where('tenant_id', Auth::user()->tenant_id)->latest();
    }

    public function store(StoreTeacherRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            // Admin-created accounts skip self-registration entirely, so there's
            // no Registered event and no verification email to click — the admin
            // vouches for the email address by typing it in here.
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Pengajar berhasil ditambahkan.');
    }

    public function update(UpdateTeacherRequest $request, User $teacher): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $teacher->update($data);

        return redirect()->back()->with('success', 'Data pengajar berhasil diperbarui.');
    }

    public function destroy(User $teacher): RedirectResponse
    {
        Gate::authorize('delete', $teacher);

        $teacher->delete();

        return redirect()->back()->with('success', 'Pengajar berhasil dihapus.');
    }
}
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `teachers` resource line:

```php
        Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
        Route::resource('teachers', TeacherController::class)->except(['create', 'edit', 'show']);
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=TeacherExportImportTest`
Expected: PASS (2 tests)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/TeachersExport.php app/Http/Controllers/TeacherController.php routes/tenant.php tests/Feature/TeacherExportImportTest.php
git commit -m "feat: add Excel export for teachers list"
```

---

### Task 8: Teachers — import

**Files:**
- Create: `app/Imports/TeachersImport.php`
- Modify: `app/Http/Controllers/TeacherController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/TeacherExportImportTest.php`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/TeacherExportImportTest.php`:

```php
test('teacher import creates accounts with a generated password and skips duplicate emails', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'existing@example.com']);

    $file = makeXlsxUploadedFile(
        ['Nama', 'Email', 'Role'],
        [
            ['Ustadzah Aminah', 'aminah@example.com', 'pengajar'],
            ['Duplikat Email', 'existing@example.com', 'pengajar'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('teachers.import'), ['file' => $file]);

    $response->assertRedirect();
    $newTeacher = User::firstWhere('email', 'aminah@example.com');
    expect($newTeacher)->not->toBeNull();
    expect($newTeacher->tenant_id)->toBe($tenant->id);
    expect($newTeacher->role)->toBe('pengajar');
    expect($newTeacher->password)->not->toBeNull();
    $this->assertDatabaseCount('users', 3); // admin + existing + new import
});

test('teacher import is denied for a non-admin', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar']);

    $file = makeXlsxUploadedFile(['Nama', 'Email', 'Role'], [['X', 'x@example.com', 'pengajar']]);

    $this->actingAsStaff($pengajar)->post(route('teachers.import'), ['file' => $file])->assertForbidden();
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=TeacherExportImportTest`
Expected: FAIL — route `teachers.import` doesn't exist.

- [ ] **Step 3: Create the import class**

Create `app/Imports/TeachersImport.php`:

```php
<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TeachersImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    public function model(array $row): User
    {
        $this->createdCount++;

        $role = strtolower((string) $row['role']) === 'admin' ? 'admin' : 'pengajar';

        return new User([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $row['nama'],
            'email' => $row['email'],
            'password' => Hash::make(Str::password(12)),
            'role' => $role,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'role' => ['required', 'string', 'in:admin,pengajar,Admin,Pengajar'],
        ];
    }
}
```

- [ ] **Step 4: Add `import()` to `TeacherController`**

Add `use App\Imports\TeachersImport;` and `use Maatwebsite\Excel\Validators\Failure;` to the imports of `app/Http/Controllers/TeacherController.php`, then add this method after `export()`:

```php
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new TeachersImport;
        Excel::import($import, $request->file('file'));

        $errors = collect($import->failures())
            ->map(fn (Failure $failure) => "Baris {$failure->row()}: ".implode(', ', $failure->errors()))
            ->take(20)
            ->all();

        Inertia::flash('import_summary', [
            'created' => $import->createdCount,
            'skipped' => count($import->failures()),
            'errors' => $errors,
        ]);

        return redirect()->back()->with('success', 'Import pengajar selesai diproses.');
    }
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right after the `teachers.export` line:

```php
        Route::get('teachers/export', [TeacherController::class, 'export'])->name('teachers.export');
        Route::post('teachers/import', [TeacherController::class, 'import'])->name('teachers.import');
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --compact --filter=TeacherExportImportTest`
Expected: PASS (4 tests)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Imports/TeachersImport.php app/Http/Controllers/TeacherController.php routes/tenant.php tests/Feature/TeacherExportImportTest.php
git commit -m "feat: add Excel import for teachers list"
```

---

### Task 9: Teachers — frontend

**Files:**
- Modify: `resources/js/pages/Teachers/Index.vue`

- [ ] **Step 1: Add the import and buttons**

Add to `<script setup>`:

```ts
import ImportDialog from '@/components/ImportDialog.vue';

function exportTeachers() {
    window.location.href = '/teachers/export';
}
```

In the template, change the header actions block from:

```html
<Button class="w-full sm:w-auto" @click="openCreateModal">
    + Tambah Pengajar
</Button>
```

to:

```html
<div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
    <Button variant="outline" @click="exportTeachers"> Export Excel </Button>
    <ImportDialog
        import-url="/teachers/import"
        template-url="/teachers/export?template=1"
        title="Import Data Pengajar"
    />
    <Button @click="openCreateModal"> + Tambah Pengajar </Button>
</div>
```

- [ ] **Step 2: Build and manually verify**

Run: `npm run build`
Visit the Teachers list page. Confirm export downloads, template downloads, and import shows a summary after upload.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Teachers/Index.vue
git commit -m "feat: wire Excel export/import buttons into Teachers list"
```

---

### Task 10: Classrooms — export

**Files:**
- Create: `app/Exports/ClassroomsExport.php`
- Modify: `app/Http/Controllers/ClassroomController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/ClassroomExportImportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ClassroomExportImportTest.php`:

```php
<?php

use App\Models\Classroom;
use App\Models\Tenant;
use App\Models\User;

test('classroom export returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Kelas A']);

    $response = $this->actingAsStaff($admin)->get(route('classrooms.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=ClassroomExportImportTest`
Expected: FAIL — route `classrooms.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/ClassroomsExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ClassroomsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Classroom>  $classrooms
     */
    public function __construct(private readonly Collection $classrooms) {}

    public function collection(): Collection
    {
        return $this->classrooms;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'Level'];
    }

    /**
     * @param  Classroom  $classroom
     * @return array<int, string|null>
     */
    public function map($classroom): array
    {
        return [
            $classroom->name,
            $classroom->level,
        ];
    }
}
```

- [ ] **Step 4: Replace `ClassroomController`**

Replace `app/Http/Controllers/ClassroomController.php` in full:

```php
<?php

namespace App\Http\Controllers;

use App\Exports\ClassroomsExport;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClassroomController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = Classroom::withCount('students')
            ->latest()
            ->get();

        return Inertia::render('Classrooms/Index', [
            'classrooms' => $classrooms,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Classroom::class);

        $classrooms = $request->boolean('template')
            ? new Collection
            : Classroom::latest()->get();

        return Excel::download(new ClassroomsExport($classrooms), 'data-kelas.xlsx');
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        Classroom::create($request->validated());

        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return redirect()->back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $classroom->delete();

        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }
}
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `classrooms` resource line:

```php
        Route::get('classrooms/export', [ClassroomController::class, 'export'])->name('classrooms.export');
        Route::resource('classrooms', ClassroomController::class)->except(['create', 'edit', 'show']);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=ClassroomExportImportTest`
Expected: PASS

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/ClassroomsExport.php app/Http/Controllers/ClassroomController.php routes/tenant.php tests/Feature/ClassroomExportImportTest.php
git commit -m "feat: add Excel export for classrooms list"
```

---

### Task 11: Classrooms — import

**Files:**
- Create: `app/Imports/ClassroomsImport.php`
- Modify: `app/Http/Controllers/ClassroomController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/ClassroomExportImportTest.php`

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/ClassroomExportImportTest.php`:

```php
test('classroom import creates rows and skips ones missing a name', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $file = makeXlsxUploadedFile(
        ['Nama', 'Level'],
        [
            ['Kelas Iqra 1', 'Jilid 1'],
            ['', 'Jilid 2'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('classrooms.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('classrooms', ['tenant_id' => $tenant->id, 'name' => 'Kelas Iqra 1', 'level' => 'Jilid 1']);
    $this->assertDatabaseCount('classrooms', 1);
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=ClassroomExportImportTest`
Expected: FAIL — route `classrooms.import` doesn't exist.

- [ ] **Step 3: Create the import class**

Create `app/Imports/ClassroomsImport.php`:

```php
<?php

namespace App\Imports;

use App\Models\Classroom;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ClassroomsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    public function model(array $row): Classroom
    {
        $this->createdCount++;

        return new Classroom([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $row['nama'],
            'level' => $row['level'] ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 4: Add `import()` to `ClassroomController`**

Add `use App\Imports\ClassroomsImport;` and `use Maatwebsite\Excel\Validators\Failure;` to the imports of `app/Http/Controllers/ClassroomController.php`, then add this method after `export()`:

```php
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Classroom::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new ClassroomsImport;
        Excel::import($import, $request->file('file'));

        $errors = collect($import->failures())
            ->map(fn (Failure $failure) => "Baris {$failure->row()}: ".implode(', ', $failure->errors()))
            ->take(20)
            ->all();

        Inertia::flash('import_summary', [
            'created' => $import->createdCount,
            'skipped' => count($import->failures()),
            'errors' => $errors,
        ]);

        return redirect()->back()->with('success', 'Import kelas selesai diproses.');
    }
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right after the `classrooms.export` line:

```php
        Route::get('classrooms/export', [ClassroomController::class, 'export'])->name('classrooms.export');
        Route::post('classrooms/import', [ClassroomController::class, 'import'])->name('classrooms.import');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=ClassroomExportImportTest`
Expected: PASS (2 tests)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Imports/ClassroomsImport.php app/Http/Controllers/ClassroomController.php routes/tenant.php tests/Feature/ClassroomExportImportTest.php
git commit -m "feat: add Excel import for classrooms list"
```

---

### Task 12: Classrooms — frontend

**Files:**
- Modify: `resources/js/pages/Classrooms/Index.vue`

- [ ] **Step 1: Add export/import UI**

Add to `<script setup>`:

```ts
import ImportDialog from '@/components/ImportDialog.vue';

function exportClassrooms() {
    window.location.href = '/classrooms/export';
}
```

In the template, replace:

```html
<Button class="w-full sm:w-auto" @click="openCreateModal">
    + Tambah Kelas
</Button>
```

with:

```html
<div
    class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
>
    <Button variant="outline" @click="exportClassrooms"> Export Excel </Button>
    <ImportDialog
        import-url="/classrooms/import"
        template-url="/classrooms/export?template=1"
        title="Import Data Kelas"
    />
    <Button @click="openCreateModal"> + Tambah Kelas </Button>
</div>
```

- [ ] **Step 2: Build and manually verify**

Run: `npm run build`
Visit the Classrooms list page and confirm export/import work as in Task 6.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Classrooms/Index.vue
git commit -m "feat: wire Excel export/import buttons into Classrooms list"
```

---

### Task 13: Guardians — export

**Files:**
- Create: `app/Exports/GuardiansExport.php`
- Modify: `app/Http/Controllers/GuardianController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/GuardianExportImportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/GuardianExportImportTest.php`:

```php
<?php

use App\Models\Guardian;
use App\Models\Tenant;
use App\Models\User;

test('guardian export returns an xlsx file', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    Guardian::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Bapak Somad']);

    $response = $this->actingAsStaff($admin)->get(route('guardians.export'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=GuardianExportImportTest`
Expected: FAIL — route `guardians.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/GuardiansExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Guardian;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GuardiansExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Guardian>  $guardians
     */
    public function __construct(private readonly Collection $guardians) {}

    public function collection(): Collection
    {
        return $this->guardians;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'No. HP'];
    }

    /**
     * @param  Guardian  $guardian
     * @return array<int, string|null>
     */
    public function map($guardian): array
    {
        return [
            $guardian->name,
            $guardian->phone,
        ];
    }
}
```

- [ ] **Step 4: Replace `GuardianController`**

Replace `app/Http/Controllers/GuardianController.php` in full:

```php
<?php

namespace App\Http\Controllers;

use App\Exports\GuardiansExport;
use App\Http\Requests\StoreGuardianRequest;
use App\Http\Requests\UpdateGuardianRequest;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuardianController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Guardian::class);

        $guardians = Guardian::with('students')
            ->latest()
            ->get();

        $students = Student::all();

        return Inertia::render('Guardians/Index', [
            'guardians' => $guardians,
            'students' => $students,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Guardian::class);

        $guardians = $request->boolean('template')
            ? new Collection
            : Guardian::latest()->get();

        return Excel::download(new GuardiansExport($guardians), 'data-wali-santri.xlsx');
    }

    public function store(StoreGuardianRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $studentIds = $validated['student_ids'] ?? [];
        unset($validated['student_ids']);

        $guardian = Guardian::create($validated);

        if (! empty($studentIds)) {
            $guardian->students()->sync($studentIds);
        }

        return redirect()->back()->with('success', 'Wali santri berhasil ditambahkan.');
    }

    public function update(UpdateGuardianRequest $request, Guardian $guardian): RedirectResponse
    {
        $validated = $request->validated();
        $studentIds = $validated['student_ids'] ?? [];
        unset($validated['student_ids']);

        $guardian->update($validated);
        $guardian->students()->sync($studentIds);

        return redirect()->back()->with('success', 'Data wali santri berhasil diperbarui.');
    }

    public function destroy(Guardian $guardian): RedirectResponse
    {
        Gate::authorize('delete', $guardian);

        $guardian->delete();

        return redirect()->back()->with('success', 'Data wali santri berhasil dihapus.');
    }
}
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `guardians` resource line:

```php
        Route::get('guardians/export', [GuardianController::class, 'export'])->name('guardians.export');
        Route::resource('guardians', GuardianController::class)->except(['create', 'edit', 'show']);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=GuardianExportImportTest`
Expected: PASS

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/GuardiansExport.php app/Http/Controllers/GuardianController.php routes/tenant.php tests/Feature/GuardianExportImportTest.php
git commit -m "feat: add Excel export for guardians list"
```

---

### Task 14: Guardians — import

**Files:**
- Create: `app/Imports/GuardiansImport.php`
- Modify: `app/Http/Controllers/GuardianController.php`
- Modify: `routes/tenant.php`
- Test: `tests/Feature/GuardianExportImportTest.php`

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/GuardianExportImportTest.php`:

```php
test('guardian import creates rows and skips ones missing a name', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);

    $file = makeXlsxUploadedFile(
        ['Nama', 'No. HP'],
        [
            ['Ibu Aisyah', '081234567890'],
            ['', '089999999999'],
        ],
    );

    $response = $this->actingAsStaff($admin)->post(route('guardians.import'), ['file' => $file]);

    $response->assertRedirect();
    $this->assertDatabaseHas('guardians', ['tenant_id' => $tenant->id, 'name' => 'Ibu Aisyah', 'phone' => '081234567890']);
    $this->assertDatabaseCount('guardians', 1);

    $guardian = Guardian::firstWhere('name', 'Ibu Aisyah');
    expect($guardian->link_token)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=GuardianExportImportTest`
Expected: FAIL — route `guardians.import` doesn't exist.

- [ ] **Step 3: Create the import class**

Create `app/Imports/GuardiansImport.php`:

```php
<?php

namespace App\Imports;

use App\Models\Guardian;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GuardiansImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    public function model(array $row): Guardian
    {
        $this->createdCount++;

        return new Guardian([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $row['nama'],
            'phone' => $row['no_hp'] ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:50'],
        ];
    }
}
```

Note: `Guardian::booted()`'s `creating` hook (already in the model, see `app/Models/Guardian.php`) generates `link_token` automatically — nothing extra needed here.

- [ ] **Step 4: Add `import()` to `GuardianController`**

Add `use App\Imports\GuardiansImport;` and `use Maatwebsite\Excel\Validators\Failure;` to the imports of `app/Http/Controllers/GuardianController.php`, then add this method after `export()`:

```php
    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Guardian::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new GuardiansImport;
        Excel::import($import, $request->file('file'));

        $errors = collect($import->failures())
            ->map(fn (Failure $failure) => "Baris {$failure->row()}: ".implode(', ', $failure->errors()))
            ->take(20)
            ->all();

        Inertia::flash('import_summary', [
            'created' => $import->createdCount,
            'skipped' => count($import->failures()),
            'errors' => $errors,
        ]);

        return redirect()->back()->with('success', 'Import wali santri selesai diproses.');
    }
```

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right after the `guardians.export` line:

```php
        Route::get('guardians/export', [GuardianController::class, 'export'])->name('guardians.export');
        Route::post('guardians/import', [GuardianController::class, 'import'])->name('guardians.import');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=GuardianExportImportTest`
Expected: PASS (2 tests)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Imports/GuardiansImport.php app/Http/Controllers/GuardianController.php routes/tenant.php tests/Feature/GuardianExportImportTest.php
git commit -m "feat: add Excel import for guardians list"
```

---

### Task 15: Guardians — frontend

**Files:**
- Modify: `resources/js/pages/Guardians/Index.vue`

- [ ] **Step 1: Add export/import UI**

Add to `<script setup>`:

```ts
import ImportDialog from '@/components/ImportDialog.vue';

function exportGuardians() {
    window.location.href = '/guardians/export';
}
```

In the template, replace:

```html
<Button class="w-full sm:w-auto" @click="openCreateModal">
    + Tambah Wali Santri
</Button>
```

with:

```html
<div
    class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center"
>
    <Button variant="outline" @click="exportGuardians"> Export Excel </Button>
    <ImportDialog
        import-url="/guardians/import"
        template-url="/guardians/export?template=1"
        title="Import Data Wali Santri"
    />
    <Button @click="openCreateModal"> + Tambah Wali Santri </Button>
</div>
```

- [ ] **Step 2: Build and manually verify**

Run: `npm run build`
Visit the Guardians list page and confirm export/import work as in Task 6.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Guardians/Index.vue
git commit -m "feat: wire Excel export/import buttons into Guardians list"
```

---

### Task 16: Invoices — export (export-only)

**Files:**
- Create: `app/Exports/InvoicesExport.php`
- Modify: `app/Http/Controllers/InvoiceController.php`
- Modify: `routes/tenant.php`
- Modify: `resources/js/pages/Invoices/Index.vue`
- Test: `tests/Feature/InvoiceExportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/InvoiceExportTest.php`:

```php
<?php

use App\Models\Invoice;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('invoice export returns an xlsx file honoring the status filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Invoice::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'status' => 'unpaid']);
    Invoice::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'status' => 'paid']);

    $response = $this->actingAsStaff($admin)->get(route('invoices.export', ['status' => 'paid']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=InvoiceExportTest`
Expected: FAIL — route `invoices.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/InvoicesExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InvoicesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Invoice>  $invoices
     */
    public function __construct(private readonly Collection $invoices) {}

    public function collection(): Collection
    {
        return $this->invoices;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Periode', 'Nama Santri', 'Kelas', 'Jumlah', 'Jatuh Tempo', 'Status'];
    }

    /**
     * @param  Invoice  $invoice
     * @return array<int, string|null>
     */
    public function map($invoice): array
    {
        return [
            $invoice->period,
            $invoice->student?->name,
            $invoice->student?->classroom?->name,
            (string) $invoice->amount,
            $invoice->due_date,
            $invoice->status,
        ];
    }
}
```

- [ ] **Step 4: Add `filteredQuery()`/`export()` to `InvoiceController`**

In `app/Http/Controllers/InvoiceController.php`, add these imports: `use App\Exports\InvoicesExport;`, `use Illuminate\Database\Eloquent\Builder;`, `use Maatwebsite\Excel\Facades\Excel;`, `use Symfony\Component\HttpFoundation\BinaryFileResponse;`.

Replace the `index()` method with:

```php
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Invoice::class);

        $invoices = $this->filteredQuery($request)->get();
        $classrooms = Classroom::all();
        $students = Student::where('status', 'active')->get();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'classrooms' => $classrooms,
            'students' => $students,
            'filters' => $request->only(['status', 'period']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Invoice::class);

        $invoices = $this->filteredQuery($request)->get();

        return Excel::download(new InvoicesExport($invoices), 'data-tagihan-spp.xlsx');
    }

    /**
     * @return Builder<Invoice>
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = Invoice::with(['student.classroom', 'payments.verifier'])
            ->latest('due_date');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        return $query;
    }
```

(Leave `store()`, `batchGenerate()`, `verifyPayment()`, and the two `notify*` private methods exactly as they are — this task only touches `index()` and adds `export()`/`filteredQuery()`.)

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `invoices` `GET` line:

```php
        Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=InvoiceExportTest`
Expected: PASS

- [ ] **Step 7: Wire the frontend button**

`resources/js/pages/Invoices/Index.vue` has no client-side filter UI today (no `router.get` calls, no filter `ref()`s — the `filters` prop it receives is currently unused by the template). So the export button doesn't need to forward any querystring; it exports everything visible in the `invoices` prop's underlying query. Add to `<script setup>`:

```ts
function exportInvoices() {
    window.location.href = '/invoices/export';
}
```

Add an "Export Excel" `<Button variant="outline" @click="exportInvoices">Export Excel</Button>` next to the existing header action button(s).

- [ ] **Step 8: Build and manually verify**

Run: `npm run build`
Visit the Invoices list page and confirm the export button downloads a filtered `.xlsx`.

- [ ] **Step 9: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/InvoicesExport.php app/Http/Controllers/InvoiceController.php routes/tenant.php resources/js/pages/Invoices/Index.vue tests/Feature/InvoiceExportTest.php
git commit -m "feat: add Excel export for invoices list"
```

---

### Task 17: Achievements — export (export-only)

**Files:**
- Create: `app/Exports/AchievementsExport.php`
- Modify: `app/Http/Controllers/AchievementController.php`
- Modify: `routes/tenant.php`
- Modify: `resources/js/pages/Achievements/Index.vue`
- Test: `tests/Feature/AchievementExportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AchievementExportTest.php`:

```php
<?php

use App\Models\Achievement;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('achievement export returns an xlsx file honoring the category filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Achievement::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'category' => 'hafalan']);

    $response = $this->actingAsStaff($admin)->get(route('achievements.export', ['category' => 'hafalan']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=AchievementExportTest`
Expected: FAIL — route `achievements.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/AchievementsExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AchievementsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Achievement>  $achievements
     */
    public function __construct(private readonly Collection $achievements) {}

    public function collection(): Collection
    {
        return $this->achievements;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Tanggal', 'Nama Santri', 'Kategori', 'Judul / Materi', 'Nilai', 'Catatan'];
    }

    /**
     * @param  Achievement  $achievement
     * @return array<int, string|null>
     */
    public function map($achievement): array
    {
        return [
            $achievement->achieved_at,
            $achievement->student?->name,
            $achievement->category,
            $achievement->title,
            $achievement->score !== null ? (string) $achievement->score : null,
            $achievement->note,
        ];
    }
}
```

- [ ] **Step 4: Add `filteredQuery()`/`export()` to `AchievementController`**

In `app/Http/Controllers/AchievementController.php`, add these imports: `use App\Exports\AchievementsExport;`, `use Illuminate\Database\Eloquent\Builder;`, `use Maatwebsite\Excel\Facades\Excel;`, `use Symfony\Component\HttpFoundation\BinaryFileResponse;`.

Replace the `index()` method with:

```php
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Achievement::class);

        $achievements = $this->filteredQuery($request)->get();
        $students = Student::all();

        return Inertia::render('Achievements/Index', [
            'achievements' => $achievements,
            'students' => $students,
            'filters' => $request->only(['student_id', 'category']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Achievement::class);

        $achievements = $this->filteredQuery($request)->get();

        return Excel::download(new AchievementsExport($achievements), 'data-prestasi.xlsx');
    }

    /**
     * @return Builder<Achievement>
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = Achievement::with(['student.classroom', 'recorder'])
            ->latest('achieved_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        return $query;
    }
```

(Leave `store()`, `update()`, `destroy()` untouched.)

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `achievements` resource line:

```php
        Route::get('achievements/export', [AchievementController::class, 'export'])->name('achievements.export');
        Route::resource('achievements', AchievementController::class)->except(['create', 'edit', 'show']);
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=AchievementExportTest`
Expected: PASS

- [ ] **Step 7: Wire the frontend button**

`resources/js/pages/Achievements/Index.vue` has no client-side filter UI today (no `router.get` calls, no filter `ref()`s — the `filters` prop it receives is currently unused). Add to `<script setup>`:

```ts
function exportAchievements() {
    window.location.href = '/achievements/export';
}
```

Add `<Button variant="outline" @click="exportAchievements">Export Excel</Button>` next to the existing header action button.

- [ ] **Step 8: Build and manually verify**

Run: `npm run build`
Visit the Achievements list page and confirm the export button works.

- [ ] **Step 9: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/AchievementsExport.php app/Http/Controllers/AchievementController.php routes/tenant.php resources/js/pages/Achievements/Index.vue tests/Feature/AchievementExportTest.php
git commit -m "feat: add Excel export for achievements list"
```

---

### Task 18: Attendance — export (export-only)

**Files:**
- Create: `app/Exports/AttendancesExport.php`
- Modify: `app/Http/Controllers/AttendanceController.php`
- Modify: `routes/tenant.php`
- Modify: `resources/js/pages/Attendance/Index.vue`
- Test: `tests/Feature/AttendanceExportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/AttendanceExportTest.php`:

```php
<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('attendance export returns an xlsx file honoring the date filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Attendance::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'date' => '2026-07-01']);

    $response = $this->actingAsStaff($admin)->get(route('attendance.export', ['date' => '2026-07-01']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=AttendanceExportTest`
Expected: FAIL — route `attendance.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/AttendancesExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendancesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Attendance>  $attendances
     */
    public function __construct(private readonly Collection $attendances) {}

    public function collection(): Collection
    {
        return $this->attendances;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama Santri', 'Kelas', 'Masuk', 'Pulang', 'Status'];
    }

    /**
     * @param  Attendance  $attendance
     * @return array<int, string|null>
     */
    public function map($attendance): array
    {
        return [
            $attendance->student?->nis,
            $attendance->student?->name,
            $attendance->student?->classroom?->name,
            $attendance->checked_in_at?->format('H:i'),
            $attendance->checked_out_at?->format('H:i'),
            $attendance->status,
        ];
    }
}
```

- [ ] **Step 4: Add `filteredQuery()`/`export()` to `AttendanceController`**

In `app/Http/Controllers/AttendanceController.php`, add these imports: `use App\Exports\AttendancesExport;`, `use Illuminate\Database\Eloquent\Builder;`, `use Maatwebsite\Excel\Facades\Excel;`, `use Symfony\Component\HttpFoundation\BinaryFileResponse;`.

Replace the `index()` method (currently at line 135) with:

```php
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Attendance::class);

        $date = $request->input('date', now()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $attendances = $this->filteredQuery($date, $classroomId)->get();
        $classrooms = Classroom::all();

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'classrooms' => $classrooms,
            'filters' => [
                'date' => $date,
                'classroom_id' => $classroomId,
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Attendance::class);

        $date = $request->input('date', now()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $attendances = $this->filteredQuery($date, $classroomId)->get();

        return Excel::download(new AttendancesExport($attendances), 'data-presensi.xlsx');
    }

    /**
     * @return Builder<Attendance>
     */
    private function filteredQuery(string $date, mixed $classroomId): Builder
    {
        $query = Attendance::with(['student.classroom', 'recorder'])
            ->where('date', $date)
            ->latest();

        if ($classroomId) {
            $query->whereHas('student', fn ($q) => $q->where('classroom_id', $classroomId));
        }

        return $query;
    }
```

(Leave `scanPage()`, `scan()`, `update()` untouched — only `index()` changes, plus the two new methods.)

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `attendance` `GET` line:

```php
        Route::get('attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
        Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=AttendanceExportTest`
Expected: PASS

- [ ] **Step 7: Wire the frontend button**

In `resources/js/pages/Attendance/Index.vue`, add a function using the existing filter refs (`date`/`classroom_id`):

```ts
function exportAttendance() {
    const params = new URLSearchParams({
        date: selectedDate.value,
        classroom_id: selectedClassroom.value,
    });
    window.location.href = `/attendance/export?${params.toString()}`;
}
```

Add `<Button variant="outline" @click="exportAttendance">Export Excel</Button>` next to the existing header action button.

- [ ] **Step 8: Build and manually verify**

Run: `npm run build`
Visit the Attendance list page and confirm the export button works.

- [ ] **Step 9: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/AttendancesExport.php app/Http/Controllers/AttendanceController.php routes/tenant.php resources/js/pages/Attendance/Index.vue tests/Feature/AttendanceExportTest.php
git commit -m "feat: add Excel export for attendance list"
```

---

### Task 19: LeaveRequests — export (export-only)

**Files:**
- Create: `app/Exports/LeaveRequestsExport.php`
- Modify: `app/Http/Controllers/LeaveRequestController.php`
- Modify: `routes/tenant.php`
- Modify: `resources/js/pages/LeaveRequests/Index.vue`
- Test: `tests/Feature/LeaveRequestExportTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LeaveRequestExportTest.php`:

```php
<?php

use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('leave request export returns an xlsx file honoring the status filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    LeaveRequest::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'status' => 'pending']);

    $response = $this->actingAsStaff($admin)->get(route('leave-requests.export', ['status' => 'pending']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test --compact --filter=LeaveRequestExportTest`
Expected: FAIL — route `leave-requests.export` doesn't exist.

- [ ] **Step 3: Create the export class**

Create `app/Exports/LeaveRequestsExport.php`:

```php
<?php

namespace App\Exports;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveRequestsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, LeaveRequest>  $leaveRequests
     */
    public function __construct(private readonly Collection $leaveRequests) {}

    public function collection(): Collection
    {
        return $this->leaveRequests;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama Santri', 'Jenis Izin', 'Rentang Tanggal', 'Alasan', 'Status'];
    }

    /**
     * @param  LeaveRequest  $leaveRequest
     * @return array<int, string|null>
     */
    public function map($leaveRequest): array
    {
        return [
            $leaveRequest->student?->name,
            $leaveRequest->type,
            "{$leaveRequest->start_date} s/d {$leaveRequest->end_date}",
            $leaveRequest->reason,
            $leaveRequest->status,
        ];
    }
}
```

- [ ] **Step 4: Add `filteredQuery()`/`export()` to `LeaveRequestController`**

In `app/Http/Controllers/LeaveRequestController.php`, add these imports: `use App\Exports\LeaveRequestsExport;`, `use Illuminate\Database\Eloquent\Builder;`, `use Maatwebsite\Excel\Facades\Excel;`, `use Symfony\Component\HttpFoundation\BinaryFileResponse;`.

Replace the `index()` method with:

```php
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $leaveRequests = $this->filteredQuery($request)->get();
        $students = Student::where('status', 'active')->get();

        return Inertia::render('LeaveRequests/Index', [
            'leaveRequests' => $leaveRequests,
            'students' => $students,
            'filters' => $request->only(['status']),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $leaveRequests = $this->filteredQuery($request)->get();

        return Excel::download(new LeaveRequestsExport($leaveRequests), 'data-perizinan.xlsx');
    }

    /**
     * @return Builder<LeaveRequest>
     */
    private function filteredQuery(Request $request): Builder
    {
        $query = LeaveRequest::with(['student.classroom', 'reviewer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }
```

(Leave `store()` and `review()` untouched.)

- [ ] **Step 5: Add the route**

In `routes/tenant.php`, right before the `leave-requests` `GET` line:

```php
        Route::get('leave-requests/export', [LeaveRequestController::class, 'export'])->name('leave-requests.export');
        Route::get('leave-requests', [LeaveRequestController::class, 'index'])->name('leave-requests.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --compact --filter=LeaveRequestExportTest`
Expected: PASS

- [ ] **Step 7: Wire the frontend button**

`resources/js/pages/LeaveRequests/Index.vue` has no client-side filter UI today (no `router.get` calls, no filter `ref()`s — the `filters` prop it receives is currently unused). Add to `<script setup>`:

```ts
function exportLeaveRequests() {
    window.location.href = '/leave-requests/export';
}
```

Add `<Button variant="outline" @click="exportLeaveRequests">Export Excel</Button>` next to the existing header action button.

- [ ] **Step 8: Build and manually verify**

Run: `npm run build`
Visit the LeaveRequests list page and confirm the export button works.

- [ ] **Step 9: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Exports/LeaveRequestsExport.php app/Http/Controllers/LeaveRequestController.php routes/tenant.php resources/js/pages/LeaveRequests/Index.vue tests/Feature/LeaveRequestExportTest.php
git commit -m "feat: add Excel export for leave requests list"
```

---

### Task 20: Final verification

**Files:** none (verification only)

- [ ] **Step 1: Full backend check**

Run: `composer test`
Expected: `config:clear` succeeds, Pint reports no style issues, PHPStan (Larastan level 7) passes, and every Pest test (including the 8 new files from this plan) passes.

- [ ] **Step 2: Full frontend check**

Run: `npm run lint && npm run types:check && npm run build`
Expected: no lint errors, no TypeScript errors, build succeeds.

- [ ] **Step 3: Route sanity check**

Run: `php artisan route:list --path=export`
Expected: 8 `GET .../export` routes listed (students, teachers, classrooms, guardians, invoices, achievements, attendance, leave-requests).

Run: `php artisan route:list --path=import`
Expected: 4 `POST .../import` routes listed (students, teachers, classrooms, guardians).

- [ ] **Step 4: Commit if anything was fixed during verification**

Only if Step 1–3 required fixes:

```bash
git add -A
git commit -m "fix: address verification issues in Excel export/import feature"
```
