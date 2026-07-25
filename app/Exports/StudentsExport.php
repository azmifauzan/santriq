<?php

namespace App\Exports;

use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Student>
 */
class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Student>  $students
     */
    public function __construct(private readonly Collection $students) {}

    /**
     * @return Collection<int, Student>
     */
    public function collection(): Collection
    {
        return $this->students;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Kelas', 'Status'];
    }

    /**
     * @param  Student  $student
     * @return array<int, string|null>
     */
    public function map($student): array
    {
        return [
            $student->nis,
            $student->name,
            $student->gender,
            $student->birth_date,
            $student->classroom?->name,
            $student->status,
        ];
    }
}
