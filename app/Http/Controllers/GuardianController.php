<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuardianRequest;
use App\Http\Requests\UpdateGuardianRequest;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

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
