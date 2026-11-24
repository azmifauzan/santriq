<?php

use App\Http\Controllers\GuideController;

test('guide page renders markdown with one suaranetijen link and a server-side description', function () {
    $this->withoutVite();
    $slug = 'operator-seluler-untuk-notifikasi-wali-telegram';

    $response = $this->get(route('guide', $slug));

    $response->assertOk();
    $response->assertSee('<meta name="description" content="'.e(GuideController::GUIDES[$slug]['description']).'">', false);
    $response->assertInertia(fn ($page) => $page
        ->component('Guide')
        ->where('html', fn ($html) => str_contains($html, '<h2>')
            && substr_count($html, 'https://suaranetijen.id/') === 1)
    );
});

test('guide page is indexable with its own title, canonical and no-javascript fallback', function () {
    $this->withoutVite();
    $slug = 'operator-seluler-untuk-notifikasi-wali-telegram';
    $guide = GuideController::GUIDES[$slug];

    $this->get(route('guide', $slug))
        ->assertOk()
        ->assertDontSee('noindex', false)
        ->assertSee('<link rel="canonical" href="'.route('guide', $slug).'">', false)
        ->assertSee('<title>'.e($guide['title']).' - SantriQ</title>', false)
        ->assertSee('<h1>'.e($guide['title']).'</h1>', false)
        ->assertSee('https://suaranetijen.id/', false);
});

test('guide and its feature page link to each other', function () {
    $this->withoutVite();
    $slug = 'operator-seluler-untuk-notifikasi-wali-telegram';
    $feature = GuideController::GUIDES[$slug]['feature'];

    $this->get(route('guide', $slug))
        ->assertInertia(fn ($page) => $page->where('featureLink.href', route('feature', $feature)))
        ->assertSee('href="'.route('feature', $feature).'"', false);

    $this->get(route('feature', $feature))
        ->assertInertia(fn ($page) => $page->where('guides', fn ($guides) => collect($guides)->contains('href', route('guide', $slug))))
        ->assertSee('href="'.route('guide', $slug).'"', false);
});

test('guide is listed in the sitemap and the guides index', function () {
    $slug = 'operator-seluler-untuk-notifikasi-wali-telegram';

    $this->get(route('sitemap'))->assertSee(route('guide', $slug), false);
    $this->get(route('articles.index'))->assertInertia(fn ($page) => $page
        ->where('articles', fn ($articles) => collect($articles)->contains('href', route('guide', $slug)))
    );
});

test('unknown guide slug returns 404', function () {
    $this->get('/panduan/tidak-ada')->assertNotFound();
});
