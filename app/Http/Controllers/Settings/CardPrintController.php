<?php

namespace App\Http\Controllers\Settings;

use App\Concerns\UpdatesTenantCardPrintSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCardPrintSettingsRequest;
use App\Support\CardPrintSettings;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardPrintController extends Controller
{
    use UpdatesTenantCardPrintSettings;

    public function edit(Request $request): Response
    {
        abort_unless($request->user('web')?->isAdmin(), 403);

        $tenant = CurrentTenant::get();
        $landing = $tenant->settings['landing'] ?? [];

        return Inertia::render('settings/CardPrint', [
            'cardPrint' => CardPrintSettings::resolve($tenant),
            'logoPath' => $landing['logo_path'] ?? null,
        ]);
    }

    public function update(UpdateCardPrintSettingsRequest $request): RedirectResponse
    {
        $this->applyCardPrintUpdate($request, CurrentTenant::get());

        return to_route('card-print.edit')->with('success', 'Tampilan kartu santri diperbarui.');
    }
}
