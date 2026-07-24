<?php

namespace App\Http\Controllers;

use App\Concerns\UpdatesTenantLandingSettings;
use App\Http\Requests\Settings\LembagaUpdateRequest;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    use UpdatesTenantLandingSettings;

    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user?->isAdmin(), 403);

        if ($user->onboarded_at !== null) {
            return to_route('dashboard');
        }

        $tenant = CurrentTenant::get();

        return Inertia::render('Onboarding', [
            'tenant' => ['address' => $tenant->address, 'phone' => $tenant->phone],
            'landing' => $tenant->settings['landing'] ?? [],
        ]);
    }

    public function update(LembagaUpdateRequest $request): RedirectResponse
    {
        $this->applyLandingUpdate($request, CurrentTenant::get());
        $request->user('web')->update(['onboarded_at' => now()]);

        return to_route('dashboard')->with('success', 'Onboarding selesai.');
    }

    public function skip(Request $request): RedirectResponse
    {
        abort_unless($request->user('web')?->isAdmin(), 403);
        $request->user('web')->update(['onboarded_at' => now()]);

        return to_route('dashboard');
    }
}
