<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<User>
 */
class TeachersExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, User>  $teachers
     */
    public function __construct(private readonly Collection $teachers) {}

    /**
     * @return Collection<int, User>
     */
    public function collection(): Collection
    {
        return $this->teachers;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'Email', 'Role'];
    }

    /**
     * @param  User  $user
     * @return array<int, string|null>
     */
    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->role,
        ];
    }
}
