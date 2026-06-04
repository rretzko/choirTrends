<?php

use App\Models\DigitalProgram;

test('published program returns an svg qr code', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.qr', $dp->slug))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

test('qr response body contains a valid svg element', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $response = $this->get(route('program.qr', $dp->slug));

    expect($response->content())->toContain('<svg');
});

test('qr svg has substantial content for the program', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $response = $this->get(route('program.qr', $dp->slug));
    $svg = $response->content();

    // SVG must have width/height attributes and path data (the QR matrix)
    expect($svg)->toContain('width="400"')
        ->toContain('height="400"')
        ->toContain('<path');
});

test('unpublished program returns 404 for qr endpoint', function () {
    $dp = DigitalProgram::factory()->create(['is_published' => false]);

    $this->get(route('program.qr', $dp->slug))
        ->assertNotFound();
});

test('non-existent slug returns 404 for qr endpoint', function () {
    $this->get(route('program.qr', 'nosuchxx'))
        ->assertNotFound();
});

test('guest can access qr endpoint for a published program', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.qr', $dp->slug))
        ->assertOk();
});

test('qr endpoint accepts a custom size parameter', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.qr', $dp->slug).'?size=600')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml');
});

test('qr size is clamped to minimum of 100', function () {
    $dp = DigitalProgram::factory()->published()->create();

    // Very small size should not error — clamped to 100
    $this->get(route('program.qr', $dp->slug).'?size=10')
        ->assertOk();
});

test('qr size is clamped to maximum of 1000', function () {
    $dp = DigitalProgram::factory()->published()->create();

    // Excessively large size should not error — clamped to 1000
    $this->get(route('program.qr', $dp->slug).'?size=9999')
        ->assertOk();
});

test('qr response includes cache-control header with max-age', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $response = $this->get(route('program.qr', $dp->slug))
        ->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('max-age=86400');
});

test('public program page links to the qr endpoint', function () {
    $dp = DigitalProgram::factory()->published()->create();

    $this->get(route('program.public', $dp->slug))
        ->assertOk()
        ->assertSee(route('program.qr', $dp->slug));
});
