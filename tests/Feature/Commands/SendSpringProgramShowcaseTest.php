<?php

declare(strict_types=1);

use App\Mail\SpringProgramShowcase;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\SongTitle;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);

    $this->featuredProgram = Program::factory()->create([
        'event_name' => 'Where the Light Begins',
        'event_date' => '2025-12-16',
    ]);
});

test('queues showcase to verified director with no spring program', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id])->assertSuccessful();

    Mail::assertQueued(SpringProgramShowcase::class, function (SpringProgramShowcase $mail) use ($user) {
        return $mail->hasTo($user->email) && $mail->program->is($this->featuredProgram);
    });
});

test('does not queue showcase to director with a spring program', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    Program::factory()->for($user)->create(['event_date' => '2026-05-15']);

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id])->assertSuccessful();

    Mail::assertNotQueued(SpringProgramShowcase::class, fn (SpringProgramShowcase $mail) => $mail->hasTo($user->email));
});

test('queues showcase when director only has programs outside the spring window', function (string $eventDate) {
    $user = User::factory()->withoutTwoFactor()->create();
    Program::factory()->for($user)->create(['event_date' => $eventDate]);

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id])->assertSuccessful();

    Mail::assertQueued(SpringProgramShowcase::class, fn (SpringProgramShowcase $mail) => $mail->hasTo($user->email));
})->with([
    'winter 2025' => '2025-12-20',
    'fall 2026' => '2026-10-01',
]);

test('respects custom spring window options', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    Program::factory()->for($user)->create(['event_date' => '2026-02-10']);

    $this->artisan('programs:showcase-spring', [
        'program' => $this->featuredProgram->id,
        '--from' => '2026-03-01',
        '--to' => '2026-06-30',
    ])->assertSuccessful();

    Mail::assertQueued(SpringProgramShowcase::class, fn (SpringProgramShowcase $mail) => $mail->hasTo($user->email));
});

test('does not queue showcase to unverified users or assistants', function () {
    $director = User::factory()->withoutTwoFactor()->create();
    Program::factory()->for($director)->create(['event_date' => '2026-04-01']);
    User::factory()->withoutTwoFactor()->unverified()->create();
    User::factory()->withoutTwoFactor()->assistant($director)->create();

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id])->assertSuccessful();

    Mail::assertQueued(SpringProgramShowcase::class, fn (SpringProgramShowcase $mail) => $mail->hasTo($this->featuredProgram->user->email));
    Mail::assertQueued(SpringProgramShowcase::class, 1);
});

test('does not queue showcase twice for the same program and user', function () {
    $user = User::factory()->withoutTwoFactor()->create();
    Cache::put("spring_program_showcase:{$this->featuredProgram->id}:{$user->id}", true, now()->addYear());

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id])->assertSuccessful();

    Mail::assertNotQueued(SpringProgramShowcase::class, fn (SpringProgramShowcase $mail) => $mail->hasTo($user->email));
});

test('dry run reports count without queuing', function () {
    User::factory()->withoutTwoFactor()->count(2)->create();

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id, '--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('3 user(s)');

    Mail::assertNothingQueued();
});

test('preview mode sends only to founder and can be repeated', function () {
    User::factory()->withoutTwoFactor()->count(3)->create();
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    Program::factory()->for($founder)->create(['event_date' => '2026-05-01']);

    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id, '--preview' => true])->assertSuccessful();
    $this->artisan('programs:showcase-spring', ['program' => $this->featuredProgram->id, '--preview' => true])->assertSuccessful();

    Mail::assertQueued(SpringProgramShowcase::class, 2);
    Mail::assertQueued(SpringProgramShowcase::class, fn (SpringProgramShowcase $mail) => $mail->hasTo($founder->email));
});

test('fails when featured program does not exist', function () {
    $this->artisan('programs:showcase-spring', ['program' => 999999])->assertFailed();

    Mail::assertNothingQueued();
});

test('email renders featured program songs grouped by ensemble in order', function () {
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Pat Director']);
    $madrigals = Ensemble::factory()->create(['ensemble_name' => 'Madrigals']);
    $chorale = Ensemble::factory()->create(['ensemble_name' => 'Chorale']);

    $finzi = SongTitle::factory()->create([
        'song_title' => 'My Spirit Sang All Day',
        'composer_id' => Artist::factory()->create(['artist_name' => 'Gerald Finzi'])->id,
        'arranger_id' => null,
    ]);
    $hymnal = SongTitle::factory()->create([
        'song_title' => 'White Winter Hymnal',
        'composer_id' => null,
        'arranger_id' => Artist::factory()->create(['artist_name' => 'Alan Billingsley'])->id,
    ]);

    $this->featuredProgram->songTitles()->attach($hymnal, ['ensemble_id' => $chorale->id, 'ensemble_sort_order' => 2, 'sort_order' => 1]);
    $this->featuredProgram->songTitles()->attach($finzi, ['ensemble_id' => $madrigals->id, 'ensemble_sort_order' => 1, 'sort_order' => 1]);

    $mailable = new SpringProgramShowcase($user, $this->featuredProgram);

    $mailable->assertHasSubject('Is this your concert program?');
    $mailable->assertSeeInOrderInHtml([
        'Pat,',
        'Where the Light Begins',
        'Madrigals',
        'My Spirit Sang All Day',
        'Gerald Finzi',
        'Chorale',
        'White Winter Hymnal',
        'arr. Alan Billingsley',
        'rick@mfrholdings.com',
        'P.S.',
        'my gift to the choral community',
    ]);
});
