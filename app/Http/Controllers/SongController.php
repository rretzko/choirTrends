<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SongTitle;
use App\Services\PublicProfileService;
use App\Services\PublicTrendsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SongController extends Controller
{
    public function show(int $id, string $slug, PublicTrendsService $trends, PublicProfileService $profiles): View|RedirectResponse
    {
        $song = SongTitle::query()->findOrFail($id);

        abort_unless($trends->isPublicSongTitle($song->id), 404);

        if ($slug !== $song->publicSlug()) {
            return redirect()->to($trends->songUrl($song->id, $song->song_title), 301);
        }

        $profile = $profiles->songProfile($song);

        abort_if($profile === null, 404);

        return view('songs.show', [
            'song' => $song,
            'profile' => $profile,
            'canonicalUrl' => $trends->songUrl($song->id, $song->song_title),
        ]);
    }
}
