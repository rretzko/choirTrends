<?php

declare(strict_types=1);

use App\Models\User;

test('assistant is redirected away from the dashboard', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();

    $this->actingAs($assistant)
        ->get(route('dashboard'))
        ->assertRedirect(route('digital-programs.index'));
});

test('assistant is redirected away from programs', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();

    $this->actingAs($assistant)
        ->get(route('programs.index'))
        ->assertRedirect(route('digital-programs.index'));
});

test('assistant is redirected away from profile settings', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();

    $this->actingAs($assistant)
        ->get(route('profile.edit'))
        ->assertRedirect(route('digital-programs.index'));
});

test('assistant can access digital programs', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();

    $this->actingAs($assistant)
        ->get(route('digital-programs.index'))
        ->assertOk();
});

test('director is not restricted', function () {
    $director = User::factory()->create();

    $this->actingAs($director)
        ->get(route('dashboard'))
        ->assertOk();

    $this->actingAs($director)
        ->get(route('programs.index'))
        ->assertOk();
});
