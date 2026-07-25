<?php

namespace App\Exports;

use App\Models\Guardian;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Guardian>
 */
class GuardiansExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @param  Collection<int, Guardian>  $guardians
     */
    public function __construct(private readonly Collection $guardians) {}

    /**
     * @return Collection<int, Guardian>
     */
    public function collection(): Collection
    {
        return $this->guardians;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'No. HP'];
    }

    /**
     * @param  Guardian  $guardian
     * @return array<int, string|null>
     */
    public function map($guardian): array
    {
        return [
            $guardian->name,
            $guardian->phone,
        ];
    }
}
