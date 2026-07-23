<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student && ($this->user('web')?->can('update', $student) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Student $student */
        $student = $this->route('student');

        return [
            'classroom_id' => ['nullable', TenantExists::in('classrooms')],
            'nis' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'nis')
                    ->where('tenant_id', $student->tenant_id)
                    ->ignore($student->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['L', 'P'])],
            'birth_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => [TenantExists::in('guardians')],
        ];
    }
}
