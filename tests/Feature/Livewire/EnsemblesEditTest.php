<?php

use App\Enums\EnsembleType;
use App\Livewire\Ensembles\Edit;
use App\Livewire\Ensembles\Index;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot access ensemble edit page', function () {
    $school = School::factory()->create();
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->get(route('ensembles.edit', $ensemble))
        ->assertRedirect(route('login'));
});

test('owner can access their ensemble edit page', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    $this->get(route('ensembles.edit', $ensemble))
        ->assertOk()
        ->assertSeeLivewire(Edit::class);
});

test('non-owner gets 403 when accessing another users ensemble', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $owner->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($otherUser);

    $this->get(route('ensembles.edit', $ensemble))
        ->assertForbidden();
});

test('edit page loads ensemble data correctly', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Test High School']);
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create([
        'ensemble_name' => 'Concert Choir',
    ]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('ensembleName', 'Concert Choir')
        ->assertSet('schoolName', 'Test High School');
});

test('can update ensemble name', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create([
        'ensemble_name' => 'Old Name',
    ]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->set('ensembleName', 'New Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('ensembles.index'));

    $ensemble->refresh();
    expect($ensemble->ensemble_name)->toBe('New Name');
});

test('validates ensemble name is required', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->set('ensembleName', '')
        ->call('save')
        ->assertHasErrors(['ensembleName']);
});

test('ensemble is editable when no other user references it', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('editable', true);
});

test('ensemble is locked when another user references it via program', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Shared Choir']);

    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();
    $song = SongTitle::factory()->create();
    $otherProgram->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('editable', false);
});

test('locked ensemble name is not updated on save', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $ensemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Shared Choir']);

    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();
    $song = SongTitle::factory()->create();
    $otherProgram->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->set('ensembleName', 'Attempted Rename')
        ->call('save')
        ->assertRedirect(route('ensembles.index'));

    $ensemble->refresh();
    expect($ensemble->ensemble_name)->toBe('Shared Choir');
});

test('edit button is visible on index for own ensembles only', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $myEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'My Choir']);

    $otherSchool = School::factory()->create();
    $otherUser->schools()->attach($otherSchool);
    $otherEnsemble = Ensemble::factory()->for($otherSchool)->create(['ensemble_name' => 'Other Choir']);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'all')
        ->assertSeeHtml(route('ensembles.edit', $myEnsemble))
        ->assertDontSeeHtml(route('ensembles.edit', $otherEnsemble));
});

test('save redirects to ensembles index with success flash', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->call('save')
        ->assertRedirect(route('ensembles.index'));
});

test('edit page loads type and a_cappella defaults correctly', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('type', EnsembleType::Unknown->value)
        ->assertSet('aCappella', false);
});

test('can update ensemble type via edit form', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->set('type', EnsembleType::Satb->value)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('ensembles.index'));

    $ensemble->refresh();
    expect($ensemble->type)->toBe(EnsembleType::Satb);
});

test('can update a_cappella via edit form', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create(['a_cappella' => false]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->set('aCappella', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('ensembles.index'));

    $ensemble->refresh();
    expect($ensemble->a_cappella)->toBeTrue();
});

test('edit form validates type must be a valid enum value', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->set('type', 'InvalidType')
        ->call('save')
        ->assertHasErrors(['type']);
});

test('type and a_cappella are editable even when ensemble name is locked', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $ensemble = Ensemble::factory()->for($school)->create([
        'ensemble_name' => 'Shared Choir',
        'type' => EnsembleType::Unknown,
        'a_cappella' => false,
    ]);

    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();
    $song = SongTitle::factory()->create();
    $otherProgram->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('editable', false)
        ->set('type', EnsembleType::SopranoAlto->value)
        ->set('aCappella', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('ensembles.index'));

    $ensemble->refresh();
    expect($ensemble->type)->toBe(EnsembleType::SopranoAlto)
        ->and($ensemble->a_cappella)->toBeTrue()
        ->and($ensemble->ensemble_name)->toBe('Shared Choir');
});

test('owner can remove their ensemble', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('editable', true)
        ->call('remove')
        ->assertRedirect(route('ensembles.index'));

    expect(Ensemble::find($ensemble->id))->toBeNull();
});

test('removal is blocked when ensemble is shared', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $ensemble = Ensemble::factory()->for($school)->create();

    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();
    $song = SongTitle::factory()->create();
    $otherProgram->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->assertSet('editable', false)
        ->call('remove')
        ->assertForbidden();

    expect(Ensemble::find($ensemble->id))->not->toBeNull();
});

test('non-owner cannot call remove', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();
    $owner->schools()->attach($school);
    $ensemble = Ensemble::factory()->for($school)->create();

    $this->actingAs($otherUser);

    $this->get(route('ensembles.edit', $ensemble))
        ->assertForbidden();
});

test('linked pivot rows have ensemble_id set to null after removal', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $ensemble = Ensemble::factory()->for($school)->create();

    $program = Program::factory()->for($user)->for($school)->create();
    $song = SongTitle::factory()->create();
    $program->songTitles()->attach($song->id, ['ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    Livewire::test(Edit::class, ['ensemble' => $ensemble])
        ->call('remove')
        ->assertRedirect(route('ensembles.index'));

    expect(Ensemble::find($ensemble->id))->toBeNull();

    $pivotRow = \Illuminate\Support\Facades\DB::table('program_song_title')
        ->where('program_id', $program->id)
        ->where('song_title_id', $song->id)
        ->first();

    expect($pivotRow)->not->toBeNull()
        ->and($pivotRow->ensemble_id)->toBeNull();
});
