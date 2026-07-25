<?php

namespace App\Exports;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Attendance>
 */
class AttendancesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Attendance>  $attendances
     */
    public function __construct(private readonly Collection $attendances) {}

    /**
     * @return Collection<int, Attendance>
     */
    public function collection(): Collection
    {
        return $this->attendances;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama Santri', 'Kelas', 'Masuk', 'Pulang', 'Status'];
    }

    /**
     * @param  Attendance  $attendance
     * @return array<int, string|null>
     */
    public function map($attendance): array
    {
        return [
            $attendance->student?->nis,
            $attendance->student?->name,
            $attendance->student?->classroom?->name,
            $attendance->checked_in_at?->format('H:i'),
            $attendance->checked_out_at?->format('H:i'),
            $attendance->status,
        ];
    }
}
