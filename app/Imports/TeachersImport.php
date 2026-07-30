<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TeachersImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $createdCount = 0;

    /**
     * @param  array<string, mixed>  $row
     */
    public function model(array $row): User
    {
        $this->createdCount++;

        $role = strtolower((string) $row['role']) === 'admin' ? 'admin' : 'pengajar';

        return new User([
            'tenant_id' => Auth::user()->tenant_id,
            'name' => $row['nama'],
            'email' => $row['email'],
            'password' => Hash::make(Str::password(12)),
            'role' => $role,
            'email_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function isEmptyWhen(array $row): bool
    {
        return empty($row['nama']) && empty($row['email']) && empty($row['role']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'role' => ['required', 'string', 'in:admin,pengajar,Admin,Pengajar'],
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
            'email.required' => 'Email wajib diisi.',
            'email.string' => 'Email harus berupa teks.',
            'email.email' => 'Email harus berupa alamat email yang valid.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.unique' => 'Email :input sudah terdaftar.',
            'role.required' => 'Role wajib diisi.',
            'role.string' => 'Role harus berupa teks.',
            'role.in' => 'Role harus admin atau pengajar.',
        ];
    }
}
