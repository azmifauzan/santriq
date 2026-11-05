<?php

namespace App\Exports;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/**
 * Every cell is bound as text so staff-entered values starting with "=" are
 * never evaluated as formulas when an admin opens the export (formula injection).
 *
 * @implements WithMapping<Achievement>
 */
class AchievementsExport extends StringValueBinder implements FromCollection, WithCustomValueBinder, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Achievement>  $achievements
     */
    public function __construct(private readonly Collection $achievements) {}

    /**
     * @return Collection<int, Achievement>
     */
    public function collection(): Collection
    {
        return $this->achievements;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Tanggal', 'Nama Santri', 'Kategori', 'Judul / Materi', 'Nilai', 'Catatan', 'Metode', 'Jilid / Tingkat', 'Halaman Awal', 'Halaman Akhir', 'Surah', 'Ayat Awal', 'Ayat Akhir', 'Jenis Kegiatan', 'Kelancaran', 'Latihan Berikutnya'];
    }

    /**
     * @param  Achievement  $achievement
     * @return array<int, string|null>
     */
    public function map($achievement): array
    {
        return [
            $achievement->achieved_at,
            $achievement->student?->name,
            $achievement->category,
            $achievement->title,
            $achievement->score !== null ? (string) $achievement->score : null,
            $achievement->note,
            $achievement->learning_method,
            $achievement->level,
            $achievement->page_start !== null ? (string) $achievement->page_start : null,
            $achievement->page_end !== null ? (string) $achievement->page_end : null,
            $achievement->surah,
            $achievement->ayah_start !== null ? (string) $achievement->ayah_start : null,
            $achievement->ayah_end !== null ? (string) $achievement->ayah_end : null,
            $achievement->activity_type,
            $achievement->fluency,
            $achievement->practice_note,
        ];
    }
}
