<?php

namespace App\Imports;

use App\Models\Guardian;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class GuardiansImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Guardian
    {
        $this->createdCount++;

        return new Guardian([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $row['nama'],
            'phone' => ! empty($row['no_hp']) ? (string) $row['no_hp'] : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'max:50'],
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
            'no_hp.max' => 'No. HP maksimal :max karakter.',
        ];
    }
}
