<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Served from a route rather than public/robots.txt, because Vapor redirects
 * static public files to the asset CDN. Non-production environments (staging)
 * block all crawling so they never compete with the live site.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = app()->environment('production')
            ? ['User-agent: *', 'Disallow:', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
