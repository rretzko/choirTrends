<?php

declare(strict_types=1);

use App\Models\QuickTip;

test('resolvedFooter returns tip footer when set', function () {
    $tip = QuickTip::factory()->withFooter()->make();

    expect($tip->resolvedFooter())->toBe($tip->footer);
});

test('resolvedFooter falls back to config default when null', function () {
    $tip = QuickTip::factory()->make(['footer' => null]);

    expect($tip->resolvedFooter())->toBe(config('quicktip.default_footer'));
});

test('resolvedCallToAction returns tip call to action when set', function () {
    $tip = QuickTip::factory()->withCallToAction()->make();

    expect($tip->resolvedCallToAction())->toBe($tip->call_to_action);
});

test('resolvedCallToAction falls back to config default when null', function () {
    $tip = QuickTip::factory()->make(['call_to_action' => null]);

    expect($tip->resolvedCallToAction())->toBe(config('quicktip.default_call_to_action'));
});
