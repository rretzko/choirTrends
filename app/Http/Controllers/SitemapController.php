<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use XMLWriter;

/**
 * Built with XMLWriter rather than Blade: an `<?xml ?>` declaration in a Blade
 * view is parsed as PHP when short_open_tag is enabled (as on Vapor).
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        /** @var array<int, array{loc: string, changefreq: string, priority: string}> $urls */
        $urls = [
            ['loc' => route('home'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('trends'), 'changefreq' => 'weekly', 'priority' => '0.9'],
            ['loc' => route('register'), 'changefreq' => 'monthly', 'priority' => '0.5'],
        ];

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($urls as $url) {
            $xml->startElement('url');
            $xml->writeElement('loc', $url['loc']);
            $xml->writeElement('changefreq', $url['changefreq']);
            $xml->writeElement('priority', $url['priority']);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
