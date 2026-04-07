<?php

declare(strict_types=1);

use App\Mail\Newsletter as NewsletterMailable;
use App\Models\Newsletter;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
    Mail::fake();
});

test('sends newsletter to all verified users', function () {
    $newsletter = Newsletter::factory()->create();
    User::factory()->withoutTwoFactor()->count(3)->create();
    User::factory()->withoutTwoFactor()->create(['email_verified_at' => null]);

    $this->artisan("newsletter:send --id={$newsletter->id}")
        ->assertSuccessful()
        ->expectsOutputToContain('Newsletter sent: 3, failed: 0');

    Mail::assertSent(NewsletterMailable::class, 3);

    expect($newsletter->fresh())
        ->sent_at->not->toBeNull()
        ->recipient_count->toBe(3);
});

test('dry run does not send emails', function () {
    Newsletter::factory()->create();
    User::factory()->withoutTwoFactor()->count(3)->create();

    $this->artisan('newsletter:send --dry-run')
        ->assertSuccessful()
        ->expectsOutputToContain('would send');

    Mail::assertNotSent(NewsletterMailable::class);
});

test('preview sends only to founder', function () {
    Newsletter::factory()->create();
    $founder = User::factory()->withoutTwoFactor()->create(['email' => 'founder@example.com']);
    User::factory()->withoutTwoFactor()->count(3)->create();

    $this->artisan('newsletter:send --preview')
        ->assertSuccessful()
        ->expectsOutputToContain('Newsletter sent: 1, failed: 0');

    Mail::assertSent(NewsletterMailable::class, 1);
    Mail::assertSent(NewsletterMailable::class, function (NewsletterMailable $mail) use ($founder) {
        return $mail->user->id === $founder->id;
    });
});

test('preview does not mark newsletter as sent', function () {
    $newsletter = Newsletter::factory()->create();
    User::factory()->withoutTwoFactor()->create(['email' => 'founder@example.com']);

    $this->artisan('newsletter:send --preview')
        ->assertSuccessful();

    expect($newsletter->fresh()->sent_at)->toBeNull();
});

test('skips unverified users', function () {
    Newsletter::factory()->create();
    User::factory()->withoutTwoFactor()->create(['email_verified_at' => null]);

    $this->artisan('newsletter:send')
        ->assertSuccessful()
        ->expectsOutputToContain('Newsletter sent: 0, failed: 0');

    Mail::assertNotSent(NewsletterMailable::class);
});

test('fails when no unsent newsletter exists', function () {
    $this->artisan('newsletter:send')
        ->assertFailed()
        ->expectsOutputToContain('No unsent newsletter found');
});

test('sends specific newsletter by id', function () {
    $sent = Newsletter::factory()->sent()->create();
    $draft = Newsletter::factory()->create(['subject' => 'Draft Newsletter']);
    User::factory()->withoutTwoFactor()->create();

    $this->artisan("newsletter:send --id={$draft->id}")
        ->assertSuccessful();

    Mail::assertSent(NewsletterMailable::class, function (NewsletterMailable $mail) {
        return $mail->hasSubject('Draft Newsletter');
    });
});

test('newsletter content renders correctly', function () {
    $user = User::factory()->withoutTwoFactor()->make(['name' => 'Jane Doe']);

    $mailable = new NewsletterMailable(
        emailSubject: 'Test Newsletter',
        user: $user,
        newsletterData: [
            'headline' => 'Test Headline',
            'intro' => 'Test introduction paragraph.',
            'sections' => [
                [
                    'title' => 'Section One',
                    'body' => '<p>Section one body content.</p>',
                ],
            ],
            'cta_text' => 'Click Here',
            'cta_url' => 'https://example.com',
            'closing' => 'Thanks for reading!',
        ],
    );

    $mailable->assertHasSubject('Test Newsletter');
    $mailable->assertSeeInHtml('Jane Doe');
    $mailable->assertSeeInHtml('Test Headline');
    $mailable->assertSeeInHtml('Test introduction paragraph.');
    $mailable->assertSeeInHtml('Section One');
    $mailable->assertSeeInHtml('Section one body content.');
    $mailable->assertSeeInHtml('Click Here');
    $mailable->assertSeeInHtml('https://example.com');
    $mailable->assertSeeInHtml('Thanks for reading!');
});
