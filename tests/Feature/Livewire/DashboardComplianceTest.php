<?php

use App\Livewire\Dashboard;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

test('dashboard shows green callout for compliant initial upload', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['event_date' => '2026-01-15']);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('You have uploaded at least one program.');

    Carbon::setTestNow();
});

test('dashboard shows amber callout when within initial upload window', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-02-10'),
    ]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('Please upload your first program by');

    Carbon::setTestNow();
});

test('dashboard shows red callout when past initial upload deadline', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('Please upload a program to access community data.');

    Carbon::setTestNow();
});

test('dashboard shows annual requirement callouts', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2024-06-01'),
    ]);
    $school = School::factory()->create();

    // Initial program
    Program::factory()->for($user)->for($school)->create(['event_date' => '2024-07-01']);

    // Only 1 program for 2025 (should be red)
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-03-15']);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('You need at least 2 programs for 2025.');

    Carbon::setTestNow();
});

test('dashboard shows current year advisory', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2025-06-01'),
    ]);
    $school = School::factory()->create();

    // Programs to satisfy gate 1 + gate 2 for 2025
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-09-01']);

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSee('of 2 recommended programs for 2026');

    Carbon::setTestNow();
});

test('founder sees no compliance callouts', function () {
    config(['app.founder' => 'founder@example.com']);
    $user = User::factory()->founder()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertDontSee('You have uploaded at least one program.')
        ->assertDontSee('Please upload your first program')
        ->assertDontSee('Please upload a program to access community data.');
});
