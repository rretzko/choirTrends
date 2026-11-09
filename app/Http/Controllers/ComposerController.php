<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Services\PublicProfileService;
use App\Services\PublicTrendsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ComposerController extends Controller
{
    public function index(PublicTrendsService $trends): View
    {
        return view('composers.index', [
            'artists' => $trends->allPublicArtists()->sortBy('sortKey')->values(),
            'minimumSchools' => $trends->minimumSchools(),
        ]);
    }

    public function show(int $id, string $slug, PublicTrendsService $trends, PublicProfileService $profiles): View|RedirectResponse
    {
        $artist = Artist::query()->findOrFail($id);

        abort_unless($trends->isPublicArtist($artist->id), 404);

        if ($slug !== $artist->publicSlug()) {
            return redirect()->to($trends->artistUrl($artist->id, $artist->artist_name), 301);
        }

        return view('composers.show', [
            'artist' => $artist,
            'profile' => $profiles->artistProfile($artist),
            'canonicalUrl' => $trends->artistUrl($artist->id, $artist->artist_name),
        ]);
    }
}
