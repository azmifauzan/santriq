<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Exports\Templates\StudentsTemplateExport;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Imports\StudentsImport;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\QrCodeService;
use App\Support\CardPrintSettings;
use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;
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

        if ($request->boolean('template')) {
            return Excel::download(new StudentsTemplateExport, 'template-data-santri.xlsx');
        }

        $students = $this->filteredQuery($request)->get();

        return Excel::download(new StudentsExport($students), 'data-santri.xlsx');
    }

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

        $tenant = CurrentTenant::get();
        $landing = $tenant->settings['landing'] ?? [];

        $students = $query->get()->map(function (Student $student) use ($tenant) {
            return [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
                'classroom_name' => $student->classroom ? $student->classroom->name : 'Tanpa Kelas',
                'qr_svg' => QrCodeService::generateSvg($student->qr_token, 180),
                'tenant_name' => $tenant->name,
            ];
        });

        return Inertia::render('Students/PrintCards', [
            'students' => $students,
            'cardSettings' => CardPrintSettings::resolve($tenant),
            'logoPath' => $landing['logo_path'] ?? null,
        ]);
    }
}
