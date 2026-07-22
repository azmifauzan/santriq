<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the public landing page is available', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});
