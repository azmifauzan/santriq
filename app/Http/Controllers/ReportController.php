<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Attendance::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $studentsQuery = Student::with('classroom');
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        $students = $studentsQuery->get();

        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();

        $rekap = $students->map(function ($student) use ($attendances) {
            $studentAtts = $attendances->where('student_id', $student->id);

            return [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'classroom_name' => $student->classroom ? $student->classroom->name : '-',
                'hadir' => $studentAtts->where('status', 'hadir')->count(),
                'izin' => $studentAtts->where('status', 'izin')->count(),
                'sakit' => $studentAtts->where('status', 'sakit')->count(),
                'alpa' => $studentAtts->where('status', 'alpa')->count(),
                'total' => $studentAtts->count(),
            ];
        });

        $classrooms = Classroom::all();

        return Inertia::render('Reports/Index', [
            'rekap' => $rekap,
            'classrooms' => $classrooms,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'classroom_id' => $classroomId,
            ],
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', Attendance::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $classroomId = $request->input('classroom_id');

        $studentsQuery = Student::with('classroom');
        if ($classroomId) {
            $studentsQuery->where('classroom_id', $classroomId);
        }
        $students = $studentsQuery->get();
        $attendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();

        $fileName = "rekap-presensi-{$startDate}-sd-{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($students, $attendances) {
            $handle = fopen('php://output', 'w');
            if (is_resource($handle)) {
                fputcsv($handle, ['NIS', 'Nama Santri', 'Kelas', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Total Presensi']);

                foreach ($students as $student) {
                    $stAtts = $attendances->where('student_id', $student->id);
                    fputcsv($handle, [
                        $student->nis,
                        $student->name,
                        $student->classroom ? $student->classroom->name : '-',
                        $stAtts->where('status', 'hadir')->count(),
                        $stAtts->where('status', 'izin')->count(),
                        $stAtts->where('status', 'sakit')->count(),
                        $stAtts->where('status', 'alpa')->count(),
                        $stAtts->count(),
                    ]);
                }

                fclose($handle);
            }
        }, 200, $headers);
    }
}
