<?php

namespace App\Exports\Guides;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Blank report-card template offered by the /panduan/format-rapor-tpq article.
 *
 * Static content only, so it does not need SafeSpreadsheetExport.
 */
class ReportCardGuideExport implements FromArray, ShouldAutoSize, WithTitle
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['RAPOR SANTRI TPQ/TPA'],
            [],
            ['Nama santri', ''],
            ['Nomor induk', ''],
            ['Kelas / jilid', ''],
            ['Periode', ''],
            ['Pengajar', ''],
            [],
            ['Aspek penilaian', 'Capaian / nilai', 'Keterangan'],
            ['Bacaan (jilid / juz dan halaman terakhir)', '', ''],
            ['Hafalan surat pendek', '', ''],
            ['Hafalan doa harian', '', ''],
            ['Tajwid dan makharij', '', ''],
            ['Praktik ibadah (wudu dan salat)', '', ''],
            ['Akhlak dan adab', '', ''],
            [],
            ['Kehadiran', 'Jumlah'],
            ['Hadir', ''],
            ['Izin', ''],
            ['Sakit', ''],
            ['Tanpa keterangan', ''],
            [],
            ['Skala: Sangat Baik / Baik / Cukup / Perlu Bimbingan'],
            [],
            ['Catatan pengajar'],
            [''],
            ['Tanggapan wali'],
            [''],
        ];
    }

    public function title(): string
    {
        return 'Rapor';
    }
}
