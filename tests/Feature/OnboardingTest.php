<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\URL;

test('admin without onboarding sees the onboarding page', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->get(route('onboarding.show'))->assertOk();
});

test('already onboarded admin visiting onboarding is redirected to dashboard', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)->get(route('onboarding.show'))
        ->assertRedirect(route('dashboard'));
});

test('pengajar cannot view onboarding', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'onboarded_at' => null]);

    $this->actingAsStaff($pengajar)->get(route('onboarding.show'))->assertForbidden();
});

test('completing onboarding saves tenant data and marks the user onboarded', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $response = $this->actingAsStaff($admin)->put(route('onboarding.update'), [
        'address' => 'Jl. Merdeka No. 1',
        'phone' => '0812345678',
        'tagline' => 'Belajar bersama',
    ]);

    $response->assertRedirect(route('dashboard'));

    $tenant->refresh();
    expect($tenant->address)->toBe('Jl. Merdeka No. 1');
    expect($tenant->settings['landing']['tagline'])->toBe('Belajar bersama');
    expect($admin->fresh()->onboarded_at)->not->toBeNull();
});

test('completing onboarding with no fields still marks the user onboarded', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->put(route('onboarding.update'), [])
        ->assertRedirect(route('dashboard'));

    expect($admin->fresh()->onboarded_at)->not->toBeNull();
});

test('skipping onboarding marks the user onboarded without touching tenant data', function () {
    $tenant = Tenant::factory()->create(['address' => 'Alamat asli']);
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->post(route('onboarding.skip'))
        ->assertRedirect(route('dashboard'));

    expect($admin->fresh()->onboarded_at)->not->toBeNull();
    expect($tenant->fresh()->address)->toBe('Alamat asli');
});

test('pengajar cannot skip onboarding', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'onboarded_at' => null]);

    $this->actingAsStaff($pengajar)->post(route('onboarding.skip'))->assertForbidden();
});

test('admin without onboarding is redirected from the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});

test('admin without onboarding is redirected from settings', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'onboarded_at' => null]);

    $this->actingAsStaff($admin)->get(route('lembaga.edit'))
        ->assertRedirect(route('onboarding.show'));
});

test('onboarded admin reaches the dashboard directly', function () {
    $tenant = Tenant::factory()->create();
    $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);

    $this->actingAsStaff($admin)->get(route('dashboard'))->assertOk();
});

test('pengajar without onboarded_at still reaches the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $pengajar = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'pengajar', 'onboarded_at' => null]);

    $this->actingAsStaff($pengajar)->get(route('dashboard'))->assertOk();
});

test('manual registration lands on onboarding after email verification', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPA Cahaya',
        'subdomain' => 'tpa-cahaya',
        'name' => 'Admin Cahaya',
        'email' => 'admin-cahaya@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'admin-cahaya@example.com')->firstOrFail();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    // Verification happens on the apex, so the dashboard is reached through a
    // signed handoff onto the subdomain — see App\Support\TenantSessionHandoff.
    $dashboardResponse = followTenantHandoff($this->get($verificationUrl));
    $dashboardResponse->assertRedirect(route('dashboard', ['subdomain' => $user->tenant->subdomain]));

    $this->get($dashboardResponse->headers->get('Location'))
        ->assertRedirect(route('onboarding.show'));
});
