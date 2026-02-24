<?php

use App\Livewire\Onboarding\WelcomeModal;
use App\Models\User;
use Livewire\Livewire;

test('new user sees the welcome modal', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(WelcomeModal::class)
        ->assertSet('showWelcomeModal', true)
        ->assertSee('Welcome to choirTrends!')
        ->assertStatus(200);
});

test('welcomed user does not see the welcome modal', function () {
    $user = User::factory()->welcomed()->create();

    $this->actingAs($user);

    Livewire::test(WelcomeModal::class)
        ->assertSet('showWelcomeModal', false)
        ->assertDontSee('Welcome to choirTrends!')
        ->assertStatus(200);
});

test('dismissing the modal sets welcomed_at timestamp', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(WelcomeModal::class)
        ->assertSet('showWelcomeModal', true)
        ->call('dismiss')
        ->assertSet('showWelcomeModal', false);

    expect($user->fresh()->welcomed_at)->not->toBeNull();
});

test('welcome modal renders on dashboard page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get('/dashboard')
        ->assertSeeLivewire(WelcomeModal::class)
        ->assertOk();
});

test('founder also sees welcome modal on first visit', function () {
    $user = User::factory()->founder()->create();

    $this->actingAs($user);

    Livewire::test(WelcomeModal::class)
        ->assertSet('showWelcomeModal', true)
        ->assertSee('Welcome to choirTrends!')
        ->assertStatus(200);
});
