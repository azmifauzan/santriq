<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;
use App\Models\Achievement;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AchievementController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Achievement::class);

        $query = Achievement::with(['student.classroom', 'recorder'])
            ->latest('achieved_at');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $achievements = $query->get();
        $students = Student::all();

        return Inertia::render('Achievements/Index', [
            'achievements' => $achievements,
            'students' => $students,
            'filters' => $request->only(['student_id', 'category']),
        ]);
    }

    public function store(StoreAchievementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['recorded_by'] = Auth::id();

        Achievement::create($data);

        return redirect()->back()->with('success', 'Catatan pencapaian berhasil ditambahkan.');
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement): RedirectResponse
    {
        $achievement->update($request->validated());

        return redirect()->back()->with('success', 'Pencapaian berhasil diperbarui.');
    }

    public function destroy(Achievement $achievement): RedirectResponse
    {
        Gate::authorize('delete', $achievement);

        $achievement->delete();

        return redirect()->back()->with('success', 'Pencapaian berhasil dihapus.');
    }
}
