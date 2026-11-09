<?php

use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Creates one program at its own school containing the given songs (public trends tests).
 *
 * @param  array<int, SongTitle>  $songs
 * @param  array<string, mixed>  $schoolAttributes
 * @param  array<string, mixed>  $programAttributes
 * @param  array<string, mixed>  $pivotAttributes
 */
function performAtNewSchool(array $songs, array $schoolAttributes = [], array $programAttributes = [], array $pivotAttributes = []): Program
{
    $school = School::factory()->create($schoolAttributes);
    $program = Program::factory()->for(User::factory())->for($school)->create($programAttributes);

    foreach (array_values($songs) as $index => $song) {
        $program->songTitles()->attach($song->id, ['sort_order' => $index + 1, 'ensemble_sort_order' => 1, ...$pivotAttributes]);
    }

    return $program;
}
