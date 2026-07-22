<?php

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Achievement::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'category' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'achieved_at' => ['required', 'date'],
        ];
    }
}
