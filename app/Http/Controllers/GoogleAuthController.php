<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\GoogleOAuthToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $intent = $request->query('intent') === 'register' ? 'register' : 'login';

        $state = GoogleOAuthToken::encode([
            'intent' => $intent,
            'subdomain' => $intent === 'login' ? $request->query('subdomain') : null,
        ]);

        return Socialite::driver('google')->stateless()->with(['state' => $state])->redirect();
    }

    public function callback(Request $request): RedirectResponse|InertiaResponse
    {
        $state = GoogleOAuthToken::decode((string) $request->query('state', ''));

        if ($state === null) {
            return redirect()->route('login')->withErrors([
                'google' => 'Sesi masuk dengan Google sudah kedaluwarsa. Silakan coba lagi.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors([
                'google' => 'Gagal masuk dengan Google. Silakan coba lagi.',
            ]);
        }

        return $state['intent'] === 'register'
            ? $this->handleRegister($googleUser)
            : $this->handleLogin($googleUser, $state['subdomain'] ?? null);
    }

    private function handleLogin(SocialiteUser $googleUser, ?string $subdomain): RedirectResponse|InertiaResponse
    {
        $tenant = $subdomain !== null ? Tenant::where('subdomain', $subdomain)->first() : null;

        if (! $tenant) {
            $user = User::where('google_id', $googleUser->getId())->first();

            if ($user) {
                return $this->redirectToTenantLogin($user);
            }

            return $this->handleRegister($googleUser);
        }

        $user = User::where('email', $googleUser->getEmail())->first();
        $emailVerifiedByGoogle = (bool) ($googleUser->user['email_verified'] ?? false);

        if ($user && $user->tenant_id === $tenant->id && $user->google_id === null && $emailVerifiedByGoogle) {
            $user->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        if (! $user || $user->tenant_id !== $tenant->id || $user->google_id !== $googleUser->getId()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Akun Google ini belum terdaftar di lembaga ini.',
            ]);
        }

        return $this->redirectToTenantLogin($user);
    }

    /**
     * Google's callback always runs on the apex domain (fixed redirect URI),
     * so a session logged in here never reaches the tenant subdomain
     * (`SESSION_DOMAIN` is deliberately null — see docs/RENCANA-IMPLEMENTASI.md).
     * Hand off through a signed link the subdomain itself verifies, same
     * pattern as GuardianAuthController::verify.
     */
    private function redirectToTenantLogin(User $user): RedirectResponse
    {
        $link = URL::temporarySignedRoute(
            'google.login.verify',
            now()->addMinutes(5),
            ['subdomain' => $user->tenant->subdomain, 'user' => $user->id]
        );

        return redirect($link);
    }

    public function verifyLogin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->hasValidSignature(), 403);
        abort_unless($user->tenant_id === CurrentTenant::get()->id, 403);

        Auth::guard('web')->login($user);

        return redirect()->intended(route('dashboard', ['subdomain' => CurrentTenant::get()->subdomain]));
    }

    private function handleRegister(SocialiteUser $googleUser): RedirectResponse|InertiaResponse
    {
        if (User::where('email', $googleUser->getEmail())->exists()) {
            return redirect()->route('login')->withErrors([
                'google' => 'Email sudah terdaftar. Silakan masuk.',
            ]);
        }

        $token = GoogleOAuthToken::encode([
            'sub' => $googleUser->getId(),
            'email' => $googleUser->getEmail(),
            'name' => $googleUser->getName(),
        ]);

        return Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'google' => [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'token' => $token,
            ],
        ]);
    }
}
