<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Student::class);

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

        $students = $query->get();
        $classrooms = Classroom::all();
        $guardians = Guardian::all();

        return Inertia::render('Students/Index', [
            'students' => $students,
            'classrooms' => $classrooms,
            'guardians' => $guardians,
            'filters' => $request->only(['classroom_id', 'search']),
        ]);
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
