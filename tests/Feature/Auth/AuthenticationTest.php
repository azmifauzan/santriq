<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
});

test('tenant login screen uses the configured tenant branding', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'TPQ Baitul Ilmi',
        'subdomain' => 'baitul-ilmi',
        'settings' => [
            'landing' => [
                'tagline' => 'Mengaji, beradab, dan bertumbuh bersama.',
                'description' => 'Tempat belajar Al-Qur\'an untuk generasi masa depan.',
                'logo_path' => 'tenants/1/logo/logo.png',
            ],
        ],
    ]);

    $this->get("http://{$tenant->subdomain}.santriq.test/login")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('tenantBrand.name', 'TPQ Baitul Ilmi')
            ->where('tenantBrand.tagline', 'Mengaji, beradab, dan bertumbuh bersama.')
            ->where('tenantBrand.description', 'Tempat belajar Al-Qur\'an untuk generasi masa depan.')
            ->where('tenantBrand.logo_path', 'tenants/1/logo/logo.png')
        );

    $layout = file_get_contents(resource_path('js/layouts/auth/AuthSimpleLayout.vue'));

    expect($layout)
        ->toContain('Powered by')
        ->toContain(':href="home.url()"');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    // `login` itself is central (no {subdomain}), so LoginResponse builds the
    // dashboard redirect from the authenticated user's own tenant instead of
    // relying on the request having resolved one — see App\Http\Responses\LoginResponse.
    $response->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAsStaff($user)->post(route('logout'));

    $response->assertRedirect('/');

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
