<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

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
            ['Ustadzah Aminah', 'aminah@contoh.sch.id', 'pengajar'],
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
                $sheet->getStyle('A2:C2')->getFont()->setItalic(true);
                foreach (range('A', 'C') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                for ($row = 3; $row <= self::LAST_ROW; $row++) {
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
            },
        ];
    }
}
