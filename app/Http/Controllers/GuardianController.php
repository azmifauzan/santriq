<?php

namespace App\Http\Controllers;

use App\Exports\GuardiansExport;
use App\Exports\Templates\GuardiansTemplateExport;
use App\Http\Requests\StoreGuardianRequest;
use App\Http\Requests\UpdateGuardianRequest;
use App\Imports\GuardiansImport;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;
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

        if ($request->boolean('template')) {
            return Excel::download(new GuardiansTemplateExport, 'template-data-wali-santri.xlsx');
        }

        $guardians = Guardian::latest()->get();

        return Excel::download(new GuardiansExport($guardians), 'data-wali-santri.xlsx');
    }

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
