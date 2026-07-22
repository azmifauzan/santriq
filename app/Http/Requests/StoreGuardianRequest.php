<?php

namespace App\Http\Requests;

use App\Models\Guardian;
use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Guardian::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => [TenantExists::in('students')],
        ];
    }
}
