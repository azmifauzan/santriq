<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Render a legal document from config/legal.php.
     *
     * The document is passed as a prop so app.blade.php can render the same
     * text into its no-JavaScript fallback.
     */
    public function show(string $document): Response
    {
        $content = config("legal.{$document}");

        abort_if($content === null, 404);

        return Inertia::render('Legal', [
            'document' => $document,
            'content' => $content,
        ]);
    }
}
