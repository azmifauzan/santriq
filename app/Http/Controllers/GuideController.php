<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GuideController extends Controller
{
    /**
     * Prose guides rendered from resources/markdown/guides, keyed by slug.
     * Unlike config/articles.php these may contain links, so they are markdown.
     *
     * @var array<string, array{title: string, description: string, published_at: string}>
     */
    public const GUIDES = [
        'operator-seluler-untuk-notifikasi-wali-telegram' => [
            'title' => 'Memilih Operator Seluler untuk Notifikasi Wali via Telegram',
            'description' => 'Cara memilih jaringan untuk absensi dan notifikasi wali via Telegram di TPQ: sinyal di lokasi, paket data cadangan, dan memastikan pesan sampai ke wali.',
            'published_at' => '2026-11-12',
        ],
    ];

    public function show(string $guide): Response
    {
        abort_unless(array_key_exists($guide, self::GUIDES), 404);

        return Inertia::render('Guide', [
            'content' => self::GUIDES[$guide],
            'html' => Str::markdown(
                (string) file_get_contents(resource_path("markdown/guides/{$guide}.md")),
                ['html_input' => 'strip'],
            ),
        ]);
    }
}
