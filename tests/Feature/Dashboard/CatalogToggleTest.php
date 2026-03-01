<?php

use App\Livewire\Dashboard;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can enable catalog', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('catalogEnabled', false)
        ->call('toggleCatalog')
        ->assertSet('catalogEnabled', true);

    $user->refresh();
    expect($user->catalog_token)->not->toBeNull();
    expect($user->catalog_enabled_at)->not->toBeNull();
});

test('authenticated user can disable catalog', function () {
    $user = User::factory()->withCatalog()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('catalogEnabled', true)
        ->call('toggleCatalog')
        ->assertSet('catalogEnabled', false);

    $user->refresh();
    expect($user->catalog_enabled_at)->toBeNull();
    expect($user->catalog_token)->not->toBeNull();
});

test('catalog url is displayed when enabled', function () {
    $user = User::factory()->withCatalog()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('catalogEnabled', true)
        ->assertSee($user->catalog_token)
        ->assertSee('Catalog sharing is on');
});

test('catalog url is hidden when disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->assertSet('catalogEnabled', false)
        ->assertSee('Catalog sharing is off')
        ->assertDontSee('catalog/');
});

test('enabling catalog generates token', function () {
    $user = User::factory()->create();
    expect($user->catalog_token)->toBeNull();

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('toggleCatalog');

    $user->refresh();
    expect($user->catalog_token)->toHaveLength(36);
});

test('disabling and re-enabling preserves the same token', function () {
    $user = User::factory()->withCatalog()->create();
    $originalToken = $user->catalog_token;

    $this->actingAs($user);

    Livewire::test(Dashboard::class)
        ->call('toggleCatalog')
        ->call('toggleCatalog');

    $user->refresh();
    expect($user->catalog_token)->toBe($originalToken);
    expect($user->catalog_enabled_at)->not->toBeNull();
});
