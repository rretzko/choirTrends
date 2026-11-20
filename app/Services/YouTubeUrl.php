<?php

declare(strict_types=1);

namespace App\Services;

use Closure;

/**
 * Parses and normalizes YouTube links (watch, youtu.be, shorts, live, embed)
 * into a video id plus an optional start time, without calling the YouTube API.
 */
final class YouTubeUrl
{
    private function __construct(
        public readonly string $videoId,
        public readonly ?int $startSeconds = null,
    ) {}

    public static function parse(?string $url): ?self
    {
        $url = trim((string) $url);

        if ($url === '' || strlen($url) > 2048) {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/i', '', $parts['host']) ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = explode('/', ltrim($path, '/'))[0] ?? null;
        } elseif ($host === 'youtube.com' || $host === 'youtube-nocookie.com') {
            if ($path === '/watch' || $path === '/watch/') {
                $videoId = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('#^/(shorts|live|embed|v)/([^/?]+)#', $path, $matches)) {
                $videoId = $matches[2];
            }
        }

        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        $start = $query['t'] ?? $query['start'] ?? null;

        return new self($videoId, is_string($start) ? self::parseStartTime($start) : null);
    }

    /**
     * Normalizes any accepted link to a canonical watch URL, or null when it is not a YouTube video link.
     */
    public static function normalize(?string $url): ?string
    {
        return self::parse($url)?->watchUrl();
    }

    /**
     * Validation closure for use in Livewire/Form Request rule arrays.
     */
    public static function rule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || self::parse($value) === null) {
                $fail(__('Please enter a valid YouTube video link (e.g. https://www.youtube.com/watch?v=… or https://youtu.be/…).'));
            }
        };
    }

    public function watchUrl(): string
    {
        return 'https://www.youtube.com/watch?v='.$this->videoId
            .($this->startSeconds ? '&t='.$this->startSeconds.'s' : '');
    }

    public function embedUrl(): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$this->videoId
            .'?rel=0'.($this->startSeconds ? '&start='.$this->startSeconds : '');
    }

    public function thumbnailUrl(): string
    {
        return 'https://img.youtube.com/vi/'.$this->videoId.'/mqdefault.jpg';
    }

    /**
     * Accepts "90", "90s", "1m30s", "1h2m3s".
     */
    private static function parseStartTime(string $value): ?int
    {
        if (preg_match('/^\d+$/', $value)) {
            return (int) $value ?: null;
        }

        if (! preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $m) || $value === '') {
            return null;
        }

        $seconds = ((int) ($m[1] ?? 0)) * 3600 + ((int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);

        return $seconds ?: null;
    }
}
