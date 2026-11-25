<?php

use App\Http\Controllers\GuideController;
use App\Models\Tenant;
use App\Support\DemoTenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

    $response->assertSee('<meta name="description" content="SantriQ menyediakan absensi QR santri', false);
    $response->assertSee('<noscript>', false);
    $response->assertSee('<h1>Absensi QR santri, kelola TPQ/TPA lebih mudah.</h1>', false);
    $response->assertSee('free and open source school management platform');
    $response->assertSee('href="'.route('register').'"', false);
    $response->assertSee('href="#tentang"', false);
    $response->assertSee('href="'.route('request-feature').'#request"', false);
    $response->assertSee('href="'.route('privacy').'"', false);
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
    $response = $this->get(route('home'))->assertOk();
    $html = $response->getContent();

    $response
        ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
        ->assertSee('<title>Absensi QR Santri &amp; Aplikasi TPQ/TPA Gratis - SantriQ</title>', false)
        ->assertSee('"@type":"WebSite"', false)
        ->assertSee('"@type":"Organization"', false)
        ->assertSee('"@type":"Person"', false)
        ->assertSee('"@type":"SoftwareApplication"', false)
        ->assertDontSee('noindex', false)
        ->assertDontSee('<meta name="keywords"', false);

    preg_match('/<title>([^<]+)<\/title>/', $html, $titleMatch);
    $title = html_entity_decode($titleMatch[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

    preg_match('/<meta name="description" content="([^"]+)">/', $html, $descriptionMatch);
    $description = html_entity_decode($descriptionMatch[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

    preg_match_all('/<h1>([^<]+)<\/h1>/', $html, $h1Matches);

    expect(mb_strlen($title))->toBeLessThan(60)
        ->and(mb_strtolower($title))->toContain('qr santri')
        ->and(mb_strlen($description))->toBeGreaterThanOrEqual(120)->toBeLessThanOrEqual(155)
        ->and(mb_strtolower($description))->toContain('qr santri')
        ->and($h1Matches[1])->toHaveCount(1)
        ->and(mb_strtolower($h1Matches[1][0]))->toContain('qr santri');

    preg_match('/<script type="application\/ld\+json">\s*(\{.*?\})\s*<\/script>/s', $html, $jsonLdMatch);
    $schema = json_decode($jsonLdMatch[1] ?? '', true, 512, JSON_THROW_ON_ERROR);
    $types = collect($schema['@graph'] ?? [])->pluck('@type')->all();

    preg_match('/<main>(.*?)<\/main>/s', $html, $mainMatch);
    $mainWords = count(array_filter(preg_split('/\s+/u', trim(strip_tags($mainMatch[1] ?? '')))));

    expect($types)->toContain('Organization', 'Person', 'SoftwareApplication', 'WebSite')
        ->and($mainWords)->toBeGreaterThanOrEqual(400);
});

test('every public sitemap page renders substantive non-thin content without javascript', function () {
    $this->withoutVite();

    $sitemap = $this->get(route('sitemap'))->assertOk()->getContent();
    preg_match_all('/<loc>([^<]+)<\/loc>/', $sitemap, $matches);

    expect($matches[1])->not->toBeEmpty();

    foreach ($matches[1] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        preg_match('/<main>(.*?)<\/main>/s', $html, $mainMatch);
        $mainWords = count(array_filter(preg_split('/\s+/u', trim(strip_tags($mainMatch[1] ?? '')))));
        preg_match_all('/<h1>([^<]+)<\/h1>/', $html, $h1Matches);

        expect($h1Matches[1])->toHaveCount(1, "Expected 1 <h1> on {$url}")
            ->and($mainWords)->toBeGreaterThanOrEqual(300, "Thin content on {$url} ({$mainWords} words)");
    }
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
        ->assertSee('<title>'.e($feature['title']).' - SantriQ</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('feature', $slug).'">', false)
        ->assertSee('<meta name="description" content="'.e($feature['description']).'">', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertDontSee('noindex', false)
        ->assertSee('<h1>'.e($feature['heading']).'</h1>', false);
})->with(array_keys(require __DIR__.'/../../config/features.php'));

test('an unknown feature page is a 404', function () {
    $this->get('/fitur/tidak-ada')->assertNotFound();
});

test('the request-feature page still lives at /fitur', function () {
    $this->get(route('request-feature'))->assertOk();
});

test('the request-feature page has its own title, description and fallback linking every feature page', function () {
    $response = $this->get(route('request-feature'))
        ->assertOk()
        ->assertSee('<title>Fitur Aplikasi Manajemen TPQ/TPA - SantriQ</title>', false)
        ->assertSee('<meta name="description" content="Daftar fitur SantriQ', false)
        ->assertSee('<h1>Semua yang dibutuhkan untuk mengelola TPA/TPQ</h1>', false);

    foreach (array_keys(config('features')) as $slug) {
        $response->assertSee('href="'.route('feature', $slug).'"', false);
    }
});

test('legal pages have their own title', function (string $route, string $document) {
    $this->get(route($route))
        ->assertSee('<title>'.e(config("legal.{$document}.title")).' - SantriQ</title>', false);
})->with([
    'privacy' => ['privacy', 'privacy'],
    'terms' => ['terms', 'terms'],
]);

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
})->with(['Welcome', 'Legal', 'RequestFeature', 'Feature', 'Article', 'ArticleIndex', 'Guide']);

test('every guide article renders its title, canonical, JSON-LD and fallback', function (string $slug) {
    $article = config("articles.{$slug}");

    $this->get(route('article', $slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Article')->where('slug', $slug)->where('templateHref', route('article.template', $slug)))
        ->assertSee('<title>'.e($article['title']).' - SantriQ</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('article', $slug).'">', false)
        ->assertSee('<meta name="description" content="'.e($article['description']).'">', false)
        ->assertSee('"@type":"Article"', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertDontSee('noindex', false)
        ->assertSee('<h1>'.e($article['heading']).'</h1>', false);
})->with(array_keys(require __DIR__.'/../../config/articles.php'));

test('the guide index lists every article and an unknown guide is a 404', function () {
    $this->get(route('articles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('ArticleIndex')->has('articles', count(config('articles')) + count(GuideController::GUIDES)))
        ->assertSee('<link rel="canonical"', false)
        ->assertSee('<title>Template dan Panduan Administrasi TPQ/TPA Gratis - SantriQ</title>', false)
        ->assertSee('<meta name="description" content="Unduh template Excel gratis', false);

    $this->get('/panduan/tidak-ada')->assertNotFound();
    $this->get('/panduan/tidak-ada/template')->assertNotFound();
});

test('the sitemap lists the guides and feature pages link back to their guides', function () {
    $response = $this->get(route('sitemap'))->assertOk()->assertSee(route('articles.index'), false);

    foreach (array_keys(config('articles')) as $slug) {
        $response->assertSee(route('article', $slug), false);
    }

    $this->get(route('feature', 'spp'))
        ->assertInertia(fn (Assert $page) => $page->where('guides.0.href', route('article', 'buku-kas-spp-tpq')));
});

test('the guide templates download as spreadsheets with working formulas', function () {
    $load = fn (string $slug) => IOFactory::load(
        $this->get(route('article.template', $slug))->assertOk()->baseResponse->getFile()->getPathname()
    );

    $report = $load('format-rapor-tpq');
    expect($report->getSheetByName('Rapor')->getCell('A1')->getValue())->toBe('RAPOR SANTRI TPQ/TPA');

    $cash = $load('buku-kas-spp-tpq');
    expect($cash->getSheetNames())->toBe(['Buku Kas', 'Rekap SPP']);
    $cash->getSheetByName('Buku Kas')->setCellValue('D2', 50000)->setCellValue('E3', 10000)->setCellValue('D3', 0);
    expect($cash->getSheetByName('Buku Kas')->getCell('F3')->getCalculatedValue())->toBe(40000);
    $cash->getSheetByName('Rekap SPP')->setCellValue('C2', 25000)->setCellValue('D2', 25000);
    expect($cash->getSheetByName('Rekap SPP')->getCell('O2')->getCalculatedValue())->toBe(50000);
});
