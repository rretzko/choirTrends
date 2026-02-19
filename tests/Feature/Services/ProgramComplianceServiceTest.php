<?php

use App\Models\Program;
use App\Models\School;
use App\Models\User;
use App\Services\ProgramComplianceService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new ProgramComplianceService;
});

test('founder is exempt and can view all', function () {
    config(['app.founder' => 'founder@example.com']);
    $user = User::factory()->founder()->create();

    $result = $this->service->getCompliance($user);

    expect($result['isExempt'])->toBeTrue();
    expect($result['canViewAll'])->toBeTrue();
    expect($result['annualRequirements'])->toBeEmpty();
});

test('initial gate is green when user has at least one program', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create();

    $result = $this->service->getCompliance($user);

    expect($result['initialUpload']['status'])->toBe('green');
});

test('initial gate is amber when within 14-day window with no programs', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-02-10'),
    ]);

    $result = $this->service->getCompliance($user);

    expect($result['initialUpload']['status'])->toBe('amber');
    expect($result['canViewAll'])->toBeTrue();

    Carbon::setTestNow();
});

test('initial gate is red when past 14-day deadline with no programs', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-01'),
    ]);

    $result = $this->service->getCompliance($user);

    expect($result['initialUpload']['status'])->toBe('red');
    expect($result['canViewAll'])->toBeFalse();

    Carbon::setTestNow();
});

test('annual gate is green when user has 2+ programs for a completed year', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2024-06-01'),
    ]);
    $school = School::factory()->create();

    // 2 programs for 2025 (a completed year after registration year)
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-03-15']);
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-10-20']);

    // Also need initial program to pass gate 1
    Program::factory()->for($user)->for($school)->create(['event_date' => '2024-07-01']);

    $result = $this->service->getCompliance($user);

    expect($result['annualRequirements'][2025]['status'])->toBe('green');
    expect($result['annualRequirements'][2025]['count'])->toBe(2);

    Carbon::setTestNow();
});

test('annual gate is red when user has fewer than 2 programs for a completed year', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2024-06-01'),
    ]);
    $school = School::factory()->create();

    // Only 1 program for 2025
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-03-15']);

    // Initial program
    Program::factory()->for($user)->for($school)->create(['event_date' => '2024-07-01']);

    $result = $this->service->getCompliance($user);

    expect($result['annualRequirements'][2025]['status'])->toBe('red');
    expect($result['annualRequirements'][2025]['count'])->toBe(1);
    expect($result['canViewAll'])->toBeFalse();

    Carbon::setTestNow();
});

test('current year advisory is amber when fewer than 2 programs', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2025-06-01'),
    ]);
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['event_date' => '2026-01-15']);

    $result = $this->service->getCompliance($user);

    expect($result['currentYearAdvisory'])->not->toBeNull();
    expect($result['currentYearAdvisory']['status'])->toBe('amber');
    expect($result['currentYearAdvisory']['count'])->toBe(1);

    Carbon::setTestNow();
});

test('current year advisory is green when 2+ programs', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2025-06-01'),
    ]);
    $school = School::factory()->create();
    Program::factory()->for($user)->for($school)->create(['event_date' => '2026-01-15']);
    Program::factory()->for($user)->for($school)->create(['event_date' => '2026-02-10']);

    $result = $this->service->getCompliance($user);

    expect($result['currentYearAdvisory'])->not->toBeNull();
    expect($result['currentYearAdvisory']['status'])->toBe('green');

    Carbon::setTestNow();
});

test('current year advisory is null when user registered this year', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2026-01-15'),
    ]);

    $result = $this->service->getCompliance($user);

    expect($result['currentYearAdvisory'])->toBeNull();

    Carbon::setTestNow();
});

test('current year advisory does not affect canViewAll', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2025-06-01'),
    ]);
    $school = School::factory()->create();

    // Has initial program (gate 1 green)
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-09-01']);
    // 2 programs for 2025 (gate 2 green)
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-11-01']);

    // 0 programs for 2026 (current year advisory amber)
    $result = $this->service->getCompliance($user);

    expect($result['currentYearAdvisory']['status'])->toBe('amber');
    expect($result['canViewAll'])->toBeTrue();

    Carbon::setTestNow();
});

test('canViewAll is true when all gates are green', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-19'));

    $user = User::factory()->create([
        'created_at' => Carbon::parse('2024-06-01'),
    ]);
    $school = School::factory()->create();

    // 2 for 2024 (registration year)
    Program::factory()->for($user)->for($school)->create(['event_date' => '2024-07-01']);
    Program::factory()->for($user)->for($school)->create(['event_date' => '2024-11-15']);
    // 2 for 2025
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-03-15']);
    Program::factory()->for($user)->for($school)->create(['event_date' => '2025-10-20']);

    $result = $this->service->getCompliance($user);

    expect($result['canViewAll'])->toBeTrue();
    expect($result['initialUpload']['status'])->toBe('green');
    expect($result['annualRequirements'][2024]['status'])->toBe('green');
    expect($result['annualRequirements'][2025]['status'])->toBe('green');

    Carbon::setTestNow();
});
