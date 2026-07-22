<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    /**
     * Generate SVG string for given payload.
     */
    public static function generateSvg(string $payload, int $size = 200): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );
        $writer = new Writer($renderer);

        return $writer->writeString($payload);
    }
}
