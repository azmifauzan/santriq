<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use App\Http\Responses\VerifyEmailResponse;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use App\Support\DemoTenant;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureMailNotifications();

        // Not registered by default in this app's bootstrap/app.php (no
        // app/Listeners scan) — without this, MustVerifyEmail users never
        // get their verification email after Fortify's Registered event.
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        // The 'login' limiter below counts every POST /login hit, success or
        // failure. Without this, a few quick successful logins (double
        // submit, multiple tabs) can trip the same 429 as failed attempts.
        Event::listen(Login::class, function (): void {
            $throttleKey = self::loginThrottleKey(request()->input(Fortify::username()), request()->ip());

            // ThrottleRequests hashes named-limiter cache keys as
            // md5($limiterName.$limit->key) — must match that exactly to
            // clear the same entry it wrote (see fortify.limiters.login).
            RateLimiter::clear(md5('login'.$throttleKey));
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check((string) $request->password, $user->password)) {
                return null;
            }

            if (app()->bound(Tenant::class) && $user->tenant_id !== app(Tenant::class)->id) {
                return null;
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(function (Request $request) {
            $tenant = CurrentTenant::resolved() ? CurrentTenant::get() : null;
            $landing = $tenant?->settings['landing'] ?? [];

            return Inertia::render('auth/Login', [
                'canResetPassword' => Features::enabled(Features::resetPasswords()),
                'status' => $request->session()->get('status'),
                'tenantBrand' => $tenant ? [
                    'name' => $tenant->name,
                    'logo_path' => $landing['logo_path'] ?? null,
                    'tagline' => $landing['tagline'] ?? 'Tumbuh dalam ilmu, dekat dalam kebersamaan.',
                    'description' => $landing['description'] ?? "{$tenant->name} mendampingi santri belajar Al-Qur'an, bertumbuh dalam adab, dan berkembang bersama.",
                ] : null,
                'demoHint' => DemoTenant::isActive() ? [
                    'admin' => ['email' => 'admin@santriq.test', 'password' => 'password'],
                    'pengajar' => ['email' => 'pengajar@santriq.test', 'password' => 'password'],
                ] : null,
                'demoUrl' => DemoTenant::isActive() ? null : DemoTenant::url('/login'),
            ]);
        });

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/Register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    /**
     * Translate the two system emails Fortify sends (verify email, reset
     * password) — the stock notifications ship in English only.
     */
    private function configureMailNotifications(): void
    {
        VerifyEmail::toMailUsing(function (User $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifikasi Alamat Email - SantriQ')
                ->greeting("Assalamu'alaikum, {$notifiable->name}!")
                ->line('Klik tombol di bawah ini untuk memverifikasi alamat email Anda.')
                ->action('Verifikasi Email', $url)
                ->line('Jika Anda tidak membuat akun ini, abaikan email ini.');
        });

        ResetPassword::toMailUsing(function (User $notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->email,
            ], false));

            $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Reset Kata Sandi - SantriQ')
                ->greeting("Assalamu'alaikum, {$notifiable->name}!")
                ->line('Anda menerima email ini karena kami menerima permintaan reset kata sandi untuk akun Anda.')
                ->action('Reset Kata Sandi', $url)
                ->line("Tautan reset kata sandi ini akan kedaluwarsa dalam {$expireMinutes} menit.")
                ->line('Jika Anda tidak meminta reset kata sandi, abaikan email ini.');
        });
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = self::loginThrottleKey($request->input(Fortify::username()), $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Build the throttle key used by the 'login' rate limiter, so it can be
     * cleared with the exact same key on a successful login.
     */
    private static function loginThrottleKey(?string $username, ?string $ip): string
    {
        return Str::transliterate(Str::lower((string) $username).'|'.$ip);
    }
}
