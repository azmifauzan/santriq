<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeachersTemplateSheet implements FromArray, WithEvents, WithTitle
{
    private const LAST_ROW = 500;

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Nama', 'Email', 'Role'],
        ];
    }

    public function title(): string
    {
        return 'Template';
    }

    /**
     * @return array<string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A1:C1')->getFont()->setBold(true);
                foreach (range('A', 'C') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                for ($row = 2; $row <= self::LAST_ROW; $row++) {
                    $validation = $sheet->getCell("C{$row}")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(true);
                    $validation->setShowDropDown(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setErrorTitle('Role tidak valid');
                    $validation->setError('Pilih admin atau pengajar.');
                    $validation->setFormula1('"admin,pengajar"');
                }

                $this->writeInstructions($sheet);
            },
        ];
    }

    private function writeInstructions(Worksheet $sheet): void
    {
        $sheet->fromArray([
            ['Contoh & Keterangan Pengisian', null],
            ['Nama', 'Contoh: Ustadzah Aminah'],
            ['Email', 'Contoh: aminah@contoh.sch.id'],
            ['Role', 'Isi admin atau pengajar'],
        ], null, 'E1');

        $sheet->getStyle('E1')->getFont()->setBold(true);
        $sheet->getStyle('E2:E4')->getFont()->setBold(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setWidth(45);
    }
}
