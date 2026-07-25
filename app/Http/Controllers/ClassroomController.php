<?php

namespace App\Http\Controllers;

use App\Exports\ClassroomsExport;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Imports\ClassroomsImport;
use App\Models\Classroom;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;
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
