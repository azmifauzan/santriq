<?php

namespace App\Exports;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Achievement>
 */
class AchievementsExport implements FromCollection, WithHeadings, WithMapping
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
        return ['Tanggal', 'Nama Santri', 'Kategori', 'Judul / Materi', 'Nilai', 'Catatan'];
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
        ];
    }
}
