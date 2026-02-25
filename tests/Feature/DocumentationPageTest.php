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
