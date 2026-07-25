<?php

namespace App\Imports;

use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class StudentsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): Student
    {
        $classroomId = null;
        if (! empty($row['kelas'])) {
            $classroomId = Classroom::where('name', $row['kelas'])->value('id');
        }

        $this->createdCount++;

        return new Student([
            'tenant_id' => Auth::user()->tenant_id,
            'classroom_id' => $classroomId,
            'nis' => (string) $row['nis'],
            'name' => $row['nama'],
            'gender' => strtoupper((string) $row['jenis_kelamin']),
            'birth_date' => $row['tanggal_lahir'] ?? null,
            'status' => $row['status'] ?: 'active',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = Auth::user()->tenant_id;

        return [
            'nis' => [
                'required',
                Rule::unique('students', 'nis')->where('tenant_id', $tenantId),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => ['required', 'string', 'in:L,P,l,p'],
            'tanggal_lahir' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }
}
