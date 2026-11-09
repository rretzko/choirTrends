<?php

declare(strict_types=1);

use App\Models\DigitalProgram;
use App\Models\Program;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\URL;

test('sitemap returns valid xml listing the public pages', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');

    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();

    $locs = [];
    foreach ($xml->url as $url) {
        $locs[] = (string) $url->loc;
    }

    expect($locs)->toContain(route('home'), route('register'));
});

test('sitemap does not list unlisted or authenticated pages', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertDontSee('/catalog/', false)
        ->assertDontSee('/p/', false)
        ->assertDontSee('/dashboard', false);
});

test('robots.txt references the sitemap', function () {
    expect(file_get_contents(public_path('robots.txt')))
        ->toContain('Sitemap: https://choirtrends.com/sitemap.xml');
});

test('homepage is indexable', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
    expect($response->headers->get('X-Robots-Tag'))->toBeNull();
});

test('organization json-ld uses a raster logo', function () {
    $this->get('/')->assertSee('"logo": "'.asset('apple-touch-icon.png').'"', false);
});

test('catalog share page is not indexable', function () {
    $user = User::factory()->withoutTwoFactor()->withCatalog()->create();

    $this->get('/catalog/'.$user->catalog_token)
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('public digital program pages are not indexable', function (string $routeName) {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route($routeName, $dp->slug))
        ->assertSuccessful()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
})->with(['program.public', 'program.booklet', 'program.qr']);

test('signed survey page is not indexable', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->get(URL::signedRoute('survey.show', ['user' => $user]));

    expect($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow');
});

test('public digital program has a clean title, rich description and share tags', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Northview High School']);
    $program = Program::factory()->for($user)->for($school)->create([
        'event_name' => 'Winter Concert',
        'event_date' => '2026-12-10',
    ]);
    $dp = DigitalProgram::factory()->for($user)->published()->create(['program_id' => $program->id]);
    $appName = config('app.name');

    $this->get(route('program.public', $dp->slug))
        ->assertSuccessful()
        ->assertSee('<title>Winter Concert — December 10, 2026 | '.$appName.'</title>', false)
        ->assertSee('<meta name="description" content="Digital concert program for Winter Concert at Northview High School on December 10, 2026.">', false)
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertSee('<link rel="canonical" href="'.route('program.public', $dp->slug).'">', false)
        ->assertSee('<meta property="og:title" content="Winter Concert — December 10, 2026 | '.$appName.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false);
});

test('public digital program without a linked program falls back to a generic title', function () {
    $dp = DigitalProgram::factory()->published()->create(['program_id' => null]);

    $this->get(route('program.public', $dp->slug))
        ->assertSuccessful()
        ->assertSee('<title>Concert Program | '.config('app.name').'</title>', false)
        ->assertSee('<meta name="description" content="Digital concert program for Concert Program.">', false);
});
