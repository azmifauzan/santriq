<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Models\LeaveRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuardianLeaveRequestController extends Controller
{
    public function index(): Response
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();
        $studentIds = $guardian->students()->pluck('students.id');

        return Inertia::render('guardian/LeaveRequests', [
            'students' => $guardian->students()->get(['students.id', 'students.name', 'students.nis']),
            'leaveRequests' => LeaveRequest::whereIn('student_id', $studentIds)
                ->with('student')
                ->latest()
                ->get(['id', 'student_id', 'type', 'start_date', 'end_date', 'reason', 'status', 'created_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Guardian $guardian */
        $guardian = Auth::guard('guardian')->user();

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:sakit,izin'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ]);

        $student = $guardian->students()->where('students.id', $validated['student_id'])->first();
        abort_unless($student !== null, 403);

        LeaveRequest::create([
            'tenant_id' => $student->tenant_id,
            'student_id' => $student->id,
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan izin berhasil dikirim.');
    }
}
