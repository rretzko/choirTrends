<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PublicTrendsService;
use Illuminate\View\View;

class TrendsController extends Controller
{
    public function __invoke(PublicTrendsService $trends): View
    {
        return view('trends.index', [
            'pieces' => $trends->topPieces(),
            'composers' => $trends->topComposers(),
            'arrangers' => $trends->topArrangers(),
            'stats' => $trends->headlineStats(),
            'minimumSchools' => $trends->minimumSchools(),
        ]);
    }
}
