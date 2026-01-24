<?php

use App\Livewire\Schools\Index;
use App\Models\School;
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
