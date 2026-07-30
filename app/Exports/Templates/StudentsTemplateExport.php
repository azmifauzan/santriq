<?php

namespace App\Exports\Templates;

use App\Models\Classroom;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StudentsTemplateExport implements WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        $kelasOptions = Classroom::orderBy('name')->pluck('name')->all();

        $sheets = [new StudentsTemplateSheet($kelasOptions)];

        if ($kelasOptions !== []) {
            $sheets[] = new ReferenceSheet(['Kelas' => $kelasOptions]);
        }

        return $sheets;
    }
}
