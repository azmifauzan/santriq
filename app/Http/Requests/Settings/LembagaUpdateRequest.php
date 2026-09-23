<?php

namespace App\Http\Requests\Settings;

use App\Support\DemoTenant;
use Illuminate\Foundation\Http\FormRequest;

class LembagaUpdateRequest extends FormRequest
{
    /**
     * The demo tenant's admin credentials are published on its own login
     * page (see FortifyServiceProvider::configureViews) so visitors can
     * explore without registering. Its landing page and login branding are
     * public and never reset by `demo:reset`, so anyone with those
     * credentials could otherwise deface a publicly crawlable page under
     * this domain indefinitely — the pattern Google Safe Browsing flags as
     * a "deceptive page".
     */
    public function authorize(): bool
    {
        return ($this->user('web')?->isAdmin() ?? false) && ! DemoTenant::isActive();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'gallery' => ['nullable', 'array', 'max:6'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
