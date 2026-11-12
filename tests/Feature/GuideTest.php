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
