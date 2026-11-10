<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PublicTrendsService;
use App\Services\SchoolYearReportService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(SchoolYearReportService $reports): View
    {
        return view('reports.index', [
            'schoolYears' => collect($reports->availableSchoolYears())
                ->map(fn (string $slug, int $startYear): array => [
                    'label' => SchoolYearReportService::labelFor($startYear),
                    'url' => route('reports.show', $slug),
                ])
                ->values(),
        ]);
    }

    public function show(string $schoolYear, SchoolYearReportService $reports, PublicTrendsService $trends): View
    {
        $startYear = SchoolYearReportService::startYearFromSlug($schoolYear);

        abort_if($startYear === null || ! array_key_exists($startYear, $reports->availableSchoolYears()), 404);

        return view('reports.show', [
            'report' => $reports->report($startYear),
            'minimumSchools' => $trends->minimumSchools(),
            'canonicalUrl' => route('reports.show', $schoolYear),
        ]);
    }
}
