<?php

use App\Models\Classroom;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

test('landing page is reachable at {domain}/{subdomain} when wildcard DNS is not active', function () {
    $tenant = Tenant::factory()->create(['subdomain' => 'tpq-fallback']);
    Student::factory()->count(2)->create(['tenant_id' => $tenant->id, 'status' => 'active']);
    Classroom::factory()->create(['tenant_id' => $tenant->id]);

    $this->get('http://santriq.test/tpq-fallback')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tenant/Landing')
            ->where('tenant.id', $tenant->id)
            ->where('stats.students', 2)
            ->where('stats.classrooms', 1)
        );
});

test('an unknown subdomain path 404s instead of falling through to the marketing page', function () {
    $this->get('http://santriq.test/tidak-ada')->assertNotFound();
});

test('the bare apex path still serves the marketing page', function () {
    $this->get('http://santriq.test/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Welcome'));
});

test('staff routes are reachable under the {subdomain} path prefix and stay tenant-scoped', function () {
    $tenantA = Tenant::factory()->create(['subdomain' => 'lembaga-fallback-a']);
    $tenantB = Tenant::factory()->create(['subdomain' => 'lembaga-fallback-b']);
    $adminA = User::factory()->create(['tenant_id' => $tenantA->id, 'role' => 'admin']);

    $this->actingAs($adminA)
        ->get("http://santriq.test/{$tenantA->subdomain}/dashboard")
        ->assertOk();

    // A session for lembaga A hitting lembaga B's path is bounced, same as
    // EnsureStaffTenantMatchesSubdomain does for the subdomain-host shape.
    $this->actingAs($adminA)
        ->get("http://santriq.test/{$tenantB->subdomain}/dashboard")
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('registration and login stay on the apex regardless of fallback mode', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPQ Fallback Register',
        'subdomain' => 'tpq-fallback-register',
        'name' => 'Admin',
        'email' => 'fallback-admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('login', ['registered' => 1]));

    $tenant = Tenant::where('subdomain', 'tpq-fallback-register')->first();
    $user = User::where('email', 'fallback-admin@example.com')->first();

    $loginResponse = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $loginResponse->assertRedirect(route('dashboard', ['subdomain' => $tenant->subdomain]));
});
