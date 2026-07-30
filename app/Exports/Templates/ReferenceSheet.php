<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Lists valid values for enum-like import columns (e.g. daftar nama kelas,
 * daftar role) on their own sheet, so the user can see and copy the exact
 * values instead of guessing.
 */
class ReferenceSheet implements FromArray, WithStyles, WithTitle
{
    /**
     * @param  array<string, array<int, string>>  $columns  Column label => list of valid values.
     */
    public function __construct(private readonly array $columns) {}

    /**
     * @return array<int, array<int, string|null>>
     */
    public function array(): array
    {
        $rowCount = max(array_map('count', $this->columns));
        $rows = [array_keys($this->columns)];

        for ($i = 0; $i < $rowCount; $i++) {
            $rows[] = array_map(
                fn (array $values) => $values[$i] ?? null,
                array_values($this->columns)
            );
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Referensi';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
