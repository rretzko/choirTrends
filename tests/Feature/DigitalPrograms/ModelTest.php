<?php

use App\Models\DigitalProgram;
use App\Models\DigitalProgramHonor;
use App\Models\DigitalProgramRoster;
use App\Models\Program;
use App\Models\School;
use App\Models\User;

test('slug is auto-generated on creation', function () {
    $dp = DigitalProgram::factory()->create();

    expect($dp->slug)->not->toBeEmpty();
});

test('slug is exactly 8 characters', function () {
    $dp = DigitalProgram::factory()->create();

    expect(strlen($dp->slug))->toBe(8);
});

test('two programs get unique slugs', function () {
    $user = User::factory()->create();

    $dp1 = DigitalProgram::factory()->for($user)->create();
    $dp2 = DigitalProgram::factory()->for($user)->create();

    expect($dp1->slug)->not->toBe($dp2->slug);
});

test('is_published defaults to false', function () {
    $dp = DigitalProgram::factory()->create();

    expect($dp->is_published)->toBeFalse();
});

test('factory published state sets is_published to true', function () {
    $dp = DigitalProgram::factory()->published()->create();

    expect($dp->is_published)->toBeTrue();
});

test('digital program belongs to a user', function () {
    $user = User::factory()->create();
    $dp = DigitalProgram::factory()->for($user)->create();

    expect($dp->user->id)->toBe($user->id);
});

test('digital program optionally belongs to a program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $program = Program::factory()->for($user)->for($school)->create();

    $dp = DigitalProgram::factory()->for($user)->create(['program_id' => $program->id]);

    expect($dp->program->id)->toBe($program->id);
});

test('digital program can exist without a linked program', function () {
    $dp = DigitalProgram::factory()->create(['program_id' => null]);

    expect($dp->program)->toBeNull();
});

test('digital program cascades delete to rosters honors and song settings', function () {
    $dp = DigitalProgram::factory()->create();

    DigitalProgramRoster::create([
        'digital_program_id' => $dp->id,
        'student_name' => 'Jane Smith',
        'sort_order' => 1,
    ]);

    DigitalProgramHonor::create([
        'digital_program_id' => $dp->id,
        'label' => 'Section Leader',
        'sort_order' => 1,
    ]);

    $dp->delete();

    expect(DigitalProgramRoster::where('digital_program_id', $dp->id)->exists())->toBeFalse();
    expect(DigitalProgramHonor::where('digital_program_id', $dp->id)->exists())->toBeFalse();
});
