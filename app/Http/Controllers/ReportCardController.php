<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportCardRequest;
use App\Models\Guardian;
use App\Models\ReportCard;
use App\Models\Student;
use App\Services\ReportCardBuilder;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ReportCardController extends Controller
{
    public function preview(ReportCardRequest $request, Student $student, ReportCardBuilder $builder): Response
    {
        $data = $request->validated();
        $snapshot = $builder->build($student, $data['start_date'], $data['end_date']);

        return Inertia::render('Reports/ReportCard', [
            'snapshot' => $snapshot,
            'snapshotHash' => $builder->hash($snapshot),
            'reportCard' => null,
            'isGuardian' => false,
            'previousReports' => ReportCard::where('student_id', $student->id)->latest('published_at')
                ->get(['id', 'start_date', 'end_date']),
        ]);
    }

    public function store(ReportCardRequest $request, Student $student, ReportCardBuilder $builder): RedirectResponse
    {
        $data = $request->validated();
        $snapshot = $builder->build($student, $data['start_date'], $data['end_date']);

        if (! hash_equals($builder->hash($snapshot), $data['snapshot_hash'])) {
            throw ValidationException::withMessages(['snapshot_hash' => 'Data berubah sejak pratinjau dibuka. Muat ulang dan periksa rapor sebelum menerbitkan.']);
        }

        $reportCard = ReportCard::firstOrCreate([
            'tenant_id' => $student->tenant_id,
            'student_id' => $student->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ], [
            'snapshot' => [...$snapshot, 'publisher_name' => $request->user('web')?->name],
            'teacher_note' => $data['teacher_note'] ?? null,
            'published_by' => $request->user('web')?->id,
            'published_at' => now(),
        ]);

        return to_route('report-cards.show', $reportCard)->with('success', $reportCard->wasRecentlyCreated
            ? 'Rapor diterbitkan dan dapat dibuka wali di portal.'
            : 'Rapor periode ini sudah terbit. Cabut rapor lama terlebih dahulu jika perlu diperbaiki.');
    }

    public function show(Request $request, ReportCard $reportCard): Response
    {
        Gate::authorize('view', $reportCard);

        return Inertia::render('Reports/ReportCard', [
            'snapshot' => $reportCard->snapshot,
            'snapshotHash' => null,
            'reportCard' => [
                ...$reportCard->only(['id', 'teacher_note', 'published_at']),
                'published_date' => $reportCard->published_at->timezone(CurrentTenant::get()->timezone)->toDateString(),
            ],
            'isGuardian' => $request->user() instanceof Guardian,
            'previousReports' => [],
        ]);
    }

    public function destroy(ReportCard $reportCard): RedirectResponse
    {
        Gate::authorize('delete', $reportCard);
        $studentId = $reportCard->student_id;
        $reportCard->delete();

        return to_route('report-cards.preview', ['student' => $studentId])->with('success', 'Rapor dicabut dari portal wali.');
    }
}
