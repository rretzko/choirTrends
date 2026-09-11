<?php

declare(strict_types=1);

use App\Enums\AuthorshipType;
use App\Enums\DifficultyLevel;
use App\Enums\EnsembleType;
use App\Enums\VoicePart;
use App\Jobs\ProcessProgram;
use App\Mail\ProgramProcessed;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\SongTitleDifficultyObservation;
use App\Models\SongTitleTag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    config(['app.founder' => 'founder@example.com']);
});

test('founder can access the add program for user page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $response = $this->actingAs($founder)->get(route('founder.addProgram'));

    $response->assertSuccessful();
    $response->assertViewIs('founder.add-program');
});

test('non-founder gets 403 on founder add program page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)->get(route('founder.addProgram'))->assertForbidden();
    $this->actingAs($user)->post(route('founder.addProgram.store'))->assertForbidden();
    $this->actingAs($user)->get(route('founder.addProgram.status'))->assertForbidden();
    $this->actingAs($user)->post(route('founder.addProgram.confirm'))->assertForbidden();
});

test('guest is redirected to login on founder routes', function () {
    $this->get(route('founder.addProgram'))->assertRedirect(route('login'));
    $this->post(route('founder.addProgram.store'))->assertRedirect(route('login'));
    $this->get(route('founder.addProgram.status'))->assertRedirect(route('login'));
    $this->post(route('founder.addProgram.confirm'))->assertRedirect(route('login'));
});

test('founder can upload file on behalf of target user', function () {
    Queue::fake();

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();
    $file = UploadedFile::fake()->create('program.pdf', 1000, 'application/pdf');

    $response = $this->actingAs($founder)->post(route('founder.addProgram.store'), [
        'target_user_id' => $targetUser->id,
        'program_file' => $file,
    ]);

    $response->assertRedirect(route('founder.addProgram'));
    $response->assertSessionHas('success');

    Queue::assertPushed(ProcessProgram::class, function ($job) use ($targetUser) {
        return $job->userId === $targetUser->id
            && $job->filePath !== ''
            && $job->uris === null;
    });
});

test('founder can submit URLs on behalf of target user', function () {
    Queue::fake();

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();
    $urls = "https://example.com/program1\nhttps://example.com/program2";

    $response = $this->actingAs($founder)->post(route('founder.addProgram.store'), [
        'target_user_id' => $targetUser->id,
        'program_uris' => $urls,
    ]);

    $response->assertRedirect(route('founder.addProgram'));
    $response->assertSessionHas('success');

    Queue::assertPushed(ProcessProgram::class, function ($job) use ($targetUser, $urls) {
        return $job->userId === $targetUser->id
            && $job->filePath === ''
            && $job->uris === $urls;
    });
});

test('cache key uses target user id not founder id', function () {
    Queue::fake();

    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();
    $file = UploadedFile::fake()->create('program.pdf', 1000, 'application/pdf');

    $this->actingAs($founder)->post(route('founder.addProgram.store'), [
        'target_user_id' => $targetUser->id,
        'program_file' => $file,
    ]);

    $analysis = cache()->get("program_analysis_{$targetUser->id}");
    expect($analysis)->toBeArray()
        ->and($analysis['status'])->toBe('processing');

    // Founder's own cache key should not be set
    expect(cache()->get("program_analysis_{$founder->id}"))->toBeNull();
});

test('validation fails without target user id', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $file = UploadedFile::fake()->create('program.pdf', 1000, 'application/pdf');

    $response = $this->actingAs($founder)->post(route('founder.addProgram.store'), [
        'program_file' => $file,
    ]);

    $response->assertSessionHasErrors('target_user_id');
});

test('status endpoint uses target user id from session', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    // Set processing status in cache for target user
    cache()->put(
        "program_analysis_{$targetUser->id}",
        ['status' => 'processing'],
        now()->addHours(2)
    );

    $response = $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->get(route('founder.addProgram.status'));

    $response->assertSuccessful();
    $response->assertJson(['status' => 'processing']);
});

test('status endpoint returns not found when no target user in session', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $response = $this->actingAs($founder)->get(route('founder.addProgram.status'));

    $response->assertSuccessful();
    $response->assertJson(['status' => 'not_found']);
});

test('founder can confirm and save program attributed to target user', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create(['name' => 'Target Director']);

    $response = $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Winter Concert 2025',
            'event_date' => '2025-12-15',
            'school_name' => 'Target User High School',
            'director_name' => 'Target Director',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                    ],
                ],
            ],
        ]);

    $response->assertRedirect(route('founder.addProgram'));
    $response->assertSessionHas('success');

    // Program should be attributed to target user, not founder
    $this->assertDatabaseHas('programs', [
        'user_id' => $targetUser->id,
        'event_name' => 'Winter Concert 2025',
        'director_name' => 'Target Director',
    ]);

    // Founder should not have a program
    expect(Program::where('user_id', $founder->id)->count())->toBe(0);

    // Ensemble should be created for the school
    $school = School::where('school_name', 'Target User High School')->first();
    $this->assertDatabaseHas('ensembles', [
        'school_id' => $school->id,
        'ensemble_name' => 'Concert Choir',
    ]);
});

test('target user is associated with school not the founder', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'School Association Test',
            'director_name' => 'John Doe',
        ]);

    $school = School::where('school_name', 'School Association Test')->first();

    // Target user should be associated with the school
    $this->assertDatabaseHas('school_user', [
        'user_id' => $targetUser->id,
        'school_id' => $school->id,
    ]);

    // Founder should NOT be associated with the school
    $this->assertDatabaseMissing('school_user', [
        'user_id' => $founder->id,
        'school_id' => $school->id,
    ]);
});

test('analysis cache is cleared after founder confirms program', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    cache()->put("program_analysis_{$targetUser->id}", [
        'status' => 'completed',
        'data' => [],
    ], now()->addHours(2));

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Cache Clear Test School',
            'director_name' => 'John Doe',
        ]);

    expect(cache()->get("program_analysis_{$targetUser->id}"))->toBeNull();
});

test('confirm fails without target user in session', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $response = $this->actingAs($founder)->post(route('founder.addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test School',
        'director_name' => 'John Doe',
    ]);

    $response->assertRedirect(route('founder.addProgram'));
    $response->assertSessionHasErrors('error');
});

test('failed method updates cache and sends failure email when job exhausts retries', function () {
    Mail::fake();

    $targetUser = User::factory()->withoutTwoFactor()->create();

    // Simulate cache being set to processing (as the controller would)
    cache()->put(
        "program_analysis_{$targetUser->id}",
        ['status' => 'processing'],
        now()->addHours(2)
    );

    $job = new ProcessProgram($targetUser->id, 'fake/path.pdf');
    $job->failed(new RuntimeException('Claude API timed out'));

    $analysis = cache()->get("program_analysis_{$targetUser->id}");
    expect($analysis)->toBeArray()
        ->and($analysis['status'])->toBe('failed')
        ->and($analysis['error'])->toBe('Claude API timed out');

    Mail::assertSent(ProgramProcessed::class, function ($mail) use ($targetUser) {
        return $mail->hasTo($targetUser->email) && $mail->success === false;
    });
});

test('page passes target user schools to view when target user is in session', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    $school = School::factory()->create(['school_name' => 'Lincoln High School', 'abbreviation' => 'LHS']);
    $targetUser->schools()->attach($school);

    $response = $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->get(route('founder.addProgram'));

    $response->assertSuccessful();
    $schools = $response->viewData('targetUserSchools');
    expect($schools)->toHaveCount(1)
        ->and($schools->first()->school_name)->toBe('Lincoln High School')
        ->and($schools->first()->abbreviation)->toBe('LHS');
});

test('page passes empty collection when no target user in session', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $response = $this->actingAs($founder)->get(route('founder.addProgram'));

    $response->assertSuccessful();
    $schools = $response->viewData('targetUserSchools');
    expect($schools)->toBeEmpty();
});

test('validation rejects unsupported file types', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();
    $file = UploadedFile::fake()->create('program.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $response = $this->actingAs($founder)->post(route('founder.addProgram.store'), [
        'target_user_id' => $targetUser->id,
        'program_file' => $file,
    ]);

    $response->assertSessionHasErrors('program_file');
});

test('page shows all users in dropdown', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user1 = User::factory()->withoutTwoFactor()->create(['name' => 'Alice Smith']);
    $user2 = User::factory()->withoutTwoFactor()->create(['name' => 'Bob Jones']);

    $response = $this->actingAs($founder)->get(route('founder.addProgram'));

    $response->assertSee('Smith, Alice');
    $response->assertSee('Jones, Bob');
});

test('ensemble type is saved and attributed via the target users program', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Voice Type Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Voice Type School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Tenor Bass Ensemble', 'type' => EnsembleType::TenorBass->value, 'songs' => []],
            ],
        ]);

    $ensemble = Ensemble::where('ensemble_name', 'Tenor Bass Ensemble')->first();
    expect($ensemble->type)->toBe(EnsembleType::TenorBass);
});

test('ensemble type does not overwrite an existing ensembles type when founder confirms', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();
    $school = School::factory()->create(['school_name' => 'Existing Voice Type School']);

    Ensemble::factory()->create([
        'school_id' => $school->id,
        'ensemble_name' => 'Chorus',
        'type' => EnsembleType::Satb,
    ]);

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Voice Type Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Existing Voice Type School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Chorus', 'type' => EnsembleType::TenorBass->value, 'songs' => []],
            ],
        ]);

    $ensemble = Ensemble::where('school_id', $school->id)->where('ensemble_name', 'Chorus')->first();
    expect($ensemble->type)->toBe(EnsembleType::Satb);
});

test('difficulty rating is saved and attributed to the target user, not the founder', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Difficulty Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Difficulty School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null, 'difficulty' => DifficultyLevel::Challenging->value],
                    ],
                ],
            ],
        ]);

    $songTitle = SongTitle::where('song_title', 'Ave Maria')->first();

    $observations = SongTitleDifficultyObservation::where('song_title_id', $songTitle->id)
        ->where('authorship_type', AuthorshipType::User)
        ->get();

    expect($observations)->toHaveCount(count(VoicePart::cases()));
    expect($observations->every(fn ($o) => $o->authorship_id === $targetUser->id))->toBeTrue();
    expect($observations->every(fn ($o) => $o->difficulty_label === DifficultyLevel::Challenging))->toBeTrue();
});

test('tags are split on commas and attributed to the target user, not the founder', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Tags Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Tags School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null, 'tags' => 'sacred, holiday'],
                    ],
                ],
            ],
        ]);

    $songTitle = SongTitle::where('song_title', 'Ave Maria')->first();
    $tags = SongTitleTag::where('song_title_id', $songTitle->id)->get();

    expect($tags->pluck('tag')->all())->toEqualCanonicalizing(['sacred', 'holiday']);
    expect($tags->every(fn ($t) => $t->authorship_type === AuthorshipType::User && $t->authorship_id === $targetUser->id))->toBeTrue();
});

test('duplicate tag text is not saved twice when founder confirms a matching tag', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $targetUser = User::factory()->withoutTwoFactor()->create();

    $composer = Artist::factory()->create(['artist_name' => 'Franz Biebl']);
    $songTitle = SongTitle::factory()->create(['song_title' => 'Ave Maria', 'composer_id' => $composer->id]);

    SongTitleTag::factory()->create([
        'song_title_id' => $songTitle->id,
        'tag' => 'Sacred',
        'authorship_type' => AuthorshipType::Ai,
        'authorship_id' => null,
    ]);

    $this->actingAs($founder)
        ->withSession(['founder_target_user_id' => $targetUser->id])
        ->post(route('founder.addProgram.confirm'), [
            'event_name' => 'Duplicate Tags Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Duplicate Tags School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null, 'tags' => 'sacred'],
                    ],
                ],
            ],
        ]);

    expect(SongTitleTag::where('song_title_id', $songTitle->id)->count())->toBe(1);
});
