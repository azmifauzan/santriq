<?php

use App\Jobs\SendSuperAdminTelegramAlert;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Register'));
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    // A manual (password) signup isn't Google-attested, so it still needs to
    // click the link Fortify just emailed — see App\Http\Responses\RegisterResponse.
    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
});

test('newly registered admin has not completed onboarding', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->onboarded_at)->toBeNull();
});

test('registering a new tenant dispatches a super admin telegram alert', function () {
    Queue::fake();

    $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Queue::assertPushed(SendSuperAdminTelegramAlert::class, function ($job) {
        return str_contains($job->messageText, 'TPA Nurul Huda')
            && str_contains($job->messageText, 'test@example.com');
    });
});
