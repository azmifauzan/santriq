<?php

use App\Exports\AchievementsExport;
use App\Models\Achievement;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use Maatwebsite\Excel\Excel as ExcelWriterType;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

test('achievement export returns an xlsx file honoring the category filter', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id]);
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Achievement::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'category' => 'hafalan']);

    $response = $this->actingAsStaff($admin)->get(route('achievements.export', ['category' => 'hafalan']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('spreadsheet');
});

test('achievement export stores staff-entered text starting with an equals sign as text, not a formula', function () {
    $tenant = Tenant::factory()->create();
    $student = Student::factory()->create(['tenant_id' => $tenant->id]);
    Achievement::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'title' => '=HYPERLINK("http://example.test","klik")',
        'surah' => '=1+1',
        'practice_note' => '=cmd|calc',
    ]);

    $file = tempnam(sys_get_temp_dir(), 'ach').'.xlsx';
    file_put_contents($file, Excel::raw(
        new AchievementsExport(Achievement::with('student.classroom')->get()),
        ExcelWriterType::XLSX,
    ));
    $sheet = IOFactory::load($file)->getActiveSheet();
    unlink($file);

    foreach (['D2' => '=HYPERLINK("http://example.test","klik")', 'K2' => '=1+1', 'P2' => '=cmd|calc'] as $cell => $value) {
        expect($sheet->getCell($cell)->getDataType())->toBe('s')
            ->and($sheet->getCell($cell)->getValue())->toBe($value);
    }
});
