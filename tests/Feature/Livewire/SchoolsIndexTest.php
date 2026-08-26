<?php

use App\Enums\SchoolType;
use App\Livewire\Schools\Index;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Models\UserPrivacy;
use Livewire\Livewire;

test('schools index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('schools.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('schools index displays schools with my filter', function () {
    $user = User::factory()->create();
    $mySchool = School::factory()->create();
    $user->schools()->attach($mySchool);

    $otherSchool = School::factory()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSet('filter', 'my')
        ->assertSee($mySchool->school_name)
        ->assertDontSee($otherSchool->school_name);
});

test('schools index displays all schools with all filter', function () {
    $user = User::factory()->create();
    $mySchool = School::factory()->create();
    $user->schools()->attach($mySchool);

    $otherSchool = School::factory()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee($mySchool->school_name)
        ->assertSee($otherSchool->school_name);
});

test('guests cannot access schools index', function () {
    $this->get(route('schools.index'))
        ->assertRedirect(route('login'));
});

test('school name is masked when owner has privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'school' => true,
    ]);

    $otherSchool = School::factory()->create(['school_name' => 'Secret Academy']);
    $otherUser->schools()->attach($otherSchool);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('School'.$otherSchool->id)
        ->assertDontSee('Secret Academy');
});

test('school name is shown when viewing own school with privacy enabled', function () {
    $user = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'school' => true,
    ]);

    $school = School::factory()->create(['school_name' => 'My Private School']);
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Private School');
});

test('school name is shown when owner has no privacy settings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // No privacy settings for other user
    $otherSchool = School::factory()->create(['school_name' => 'Public Academy']);
    $otherUser->schools()->attach($otherSchool);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Public Academy');
});

test('schools index displays counts for programs, ensembles, composers/arrangers, and songs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    // Create 2 programs for the school
    $program1 = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);
    $program2 = Program::factory()->create(['user_id' => $user->id, 'school_id' => $school->id]);

    // Create 3 ensembles for the school
    $ensemble1 = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Concert Choir']);
    $ensemble2 = Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Chamber Singers']);
    Ensemble::factory()->create(['school_id' => $school->id, 'ensemble_name' => 'Jazz Choir']);

    // Create artists
    $composer1 = Artist::factory()->create();
    $composer2 = Artist::factory()->create();
    $arranger = Artist::factory()->create();

    // Create songs with composers and arrangers
    $song1 = SongTitle::factory()->create(['composer_id' => $composer1->id, 'arranger_id' => $arranger->id]);
    $song2 = SongTitle::factory()->create(['composer_id' => $composer2->id, 'arranger_id' => null]);
    $song3 = SongTitle::factory()->create(['composer_id' => $composer1->id, 'arranger_id' => null]);

    // Attach songs to programs via pivot
    $program1->songTitles()->attach($song1->id, ['ensemble_id' => $ensemble1->id]);
    $program1->songTitles()->attach($song2->id, ['ensemble_id' => $ensemble1->id]);
    $program2->songTitles()->attach($song3->id, ['ensemble_id' => $ensemble2->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSeeInOrder([$school->school_name, '2', '3', '3', '3']);
});

test('schools index shows zero counts for school with no programs', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSeeInOrder([$school->school_name, '0', '0', '0', '0']);
});

test('schools index columns are sortable', function () {
    $user = User::factory()->create();

    $schoolA = School::factory()->create(['school_name' => 'Alpha School']);
    $schoolB = School::factory()->create(['school_name' => 'Beta School']);
    $user->schools()->attach([$schoolA->id, $schoolB->id]);

    // Give Beta more programs than Alpha
    Program::factory()->count(3)->create(['user_id' => $user->id, 'school_id' => $schoolB->id]);
    Program::factory()->create(['user_id' => $user->id, 'school_id' => $schoolA->id]);

    $this->actingAs($user);

    // Default sort: school_name asc
    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSet('sortBy', 'school_name')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Alpha School', 'Beta School'])
        // Sort by programs_count desc (click once for asc, then toggle)
        ->call('sort', 'programs_count')
        ->assertSet('sortBy', 'programs_count')
        ->assertSet('sortDirection', 'asc')
        ->assertSeeInOrder(['Alpha School', 'Beta School'])
        ->call('sort', 'programs_count')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder(['Beta School', 'Alpha School']);
});

test('school factory defaults to high school type', function () {
    $school = School::factory()->create();

    expect($school->school_type)->toBe(SchoolType::HighSchool);
});

test('schools index displays school type', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Downtown Community Choir', 'school_type' => SchoolType::CommunityChoir]);
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->assertSee('Community Choir');
});

test('schools index can filter by school type', function () {
    $user = User::factory()->create();
    $highSchool = School::factory()->create(['school_name' => 'Lincoln High', 'school_type' => SchoolType::HighSchool]);
    $churchChoir = School::factory()->create(['school_name' => 'First Baptist Choir', 'school_type' => SchoolType::ChurchChoir]);
    $user->schools()->attach([$highSchool->id, $churchChoir->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->set('typeFilter', SchoolType::ChurchChoir->value)
        ->assertSee('First Baptist Choir')
        ->assertDontSee('Lincoln High');
});

test('schools index paginates at 20 per page and a second page shows the remaining rows', function () {
    $user = User::factory()->create();
    $schools = School::factory()->count(25)->create();
    Program::factory()->for($user)->for($schools->first())->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertViewHas('schools', fn ($schools) => $schools->count() === 20)
        ->call('gotoPage', 2)
        ->assertViewHas('schools', fn ($schools) => $schools->count() === 5);
});

test('sorting schools by songs count sorts globally across pages, not just within a page', function () {
    $user = User::factory()->create();

    // 19 filler schools tied at 1 song each, plus a 20th filler with 2 songs (uniquely the
    // highest count of all 21 schools) — all created FIRST, in DB order.
    collect(range(1, 20))->each(function (int $i) use ($user) {
        $school = School::factory()->create(['school_name' => "Filler School {$i}"]);
        $program = Program::factory()->for($i === 1 ? $user : User::factory()->create())->for($school)->create();
        $songCount = $i === 20 ? 2 : 1;
        collect(range(1, $songCount))->each(function (int $j) use ($program, $i) {
            $song = SongTitle::create(['song_title' => "Filler Song {$i}-{$j}"]);
            $program->songTitles()->attach($song->id);
        });
    });

    // Marker school created LAST (21st), with zero songs — uniquely the lowest count, so it must
    // rank first ascending. Correct behavior (sort the full set, then slice to a page) puts the
    // Marker on page 1 and pushes "Filler School 20" (the uniquely-highest count) to page 2.
    // Buggy behavior (paginate in raw creation order first, then sort only within that page) would
    // do the opposite: Marker stays stranded alone on page 2 (it's the 21st row), while Filler
    // School 20 stays on page 1 (it's among the first 20 rows by creation order either way).
    School::factory()->create(['school_name' => 'Marker School']);

    $this->actingAs($user);

    $schools = Livewire::test(Index::class)
        ->set('filter', 'all')
        ->call('sort', 'songs_count')
        ->viewData('schools');

    $schoolNames = $schools->pluck('school_name')->all();

    expect($schoolNames)->toContain('Marker School')
        ->and($schoolNames)->not->toContain('Filler School 20');
});
