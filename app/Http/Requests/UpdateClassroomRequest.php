<?php

namespace App\Http\Requests;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $classroom = $this->route('classroom');

        return $classroom instanceof Classroom && ($this->user()?->can('update', $classroom) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
        ];
    }
}
