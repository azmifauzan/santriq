<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\GoogleOAuthToken;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('login redirect sends the user to google', function () {
    $response = $this->get(route('google.redirect', ['intent' => 'login', 'subdomain' => 'al-hidayah']));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('accounts.google.com');
});

test('google login authenticates an already linked user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant)->create(['google_id' => 'g-123']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-123',
        'email' => $user->email,
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'login', 'subdomain' => $tenant->subdomain]);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', ['subdomain' => $tenant->subdomain]));
});

test('google login authenticates an already linked user from the central login screen', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant)->create(['google_id' => 'g-123']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-123',
        'email' => $user->email,
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'login', 'subdomain' => '']);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', ['subdomain' => $tenant->subdomain]));
});

test('google login auto-links a verified google email to a matching password account', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant)->create(['google_id' => null]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-456',
        'email' => $user->email,
        'email_verified' => true,
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'login', 'subdomain' => $tenant->subdomain]);
    $this->get(route('google.callback', ['state' => $state]));

    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->google_id)->toBe('g-456');
});

test('google login does not auto-link when google reports an unverified email', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->for($tenant)->create(['google_id' => null]);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-456',
        'email' => $user->email,
        'email_verified' => false,
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'login', 'subdomain' => $tenant->subdomain]);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $this->assertGuest();
    expect($user->refresh()->google_id)->toBeNull();
    $response->assertRedirect(route('login'));
});

test('google login redirects to the registration form prefilled when the tenant is not found', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-321',
        'email' => 'no-tenant@example.com',
        'name' => 'No Tenant',
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'login', 'subdomain' => 'unknown-subdomain']);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $this->assertGuest();
    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('auth/Register')
        ->where('google.email', 'no-tenant@example.com')
        ->where('google.name', 'No Tenant')
        ->has('google.token')
    );
});

test('google login rejects a user from a different tenant', function () {
    $tenant = Tenant::factory()->create();
    $otherTenant = Tenant::factory()->create();
    $user = User::factory()->for($otherTenant)->create(['google_id' => 'g-789']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-789',
        'email' => $user->email,
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'login', 'subdomain' => $tenant->subdomain]);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('google callback rejects an expired or tampered state', function () {
    $response = $this->get(route('google.callback', ['state' => 'not-a-real-token']));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});

test('google register renders the registration form prefilled with the google identity', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-999',
        'email' => 'new-admin@example.com',
        'name' => 'New Admin',
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'register', 'subdomain' => null]);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('auth/Register')
        ->where('google.email', 'new-admin@example.com')
        ->where('google.name', 'New Admin')
        ->has('google.token')
    );
});

test('google register redirects to login when the email is already taken', function () {
    $existing = User::factory()->create();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'g-999',
        'email' => $existing->email,
    ]));

    $state = GoogleOAuthToken::encode(['intent' => 'register', 'subdomain' => null]);
    $response = $this->get(route('google.callback', ['state' => $state]));

    $response->assertRedirect(route('login'));
});

test('submitting the register form with a google token creates a passwordless linked account', function () {
    $token = GoogleOAuthToken::encode([
        'sub' => 'g-999',
        'email' => 'new-admin@example.com',
        'name' => 'New Admin',
    ]);

    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'google_token' => $token,
    ]);

    $user = User::where('email', 'new-admin@example.com')->firstOrFail();
    $response->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));

    expect($user->google_id)->toBe('g-999')
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('submitting the register form with a google token ignores a spoofed email but keeps the edited name', function () {
    $token = GoogleOAuthToken::encode([
        'sub' => 'g-999',
        'email' => 'real-owner@example.com',
        'name' => 'Real Owner',
    ]);

    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPA Nurul Huda',
        'subdomain' => 'tpa-nurul-huda',
        'name' => 'Edited Name',
        'email' => 'victim@example.com',
        'google_token' => $token,
    ]);

    expect(User::where('email', 'victim@example.com')->exists())->toBeFalse();

    $user = User::where('email', 'real-owner@example.com')->firstOrFail();
    $response->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));

    expect($user->name)->toBe('Edited Name')
        ->and($user->google_id)->toBe('g-999');
});
