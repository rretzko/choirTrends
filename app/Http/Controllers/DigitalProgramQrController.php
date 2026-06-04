<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DigitalProgram;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Response;

class DigitalProgramQrController extends Controller
{
    // Clamp bounds for the optional ?size= query parameter
    private const MIN_SIZE = 100;

    private const MAX_SIZE = 1000;

    private const DEFAULT_SIZE = 400;

    public function __invoke(string $slug): Response
    {
        $dp = DigitalProgram::where('slug', $slug)->firstOrFail();

        abort_unless($dp->is_published, 404);

        $size = (int) request()->query('size', self::DEFAULT_SIZE);
        $size = max(self::MIN_SIZE, min(self::MAX_SIZE, $size));

        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd);
        $svg = (new Writer($renderer))->writeString(
            route('program.public', ['slug' => $dp->slug])
        );

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
