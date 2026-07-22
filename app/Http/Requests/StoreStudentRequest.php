<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = $this->user()?->tenant_id;

        return [
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nis')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['L', 'P'])],
            'birth_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => ['exists:guardians,id'],
        ];
    }
}
