<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\UpdatesTenantLandingSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LembagaController extends Controller
{
    use UpdatesTenantLandingSettings;

    public function edit(Request $request): Response
    {
        abort_unless($request->user('web')?->isAdmin(), 403);

        $tenant = CurrentTenant::get();

        return Inertia::render('settings/Lembaga', [
            'tenant' => ['address' => $tenant->address, 'phone' => $tenant->phone],
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $this->applyLandingUpdate($request, CurrentTenant::get());

        return to_route('lembaga.edit')->with('success', 'Konten landing page diperbarui.');
    }
}
