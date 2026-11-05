<?php

namespace App\Http\Requests;

use App\Models\Achievement;
use App\Rules\TenantExists;
use Illuminate\Foundation\Http\FormRequest;

class StoreAchievementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('create', Achievement::class) ?? false;
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
            'learning_method' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'page_start' => ['nullable', 'required_with:page_end', 'integer', 'between:1,9999'],
            'page_end' => ['nullable', 'integer', 'between:1,9999', 'gte:page_start'],
            'surah' => ['nullable', 'required_with:ayah_start,ayah_end', 'string', 'max:255'],
            'ayah_start' => ['nullable', 'required_with:ayah_end', 'integer', 'between:1,286'],
            'ayah_end' => ['nullable', 'integer', 'between:1,286', 'gte:ayah_start'],
            'activity_type' => ['nullable', 'in:bacaan,ziyadah,murajaah'],
            'fluency' => ['nullable', 'in:lancar,perlu_latihan,mengulang'],
            'practice_note' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
