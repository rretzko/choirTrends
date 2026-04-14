<?php

declare(strict_types=1);

use App\Mail\SurveyInvitation;
use App\Models\Program;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    config(['app.founder' => 'founder@example.com']);
});

test('sends survey to eligible user with no programs registered over two weeks ago', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subWeeks(3),
    ]);

    $this->artisan('survey:send')->assertSuccessful();

    Mail::assertQueued(SurveyInvitation::class, function (SurveyInvitation $mail) use ($user) {
        return $mail->hasTo($user->email);
    });

    expect($user->fresh()->survey_emails_sent_count)->toBe(1);
});

test('does not send to user registered less than two weeks ago', function () {
    User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('survey:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('does not send to user who has programs', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subWeeks(3),
    ]);
    Program::factory()->create(['user_id' => $user->id]);

    $this->artisan('survey:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('does not send to user with survey_emails_sent_count at 3', function () {
    User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subWeeks(3),
        'survey_emails_sent_count' => 3,
    ]);

    $this->artisan('survey:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('does not send to user who already submitted a survey response', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subWeeks(3),
    ]);
    SurveyResponse::factory()->create(['user_id' => $user->id]);

    $this->artisan('survey:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('increments survey_emails_sent_count on each send', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subWeeks(3),
        'survey_emails_sent_count' => 1,
    ]);

    $this->artisan('survey:send')->assertSuccessful();

    expect($user->fresh()->survey_emails_sent_count)->toBe(2);
});

test('sends to multiple eligible users', function () {
    User::factory()->withoutTwoFactor()->count(3)->create([
        'created_at' => now()->subWeeks(3),
    ]);

    $this->artisan('survey:send')->assertSuccessful();

    Mail::assertQueued(SurveyInvitation::class, 3);
});
