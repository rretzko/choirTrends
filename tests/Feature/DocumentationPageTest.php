<?php

declare(strict_types=1);

use App\Models\User;

test('site guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/site-guide')
        ->assertOk()
        ->assertSee('Documentation');
});

test('site guide page requires authentication', function () {
    $this->get('/documentation/site-guide')
        ->assertRedirect('/login');
});

test('site guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/site-guide')
        ->assertOk()
        ->assertSee('Welcome to ChoirTrends')
        ->assertSee('Sidebar Links')
        ->assertSee('Page Conventions')
        ->assertSee('Sortable Columns')
        ->assertSee('Filter Toggles')
        ->assertSee('Edit Icons');
});

test('orientation email page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/orientation-email')
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee('Sidebar Links')
        ->assertSee('Page Conventions');
});

test('orientation email page requires authentication', function () {
    $this->get('/documentation/orientation-email')
        ->assertRedirect('/login');
});

test('add program guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/add-program-guide')
        ->assertOk()
        ->assertSee('Add Program Guide');
});

test('add program guide page requires authentication', function () {
    $this->get('/documentation/add-program-guide')
        ->assertRedirect('/login');
});

test('add program guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/add-program-guide')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('Step 1: Upload Your Program')
        ->assertSee('Step 2: Review & Confirm')
        ->assertSee('Tips for Best Results')
        ->assertSee('Privacy');
});

test('programs guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/programs-guide')
        ->assertOk()
        ->assertSee('Programs Guide');
});

test('programs guide page requires authentication', function () {
    $this->get('/documentation/programs-guide')
        ->assertRedirect('/login');
});

test('programs guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/programs-guide')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('Filtering by School')
        ->assertSee('Sorting Columns')
        ->assertSee('Viewing Program Details')
        ->assertSee('Editing Your Programs');
});

test('composers arrangers guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/composers-arrangers-guide')
        ->assertOk()
        ->assertSee('Composers & Arrangers Guide');
});

test('composers arrangers guide page requires authentication', function () {
    $this->get('/documentation/composers-arrangers-guide')
        ->assertRedirect('/login');
});

test('composers arrangers guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/composers-arrangers-guide')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('My vs. All')
        ->assertSee('Viewing Repertoire')
        ->assertSee('Searching & Sorting');
});

test('ensembles guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/ensembles-guide')
        ->assertOk()
        ->assertSee('Ensembles Guide');
});

test('ensembles guide page requires authentication', function () {
    $this->get('/documentation/ensembles-guide')
        ->assertRedirect('/login');
});

test('ensembles guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/ensembles-guide')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('My vs. All')
        ->assertSee('Ensemble Attributes')
        ->assertSee('Editing Ensembles');
});

test('schools guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/schools-guide')
        ->assertOk()
        ->assertSee('Schools Guide');
});

test('schools guide page requires authentication', function () {
    $this->get('/documentation/schools-guide')
        ->assertRedirect('/login');
});

test('schools guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/schools-guide')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('My vs. All')
        ->assertSee('Sorting Columns');
});

test('song titles guide page is accessible to authenticated users', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/song-titles-guide')
        ->assertOk()
        ->assertSee('Song Titles Guide');
});

test('song titles guide page requires authentication', function () {
    $this->get('/documentation/song-titles-guide')
        ->assertRedirect('/login');
});

test('song titles guide page contains key content sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get('/documentation/song-titles-guide')
        ->assertOk()
        ->assertSee('Overview')
        ->assertSee('My vs. All')
        ->assertSee('Understanding the Table')
        ->assertSee('Searching & Sorting');
});
