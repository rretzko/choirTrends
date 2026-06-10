<?php

use App\Livewire\DigitalPrograms\Index;
use App\Models\DigitalProgram;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('guests are redirected to login', function () {
    $this->get(route('digital-programs.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can access the index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('digital-programs.index'))
        ->assertOk()
        ->assertSeeLivewire(Index::class);
});

test('index shows only the current users digital programs', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $school = School::factory()->create();
    $myProgram = Program::factory()->for($user)->for($school)->create(['event_name' => 'My Concert']);
    $otherProgram = Program::factory()->for($other)->for($school)->create(['event_name' => 'Other Concert']);

    DigitalProgram::factory()->for($user)->create(['program_id' => $myProgram->id]);
    DigitalProgram::factory()->for($other)->create(['program_id' => $otherProgram->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('My Concert')
        ->assertDontSee('Other Concert');
});

test('empty state is shown when user has no digital programs', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('No digital programs yet');
});

test('toggle publish makes a draft program published', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('togglePublish', $dp->id);

    expect($dp->fresh()->is_published)->toBeTrue();
});

test('toggle publish makes a published program a draft', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->published()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('togglePublish', $dp->id);

    expect($dp->fresh()->is_published)->toBeFalse();
});

test('user cannot toggle publish on another users digital program', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $dp = DigitalProgram::factory()->for($owner)->create(['is_published' => false]);

    $this->actingAs($attacker);

    expect(fn () => Livewire::test(Index::class)->call('togglePublish', $dp->id))
        ->toThrow(ModelNotFoundException::class);
});

test('confirm delete opens the modal', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('confirmDelete', $dp->id)
        ->assertSet('showDeleteModal', true)
        ->assertSet('confirmingDeleteId', $dp->id);
});

test('cancel delete closes the modal', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('showDeleteModal', true)
        ->set('confirmingDeleteId', 999)
        ->call('cancelDelete')
        ->assertSet('showDeleteModal', false)
        ->assertSet('confirmingDeleteId', null);
});

test('delete removes the digital program', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('confirmingDeleteId', $dp->id)
        ->call('delete');

    expect(DigitalProgram::find($dp->id))->toBeNull();
});

test('user cannot delete another users digital program', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $dp = DigitalProgram::factory()->for($owner)->create();

    $this->actingAs($attacker);

    expect(
        fn () => Livewire::test(Index::class)
            ->set('confirmingDeleteId', $dp->id)
            ->call('delete')
    )->toThrow(ModelNotFoundException::class);

    expect(DigitalProgram::find($dp->id))->not->toBeNull();
});

test('slug is shown for each program', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('/p/'.$dp->slug);
});

test('published badge shows for published programs', function () {
    $user = User::factory()->create();
    DigitalProgram::factory()->for($user)->published()->create();

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Published');
});

test('draft badge shows for unpublished programs', function () {
    $user = User::factory()->create();
    DigitalProgram::factory()->for($user)->create(['is_published' => false]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee('Draft');
});

test('assistant can view the directors digital programs', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();

    $school = School::factory()->create();
    $program = Program::factory()->for($director)->for($school)->create(['event_name' => 'Spring Concert']);
    DigitalProgram::factory()->for($director)->create(['program_id' => $program->id]);

    $this->actingAs($assistant);

    Livewire::test(Index::class)
        ->assertSee('Spring Concert');
});

test('assistant cannot toggle publish', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $dp = DigitalProgram::factory()->for($director)->create(['is_published' => false]);

    $this->actingAs($assistant);

    Livewire::test(Index::class)
        ->call('togglePublish', $dp->id)
        ->assertForbidden();

    expect($dp->fresh()->is_published)->toBeFalse();
});

test('assistant cannot delete a digital program', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    $dp = DigitalProgram::factory()->for($director)->create();

    $this->actingAs($assistant);

    Livewire::test(Index::class)
        ->set('confirmingDeleteId', $dp->id)
        ->call('delete')
        ->assertForbidden();

    expect(DigitalProgram::find($dp->id))->not->toBeNull();
});

test('publish, unpublish, and delete buttons are hidden for assistants', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();
    DigitalProgram::factory()->for($director)->create(['is_published' => false]);

    $this->actingAs($assistant);

    Livewire::test(Index::class)
        ->assertDontSee('Publish')
        ->assertDontSee('wire:click="confirmDelete', false);
});
