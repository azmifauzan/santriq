<?php

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

    Route::post('telegram/webhook', [TelegramWebhookController::class, 'handle'])
        ->middleware('throttle:120,1')
        ->name('telegram.webhook');
});

require __DIR__.'/tenant.php';
