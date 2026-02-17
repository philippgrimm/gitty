<?php

declare(strict_types=1);

use App\DTOs\AheadBehind;

test('AheadBehind can be constructed', function () {
    $ab = new AheadBehind(ahead: 3, behind: 2);

    expect($ab->ahead)->toBe(3);
    expect($ab->behind)->toBe(2);
});

test('AheadBehind isUpToDate returns true when both zero', function () {
    $ab = new AheadBehind(ahead: 0, behind: 0);
    expect($ab->isUpToDate())->toBeTrue();
});

test('AheadBehind isUpToDate returns false when ahead', function () {
    $ab = new AheadBehind(ahead: 1, behind: 0);
    expect($ab->isUpToDate())->toBeFalse();
});

test('AheadBehind isUpToDate returns false when behind', function () {
    $ab = new AheadBehind(ahead: 0, behind: 1);
    expect($ab->isUpToDate())->toBeFalse();
});

test('AheadBehind hasDiverged returns true when both non-zero', function () {
    $ab = new AheadBehind(ahead: 2, behind: 3);
    expect($ab->hasDiverged())->toBeTrue();
});

test('AheadBehind hasDiverged returns false when only ahead', function () {
    $ab = new AheadBehind(ahead: 2, behind: 0);
    expect($ab->hasDiverged())->toBeFalse();
});

test('AheadBehind hasDiverged returns false when only behind', function () {
    $ab = new AheadBehind(ahead: 0, behind: 3);
    expect($ab->hasDiverged())->toBeFalse();
});

test('AheadBehind hasDiverged returns false when up to date', function () {
    $ab = new AheadBehind(ahead: 0, behind: 0);
    expect($ab->hasDiverged())->toBeFalse();
});
