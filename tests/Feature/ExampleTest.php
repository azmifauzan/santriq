<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the public landing page is available', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('legal pages are available', function (string $route, string $document) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Legal')
            ->where('document', $document));
})->with([
    'privacy' => ['privacy', 'privacy'],
    'terms' => ['terms', 'terms'],
]);
