<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get('http://tpq-demo.santriq.test/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAsStaff($user)->get(route('dashboard'));
    $response->assertOk();
});
