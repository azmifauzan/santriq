<?php

namespace App\Http\Controllers;

use App\Exports\AttendancesExport;
use App\Jobs\SendTelegramMessage;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceController extends Controller
{
    public function scanPage(): Response
    {
        Gate::authorize('viewAny', Attendance::class);

        return Inertia::render('Attendance/Scan');
    }

    public function scan(Request $request): JsonResponse
    {
        Gate::authorize('create', Attendance::class);

        $request->validate([
            'qr_token' => ['required_without:nis', 'nullable', 'string'],
            'nis' => ['required_without:qr_token', 'nullable', 'string'],
        ]);

        $studentQuery = Student::where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'active');

        if ($request->filled('qr_token')) {
            $studentQuery->where('qr_token', $request->input('qr_token'));
            $notFoundMessage = 'Kode QR tidak dikenali atau santri tidak aktif.';
        } else {
            $studentQuery->where('nis', $request->input('nis'));
            $notFoundMessage = 'NIS tidak ditemukan atau santri tidak aktif.';
        }

        $student = $studentQuery->first();

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => $notFoundMessage,
            ], 404);
        }

        $today = now()->format('Y-m-d');
        $attendance = Attendance::where('tenant_id', Auth::user()->tenant_id)
            ->where('student_id', $student->id)
            ->where('date', $today)
            ->first();

        $dedupMinutes = (int) (Auth::user()->tenant?->settings['dedup_minutes'] ?? 5);

        if (! $attendance) {
            $attendance = Attendance::create([
                'tenant_id' => Auth::user()->tenant_id,
                'student_id' => $student->id,
                'date' => $today,
                'checked_in_at' => now(),
                'status' => 'hadir',
                'recorded_by' => Auth::id(),
            ]);

            $checkInTime = $attendance->checked_in_at?->format('H:i') ?? now()->format('H:i');

            foreach ($student->guardians as $guardian) {
                if (! empty($guardian->telegram_chat_id)) {
                    SendTelegramMessage::dispatch(
                        $guardian,
                        "🔔 <b>Notifikasi Presensi Santri</b>\n\nSantri <b>{$student->name}</b> telah melakukan presensi <b>MASUK</b> pada pukul <b>{$checkInTime}</b> WIB."
                    );
                }
            }

            return response()->json([
                'success' => true,
                'action' => 'check_in',
                'message' => "Presensi MASUK berhasil: {$student->name}",
                'student' => $student,
                'time' => $checkInTime,
            ]);
        }

        if ($attendance->checked_out_at !== null) {
            return response()->json([
                'success' => true,
                'action' => 'already_done',
                'message' => "{$student->name} sudah mencatat presensi masuk & pulang hari ini.",
                'student' => $student,
            ]);
        }

        $minsSinceCheckin = $attendance->checked_in_at
            ? Carbon::parse($attendance->checked_in_at)->diffInMinutes(now())
            : 999;

        $checkInTime = $attendance->checked_in_at?->format('H:i') ?? '-';

        if ($minsSinceCheckin < $dedupMinutes) {
            return response()->json([
                'success' => true,
                'action' => 'deduplicated',
                'message' => "Presensi masuk {$student->name} sudah tercatat baru saja.",
                'student' => $student,
                'time' => $checkInTime,
            ]);
        }

        $now = now();
        $attendance->update([
            'checked_out_at' => $now,
        ]);

        $checkOutTime = $now->format('H:i');

        foreach ($student->guardians as $guardian) {
            if (! empty($guardian->telegram_chat_id)) {
                SendTelegramMessage::dispatch(
                    $guardian,
                    "🔔 <b>Notifikasi Presensi Santri</b>\n\nSantri <b>{$student->name}</b> telah melakukan presensi <b>PULANG</b> pada pukul <b>{$checkOutTime}</b> WIB."
                );
            }
        }

        return response()->json([
            'success' => true,
            'action' => 'check_out',
            'message' => "Presensi PULANG berhasil: {$student->name}",
            'student' => $student,
            'time' => $checkOutTime,
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Attendance::class);

        $date = $request->input('date', now()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $attendances = $this->filteredQuery($date, $classroomId)->get();
        $classrooms = Classroom::all();

        return Inertia::render('Attendance/Index', [
            'attendances' => $attendances,
            'classrooms' => $classrooms,
            'filters' => [
                'date' => $date,
                'classroom_id' => $classroomId,
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Attendance::class);

        $date = $request->input('date', now()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $attendances = $this->filteredQuery($date, $classroomId)->get();

        return Excel::download(new AttendancesExport($attendances), 'data-presensi.xlsx');
    }

    /**
     * @return Builder<Attendance>
     */
    private function filteredQuery(string $date, mixed $classroomId): Builder
    {
        $query = Attendance::with(['student.classroom', 'recorder'])
            ->where('date', $date)
            ->latest();

        if ($classroomId) {
            $query->whereHas('student', fn ($q) => $q->where('classroom_id', $classroomId));
        }

        return $query;
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('update', $attendance);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:hadir,izin,sakit,alpa'],
            'checked_in_at' => ['nullable', 'date'],
            'checked_out_at' => ['nullable', 'date'],
        ]);

        $attendance->update($validated);

        return redirect()->back()->with('success', 'Koreksi presensi berhasil disimpan.');
    }
}
