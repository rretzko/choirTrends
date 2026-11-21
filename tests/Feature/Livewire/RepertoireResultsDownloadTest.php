<?php

declare(strict_types=1);

use App\Livewire\Dashboard\RepertoireFinder as DashboardRepertoireFinder;
use App\Livewire\Guest\RepertoireFinder as GuestRepertoireFinder;
use App\Livewire\SongTitles\Index as SongTitlesIndex;
use App\Models\RepertoireQuery;
use App\Models\User;
use App\Services\RepertoireResultsExporter;
use Livewire\Livewire;

/**
 * @return array<string, mixed>
 */
function downloadableResponse(): array
{
    return [
        'query_interpretation' => ['interpretation_notes' => 'Director wants SATB sea chanties.'],
        'results' => [
            [
                'song_title' => 'Three Sea Shanties',
                'composer' => 'Jonathan Willcocks',
                'arranger' => null,
                'voicing' => 'SATB',
                'source' => 'web_knowledge',
                'difficulty_by_part' => ['soprano' => 'easy', 'alto' => 'easy', 'tenor' => 'easy', 'bass' => 'easy'],
                'fit_rationale' => 'A proven concert set, "with quotes", and commas.',
                'youtube_url' => null,
                'tags' => ['sea shanty', 'a cappella'],
            ],
            [
                'song_title' => 'What Shall We Do with the Drunken Sailor',
                'composer' => 'Traditional',
                'arranger' => 'Alice Parker',
                'voicing' => 'SATB',
                'source' => 'internal_catalog',
                'difficulty_by_part' => ['soprano' => 'moderate', 'alto' => 'moderate', 'tenor' => 'moderate', 'bass' => null],
                'fit_rationale' => 'The Parker/Shaw arrangement.',
                'youtube_url' => 'https://www.youtube.com/watch?v=abc123',
                'tags' => [],
            ],
        ],
    ];
}

test('guest can download their results in each format', function (string $format) {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'ip_address' => '127.0.0.1',
        'query_text' => 'SATB Sea Chanties',
        'response' => downloadableResponse(),
    ]);

    Livewire::test(GuestRepertoireFinder::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->assertSee(__('Spreadsheet (.csv)'))
        ->call('downloadResults', $format)
        ->assertFileDownloaded("ChoirTrends-repertoire-satb-sea-chanties.{$format}");
})->with(['txt', 'csv', 'pdf']);

test('guest cannot download results from a different ip address', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'ip_address' => '10.0.0.99',
        'response' => downloadableResponse(),
    ]);

    Livewire::test(GuestRepertoireFinder::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->call('downloadResults', 'csv')
        ->assertNoFileDownloaded();
});

test('guest cannot download a signed-in user search', function () {
    $repertoireQuery = RepertoireQuery::factory()->forUser()->create([
        'ip_address' => '127.0.0.1',
        'response' => downloadableResponse(),
    ]);

    Livewire::test(GuestRepertoireFinder::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->call('downloadResults', 'csv')
        ->assertNoFileDownloaded();
});

test('rejects unsupported formats and missing results', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'ip_address' => '127.0.0.1',
        'response' => downloadableResponse(),
    ]);

    Livewire::test(GuestRepertoireFinder::class)
        ->call('downloadResults', 'csv')
        ->assertNoFileDownloaded()
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->call('downloadResults', 'exe')
        ->assertNoFileDownloaded()
        ->set('aiResultQueryId', 999999)
        ->call('downloadResults', 'csv')
        ->assertNoFileDownloaded();
});

test('download menu is hidden when there are no results', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'ip_address' => '127.0.0.1',
        'response' => ['query_interpretation' => [], 'results' => []],
    ]);

    Livewire::test(GuestRepertoireFinder::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->assertDontSee(__('Spreadsheet (.csv)'));
});

test('signed-in user can download their results from the dashboard and song titles', function (string $component) {
    $user = User::factory()->withoutTwoFactor()->create();
    $this->actingAs($user);

    $repertoireQuery = RepertoireQuery::factory()->forUser($user)->create([
        'query_text' => 'SATB Sea Chanties',
        'response' => downloadableResponse(),
    ]);

    Livewire::test($component)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->assertSee(__('Spreadsheet (.csv)'))
        ->call('downloadResults', 'pdf')
        ->assertFileDownloaded('ChoirTrends-repertoire-satb-sea-chanties.pdf');
})->with([
    'dashboard' => [DashboardRepertoireFinder::class],
    'song titles' => [SongTitlesIndex::class],
]);

test('signed-in user cannot download another user search', function () {
    $this->actingAs(User::factory()->withoutTwoFactor()->create());

    $repertoireQuery = RepertoireQuery::factory()->forUser()->create([
        'response' => downloadableResponse(),
    ]);

    Livewire::test(SongTitlesIndex::class)
        ->set('aiResultQueryId', $repertoireQuery->id)
        ->call('downloadResults', 'txt')
        ->assertNoFileDownloaded();
});

test('text export lists every match with credits, difficulty, tags and links', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'query_text' => 'SATB Sea Chanties',
        'response' => downloadableResponse(),
    ]);

    $text = app(RepertoireResultsExporter::class)->toText($repertoireQuery);

    expect($text)
        ->toContain('Search: SATB Sea Chanties')
        ->toContain('Director wants SATB sea chanties.')
        ->toContain('1. Three Sea Shanties')
        ->toContain('Jonathan Willcocks · SATB')
        ->toContain('2. What Shall We Do with the Drunken Sailor')
        ->toContain('Traditional / arr. Alice Parker · SATB')
        ->toContain('Source: In ChoirTrends')
        ->toContain('Soprano Moderate | Alto Moderate | Tenor Moderate | Bass Unknown')
        ->toContain('Tags: sea shanty, a cappella')
        ->toContain('YouTube: https://www.youtube.com/watch?v=abc123');
});

test('csv export has a header row and one properly escaped row per match', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'response' => downloadableResponse(),
    ]);

    $csv = app(RepertoireResultsExporter::class)->toCsv($repertoireQuery);

    expect($csv)->toStartWith("\xEF\xBB\xBF");

    $rows = array_map(
        fn (string $line): array => str_getcsv($line, escape: ''),
        array_values(array_filter(explode("\n", substr($csv, 3)))),
    );

    expect($rows)->toHaveCount(3)
        ->and($rows[0][0])->toBe('Title')
        ->and($rows[1][0])->toBe('Three Sea Shanties')
        ->and($rows[1][9])->toBe('sea shanty, a cappella')
        ->and($rows[1][10])->toBe('A proven concert set, "with quotes", and commas.')
        ->and($rows[2][2])->toBe('Alice Parker')
        ->and($rows[2][8])->toBe('Unknown')
        ->and($rows[2][11])->toBe('https://www.youtube.com/watch?v=abc123');
});

test('pdf export renders a pdf document', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'response' => downloadableResponse(),
    ]);

    expect(app(RepertoireResultsExporter::class)->toPdf($repertoireQuery))->toStartWith('%PDF');
});

test('exports tolerate a missing response and a query with no sluggable text', function () {
    $repertoireQuery = RepertoireQuery::factory()->create([
        'query_text' => '!!!',
        'response' => null,
    ]);

    $exporter = app(RepertoireResultsExporter::class);

    expect($exporter->filename($repertoireQuery, 'csv'))->toBe('ChoirTrends-repertoire-search.csv')
        ->and($exporter->toText($repertoireQuery))->toContain('No matches found.')
        ->and($exporter->toPdf($repertoireQuery))->toStartWith('%PDF');
});
