<?php

use App\Livewire\Programs\Index;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use App\Models\UserPrivacy;
use Livewire\Livewire;

test('programs index page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('programs.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('programs index displays programs with my filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $myProgram = Program::factory()->for($user)->for($school)->create();

    $otherUser = User::factory()->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSet('filter', 'my')
        ->assertSee($myProgram->event_name)
        ->assertDontSee($otherProgram->event_name);
});

test('programs index displays all programs with all filter', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $myProgram = Program::factory()->for($user)->for($school)->create();

    $otherUser = User::factory()->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee($myProgram->event_name)
        ->assertSee($otherProgram->event_name);
});

test('guests cannot access programs index', function () {
    $this->get(route('programs.index'))
        ->assertRedirect(route('login'));
});

test('school name is masked when program owner has school privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'school' => true,
    ]);

    $school = School::factory()->create(['school_name' => 'Secret High School']);
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('School'.$school->id)
        ->assertDontSee('Secret High School');
});

test('director name is masked when program owner has name privacy enabled', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $otherUser->id,
        'name' => true,
    ]);

    $school = School::factory()->create();
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create([
        'director_name' => 'John Secret',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Director'.$otherUser->id)
        ->assertDontSee('John Secret');
});

test('school and director are shown when viewing own program with privacy enabled', function () {
    $user = User::factory()->create();

    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'school' => true,
        'name' => true,
    ]);

    $school = School::factory()->create(['school_name' => 'My Private School']);
    $program = Program::factory()->for($user)->for($school)->create([
        'director_name' => 'My Real Name',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Private School')
        ->assertSee('My Real Name');
});

test('school and director are shown when owner has no privacy settings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // No privacy settings for other user
    $school = School::factory()->create(['school_name' => 'Public School']);
    $otherProgram = Program::factory()->for($otherUser)->for($school)->create([
        'director_name' => 'Public Director',
    ]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSee('Public School')
        ->assertSee('Public Director');
});
