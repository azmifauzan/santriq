<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardPrintSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'columns_per_print_row' => ['required', 'integer', 'in:2,3,4'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_nis' => ['sometimes', 'boolean'],
            'show_classroom' => ['sometimes', 'boolean'],
            'show_gender' => ['sometimes', 'boolean'],
            'show_logo' => ['sometimes', 'boolean'],
        ];
    }
}
