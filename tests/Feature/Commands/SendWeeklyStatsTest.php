<?php

declare(strict_types=1);

use App\Enums\SongTitleOrigin;
use App\Mail\WeeklyStatsEmail;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\StatSnapshot;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
    Mail::fake();
});

test('stores a snapshot with current counts', function () {
    User::factory()->withoutTwoFactor()->count(2)->create(['email_verified_at' => now()]);
    User::factory()->withoutTwoFactor()->create(['email_verified_at' => null]);
    School::factory()->count(3)->create();
    Program::factory()->count(4)->create();
    SongTitle::factory()->count(5)->create();

    $expected = [
        'verified_users_count' => User::query()->whereNotNull('email_verified_at')->count(),
        'schools_count' => School::query()->count(),
        'programs_count' => Program::query()->count(),
        'song_titles_count' => SongTitle::query()->count(),
    ];

    $this->artisan('stats:send-weekly')->assertSuccessful();

    $this->assertDatabaseHas('stat_snapshots', $expected);
});

test('song_titles_count excludes ai_discovered song titles', function () {
    SongTitle::factory()->count(2)->create(['origin' => SongTitleOrigin::Performed]);
    SongTitle::factory()->create(['origin' => SongTitleOrigin::AiDiscovered]);

    $this->artisan('stats:send-weekly')->assertSuccessful();

    $this->assertDatabaseHas('stat_snapshots', ['song_titles_count' => 2]);
});

test('sends stats email to the founder', function () {
    $this->artisan('stats:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyStatsEmail::class, function ($mail) {
        return $mail->hasTo('founder@example.com');
    });
});

test('computes week over week delta from the closest prior snapshot', function () {
    $priorWeek = StatSnapshot::factory()->create([
        'verified_users_count' => 10,
        'captured_at' => now()->subWeek()->subDay(),
    ]);

    $this->artisan('stats:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyStatsEmail::class, function ($mail) use ($priorWeek) {
        return $mail->comparisons['week']?->id === $priorWeek->id;
    });
});

test('handles the first ever run with no prior snapshots', function () {
    $this->artisan('stats:send-weekly')->assertSuccessful();

    Mail::assertSent(WeeklyStatsEmail::class, function ($mail) {
        return $mail->comparisons['week'] === null
            && $mail->comparisons['month'] === null
            && $mail->comparisons['quarter'] === null
            && $mail->comparisons['year'] === null;
    });

    $this->assertDatabaseCount('stat_snapshots', 1);
});

test('stores the snapshot but skips the email when app.founder is not configured', function () {
    config(['app.founder' => null]);

    $this->artisan('stats:send-weekly')->assertSuccessful();

    Mail::assertNothingSent();
    $this->assertDatabaseCount('stat_snapshots', 1);
});
