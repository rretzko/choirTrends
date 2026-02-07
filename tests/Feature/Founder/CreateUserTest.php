<?php

declare(strict_types=1);

use App\Livewire\Founder\CreateUser;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
    Notification::fake();
});

test('founder can create a user via the component', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertRedirect(route('founder.addProgram'));

    $this->assertDatabaseHas('users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
});

test('new user receives a password reset email', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('save');

    $newUser = User::where('email', 'jane@example.com')->first();

    Notification::assertSentTo($newUser, ResetPassword::class);
});

test('founder can create a user with a school', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('schoolName', 'Springfield High School')
        ->call('save')
        ->assertRedirect(route('founder.addProgram'));

    $newUser = User::where('email', 'jane@example.com')->first();
    $school = School::where('school_name', 'Springfield High School')->first();

    expect($school)->not->toBeNull();

    $this->assertDatabaseHas('school_user', [
        'user_id' => $newUser->id,
        'school_id' => $school->id,
    ]);
});

test('founder can create a user without a school', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertRedirect(route('founder.addProgram'));

    $newUser = User::where('email', 'jane@example.com')->first();

    expect($newUser->schools)->toBeEmpty();
});

test('existing school is reused when name matches', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $existingSchool = School::factory()->create(['school_name' => 'Existing School']);

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('schoolName', 'Existing School')
        ->call('save');

    $newUser = User::where('email', 'jane@example.com')->first();

    expect(School::where('school_name', 'Existing School')->count())->toBe(1);

    $this->assertDatabaseHas('school_user', [
        'user_id' => $newUser->id,
        'school_id' => $existingSchool->id,
    ]);
});

test('duplicate email is rejected', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    User::factory()->withoutTwoFactor()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email' => 'unique']);
});

test('non-founder cannot use the component', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertForbidden();
});

test('name is required', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', '')
        ->set('email', 'jane@example.com')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('email is required', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('email', '')
        ->set('name', 'Jane Doe')
        ->call('save')
        ->assertHasErrors(['email' => 'required']);
});

test('email must be valid', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email' => 'email']);
});
