<?php

namespace App\Concerns;

use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Models\Tenant;

trait UpdatesTenantLandingSettings
{
    private function applyLandingUpdate(LembagaUpdateRequest $request, Tenant $tenant): void
    {
        $landing = $tenant->settings['landing'] ?? [];

        foreach (['tagline', 'description', 'operating_hours', 'accent_color'] as $field) {
            if ($request->filled($field)) {
                $landing[$field] = $request->string($field)->toString();
            }
        }

        if ($request->hasFile('logo')) {
            $landing['logo_path'] = $request->file('logo')->store('tenants/'.$tenant->id.'/logo', 'public');
        }

        if ($request->hasFile('gallery')) {
            $landing['gallery'] = collect($request->file('gallery'))
                ->map(fn ($file) => $file->store('tenants/'.$tenant->id.'/gallery', 'public'))
                ->all();
        }

        $tenant->update([
            'address' => $request->filled('address') ? $request->string('address')->toString() : $tenant->address,
            'phone' => $request->filled('phone') ? $request->string('phone')->toString() : $tenant->phone,
            'settings' => [...$tenant->settings ?? [], 'landing' => $landing],
        ]);
    }
}
