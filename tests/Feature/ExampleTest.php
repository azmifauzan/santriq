<?php

use App\Models\Tenant;
use App\Support\DemoTenant;
use Inertia\Testing\AssertableInertia as Assert;

test('the public landing page is available', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
});

test('the public landing page links to the demo tenant when it exists', function () {
    Tenant::factory()->create(['subdomain' => DemoTenant::SUBDOMAIN]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('demoUrl', DemoTenant::url('/login')));
});

test('the public landing page has no demo link when the demo tenant is not seeded', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('demoUrl', null));
});

test('legal pages are available', function (string $route, string $document) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Legal')
            ->where('document', $document)
            ->where('content.title', config("legal.{$document}.title")));
})->with([
    'privacy' => ['privacy', 'privacy'],
    'terms' => ['terms', 'terms'],
]);

test('the landing page names the app and its purpose without running javascript', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('<meta name="description" content="SantriQ adalah platform', false);
    $response->assertSee('<noscript>', false);
    $response->assertSee('<h1>SantriQ</h1>', false);
    $response->assertSee('free and open source school management platform');
});

test('legal pages render their full text without running javascript', function (string $route, string $document) {
    $content = config("legal.{$document}");

    $response = $this->get(route($route))->assertOk();

    $response->assertSee('<noscript>', false);
    $response->assertSee($content['description']);

    foreach ($content['sections'] as $section) {
        $response->assertSee($section['title']);

        foreach ([...$section['paragraphs'] ?? [], ...$section['items'] ?? []] as $text) {
            $response->assertSee($text, false);
        }
    }
})->with([
    'privacy' => ['privacy', 'privacy'],
    'terms' => ['terms', 'terms'],
]);

test('sign-in pages identify the site without running javascript', function (string $route) {
    // A credential form that renders as a blank page to a crawler cannot be
    // attributed to anyone — see resources/views/partials/crawler-fallback.blade.php.
    $response = $this->get(route($route))->assertOk();

    $response->assertSee('<noscript>', false);
    $response->assertSee('SantriQ');
    $response->assertSee('only ever asks for your', false)
        ->assertSee('tidak pernah meminta data kartu, PIN, atau OTP');
})->with(['login', 'register', 'password.request']);
