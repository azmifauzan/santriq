<?php

use App\Models\Tenant;
use App\Support\DemoTenant;
use Illuminate\Support\Str;
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

test('the request-feature page links to the maintainer\'s social profiles', function () {
    $this->get(route('request-feature'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('RequestFeature')
            ->where('links.threads', 'https://www.threads.com/@azmifauzan')
            ->where('links.facebook', 'https://www.facebook.com/azmifauzan/')
            ->where('links.linkedin', 'https://www.linkedin.com/in/fauzan-azmi-29094522/'));
});

test('the landing page names the app and its purpose without running javascript', function () {
    $response = $this->get(route('home'))->assertOk();

    $response->assertSee('<meta name="description" content="SantriQ adalah platform', false);
    $response->assertSee('<noscript>', false);
    $response->assertSee('<h1>SantriQ</h1>', false);
    $response->assertSee('free and open source school management platform');
});

test('pages carry open graph tags for social link previews', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<meta property="og:image" content="'.asset('og-image.jpg').'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);

    expect(public_path('og-image.jpg'))->toBeFile();
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

test('the google analytics tag is rendered only when a measurement id is configured', function () {
    $this->get(route('home'))->assertDontSee('googletagmanager.com', false);

    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $this->get(route('home'))
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-TEST123', false)
        ->assertSee("gtag('config', 'G-TEST123')", false);
});

test('the google analytics tag is not rendered on tenant app screens', function () {
    config(['services.google_analytics.measurement_id' => 'G-TEST123']);

    $this->get(route('login'))->assertSee('googletagmanager.com', false);

    $tenant = Tenant::factory()->create();
    $this->get(route('tenant.landing', ['subdomain' => $tenant->subdomain]))
        ->assertDontSee('googletagmanager.com', false);
    $this->get(route('login', ['subdomain' => $tenant->subdomain]))
        ->assertDontSee('googletagmanager.com', false);
});

test('the landing page carries SEO tags in its server-rendered HTML', function () {
    $this->get(route('home'))
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertSee('Aplikasi Manajemen TPQ/TPA Gratis', false)
        ->assertSee('"@type":"SoftwareApplication"', false)
        ->assertDontSee('noindex', false);
});

test('the canonical url drops tracking query strings', function () {
    $this->get(route('home', ['ref' => 'artifisial']))
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false);
});

test('screens outside the marketing pages are noindex', function () {
    $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
});

test('the sitemap lists the public pages', function () {
    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('home'), false)
        ->assertSee(route('privacy'), false)
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
});

test('every feature page renders its own title, canonical, description and FAQ JSON-LD', function (string $slug) {
    $feature = config("features.{$slug}");

    $this->get(route('feature', $slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Feature')->where('slug', $slug))
        ->assertSee('<title inertia>'.e($feature['title']).' - SantriQ</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('feature', $slug).'">', false)
        ->assertSee('<meta name="description" content="'.e($feature['description']).'">', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertDontSee('noindex', false)
        ->assertSee('<h1>'.e($feature['heading']).'</h1>', false);
})->with(fn () => array_keys(config('features')));

test('an unknown feature page is a 404', function () {
    $this->get('/fitur/tidak-ada')->assertNotFound();
});

test('the request-feature page still lives at /fitur', function () {
    $this->get(route('request-feature'))->assertOk();
});

test('the sitemap lists every feature page and the landing page links to them', function () {
    $response = $this->get(route('sitemap'))->assertOk();

    foreach (array_keys(config('features')) as $slug) {
        $response->assertSee(route('feature', $slug), false);
    }

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('featurePages', count(config('features'))));
});

test('every public marketing page opts out of the panel layout in app.ts', function (string $component) {
    $nullLayoutList = Str::between(file_get_contents(resource_path('js/app.ts')), 'case [', '].includes(name)');

    expect($nullLayoutList)->toContain("'{$component}'");
})->with(['Welcome', 'Legal', 'RequestFeature', 'Feature']);
