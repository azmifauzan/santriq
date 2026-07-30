<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ClassroomsTemplateExport implements FromArray, WithEvents
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Nama', 'Level'],
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

                $sheet->fromArray([
                    ['Contoh & Keterangan Pengisian', null],
                    ['Nama', 'Contoh: Kelas Iqra 1'],
                    ['Level', 'Contoh: Jilid 1 (opsional, bebas isi apa saja)'],
                ], null, 'D1');

                $sheet->getStyle('D1')->getFont()->setBold(true);
                $sheet->getStyle('D2:D3')->getFont()->setBold(true);
                $sheet->getColumnDimension('D')->setAutoSize(true);
                $sheet->getColumnDimension('E')->setWidth(45);
            },
        ];
    }
}
