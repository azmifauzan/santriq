<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class FeatureController extends Controller
{
    /**
     * Render a feature landing page from config/features.php.
     *
     * The content is a prop so app.blade.php can render the same text into the
     * FAQPage JSON-LD and the no-JavaScript fallback.
     */
    public function show(string $feature): Response
    {
        /** @var array{related: list<string>}|null $content */
        $content = config("features.{$feature}");

        abort_if($content === null, 404);

        return Inertia::render('Feature', [
            'slug' => $feature,
            'content' => $content,
            'related' => array_map(fn (string $slug): array => [
                'slug' => $slug,
                'title' => config("features.{$slug}.nav"),
                'href' => route('feature', $slug),
            ], $content['related']),
        ]);
    }
}
