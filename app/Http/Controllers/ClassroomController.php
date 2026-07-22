<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

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
