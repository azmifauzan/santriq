<?php

namespace App\Exports;

use App\Models\Classroom;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Classroom>
 */
class ClassroomsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Classroom>  $classrooms
     */
    public function __construct(private readonly Collection $classrooms) {}

    /**
     * @return Collection<int, Classroom>
     */
    public function collection(): Collection
    {
        return $this->classrooms;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'Level'];
    }

    /**
     * @param  Classroom  $classroom
     * @return array<int, string|null>
     */
    public function map($classroom): array
    {
        return [
            $classroom->name,
            $classroom->level,
        ];
    }
}
