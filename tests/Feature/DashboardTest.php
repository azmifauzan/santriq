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

test('user panel header includes the theme toggle', function () {
    $header = file_get_contents(resource_path('js/components/AppSidebarHeader.vue'));

    expect($header)->toContain('<ThemeToggle class="ml-auto" />');
});
