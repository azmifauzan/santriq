<?php

namespace App\Imports;

use App\Models\Classroom;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ClassroomsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Classroom
    {
        $this->createdCount++;

        return new Classroom([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $row['nama'],
            'level' => $row['level'] ?: null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal :max karakter.',
            'level.string' => 'Level harus berupa teks.',
            'level.max' => 'Level maksimal :max karakter.',
        ];
    }
}
