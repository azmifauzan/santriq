<?php

use App\Models\Achievement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\LeaveRequest;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Seeds one row per exported entity whose staff-entered text starts with "=".
 *
 * @return array{admin: User, student: Student}
 */
function seedFormulaPayloads(): array
{
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
    $classroom = Classroom::factory()->create(['tenant_id' => $tenant->id, 'name' => '=CLS', 'level' => '=LVL']);
    $student = Student::factory()->create([
        'tenant_id' => $tenant->id,
        'classroom_id' => $classroom->id,
        'name' => '=STU',
        'nis' => '=NIS',
    ]);
    User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'name' => '=TEA']);
    Guardian::factory()->create(['tenant_id' => $tenant->id, 'name' => '=GUA']);
    Attendance::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id]);
    Invoice::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'amount' => 150000]);
    LeaveRequest::factory()->create(['tenant_id' => $tenant->id, 'student_id' => $student->id, 'reason' => '=RSN']);
    Achievement::factory()->create([
        'tenant_id' => $tenant->id,
        'student_id' => $student->id,
        'title' => '=TTL',
        'score' => 80,
        'page_start' => 3,
    ]);

    return ['admin' => $admin, 'student' => $student];
}

/**
 * @return array<int, array<int, array{type: string, value: mixed}>>
 */
function readXlsxCells(string $path): array
{
    // The export's binder is registered globally and would also rewrite values while reading the file back.
    Cell::setValueBinder(new DefaultValueBinder);

    $rows = [];
    foreach (IOFactory::load($path)->getActiveSheet()->getRowIterator() as $row) {
        $cells = [];
        foreach ($row->getCellIterator() as $cell) {
            $cells[] = ['type' => $cell->getDataType(), 'value' => $cell->getValue()];
        }
        $rows[] = $cells;
    }

    return $rows;
}

test('no export writes staff-entered text as a live formula', function (string $route) {
    $admin = seedFormulaPayloads()['admin'];

    $response = $this->actingAsStaff($admin)->get(route($route))->assertOk();
    $cells = collect(readXlsxCells($response->baseResponse->getFile()->getPathname()))->flatten(1);

    expect($cells->where('type', DataType::TYPE_FORMULA))->toBeEmpty()
        ->and($cells->contains(fn ($c) => $c['type'] === DataType::TYPE_STRING && str_starts_with((string) $c['value'], '=')))->toBeTrue();
})->with([
    'achievements' => 'achievements.export',
    'attendance' => 'attendance.export',
    'classrooms' => 'classrooms.export',
    'guardians' => 'guardians.export',
    'invoices' => 'invoices.export',
    'leave requests' => 'leave-requests.export',
    'students' => 'students.export',
    'teachers' => 'teachers.export',
]);

test('numeric export columns stay numeric so they can be summed', function () {
    $admin = seedFormulaPayloads()['admin'];

    $invoices = readXlsxCells($this->actingAsStaff($admin)->get(route('invoices.export'))->baseResponse->getFile()->getPathname());
    $achievements = readXlsxCells($this->actingAsStaff($admin)->get(route('achievements.export'))->baseResponse->getFile()->getPathname());

    expect($invoices[1][3]['type'])->toBe(DataType::TYPE_NUMERIC)
        ->and($invoices[1][3]['value'])->toEqual(150000)
        ->and($achievements[1][4]['type'])->toBe(DataType::TYPE_NUMERIC)
        ->and($achievements[1][4]['value'])->toEqual(80)
        ->and($achievements[1][8]['type'])->toBe(DataType::TYPE_NUMERIC);
});

test('attendance csv report quotes names that would run as formulas', function () {
    $admin = seedFormulaPayloads()['admin'];

    $csv = $this->actingAsStaff($admin)->get(route('reports.export-csv'))->assertOk()->streamedContent();
    $row = str_getcsv(explode("\n", trim($csv))[1]);

    expect($row[0])->toBe("'=NIS")
        ->and($row[1])->toBe("'=STU")
        ->and($row[2])->toBe("'=CLS");
});
