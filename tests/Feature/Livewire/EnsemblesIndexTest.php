<?php

use App\Livewire\Ensembles\Index;
use App\Models\Ensemble;
use App\Models\School;
use App\Models\User;
use App\Models\UserPrivacy;
use Livewire\Livewire;

test('ensembles index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('ensembles.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('ensembles index displays ensembles with my filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $myEnsemble = Ensemble::factory()->for($school)->create([
        'ensemble_name' => 'My Concert Choir',
    ]);

    $otherSchool = School::factory()->create();
    $otherEnsemble = Ensemble::factory()->for($otherSchool)->create([
        'ensemble_name' => 'Other School Singers',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('filter', 'my')
        ->assertSee('My Concert Choir')
        ->assertDontSee('Other School Singers');
});

test('ensembles index displays all ensembles with all filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $myEnsemble = Ensemble::factory()->for($school)->create([
        'ensemble_name' => 'My Chamber Singers',
    ]);

    $otherSchool = School::factory()->create();
    $otherEnsemble = Ensemble::factory()->for($otherSchool)->create([
        'ensemble_name' => 'Other Jazz Choir',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('My Chamber Singers')
        ->assertSee('Other Jazz Choir');
});

test('guests cannot access ensembles index', function () {
    $this->get(route('ensembles.index'))
        ->assertRedirect(route('login'));
});

test('ensemble name is masked when owner has privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Other user has privacy enabled
    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'ensemble_name' => true,
    ]);

    $otherSchool = School::factory()->create();
    $otherUser->schools()->attach($otherSchool);
    $otherEnsemble = Ensemble::factory()->for($otherSchool)->create([
        'ensemble_name' => 'Secret Choir',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Ensemble'.$otherEnsemble->id)
        ->assertDontSee('Secret Choir');
});

test('ensemble name is shown when viewing own ensemble with privacy enabled', function () {
    $user = User::factory()->create();

    // User has privacy enabled for their own ensembles
    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'ensemble_name' => true,
    ]);

    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create([
        'ensemble_name' => 'My Secret Choir',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Secret Choir');
});

test('ensemble name is shown when owner has no privacy settings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Other user has no privacy settings
    $otherSchool = School::factory()->create();
    $otherUser->schools()->attach($otherSchool);
    $otherEnsemble = Ensemble::factory()->for($otherSchool)->create([
        'ensemble_name' => 'Public Choir',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Public Choir');
});

test('school name is masked when owner has school privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Other user has school privacy enabled
    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'school' => true,
    ]);

    $otherSchool = School::factory()->create([
        'school_name' => 'Secret Academy',
    ]);
    $otherUser->schools()->attach($otherSchool);
    Ensemble::factory()->for($otherSchool)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('School'.$otherUser->id)
        ->assertDontSee('Secret Academy');
});

test('school name is shown when viewing own school with privacy enabled', function () {
    $user = User::factory()->create();

    // User has school privacy enabled
    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'school' => true,
    ]);

    $school = School::factory()->create([
        'school_name' => 'My Private School',
    ]);
    $user->schools()->attach($school);
    Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Private School');
});

test('school name is shown when owner has no school privacy settings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Other user has no privacy settings
    $otherSchool = School::factory()->create([
        'school_name' => 'Public School Name',
    ]);
    $otherUser->schools()->attach($otherSchool);
    Ensemble::factory()->for($otherSchool)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Public School Name');
});
