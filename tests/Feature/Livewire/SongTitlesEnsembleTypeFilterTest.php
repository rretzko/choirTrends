<?php

declare(strict_types=1);

use App\Enums\EnsembleType;
use App\Livewire\SongTitles\Index;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use Livewire\Livewire;

test('ensemble type badges render for a song performed by a known ensemble type', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $ensemble = Ensemble::factory()->for($school)->create(['type' => EnsembleType::Satb]);
    $song = SongTitle::factory()->create(['song_title' => 'Ave Maria']);

    $program->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_id' => $ensemble->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->assertSee(EnsembleType::Satb->label());
});

test('a song performed by two different ensemble types shows both badges once each', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $satbEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir A', 'type' => EnsembleType::Satb]);
    $tenorBassEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir B', 'type' => EnsembleType::TenorBass]);
    $song = SongTitle::factory()->create(['song_title' => 'Shared Song']);

    // Same song performed by two different ensembles across two programs.
    $program->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_id' => $satbEnsemble->id]);

    $otherProgram = Program::factory()->for($user)->for($school)->create();
    $otherProgram->songTitles()->attach($song->id, ['sort_order' => 1, 'ensemble_id' => $tenorBassEnsemble->id]);

    $this->actingAs($user);

    $component = Livewire::test(Index::class);

    $component->assertSee(EnsembleType::Satb->label())
        ->assertSee(EnsembleType::TenorBass->label());
});

test('filtering by ensemble type only shows songs performed by that type', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();
    $satbEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir A', 'type' => EnsembleType::Satb]);
    $sopranoAltoEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir B', 'type' => EnsembleType::SopranoAlto]);

    $satbSong = SongTitle::factory()->create(['song_title' => 'SATB Song']);
    $saSong = SongTitle::factory()->create(['song_title' => 'Soprano Alto Song']);

    $program->songTitles()->attach($satbSong->id, ['sort_order' => 1, 'ensemble_id' => $satbEnsemble->id]);
    $program->songTitles()->attach($saSong->id, ['sort_order' => 2, 'ensemble_id' => $sopranoAltoEnsemble->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleEnsembleTypeFilter', EnsembleType::Satb->value)
        ->assertSee('SATB Song')
        ->assertDontSee('Soprano Alto Song');
});

test('toggling the same ensemble type filter twice clears it', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Index::class)
        ->call('toggleEnsembleTypeFilter', EnsembleType::Satb->value)
        ->assertSet('ensembleTypeFilter', [EnsembleType::Satb->value])
        ->call('toggleEnsembleTypeFilter', EnsembleType::Satb->value)
        ->assertSet('ensembleTypeFilter', []);
});

test('ensemble type filter respects the My filter scope', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $school = School::factory()->create();

    // Viewer needs a program to unlock "All" data.
    $viewerProgram = Program::factory()->for($user)->for($school)->create();
    $viewerEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir A', 'type' => EnsembleType::TenorBass]);
    $viewerSong = SongTitle::factory()->create(['song_title' => 'My Tenor Bass Song']);
    $viewerProgram->songTitles()->attach($viewerSong->id, ['sort_order' => 1, 'ensemble_id' => $viewerEnsemble->id]);

    $otherProgram = Program::factory()->for($otherUser)->for($school)->create();
    $otherEnsemble = Ensemble::factory()->for($school)->create(['ensemble_name' => 'Concert Choir B', 'type' => EnsembleType::Satb]);
    $otherSong = SongTitle::factory()->create(['song_title' => 'Other SATB Song']);
    $otherProgram->songTitles()->attach($otherSong->id, ['sort_order' => 1, 'ensemble_id' => $otherEnsemble->id]);

    $this->actingAs($user);

    Livewire::test(Index::class)
        ->set('filter', 'my')
        ->call('toggleEnsembleTypeFilter', EnsembleType::Satb->value)
        ->assertDontSee('Other SATB Song');
});
