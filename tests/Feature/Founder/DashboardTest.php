<?php

declare(strict_types=1);

use App\Livewire\Founder\Dashboard;
use App\Models\User;
use App\Models\UserLogin;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the dashboard page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.dashboard'))
        ->assertOk();
});

test('non-founder gets 403 on dashboard page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.dashboard'))
        ->assertForbidden();
});

test('guest is redirected from dashboard page', function () {
    $this->get(route('founder.dashboard'))
        ->assertRedirect(route('login'));
});

// --- Page Rendering ---

test('dashboard page renders with correct layout', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('Founder Dashboard')
        ->assertSee('Total Logins')
        ->assertSee('Unique Users')
        ->assertStatus(200);
});

test('dashboard shows correct totals', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $userA = User::factory()->withoutTwoFactor()->create();
    $userB = User::factory()->withoutTwoFactor()->create();

    UserLogin::factory()->create(['user_id' => $userA->id, 'counter' => 1]);
    UserLogin::factory()->create(['user_id' => $userA->id, 'counter' => 2]);
    UserLogin::factory()->create(['user_id' => $userB->id, 'counter' => 1]);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('3')  // total logins
        ->assertSee('2'); // unique users
});

test('dashboard shows recent logins with user details', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Jane Smith']);

    UserLogin::factory()->create([
        'user_id' => $user->id,
        'os' => 'Windows',
        'browser' => 'Chrome',
        'device' => 'desktop',
        'viewport' => '1920x1080',
    ]);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('Jane Smith')
        ->assertSee('Windows')
        ->assertSee('Chrome')
        ->assertSee('1920x1080');
});

test('dashboard shows breakdown by OS', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    UserLogin::factory()->count(2)->create(['os' => 'Windows']);
    UserLogin::factory()->create(['os' => 'macOS']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('By OS')
        ->assertSee('Windows')
        ->assertSee('macOS');
});

test('dashboard shows breakdown by browser', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    UserLogin::factory()->create(['browser' => 'Chrome']);
    UserLogin::factory()->create(['browser' => 'Firefox']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('By Browser')
        ->assertSee('Chrome')
        ->assertSee('Firefox');
});

test('dashboard shows breakdown by device', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    UserLogin::factory()->create(['device' => 'desktop']);
    UserLogin::factory()->create(['device' => 'mobile']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('By Device')
        ->assertSee('Desktop')
        ->assertSee('Mobile');
});

test('dashboard shows empty state when no logins exist', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('No login records yet.');
});
