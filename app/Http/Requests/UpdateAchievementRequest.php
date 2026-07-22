<?php

namespace App\Http\Requests;

use App\Models\Achievement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $achievement = $this->route('achievement');

        return $achievement instanceof Achievement && ($this->user()?->can('update', $achievement) ?? false);
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
