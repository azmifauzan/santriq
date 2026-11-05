<?php

namespace App\Http\Requests;

class UpdateAchievementRequest extends StoreAchievementRequest
{
    public function authorize(): bool
    {
        return $this->user('web')?->can('update', $this->route('achievement')) ?? false;
    }
}
