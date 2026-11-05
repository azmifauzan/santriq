<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Student;

class ReportCardBuilder
{
    /** @return array<string, mixed> */
    public function build(Student $student, string $startDate, string $endDate): array
    {
        $student->load(['classroom', 'tenant']);
        $attendance = Attendance::where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'tenant_name' => $student->tenant->name,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'classroom' => $student->classroom?->name,
            ],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'attendance' => collect(['hadir', 'izin', 'sakit', 'alpa'])
                ->mapWithKeys(fn (string $status) => [$status => (int) ($attendance[$status] ?? 0)])->all(),
            'achievements' => Achievement::where('tenant_id', $student->tenant_id)
                ->where('student_id', $student->id)
                ->whereBetween('achieved_at', [$startDate, $endDate])
                ->orderBy('achieved_at')->orderBy('id')
                ->get(['achieved_at', 'category', 'title', 'score', 'note', 'learning_method', 'level', 'page_start', 'page_end', 'surah', 'ayah_start', 'ayah_end', 'activity_type', 'fluency', 'practice_note'])
                ->toArray(),
        ];
    }

    /** @param array<string, mixed> $snapshot */
    public function hash(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }
}
