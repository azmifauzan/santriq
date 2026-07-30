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
                foreach (range('A', 'B') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->getStyle('B2:B'.self::LAST_ROW)->getNumberFormat()->setFormatCode('@');

                $sheet->fromArray([
                    ['Contoh & Keterangan Pengisian', null],
                    ['Nama', 'Contoh: Ibu Aisyah'],
                    ['No. HP', 'Contoh: 081234567890 (opsional, boleh dikosongkan)'],
                ], null, 'D1');

                $sheet->getStyle('D1')->getFont()->setBold(true);
                $sheet->getStyle('D2:D3')->getFont()->setBold(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('E')->setWidth(45);
            },
        ];
    }
}
