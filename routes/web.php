<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\TenantSubdomainAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::domain(config('tenancy.domain'))->group(function () {
    Route::inertia('/', 'Welcome')->name('home');
    Route::inertia('privacy', 'Legal', ['document' => 'privacy'])->name('privacy');
    Route::inertia('terms', 'Legal', ['document' => 'terms'])->name('terms');

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
        Route::get('{tenant}', [SuperAdminController::class, 'show'])->name('show');
        Route::patch('{tenant}/toggle-status', [SuperAdminController::class, 'toggleStatus'])->name('toggle-status');
    });
});

require __DIR__.'/tenant.php';
