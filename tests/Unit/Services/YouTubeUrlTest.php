<?php

declare(strict_types=1);

use App\Services\YouTubeUrl;

it('extracts the video id from supported link formats', function (string $url) {
    expect(YouTubeUrl::parse($url)?->videoId)->toBe('dQw4w9WgXcQ');
})->with([
    'watch' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'watch with extra params' => 'https://www.youtube.com/watch?feature=share&v=dQw4w9WgXcQ&list=PL123',
    'no scheme' => 'youtube.com/watch?v=dQw4w9WgXcQ',
    'mobile' => 'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
    'music' => 'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
    'short link' => 'https://youtu.be/dQw4w9WgXcQ',
    'short link with share param' => 'https://youtu.be/dQw4w9WgXcQ?si=abcdef',
    'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'live' => 'https://www.youtube.com/live/dQw4w9WgXcQ?feature=share',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'nocookie embed' => 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
    'surrounding whitespace' => '  https://youtu.be/dQw4w9WgXcQ  ',
]);

it('rejects links that are not YouTube videos', function (?string $url) {
    expect(YouTubeUrl::parse($url))->toBeNull();
})->with([
    'null' => null,
    'empty' => '',
    'plain text' => 'not a link',
    'other site' => 'https://vimeo.com/123456789',
    'lookalike host' => 'https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ',
    'channel page' => 'https://www.youtube.com/@SomeChoir',
    'search results' => 'https://www.youtube.com/results?search_query=choir',
    'playlist only' => 'https://www.youtube.com/playlist?list=PL1234567890',
    'id too short' => 'https://youtu.be/abc123',
    'id with bad characters' => 'https://www.youtube.com/watch?v=dQw4w9WgX<Q',
    'javascript scheme' => 'javascript:alert(1)',
]);

it('reads start times in seconds and h/m/s formats', function (string $url, ?int $expected) {
    expect(YouTubeUrl::parse($url)?->startSeconds)->toBe($expected);
})->with([
    'seconds' => ['https://youtu.be/dQw4w9WgXcQ?t=90', 90],
    'seconds with suffix' => ['https://youtu.be/dQw4w9WgXcQ?t=90s', 90],
    'minutes and seconds' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1m30s', 90],
    'hours' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1h2m3s', 3723],
    'start param' => ['https://www.youtube.com/embed/dQw4w9WgXcQ?start=45', 45],
    'zero' => ['https://youtu.be/dQw4w9WgXcQ?t=0', null],
    'garbage' => ['https://youtu.be/dQw4w9WgXcQ?t=abc', null],
    'none' => ['https://youtu.be/dQw4w9WgXcQ', null],
]);

it('normalizes to a canonical watch link', function () {
    expect(YouTubeUrl::normalize('youtu.be/dQw4w9WgXcQ?si=xyz&t=1m5s'))
        ->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=65s')
        ->and(YouTubeUrl::normalize('https://vimeo.com/1'))->toBeNull();
});

it('builds privacy-enhanced embed and thumbnail links', function () {
    $youTube = YouTubeUrl::parse('https://youtu.be/dQw4w9WgXcQ?t=30');

    expect($youTube->embedUrl())->toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0&start=30')
        ->and($youTube->thumbnailUrl())->toBe('https://img.youtube.com/vi/dQw4w9WgXcQ/mqdefault.jpg');
});
