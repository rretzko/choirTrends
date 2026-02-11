<?php

declare(strict_types=1);

use App\Livewire\Founder\ImpersonateUser;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the impersonate page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.impersonate'))
        ->assertOk();
});

test('non-founder gets 403 on impersonate page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.impersonate'))
        ->assertForbidden();
});

test('guest is redirected from impersonate page', function () {
    $this->get(route('founder.impersonate'))
        ->assertRedirect(route('login'));
});

// --- Start Impersonation ---

test('founder can impersonate another user', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($founder)
        ->test(ImpersonateUser::class)
        ->set('userId', $target->id)
        ->call('impersonate')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($target->id);
    expect(session('impersonating_from'))->toBe($founder->id);
});

test('founder cannot impersonate themselves', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(ImpersonateUser::class)
        ->set('userId', $founder->id)
        ->call('impersonate')
        ->assertHasErrors('userId');

    expect(auth()->id())->toBe($founder->id);
    expect(session()->has('impersonating_from'))->toBeFalse();
});

test('impersonation validates user exists', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(ImpersonateUser::class)
        ->set('userId', 99999)
        ->call('impersonate')
        ->assertHasErrors('userId');
});

test('impersonation requires a user selection', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(ImpersonateUser::class)
        ->set('userId', '')
        ->call('impersonate')
        ->assertHasErrors('userId');
});

test('non-founder cannot impersonate', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(ImpersonateUser::class)
        ->set('userId', $target->id)
        ->call('impersonate')
        ->assertForbidden();
});

// --- Stop Impersonation ---

test('stop impersonation restores founder auth and clears session', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($target)
        ->withSession(['impersonating_from' => $founder->id])
        ->post(route('founder.impersonate.stop'))
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($founder->id);
    expect(session()->has('impersonating_from'))->toBeFalse();
});

test('stop impersonation fails without session key', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->post(route('founder.impersonate.stop'))
        ->assertForbidden();
});

test('stop impersonation fails if stored user is not founder', function () {
    $nonFounder = User::factory()->withoutTwoFactor()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($target)
        ->withSession(['impersonating_from' => $nonFounder->id])
        ->post(route('founder.impersonate.stop'))
        ->assertForbidden();
});

// --- Banner Visibility ---

test('impersonation banner is visible during impersonation', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $target = User::factory()->withoutTwoFactor()->create(['name' => 'Jane Target']);

    $this->actingAs($target)
        ->withSession(['impersonating_from' => $founder->id])
        ->get(route('dashboard'))
        ->assertSee('Impersonating Jane Target')
        ->assertSee('Stop Impersonating');
});

test('impersonation banner is hidden when not impersonating', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertDontSee('Stop Impersonating');
});

test('founder sidebar links are hidden during impersonation', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $target = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($target)
        ->withSession(['impersonating_from' => $founder->id])
        ->get(route('dashboard'))
        ->assertDontSee('Impersonate User')
        ->assertDontSee('Add Program for User');
});
