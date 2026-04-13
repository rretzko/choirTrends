<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserGuide;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class UserGuidePdfController extends Controller
{
    public function __invoke(): Response
    {
        $sections = UserGuide::query()->published()->ordered()->get();

        $pdf = Pdf::loadView('pdf.user-guide', [
            'sections' => $sections,
        ]);

        $pdf->setPaper('letter');

        return $pdf->download('ChoirTrends-Users-Guide.pdf');
    }
}
