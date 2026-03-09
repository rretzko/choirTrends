<?php

declare(strict_types=1);

use App\Livewire\QuickTips\Index;
use App\Models\QuickTip;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('authenticated user can access quick tips page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('quick-tips.index'))
        ->assertOk();
});

test('guest is redirected from quick tips page', function () {
    $this->get(route('quick-tips.index'))
        ->assertRedirect(route('login'));
});

// --- Display ---

test('shows empty state when no tips available', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No quick tips available yet')
        ->assertOk();
});

test('only shows Sent tips that user has received', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $sentTip = QuickTip::factory()->sent()->create(['header' => 'Visible Tip']);
    $draftTip = QuickTip::factory()->create(['header' => 'Draft Tip']);
    $pendingTip = QuickTip::factory()->pending()->create(['header' => 'Pending Tip']);

    $user->quickTips()->attach($sentTip->id, ['emailed_at' => now()]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Visible Tip')
        ->assertDontSee('Draft Tip')
        ->assertDontSee('Pending Tip');
});

test('does not show Sent tips user has not received', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    QuickTip::factory()->sent()->create(['header' => 'Unreceived Tip']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertDontSee('Unreceived Tip');
});

// --- Accordion Behavior ---

test('clicking a tip toggles it open', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $tip = QuickTip::factory()->sent()->create(['tip' => '<p>Tip content here</p>']);
    $user->quickTips()->attach($tip->id, ['emailed_at' => now()]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('toggleTip', $tip->id)
        ->assertSet('openTipId', $tip->id)
        ->assertSee('Tip content here');
});

test('clicking the same tip again closes it', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $tip = QuickTip::factory()->sent()->create();
    $user->quickTips()->attach($tip->id, ['emailed_at' => now()]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('toggleTip', $tip->id)
        ->assertSet('openTipId', $tip->id)
        ->call('toggleTip', $tip->id)
        ->assertSet('openTipId', null);
});

// --- Next Quick Tip ---

test('next quick tip button appears when sent tips exist beyond user position', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    QuickTip::factory()->sent()->create(['sort_order' => 1]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Next Quick Tip');
});

test('next quick tip button does not appear when all caught up', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $tip = QuickTip::factory()->sent()->create(['sort_order' => 1]);
    $user->quickTips()->attach($tip->id, ['emailed_at' => now()]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertDontSee('Next Quick Tip');
});

test('clicking next quick tip creates pivot record and opens the tip', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $tip = QuickTip::factory()->sent()->create(['sort_order' => 1, 'header' => 'New Tip']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('viewNextTip')
        ->assertSet('openTipId', $tip->id)
        ->assertSee('New Tip');

    $this->assertDatabaseHas('quick_tip_user', [
        'quick_tip_id' => $tip->id,
        'user_id' => $user->id,
    ]);

    expect($user->quickTips()->first()->pivot->viewed_at)->not->toBeNull();
});

test('next quick tip follows sort order', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    $tip1 = QuickTip::factory()->sent()->create(['sort_order' => 1, 'header' => 'First']);
    $tip2 = QuickTip::factory()->sent()->create(['sort_order' => 2, 'header' => 'Second']);

    $user->quickTips()->attach($tip1->id, ['emailed_at' => now()]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('viewNextTip')
        ->assertSet('openTipId', $tip2->id)
        ->assertSee('Second');
});
