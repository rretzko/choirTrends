<?php

use App\Enums\VideoVisibility;

test('has Private and Public cases', function () {
    expect(VideoVisibility::cases())->toHaveCount(2);
    expect(VideoVisibility::Private->value)->toBe('Private');
    expect(VideoVisibility::Public->value)->toBe('Public');
});

test('label returns correct labels', function () {
    expect(VideoVisibility::Private->label())->toBe('Private');
    expect(VideoVisibility::Public->label())->toBe('Public');
});

test('can be created from string value', function () {
    expect(VideoVisibility::from('Private'))->toBe(VideoVisibility::Private);
    expect(VideoVisibility::from('Public'))->toBe(VideoVisibility::Public);
});

test('tryFrom returns null for invalid value', function () {
    expect(VideoVisibility::tryFrom('invalid'))->toBeNull();
});
