<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TenantSubdomainAvailabilityController;
use App\Models\AppSetting;
use App\Support\DemoTenant;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::domain(config('tenancy.domain'))->group(function () {
    Route::get('/', fn () => Inertia::render('Welcome', [
        'demoUrl' => DemoTenant::url('/login'),
        'whatsappNumber' => AppSetting::current()->whatsapp_number,
    ]))->name('home');
    Route::get('privacy', [LegalController::class, 'show'])->defaults('document', 'privacy')->name('privacy');
    Route::get('terms', [LegalController::class, 'show'])->defaults('document', 'terms')->name('terms');
    Route::inertia('request-fitur', 'RequestFeature', [
        'links' => [
            'threads' => 'https://www.threads.com/@azmifauzan',
            'facebook' => 'https://www.facebook.com/azmifauzan/',
            'linkedin' => 'https://www.linkedin.com/in/fauzan-azmi-29094522/',
        ],
    ])->name('request-feature');

    Route::get('subdomain-availability', [TenantSubdomainAvailabilityController::class, 'check'])
        ->middleware('throttle:30,1')
        ->name('subdomain.availability');

    // The apex domain is the only one that matches GOOGLE_REDIRECT_URI: a request
    // that started on a tenant subdomain still bounces through here on its way
    // back from Google (see App\Support\GoogleOAuthToken).
    Route::get('auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:10,1')
        ->name('google.redirect');
    Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');

    Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle'])
        ->middleware('throttle:120,1')
        ->name('telegram.webhook');

    Route::get('super-admin/verify/{user}', [SuperAdminController::class, 'verifyHandoff'])
        ->middleware('signed')
        ->name('super-admin.verify');

    Route::middleware(['auth', 'verified'])->prefix('super-admin')->name('super-admin.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'index'])->name('index');
        // Declared before {tenant} below so "settings" isn't swallowed as a tenant route-model-binding attempt.
        Route::get('settings', [SuperAdminController::class, 'settingsEdit'])->name('settings.edit');
        Route::put('settings', [SuperAdminController::class, 'settingsUpdate'])->name('settings.update');
        Route::get('{tenant}', [SuperAdminController::class, 'show'])->name('show');
        Route::patch('{tenant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('toggle-status');
    });
});

require __DIR__.'/tenant.php';
