<?php

namespace App\Http\Requests;

use App\Models\Achievement;
use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('update', $this->route('achievement')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', TenantExists::in('students')],
            'category' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'achieved_at' => ['required', 'date'],
        ];
    }
}
