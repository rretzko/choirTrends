<?php

declare(strict_types=1);

use App\Models\User;

test('welcome page has homepage title without pipe separator', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $appName = config('app.name');
    $response->assertSee("<title>{$appName} — Discover What&#039;s Trending in Choral Music</title>", false);
});

test('welcome page has custom meta description', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('<meta name="description" content="Upload your choral concert programs', false);
});

test('welcome page has open graph tags', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('<meta property="og:type" content="website">', false);
    $response->assertSee('<meta property="og:title"', false);
    $response->assertSee('<meta property="og:description"', false);
    $response->assertSee('<meta property="og:url"', false);
    $response->assertSee('<meta property="og:site_name" content="'.config('app.name').'">', false);
    $response->assertSee('<meta property="og:image"', false);
    $response->assertSee('<meta property="og:locale"', false);
});

test('welcome page has twitter card tags', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
    $response->assertSee('<meta name="twitter:title"', false);
    $response->assertSee('<meta name="twitter:description"', false);
    $response->assertSee('<meta name="twitter:image"', false);
});

test('welcome page has canonical url', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('<link rel="canonical" href="', false);
});

test('welcome page has organization json-ld schema', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('"@type": "Organization"', false);
    $response->assertSee('"name": "'.config('app.name').'"', false);
});

test('welcome page has website json-ld schema', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('"@type": "WebSite"', false);
});

test('welcome page og image references og-image.png', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertSee('og-image.png', false);
});

test('authenticated page has pipe-separated title', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('<title>Dashboard | '.config('app.name').'</title>', false);
});

test('authenticated page has default meta description', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('<meta name="description" content="Upload concert programs and discover trending choral repertoire', false);
});

test('authenticated page has canonical url for its path', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSuccessful();
    $response->assertSee('<link rel="canonical" href="'.url('/dashboard').'">', false);
});

test('catalog page has seo meta tags', function () {
    $user = User::factory()->withoutTwoFactor()->withCatalog()->create();

    $response = $this->get('/catalog/'.$user->catalog_token);

    $response->assertSuccessful();
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('<meta property="og:title"', false);
    $response->assertSee('<meta name="twitter:card"', false);
    $response->assertSee('<link rel="canonical"', false);
});

test('login page has seo meta tags', function () {
    $response = $this->get('/login');

    $response->assertSuccessful();
    $response->assertSee('<meta name="description"', false);
    $response->assertSee('<meta property="og:title"', false);
    $response->assertSee('<link rel="canonical"', false);
});
