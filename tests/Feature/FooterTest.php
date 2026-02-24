<?php

declare(strict_types=1);

use App\Models\User;

test('welcome page displays footer with all sections', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('All rights reserved.');
    $response->assertSee('About');
    $response->assertSee('v'.config('app.version'));
    $response->assertSee('Powered by MFR Holdings, LLC');
    $response->assertSee('https://mfrholdings.com');
});

test('login page displays footer with all sections', function () {
    $response = $this->get('/login');

    $response->assertSuccessful();
    $response->assertSee('All rights reserved.');
    $response->assertSee('About');
    $response->assertSee('v'.config('app.version'));
    $response->assertSee('Powered by MFR Holdings, LLC');
});

test('dashboard page displays footer with all sections', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('All rights reserved.');
    $response->assertSee('About');
    $response->assertSee('v'.config('app.version'));
    $response->assertSee('Powered by MFR Holdings, LLC');
});

test('footer displays current year in copyright', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('&copy; '.date('Y').' '.config('app.name'), false);
});

test('footer displays app version from config', function () {
    config(['app.version' => '2.5.0']);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('v2.5.0');
});
