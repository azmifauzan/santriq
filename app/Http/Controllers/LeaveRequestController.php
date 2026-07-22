<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessage;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LeaveRequest::class);

        $query = LeaveRequest::with(['student.classroom', 'reviewer'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaveRequests = $query->get();
        $students = Student::where('status', 'active')->get();

        return Inertia::render('LeaveRequests/Index', [
            'leaveRequests' => $leaveRequests,
            'students' => $students,
            'filters' => $request->only(['status']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', LeaveRequest::class);

        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'type' => ['required', 'string', 'in:sakit,izin'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ]);

        LeaveRequest::create([
            'tenant_id' => Auth::user()->tenant_id,
            'student_id' => $validated['student_id'],
            'type' => $validated['type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Pengajuan perizinan berhasil dibuat.');
    }

    public function review(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        Gate::authorize('update', $leaveRequest);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
        ]);

        DB::transaction(function () use ($leaveRequest, $validated) {
            $leaveRequest->update([
                'status' => $validated['status'],
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            if ($validated['status'] === 'approved') {
                $start = Carbon::parse($leaveRequest->start_date);
                $end = Carbon::parse($leaveRequest->end_date);

                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $dateStr = $date->format('Y-m-d');

                    Attendance::updateOrCreate(
                        [
                            'tenant_id' => $leaveRequest->tenant_id,
                            'student_id' => $leaveRequest->student_id,
                            'date' => $dateStr,
                        ],
                        [
                            'status' => $leaveRequest->type,
                            'recorded_by' => Auth::id(),
                        ]
                    );
                }
            }
        });

        $this->notifyLeaveReview($leaveRequest);

        $statusStr = $validated['status'] === 'approved' ? 'DISETUJUI' : 'DITOLAK';

        return redirect()->back()->with('success', "Pengajuan izin berhasil {$statusStr}.");
    }

    private function notifyLeaveReview(LeaveRequest $leaveRequest): void
    {
        $student = $leaveRequest->student;
        if (! $student) {
            return;
        }

        $decision = $leaveRequest->status === 'approved' ? 'DISETUJUI' : 'DITOLAK';
        $typeLabel = strtoupper($leaveRequest->type);

        $msg = "📋 <b>Hasil Pengajuan Izin Santri</b>\n\n".
            "Santri: <b>{$student->name}</b>\n".
            "Jenis: <b>{$typeLabel}</b>\n".
            "Tanggal: <b>{$leaveRequest->start_date}</b> s/d <b>{$leaveRequest->end_date}</b>\n".
            "Keputusan: <b>{$decision}</b>";

        foreach ($student->guardians as $guardian) {
            if (! empty($guardian->telegram_chat_id)) {
                SendTelegramMessage::dispatch($guardian, $msg);
            }
        }
    }
}
