<?php

declare(strict_types=1);

use App\Livewire\Founder\ChangeUserPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the change user password page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.changeUserPassword'))
        ->assertOk();
});

test('non-founder gets 403 on change user password page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.changeUserPassword'))
        ->assertForbidden();
});

test('guest is redirected from change user password page', function () {
    $this->get(route('founder.changeUserPassword'))
        ->assertRedirect(route('login'));
});

// --- Happy Path ---

test('founder can reset a user password to their lowercased email', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $target = User::factory()->withoutTwoFactor()->create(['email' => 'Jane.Doe@Example.com']);

    Livewire::actingAs($founder)
        ->test(ChangeUserPassword::class)
        ->set('userId', $target->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('founder.changeUserPassword'));

    expect(Hash::check('jane.doe@example.com', $target->fresh()->password))->toBeTrue();
});

// --- Validation ---

test('user selection is required', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(ChangeUserPassword::class)
        ->set('userId', '')
        ->call('save')
        ->assertHasErrors('userId');
});

test('user must exist', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(ChangeUserPassword::class)
        ->set('userId', 99999)
        ->call('save')
        ->assertHasErrors('userId');
});

// --- Authorization ---

test('non-founder cannot reset a user password', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(ChangeUserPassword::class)
        ->set('userId', $target->id)
        ->call('save')
        ->assertForbidden();
});
