<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LembagaController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tenant = CurrentTenant::get();

        return Inertia::render('settings/Lembaga', [
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $tenant = CurrentTenant::get();
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

        $tenant->update(['settings' => [...$tenant->settings ?? [], 'landing' => $landing]]);

        return to_route('lembaga.edit')->with('success', 'Konten landing page diperbarui.');
    }
}
