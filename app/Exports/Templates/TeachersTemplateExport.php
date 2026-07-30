<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TeachersTemplateExport implements WithMultipleSheets
{
    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new TeachersTemplateSheet,
            new ReferenceSheet(['Role' => ['admin', 'pengajar']]),
        ];
    }
}
