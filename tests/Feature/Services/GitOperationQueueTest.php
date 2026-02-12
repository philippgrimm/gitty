<?php

declare(strict_types=1);

use App\Exceptions\GitOperationInProgressException;
use App\Services\Git\GitOperationQueue;
use Illuminate\Support\Facades\Cache;

test('it executes operation with lock', function () {
    Cache::flush();

    $queue = new GitOperationQueue('/fake/repo');
    $result = $queue->execute(fn () => 'test result');

    expect($result)->toBe('test result');
});

test('it throws exception when lock cannot be acquired', function () {
    Cache::flush();

    $queue1 = new GitOperationQueue('/fake/repo');
    $queue2 = new GitOperationQueue('/fake/repo');

    $queue1->execute(function () use ($queue2) {
        expect(fn () => $queue2->execute(fn () => 'should fail'))
            ->toThrow(GitOperationInProgressException::class);

        return 'success';
    });
});

test('it checks if operation is locked', function () {
    Cache::flush();

    $queue = new GitOperationQueue('/fake/repo');

    expect($queue->isLocked())->toBeFalse();

    $queue->execute(function () use ($queue) {
        expect($queue->isLocked())->toBeTrue();

        return 'done';
    });

    expect($queue->isLocked())->toBeFalse();
});
