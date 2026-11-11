<?php

declare(strict_types=1);

use App\Models\Program;
use Illuminate\Support\Facades\Cache;

test('public stats endpoint returns the headline counts as json', function () {
    Cache::forget('public-stats');
    Program::factory()->count(2)->create();

    $response = $this->getJson('/stats.json');

    $response->assertSuccessful()
        ->assertJsonStructure(['programs', 'songs', 'artists', 'schools', 'generated_at'])
        ->assertJsonPath('programs', Program::count());
});

test('public stats endpoint does not require authentication', function () {
    $this->getJson(route('stats'))->assertSuccessful();
});
