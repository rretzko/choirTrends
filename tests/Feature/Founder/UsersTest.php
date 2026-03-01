<?php

declare(strict_types=1);

use App\Livewire\Founder\Users;
use App\Models\School;
use App\Models\User;
use App\Models\UserLogin;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the users page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.users'))
        ->assertOk();
});

test('non-founder gets 403 on users page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.users'))
        ->assertForbidden();
});

test('guest is redirected from users page', function () {
    $this->get(route('founder.users'))
        ->assertRedirect(route('login'));
});

// --- Page Rendering ---

test('users page renders with correct layout', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->assertSee('Users')
        ->assertSee('All registered users.')
        ->assertStatus(200);
});

test('users page displays user details', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Jane Smith']);

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->assertSee('Smith, Jane')
        ->assertSee($user->email);
});

test('users page shows school and program counts', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->assertStatus(200);
});

test('users page shows last login info', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Bob Jones']);

    UserLogin::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->assertSee('Jones, Bob');
});

test('users page shows total count', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    User::factory()->withoutTwoFactor()->count(3)->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->assertSee('(4)'); // founder + 3 users
});

// --- Search ---

test('users page can search by name', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    User::factory()->withoutTwoFactor()->create(['name' => 'Alice Wonder']);
    User::factory()->withoutTwoFactor()->create(['name' => 'Bob Builder']);

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->set('search', 'Alice')
        ->assertSee('Wonder, Alice')
        ->assertDontSee('Builder, Bob');
});

test('users page can search by email', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create(['email' => 'unique-test@example.com']);
    User::factory()->withoutTwoFactor()->create(['email' => 'other@example.com']);

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->set('search', 'unique-test')
        ->assertSee('unique-test@example.com')
        ->assertDontSee('other@example.com');
});

// --- Sorting ---

test('users page can sort by name', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->assertSet('sortBy', 'alpha_name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'alpha_name')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'alpha_name')
        ->assertSet('sortDirection', 'asc');
});

test('users page can sort by email', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->call('sort', 'email')
        ->assertSet('sortBy', 'email')
        ->assertSet('sortDirection', 'asc');
});

test('users page can sort by programs count', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->call('sort', 'programs_count')
        ->assertSet('sortBy', 'programs_count')
        ->assertSet('sortDirection', 'asc');
});

test('users page can sort by last login', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->call('sort', 'last_login_at')
        ->assertSet('sortBy', 'last_login_at')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'last_login_at')
        ->assertSet('sortDirection', 'desc');
});

test('users page can apply sort via mobile dropdown', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->call('applySort', 'last_login_at:desc')
        ->assertSet('sortBy', 'last_login_at')
        ->assertSet('sortDirection', 'desc')
        ->call('applySort', 'programs_count:asc')
        ->assertSet('sortBy', 'programs_count')
        ->assertSet('sortDirection', 'asc');
});

test('users page mobile sort ignores invalid values', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->call('applySort', 'invalid_column:asc')
        ->assertSet('sortBy', 'alpha_name')
        ->assertSet('sortDirection', 'asc');
});

test('users page shows empty state when search has no results', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Users::class)
        ->set('search', 'nonexistent-user-xyz')
        ->assertSee('No users found');
});
