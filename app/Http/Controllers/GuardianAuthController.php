<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramMessage;
use App\Models\Guardian;
use App\Support\CurrentTenant;
use App\Support\DemoTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class GuardianAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('guardian/Login', [
            'isDemo' => DemoTenant::isActive(),
        ]);
    }

    public function loginDemo(): RedirectResponse
    {
        abort_unless(DemoTenant::isActive(), 404);

        $guardian = Guardian::where('tenant_id', CurrentTenant::get()->id)
            ->oldest('id')
            ->firstOrFail();

        Auth::guard('guardian')->login($guardian, remember: true);

        return redirect()->route('guardian.portal.index');
    }

    public function requestLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
        ]);

        $guardian = Guardian::where('tenant_id', CurrentTenant::get()->id)
            ->where('phone', $validated['phone'])
            ->first();

        if (! $guardian || empty($guardian->telegram_chat_id)) {
            return back()->withErrors([
                'phone' => 'Nomor tidak ditemukan atau belum tertaut Telegram. Hubungi pengurus lembaga.',
            ]);
        }

        $link = URL::temporarySignedRoute(
            'guardian.login.verify',
            now()->addMinutes(15),
            ['guardian' => $guardian->id]
        );

        SendTelegramMessage::dispatch(
            $guardian,
            "🔑 <b>Tautan masuk Portal Wali</b>\n\nKlik tautan berikut untuk masuk (berlaku 15 menit):\n{$link}"
        );

        return back()->with('success', 'Tautan masuk telah dikirim ke Telegram Anda.');
    }

    public function verify(Request $request, Guardian $guardian): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($guardian->tenant_id === CurrentTenant::get()->id, 403);

        Auth::guard('guardian')->login($guardian, remember: true);

        return redirect()->route('guardian.portal.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('guardian')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('guardian.login');
    }
}
