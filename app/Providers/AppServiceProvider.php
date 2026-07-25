<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\ExceptionResponse;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureErrorPages();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Render custom Inertia error pages instead of Laravel's default pages.
     */
    protected function configureErrorPages(): void
    {
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            if (app()->environment(['local', 'testing'])) {
                return;
            }

            // Verification links expire after an hour (see config/auth.php).
            // The default 403 is a dead end for a user who opened a stale
            // email — send them back to the resend-verification screen instead.
            if ($response->exception instanceof InvalidSignatureException
                && $response->request->route()?->getName() === 'verification.verify') {
                return redirect()->route('verification.notice')
                    ->with('status', 'verification-link-expired');
            }

            if (in_array($response->statusCode(), [403, 404, 419, 429, 500, 503])) {
                return $response->render('ErrorPage', [
                    'status' => $response->statusCode(),
                ])->withSharedData();
            }
        });
    }
}
