<?php

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Guardian;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ReportCardBuilder;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->teacher = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'pengajar']);
    $this->student = Student::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAsStaff($this->teacher);
    $this->period = ['start_date' => '2026-11-01', 'end_date' => '2026-11-30'];
});

test('report preview contains only the students records in the chosen period and is not published', function () {
    Attendance::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id, 'date' => '2026-11-02', 'status' => 'hadir']);
    Achievement::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id, 'achieved_at' => '2026-11-03', 'learning_method' => 'Iqra', 'level' => '3', 'page_start' => 12, 'fluency' => 'perlu_latihan']);
    Achievement::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id, 'achieved_at' => '2026-10-31']);
    Achievement::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->get(route('report-cards.preview', ['student' => $this->student->id, ...$this->period]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->component('Reports/ReportCard')->where('reportCard', null)
        ->where('snapshot.student.id', $this->student->id)
        ->where('snapshot.attendance.hadir', 1)->where('snapshot.attendance.alpa', 0)
        ->has('snapshot.achievements', 1)->where('snapshot.achievements.0.page_start', 12)
        ->has('snapshotHash'));
    $this->assertDatabaseCount('report_cards', 0);
});

test('teacher can publish a reviewed report and published data remains unchanged after source edits', function () {
    $achievement = Achievement::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id, 'achieved_at' => '2026-11-03', 'title' => 'Iqra halaman 12']);
    $preview = $this->get(route('report-cards.preview', ['student' => $this->student->id, ...$this->period]))->assertOk();
    $hash = $preview->viewData('page')['props']['snapshotHash'];

    $this->post(route('report-cards.store', ['student' => $this->student->id]), [
        ...$this->period, 'snapshot_hash' => $hash, 'teacher_note' => 'Latihan panjang pendek di rumah.',
        'snapshot' => ['student' => ['name' => 'Forged name']], 'tenant_id' => 999999,
    ])->assertRedirect();

    $report = ReportCard::firstOrFail();
    expect($report->tenant_id)->toBe($this->tenant->id)
        ->and($report->published_by)->toBe($this->teacher->id)
        ->and($report->teacher_note)->toBe('Latihan panjang pendek di rumah.');
    $achievement->update(['title' => 'Sudah berubah']);
    $this->student->update(['name' => 'Nama baru']);

    $this->get(route('report-cards.show', $report))->assertOk()->assertInertia(fn ($page) => $page
        ->where('snapshot.achievements.0.title', 'Iqra halaman 12')
        ->where('snapshot.student.name', $report->snapshot['student']['name'])
        ->where('reportCard.teacher_note', 'Latihan panjang pendek di rumah.'));
});

test('publication rejects stale previews', function () {
    $snapshot = app(ReportCardBuilder::class)->build($this->student, ...array_values($this->period));
    $hash = app(ReportCardBuilder::class)->hash($snapshot);
    Achievement::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id, 'achieved_at' => '2026-11-03']);

    $this->post(route('report-cards.store', ['student' => $this->student->id]), [...$this->period, 'snapshot_hash' => $hash])
        ->assertSessionHasErrors('snapshot_hash');
    $this->assertDatabaseCount('report_cards', 0);
});

test('repeated publication keeps a single report and does not overwrite the first note', function () {
    $builder = app(ReportCardBuilder::class);
    $data = [...$this->period, 'snapshot_hash' => $builder->hash($builder->build($this->student, ...array_values($this->period)))];
    $url = route('report-cards.store', ['student' => $this->student->id]);
    $this->post($url, [...$data, 'teacher_note' => 'Pertama'])->assertRedirect();
    $this->post($url, [...$data, 'teacher_note' => 'Kedua'])->assertRedirect();
    $this->assertDatabaseCount('report_cards', 1);
    expect(ReportCard::firstOrFail()->teacher_note)->toBe('Pertama');
});

test('guardian can read published reports only for their own children and tenant', function () {
    $own = ReportCard::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id]);
    $other = ReportCard::factory()->create(['tenant_id' => $this->tenant->id]);
    $foreignTenant = Tenant::factory()->create();
    $foreign = ReportCard::factory()->create(['tenant_id' => $foreignTenant->id]);
    $guardian = Guardian::factory()->create(['tenant_id' => $this->tenant->id]);
    $guardian->students()->attach($this->student->id, ['relation' => 'Ibu']);
    $this->actingAs($guardian, 'guardian');
    $host = "http://{$this->tenant->subdomain}.santriq.test";

    $this->get("{$host}/wali/portal/rapor/{$own->id}")->assertOk()->assertInertia(fn ($page) => $page->where('isGuardian', true));
    $this->get("{$host}/wali/portal/rapor/{$other->id}")->assertForbidden();
    $this->get("{$host}/wali/portal/rapor/{$foreign->id}")->assertNotFound();
    $this->get("http://{$foreignTenant->subdomain}.santriq.test/wali/portal/rapor/{$own->id}")->assertForbidden();
    $this->get("{$host}/wali/portal/anak/{$this->student->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->has('reportCards', 1)->where('reportCards.0.id', $own->id));
});

test('staff cannot preview publish read or revoke another tenants reports', function () {
    $otherTenant = Tenant::factory()->create();
    $otherStudent = Student::factory()->create(['tenant_id' => $otherTenant->id]);
    $report = ReportCard::factory()->create(['tenant_id' => $otherTenant->id, 'student_id' => $otherStudent->id]);
    $this->get(route('report-cards.preview', ['student' => $otherStudent->id]))->assertNotFound();
    $this->post(route('report-cards.store', ['student' => $otherStudent->id]), $this->period)->assertNotFound();
    $this->get(route('report-cards.show', $report))->assertNotFound();
    $this->delete(route('report-cards.destroy', $report))->assertNotFound();
});

test('revoking a report removes it from the guardian portal', function () {
    $report = ReportCard::factory()->create(['tenant_id' => $this->tenant->id, 'student_id' => $this->student->id]);
    $this->delete(route('report-cards.destroy', $report))->assertRedirect();
    $this->assertDatabaseMissing('report_cards', ['id' => $report->id]);
    $guardian = Guardian::factory()->create(['tenant_id' => $this->tenant->id]);
    $guardian->students()->attach($this->student->id, ['relation' => 'Ibu']);
    $this->actingAs($guardian, 'guardian')->get("http://{$this->tenant->subdomain}.santriq.test/wali/portal/rapor/{$report->id}")->assertNotFound();
});

test('report periods and publication review are validated', function () {
    $this->get(route('report-cards.preview', ['student' => $this->student->id, 'start_date' => '2026-11-30', 'end_date' => '2026-11-01']))->assertSessionHasErrors('end_date');
    $this->post(route('report-cards.store', ['student' => $this->student->id]), $this->period)->assertSessionHasErrors('snapshot_hash');
    $this->assertDatabaseCount('report_cards', 0);
});

test('published report date follows the tenant timezone', function () {
    $this->tenant->update(['timezone' => 'Asia/Jakarta']);
    $report = ReportCard::factory()->create([
        'tenant_id' => $this->tenant->id,
        'student_id' => $this->student->id,
        'published_at' => '2026-11-03 20:00:00',
    ]);

    $this->get(route('report-cards.show', $report))->assertOk()
        ->assertInertia(fn ($page) => $page->where('reportCard.published_date', '2026-11-04'));
});
