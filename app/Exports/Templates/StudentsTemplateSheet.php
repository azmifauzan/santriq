<?php

namespace App\Exports\Templates;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentsTemplateSheet implements FromArray, WithEvents, WithTitle
{
    private const LAST_ROW = 500;

    /**
     * @param  array<int, string>  $kelasOptions
     */
    public function __construct(private readonly array $kelasOptions) {}

    /**
     * @return array<int, array<int, string|null>>
     */
    public function array(): array
    {
        return [
            ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'],
            ['2024001', 'Ahmad Fauzan', 'L', '2015-05-14', $this->kelasOptions[0] ?? '', 'active'],
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

                $sheet->getStyle('A1:F1')->getFont()->setBold(true);
                $sheet->getStyle('A2:F2')->getFont()->setItalic(true);
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                $sheet->getStyle('A3:A'.self::LAST_ROW)->getNumberFormat()->setFormatCode('@');
                $sheet->getStyle('D3:D'.self::LAST_ROW)->getNumberFormat()->setFormatCode('yyyy-mm-dd');

                $this->applyListValidation($sheet, 'C', '"L,P"', 'Jenis Kelamin', 'Pilih L atau P.');
                $this->applyDateValidation($sheet, 'D');

                if ($this->kelasOptions !== []) {
                    $lastKelasRow = count($this->kelasOptions) + 1;
                    $this->applyListValidation(
                        $sheet,
                        'E',
                        "Referensi!\$A\$2:\$A\${$lastKelasRow}",
                        'Kelas',
                        'Pilih kelas dari daftar di sheet Referensi.'
                    );
                }

                $this->applyListValidation($sheet, 'F', '"active,inactive"', 'Status', 'Pilih active atau inactive.');
            },
        ];
    }

    private function applyListValidation(Worksheet $sheet, string $column, string $formula, string $label, string $message): void
    {
        for ($row = 3; $row <= self::LAST_ROW; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle("{$label} tidak valid");
            $validation->setError($message);
            $validation->setFormula1($formula);
        }
    }

    private function applyDateValidation(Worksheet $sheet, string $column): void
    {
        for ($row = 3; $row <= self::LAST_ROW; $row++) {
            $validation = $sheet->getCell("{$column}{$row}")->getDataValidation();
            $validation->setType(DataValidation::TYPE_DATE);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Tanggal Lahir tidak valid');
            $validation->setError('Isi tanggal lahir dengan format tanggal, contoh: 2015-05-14.');
            $validation->setOperator(DataValidation::OPERATOR_GREATERTHAN);
            $validation->setFormula1('DATE(1900,1,1)');
        }
    }
}
