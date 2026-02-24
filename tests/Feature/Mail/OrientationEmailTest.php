<?php

declare(strict_types=1);

use App\Mail\OrientationEmail;
use App\Models\User;

test('email subject is correct', function () {
    $user = User::factory()->withoutTwoFactor()->make(['name' => 'Jane Doe']);

    $mailable = new OrientationEmail($user);

    $mailable->assertHasSubject('Welcome to ChoirTrends - Your Orientation Guide');
});

test('email contains user name', function () {
    $user = User::factory()->withoutTwoFactor()->make(['name' => 'Jane Doe']);

    $mailable = new OrientationEmail($user);

    $mailable->assertSeeInHtml('Jane Doe');
});

test('email contains all sidebar feature names', function () {
    $user = User::factory()->withoutTwoFactor()->make(['name' => 'Jane Doe']);

    $mailable = new OrientationEmail($user);

    $mailable->assertSeeInHtml('Dashboard');
    $mailable->assertSeeInHtml('Programs');
    $mailable->assertSeeInHtml('Add Program');
    $mailable->assertSeeInHtml('Composers/Arrangers');
    $mailable->assertSeeInHtml('Ensembles');
    $mailable->assertSeeInHtml('Schools');
    $mailable->assertSeeInHtml('Song Titles');
    $mailable->assertSeeInHtml('Feedback');
    $mailable->assertSeeInHtml('Dark Mode');
});
