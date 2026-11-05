<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuardianPortalController extends Controller
{
    public function index(): Response
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();

        $students = $guardian->students()
            ->with('classroom')
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'classroom' => $student->classroom?->name,
                'today_status' => Attendance::where('student_id', $student->id)
                    ->where('date', now()->format('Y-m-d'))
                    ->value('status'),
            ]);

        return Inertia::render('guardian/Portal', [
            'guardian' => ['name' => $guardian->name],
            'students' => $students,
        ]);
    }

    public function show(Student $student): Response
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();

        abort_unless(
            $guardian->students()->where('students.id', $student->id)->exists(),
            403
        );

        return Inertia::render('guardian/StudentDetail', [
            'student' => ['id' => $student->id, 'name' => $student->name, 'nis' => $student->nis],
            'attendances' => Attendance::where('student_id', $student->id)
                ->latest('date')
                ->take(30)
                ->get(['date', 'checked_in_at', 'checked_out_at', 'status']),
            'achievements' => Achievement::where('student_id', $student->id)
                ->latest('achieved_at')->latest('id')
                ->take(20)
                ->get(['category', 'title', 'note', 'score', 'achieved_at', 'learning_method', 'level', 'page_start', 'page_end', 'surah', 'ayah_start', 'ayah_end', 'activity_type', 'fluency', 'practice_note']),
            'reportCards' => ReportCard::where('tenant_id', $guardian->tenant_id)
                ->where('student_id', $student->id)->latest('published_at')
                ->get(['id', 'start_date', 'end_date']),
        ]);
    }
}
