<?php

namespace App\Exports\Guides;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Cash book and monthly tuition tracker offered by /panduan/buku-kas-spp-tpq.
 *
 * Static content with formulas, so it deliberately bypasses the text-only
 * value binder of SafeSpreadsheetExport.
 */
class CashBookGuideExport implements WithMultipleSheets
{
    private const ROWS = 60;

    /**
     * @return array<int, object>
     */
    public function sheets(): array
    {
        return [
            new class(self::ROWS) extends GuideSheet
            {
                public function title(): string
                {
                    return 'Buku Kas';
                }

                public function array(): array
                {
                    $rows = [['Tanggal', 'Uraian', 'Nama santri', 'Pemasukan', 'Pengeluaran', 'Saldo']];

                    for ($row = 2; $row <= $this->rows + 1; $row++) {
                        $rows[] = ['', '', '', '', '', "=IF(AND(D{$row}=\"\",E{$row}=\"\"),\"\",SUM(\$D\$2:D{$row})-SUM(\$E\$2:E{$row}))"];
                    }

                    return $rows;
                }
            },
            new class(self::ROWS) extends GuideSheet
            {
                public function title(): string
                {
                    return 'Rekap SPP';
                }

                public function array(): array
                {
                    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    $rows = [['No', 'Nama santri', ...$months, 'Total']];

                    for ($row = 2; $row <= $this->rows + 1; $row++) {
                        $rows[] = [$row - 1, '', ...array_fill(0, 12, ''), "=SUM(C{$row}:N{$row})"];
                    }

                    $last = $this->rows + 1;
                    $totals = ['', 'Total penerimaan'];

                    foreach (range('C', 'O') as $column) {
                        $totals[] = "=SUM({$column}2:{$column}{$last})";
                    }

                    $rows[] = $totals;

                    return $rows;
                }
            },
        ];
    }
}
