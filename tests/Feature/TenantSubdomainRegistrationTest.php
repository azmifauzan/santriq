<?php

use App\Models\Tenant;

test('registration rejects a reserved subdomain', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPQ Uji',
        'subdomain' => 'www',
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('subdomain');
});

test('registration rejects an invalid subdomain format', function () {
    $this->post(route('register.store'), [
        'institution_name' => 'TPQ Uji',
        'subdomain' => 'Not Valid!',
        'name' => 'Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('subdomain');
});

test('registration rejects a subdomain already taken', function () {
    Tenant::factory()->create(['subdomain' => 'sudah-ada']);

    $this->post(route('register.store'), [
        'institution_name' => 'TPQ Uji',
        'subdomain' => 'sudah-ada',
        'name' => 'Admin',
        'email' => 'admin2@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('subdomain');
});

test('registration redirects to the new subdomain login screen', function () {
    $response = $this->post(route('register.store'), [
        'institution_name' => 'TPQ Baru',
        'subdomain' => 'tpq-baru',
        'name' => 'Admin',
        'email' => 'admin3@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();
});

test('subdomain availability check reports taken and free values', function () {
    Tenant::factory()->create(['subdomain' => 'taken-name']);

    $this->getJson(route('subdomain.availability', ['value' => 'taken-name']))
        ->assertJson(['available' => false]);

    $this->getJson(route('subdomain.availability', ['value' => 'free-name']))
        ->assertJson(['available' => true]);

    $this->getJson(route('subdomain.availability', ['value' => 'admin']))
        ->assertJson(['available' => false]);
});
