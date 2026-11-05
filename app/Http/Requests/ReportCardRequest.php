<?php

namespace App\Http\Requests;

use App\Models\ReportCard;
use App\Support\CurrentTenant;
use Illuminate\Foundation\Http\FormRequest;

class ReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('web');

        return $user !== null && $user->can('create', ReportCard::class) && $user->can('view', $this->route('student'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethod('GET')) {
            $today = now(CurrentTenant::get()->timezone);
            $this->mergeIfMissing([
                'start_date' => $today->copy()->startOfMonth()->format('Y-m-d'),
                'end_date' => $today->format('Y-m-d'),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'teacher_note' => ['nullable', 'string', 'max:5000'],
            'snapshot_hash' => [$this->isMethod('POST') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }
}
