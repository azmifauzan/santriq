<?php

namespace App\Http\Controllers;

use App\Exports\Guides\CashBookGuideExport;
use App\Exports\Guides\ReportCardGuideExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArticleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ArticleIndex', [
            'articles' => array_map(fn (string $slug): array => [
                'title' => config("articles.{$slug}.title"),
                'description' => config("articles.{$slug}.description"),
                'href' => route('article', $slug),
            ], array_map('strval', array_keys(config('articles')))),
        ]);
    }

    /**
     * Render a guide from config/articles.php (the text is a prop so
     * app.blade.php can reuse it for JSON-LD and the no-JavaScript fallback).
     */
    public function show(string $article): Response
    {
        /** @var array{feature: string, template: array{key: string, label: string}}|null $content */
        $content = config("articles.{$article}");

        abort_if($content === null, 404);

        return Inertia::render('Article', [
            'slug' => $article,
            'content' => $content,
            'featureLink' => [
                'title' => config("features.{$content['feature']}.nav"),
                'href' => route('feature', $content['feature']),
            ],
            'templateHref' => route('article.template', $article),
        ]);
    }

    public function template(Request $request, string $article): BinaryFileResponse
    {
        return match ($article) {
            'format-rapor-tpq' => Excel::download(new ReportCardGuideExport, 'template-rapor-santri-tpq.xlsx'),
            'buku-kas-spp-tpq' => Excel::download(new CashBookGuideExport, 'template-buku-kas-spp-tpq.xlsx'),
            default => abort(404),
        };
    }
}
