<?php

declare(strict_types=1);

use App\Livewire\Founder\UserGuideEditor;
use App\Livewire\UserGuide\Index;
use App\Models\User;
use App\Models\UserGuide;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- User Guide Index (Viewing) ---

test('authenticated user can view the user guide', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Quick Start', 'slug' => 'quick-start', 'sort_order' => 1]);

    $this->actingAs($user)
        ->get(route('user-guide.index'))
        ->assertOk();
});

test('guest is redirected from user guide', function () {
    $this->get(route('user-guide.index'))
        ->assertRedirect(route('login'));
});

test('user guide displays published sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Quick Start', 'slug' => 'quick-start', 'sort_order' => 1]);
    UserGuide::factory()->create(['title' => 'Dashboard', 'slug' => 'dashboard', 'sort_order' => 2]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Quick Start')
        ->assertSee('Dashboard')
        ->assertOk();
});

test('user guide hides unpublished sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Published Section', 'slug' => 'published', 'sort_order' => 1]);
    UserGuide::factory()->unpublished()->create(['title' => 'Draft Section', 'slug' => 'draft', 'sort_order' => 2]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('Published Section')
        ->assertDontSee('Draft Section')
        ->assertOk();
});

test('user guide defaults to first section', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Quick Start', 'slug' => 'quick-start', 'sort_order' => 1, 'body' => '<p>Welcome!</p>']);
    UserGuide::factory()->create(['title' => 'Dashboard', 'slug' => 'dashboard', 'sort_order' => 2]);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSet('activeSlug', 'quick-start')
        ->assertSee('Welcome!')
        ->assertOk();
});

test('user guide can navigate to a specific section', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Quick Start', 'slug' => 'quick-start', 'sort_order' => 1]);
    UserGuide::factory()->create(['title' => 'Dashboard', 'slug' => 'dashboard', 'sort_order' => 2, 'body' => '<p>Your home base</p>']);

    Livewire::actingAs($user)
        ->test(Index::class)
        ->call('setSection', 'dashboard')
        ->assertSet('activeSlug', 'dashboard')
        ->assertSee('Your home base')
        ->assertOk();
});

test('user guide can mount with a specific section', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Quick Start', 'slug' => 'quick-start', 'sort_order' => 1]);
    UserGuide::factory()->create(['title' => 'Dashboard', 'slug' => 'dashboard', 'sort_order' => 2, 'body' => '<p>Your home base</p>']);

    Livewire::actingAs($user)
        ->test(Index::class, ['section' => 'dashboard'])
        ->assertSet('activeSlug', 'dashboard')
        ->assertSee('Your home base')
        ->assertOk();
});

test('user guide shows empty state when no sections exist', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    Livewire::actingAs($user)
        ->test(Index::class)
        ->assertSee('No guide content available yet')
        ->assertOk();
});

// --- PDF Download ---

test('authenticated user can download the pdf', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    UserGuide::factory()->create(['title' => 'Quick Start', 'slug' => 'quick-start', 'sort_order' => 1]);

    $this->actingAs($user)
        ->get(route('user-guide.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('guest cannot download the pdf', function () {
    $this->get(route('user-guide.pdf'))
        ->assertRedirect(route('login'));
});

// --- Founder Editor ---

test('founder can access the user guide editor', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.userGuide'))
        ->assertOk();
});

test('non-founder gets 403 on user guide editor', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.userGuide'))
        ->assertForbidden();
});

test('founder can see all sections including unpublished', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    UserGuide::factory()->create(['title' => 'Published', 'slug' => 'published', 'sort_order' => 1]);
    UserGuide::factory()->unpublished()->create(['title' => 'Draft', 'slug' => 'draft', 'sort_order' => 2]);

    Livewire::actingAs($founder)
        ->test(UserGuideEditor::class)
        ->assertSee('Published')
        ->assertSee('Draft')
        ->assertOk();
});

test('founder can edit a section', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $section = UserGuide::factory()->create(['title' => 'Original Title', 'slug' => 'original', 'sort_order' => 1]);

    Livewire::actingAs($founder)
        ->test(UserGuideEditor::class)
        ->call('editSection', $section->id)
        ->assertSet('editTitle', 'Original Title')
        ->set('editTitle', 'Updated Title')
        ->set('editBody', '<p>Updated body content</p>')
        ->call('saveSection');

    $this->assertDatabaseHas('user_guides', [
        'id' => $section->id,
        'title' => 'Updated Title',
        'body' => '<p>Updated body content</p>',
    ]);
});

test('founder can toggle section published status', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $section = UserGuide::factory()->create(['title' => 'Test', 'slug' => 'test', 'sort_order' => 1, 'is_published' => true]);

    Livewire::actingAs($founder)
        ->test(UserGuideEditor::class)
        ->call('editSection', $section->id)
        ->set('editIsPublished', false)
        ->call('saveSection');

    $this->assertDatabaseHas('user_guides', [
        'id' => $section->id,
        'is_published' => false,
    ]);
});

test('founder can reorder sections', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $first = UserGuide::factory()->create(['title' => 'First', 'slug' => 'first', 'sort_order' => 1]);
    $second = UserGuide::factory()->create(['title' => 'Second', 'slug' => 'second', 'sort_order' => 2]);

    Livewire::actingAs($founder)
        ->test(UserGuideEditor::class)
        ->call('moveDown', $first->id);

    expect($first->fresh()->sort_order)->toBe(2);
    expect($second->fresh()->sort_order)->toBe(1);
});

test('founder can cancel editing', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $section = UserGuide::factory()->create(['title' => 'Test', 'slug' => 'test', 'sort_order' => 1]);

    Livewire::actingAs($founder)
        ->test(UserGuideEditor::class)
        ->call('editSection', $section->id)
        ->assertSet('editingId', $section->id)
        ->call('cancelEdit')
        ->assertSet('editingId', null)
        ->assertOk();
});

test('editor validates required fields', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $section = UserGuide::factory()->create(['title' => 'Test', 'slug' => 'test', 'sort_order' => 1]);

    Livewire::actingAs($founder)
        ->test(UserGuideEditor::class)
        ->call('editSection', $section->id)
        ->set('editTitle', '')
        ->set('editBody', '')
        ->call('saveSection')
        ->assertHasErrors(['editTitle' => 'required', 'editBody' => 'required']);
});
