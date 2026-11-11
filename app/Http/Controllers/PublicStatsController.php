<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Public, read-only headline counts (the same four shown on the welcome page),
 * consumed by sibling sites such as mfrholdings.com. Cached for ten minutes so
 * repeated polling never touches the database more than that.
 */
class PublicStatsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        /** @var array{programs: int, songs: int, artists: int, schools: int} $stats */
        $stats = Cache::remember('public-stats', now()->addMinutes(10), fn (): array => [
            'programs' => Program::count(),
            'songs' => SongTitle::count(),
            'artists' => Artist::count(),
            'schools' => School::count(),
        ]);

        return response()
            ->json($stats + ['generated_at' => now()->toIso8601String()])
            ->header('Cache-Control', 'public, max-age=600');
    }
}
