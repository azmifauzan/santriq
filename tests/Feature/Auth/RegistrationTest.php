<?php

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
