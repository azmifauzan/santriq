<?php

namespace App\Http\Controllers;

use App\Exports\TeachersExport;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Imports\TeachersImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\Failure;
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

    /**
     * @return Builder<User>
     */
    private function filteredQuery(): Builder
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
