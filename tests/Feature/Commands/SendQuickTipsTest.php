<?php

declare(strict_types=1);

use App\Enums\QuickTipStatus;
use App\Mail\QuickTipEmail;
use App\Models\QuickTip;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
    Mail::fake();
});

test('sends first tip to user after grace period', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(22),
    ]);

    $tip = QuickTip::factory()->pending()->create(['sort_order' => 1]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertQueued(QuickTipEmail::class, function ($mail) use ($user, $tip) {
        return $mail->user->id === $user->id && $mail->quickTip->id === $tip->id;
    });

    $this->assertDatabaseHas('quick_tip_user', [
        'quick_tip_id' => $tip->id,
        'user_id' => $user->id,
    ]);
});

test('does not send tip to user within grace period', function () {
    User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(10),
    ]);

    QuickTip::factory()->pending()->create(['sort_order' => 1]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('respects 21-day interval between tips', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(60),
    ]);

    $tip1 = QuickTip::factory()->sent()->create(['sort_order' => 1]);
    $tip2 = QuickTip::factory()->pending()->create(['sort_order' => 2]);

    $user->quickTips()->attach($tip1->id, [
        'emailed_at' => now()->subDays(10),
    ]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('sends next tip when interval has passed', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(60),
    ]);

    $tip1 = QuickTip::factory()->sent()->create(['sort_order' => 1]);
    $tip2 = QuickTip::factory()->pending()->create(['sort_order' => 2]);

    $user->quickTips()->attach($tip1->id, [
        'emailed_at' => now()->subDays(22),
    ]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertQueued(QuickTipEmail::class, function ($mail) use ($user, $tip2) {
        return $mail->user->id === $user->id && $mail->quickTip->id === $tip2->id;
    });
});

test('delivers tips in sort_order sequence', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(60),
    ]);

    $tip2 = QuickTip::factory()->pending()->create(['sort_order' => 2, 'header' => 'Second']);
    $tip1 = QuickTip::factory()->pending()->create(['sort_order' => 1, 'header' => 'First']);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertQueued(QuickTipEmail::class, function ($mail) use ($tip1) {
        return $mail->quickTip->id === $tip1->id;
    });
});

test('transitions Pending tip to Sent status', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(22),
    ]);

    $tip = QuickTip::factory()->pending()->create(['sort_order' => 1]);

    $this->artisan('quicktips:send')->assertSuccessful();

    expect($tip->fresh()->status)->toBe(QuickTipStatus::Sent);
});

test('skips Draft tips', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(22),
    ]);

    QuickTip::factory()->create(['sort_order' => 1, 'status' => QuickTipStatus::Draft]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('does not send duplicate tips', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(60),
    ]);

    $tip = QuickTip::factory()->sent()->create(['sort_order' => 1]);

    $user->quickTips()->attach($tip->id, [
        'emailed_at' => now()->subDays(22),
    ]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertNothingQueued();
});

test('skips users who have unsubscribed from quick tip emails', function () {
    $user = User::factory()->withoutTwoFactor()->create([
        'created_at' => now()->subDays(22),
        'quick_tip_emails_enabled' => false,
    ]);

    QuickTip::factory()->pending()->create(['sort_order' => 1]);

    $this->artisan('quicktips:send')->assertSuccessful();

    Mail::assertNothingQueued();
});
