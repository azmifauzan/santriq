<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class GuardiansTemplateExport implements FromArray, WithEvents
{
    private const LAST_ROW = 500;

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Nama', 'No. HP'],
            ['Ibu Aisyah', '081234567890'],
        ];
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:B1')->getFont()->setBold(true);
                $sheet->getStyle('A2:B2')->getFont()->setItalic(true);
                foreach (range('A', 'B') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->getStyle('B3:B'.self::LAST_ROW)->getNumberFormat()->setFormatCode('@');
            },
        ];
    }
}
