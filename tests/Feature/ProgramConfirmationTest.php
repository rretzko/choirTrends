<?php

declare(strict_types=1);

use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;

test('user can confirm and save a program with all required fields', function () {
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Test Director']);

    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert 2025',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success', 'Program saved successfully!');

    // Verify school was created
    $this->assertDatabaseHas('schools', [
        'school_name' => 'Test High School',
    ]);

    $school = School::where('school_name', 'Test High School')->first();

    // Verify program was created with correct school_id
    $this->assertDatabaseHas('programs', [
        'user_id' => $user->id,
        'event_name' => 'Winter Concert 2025',
        'school_id' => $school->id,
        'director_name' => 'John Doe',
    ]);

    $program = Program::where('user_id', $user->id)->first();
    expect($program->event_date->format('Y-m-d'))->toBe('2025-12-15');
    expect($program->school->school_name)->toBe('Test High School');
});

test('director name defaults to authenticated user name when missing', function () {
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Test Director']);

    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert 2025',
        'event_date' => '2025-05-20',
        'school_name' => 'Test High School',
        'director_name' => '', // Empty director name
    ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('programs', [
        'user_id' => $user->id,
        'event_name' => 'Spring Concert 2025',
        'director_name' => 'Test Director', // Should use auth user's name
    ]);
});

test('validation fails when event name is missing', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('event_name');
});

test('validation fails when event date is missing', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('event_date');
});

test('validation fails when school name is missing', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'director_name' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('school_name');
});

test('duplicate program with same user event name and date is rejected', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Create first program
    Program::factory()->create([
        'user_id' => $user->id,
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
    ]);

    // Try to create duplicate
    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertSessionHasErrors('event_name');
    $this->assertEquals(1, Program::where('user_id', $user->id)->count());
});

test('different users can create programs with same event name and date', function () {
    $user1 = User::factory()->withoutTwoFactor()->create();
    $user2 = User::factory()->withoutTwoFactor()->create();

    $programData = [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ];

    // User 1 creates program
    $this->actingAs($user1)->post(route('addProgram.confirm'), $programData);

    // User 2 creates program with same event name and date
    $response = $this->actingAs($user2)->post(route('addProgram.confirm'), $programData);

    $response->assertRedirect(route('dashboard'));
    $this->assertEquals(1, Program::where('user_id', $user1->id)->count());
    $this->assertEquals(1, Program::where('user_id', $user2->id)->count());
});

test('same user can create programs with different event names', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Create first program
    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    // Create second program with different event name
    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-20',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertEquals(2, Program::where('user_id', $user->id)->count());
});

test('same user can create programs with same name but different dates', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Create first program
    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    // Create second program with same name but different date
    $response = $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2026-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertEquals(2, Program::where('user_id', $user->id)->count());
});

test('analysis cache is cleared after successful save', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Set some analysis in cache
    cache()->put("program_analysis_{$user->id}", ['status' => 'completed', 'data' => []], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    expect(cache()->get("program_analysis_{$user->id}"))->toBeNull();
});

test('guest cannot confirm a program', function () {
    $response = $this->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Test High School',
        'director_name' => 'John Doe',
    ]);

    $response->assertRedirect(route('login'));
});

test('same school name reuses existing school record', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Create first program with a school
    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Reuse High School',
        'director_name' => 'John Doe',
    ]);

    // Create second program with same school name
    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-20',
        'school_name' => 'Reuse High School',
        'director_name' => 'John Doe',
    ]);

    // Should only have one school record
    expect(School::where('school_name', 'Reuse High School')->count())->toBe(1);

    // Both programs should reference the same school
    $school = School::where('school_name', 'Reuse High School')->first();
    $programs = Program::where('user_id', $user->id)->get();

    expect($programs)->toHaveCount(2);
    expect($programs[0]->school_id)->toBe($school->id);
    expect($programs[1]->school_id)->toBe($school->id);
});

test('user is associated with school when creating a program', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Association Test High School',
        'director_name' => 'John Doe',
    ]);

    $school = School::where('school_name', 'Association Test High School')->first();

    // Verify the user is associated with the school in the pivot table
    $this->assertDatabaseHas('school_user', [
        'user_id' => $user->id,
        'school_id' => $school->id,
    ]);

    // Verify through the relationship
    expect($user->schools)->toHaveCount(1);
    expect($user->schools->first()->id)->toBe($school->id);
});

test('multiple programs for same school only create one school_user relationship', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Create first program
    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Single Association High School',
        'director_name' => 'John Doe',
    ]);

    // Create second program for same school
    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-20',
        'school_name' => 'Single Association High School',
        'director_name' => 'John Doe',
    ]);

    $school = School::where('school_name', 'Single Association High School')->first();

    // Should only have one pivot record
    $pivotCount = \DB::table('school_user')
        ->where('user_id', $user->id)
        ->where('school_id', $school->id)
        ->count();

    expect($pivotCount)->toBe(1);
    expect($user->schools)->toHaveCount(1);
});

test('ensembles are created from uploaded program data', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Set up cache with ensemble data
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Ensemble Test High School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => []],
                ['name' => 'Women\'s Ensemble', 'songs' => []],
                ['name' => 'Jazz Choir', 'songs' => []],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Ensemble Test High School',
        'director_name' => 'John Doe',
    ]);

    $school = School::where('school_name', 'Ensemble Test High School')->first();

    // Verify ensembles were created
    expect(Ensemble::where('school_id', $school->id)->count())->toBe(3);

    $this->assertDatabaseHas('ensembles', [
        'school_id' => $school->id,
        'ensemble_name' => 'Concert Choir',
    ]);

    $this->assertDatabaseHas('ensembles', [
        'school_id' => $school->id,
        'ensemble_name' => 'Women\'s Ensemble',
    ]);

    $this->assertDatabaseHas('ensembles', [
        'school_id' => $school->id,
        'ensemble_name' => 'Jazz Choir',
    ]);
});

test('duplicate ensembles are not created for same school', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // First program with ensembles
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Duplicate Ensemble School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => []],
                ['name' => 'Jazz Choir', 'songs' => []],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Duplicate Ensemble School',
        'director_name' => 'John Doe',
    ]);

    // Second program with overlapping ensembles
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Spring Concert',
            'event_date' => '2025-05-20',
            'school_name' => 'Duplicate Ensemble School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => []],
                ['name' => 'Women\'s Ensemble', 'songs' => []],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-20',
        'school_name' => 'Duplicate Ensemble School',
        'director_name' => 'John Doe',
    ]);

    $school = School::where('school_name', 'Duplicate Ensemble School')->first();

    // Should have 3 unique ensembles total (Concert Choir, Jazz Choir, Women's Ensemble)
    expect(Ensemble::where('school_id', $school->id)->count())->toBe(3);

    // Concert Choir should only exist once
    expect(Ensemble::where('school_id', $school->id)
        ->where('ensemble_name', 'Concert Choir')
        ->count())->toBe(1);
});

test('ensembles with empty names are not created', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Empty Ensemble School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => []],
                ['name' => '', 'songs' => []],
                ['name' => 'Jazz Choir', 'songs' => []],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Empty Ensemble School',
        'director_name' => 'John Doe',
    ]);

    $school = School::where('school_name', 'Empty Ensemble School')->first();

    // Should only have 2 ensembles (empty name should be skipped)
    expect(Ensemble::where('school_id', $school->id)->count())->toBe(2);
});

test('song titles are created from uploaded program data', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Song Test High School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                        ['title' => 'Lux Aurumque', 'composer' => 'Eric Whitacre', 'arranger' => null, 'notes' => null],
                    ],
                ],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Song Test High School',
        'director_name' => 'John Doe',
    ]);

    expect(SongTitle::count())->toBe(2);

    $this->assertDatabaseHas('song_titles', ['song_title' => 'Ave Maria']);
    $this->assertDatabaseHas('song_titles', ['song_title' => 'Lux Aurumque']);
});

test('duplicate song titles are not created', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // First program
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Duplicate Song School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => [
                    ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                ]],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Duplicate Song School',
        'director_name' => 'John Doe',
    ]);

    // Second program with same song
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Spring Concert',
            'event_date' => '2025-05-20',
            'school_name' => 'Duplicate Song School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => [
                    ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                ]],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-20',
        'school_name' => 'Duplicate Song School',
        'director_name' => 'John Doe',
    ]);

    // Should only have one song title
    expect(SongTitle::where('song_title', 'Ave Maria')->count())->toBe(1);
});

test('composers are created and parsed from uploaded program data', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Composer Test High School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                        ['title' => 'Lux Aurumque', 'composer' => 'Eric Whitacre', 'arranger' => null, 'notes' => null],
                    ],
                ],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Composer Test High School',
        'director_name' => 'John Doe',
    ]);

    expect(Artist::count())->toBe(2);

    $this->assertDatabaseHas('artists', [
        'artist_name' => 'Franz Biebl',
        'artist_first_name' => 'Franz',
        'artist_last_name' => 'Biebl',
    ]);

    $this->assertDatabaseHas('artists', [
        'artist_name' => 'Eric Whitacre',
        'artist_first_name' => 'Eric',
        'artist_last_name' => 'Whitacre',
    ]);
});

test('arrangers are created and parsed from uploaded program data', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Arranger Test High School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Ave Maria', 'composer' => 'Traditional', 'arranger' => 'John Smith', 'notes' => null],
                    ],
                ],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Arranger Test High School',
        'director_name' => 'John Doe',
    ]);

    expect(Artist::count())->toBe(2);

    $this->assertDatabaseHas('artists', [
        'artist_name' => 'John Smith',
        'artist_first_name' => 'John',
        'artist_last_name' => 'Smith',
    ]);
});

test('single word artist names are parsed correctly', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Single Word Artist School',
            'director_name' => 'John Doe',
            'ensembles' => [
                [
                    'name' => 'Concert Choir',
                    'songs' => [
                        ['title' => 'Traditional Song', 'composer' => 'Traditional', 'arranger' => null, 'notes' => null],
                    ],
                ],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Single Word Artist School',
        'director_name' => 'John Doe',
    ]);

    $this->assertDatabaseHas('artists', [
        'artist_name' => 'Traditional',
        'artist_first_name' => null,
        'artist_last_name' => 'Traditional',
    ]);
});

test('duplicate artists are not created', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // First program
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Winter Concert',
            'event_date' => '2025-12-15',
            'school_name' => 'Duplicate Artist School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => [
                    ['title' => 'Ave Maria', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                ]],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Winter Concert',
        'event_date' => '2025-12-15',
        'school_name' => 'Duplicate Artist School',
        'director_name' => 'John Doe',
    ]);

    // Second program with same composer
    cache()->put("program_analysis_{$user->id}", [
        'status' => 'completed',
        'data' => [
            'event_name' => 'Spring Concert',
            'event_date' => '2025-05-20',
            'school_name' => 'Duplicate Artist School',
            'director_name' => 'John Doe',
            'ensembles' => [
                ['name' => 'Concert Choir', 'songs' => [
                    ['title' => 'Another Song', 'composer' => 'Franz Biebl', 'arranger' => null, 'notes' => null],
                ]],
            ],
        ],
    ], now()->addHours(2));

    $this->actingAs($user)->post(route('addProgram.confirm'), [
        'event_name' => 'Spring Concert',
        'event_date' => '2025-05-20',
        'school_name' => 'Duplicate Artist School',
        'director_name' => 'John Doe',
    ]);

    // Should only have one artist named Franz Biebl
    expect(Artist::where('artist_name', 'Franz Biebl')->count())->toBe(1);
});
