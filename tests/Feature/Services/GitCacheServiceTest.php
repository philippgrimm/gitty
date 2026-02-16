<?php

declare(strict_types=1);

use App\Services\Git\GitCacheService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('it generates cache key with md5 hash of repo path', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';
    $expectedHash = md5($repoPath);

    $result = $service->get($repoPath, 'status', fn () => 'test-value', 5);

    expect(Cache::has("gitty:{$expectedHash}:status"))->toBeTrue();
});

test('it caches callback result with TTL', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';
    $callCount = 0;

    $callback = function () use (&$callCount) {
        $callCount++;

        return 'cached-value';
    };

    // First call executes callback
    $result1 = $service->get($repoPath, 'test-key', $callback, 60);
    expect($result1)->toBe('cached-value')
        ->and($callCount)->toBe(1);

    // Second call returns cached value without executing callback
    $result2 = $service->get($repoPath, 'test-key', $callback, 60);
    expect($result2)->toBe('cached-value')
        ->and($callCount)->toBe(1);
});

test('it invalidates specific cache key', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';
    $callCount = 0;

    $callback = function () use (&$callCount) {
        $callCount++;

        return "value-{$callCount}";
    };

    // Cache initial value
    $result1 = $service->get($repoPath, 'status', $callback, 60);
    expect($result1)->toBe('value-1');

    // Invalidate cache
    $service->invalidate($repoPath, 'status');

    // Next call executes callback again
    $result2 = $service->get($repoPath, 'status', $callback, 60);
    expect($result2)->toBe('value-2')
        ->and($callCount)->toBe(2);
});

test('it invalidates all cache for a repository', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';

    // Cache multiple keys
    $service->get($repoPath, 'status', fn () => 'status-value', 60);
    $service->get($repoPath, 'log', fn () => 'log-value', 60);
    $service->get($repoPath, 'branches', fn () => 'branches-value', 60);

    $hash = md5($repoPath);
    expect(Cache::has("gitty:{$hash}:status"))->toBeTrue()
        ->and(Cache::has("gitty:{$hash}:log"))->toBeTrue()
        ->and(Cache::has("gitty:{$hash}:branches"))->toBeTrue();

    // Invalidate all
    $service->invalidateAll($repoPath);

    expect(Cache::has("gitty:{$hash}:status"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:log"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:branches"))->toBeFalse();
});

test('it invalidates cache group', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';

    // Cache multiple keys
    $service->get($repoPath, 'status', fn () => 'status-value', 60);
    $service->get($repoPath, 'diff', fn () => 'diff-value', 60);
    $service->get($repoPath, 'log', fn () => 'log-value', 60);
    $service->get($repoPath, 'branches', fn () => 'branches-value', 60);

    $hash = md5($repoPath);

    // Invalidate 'status' group (status + diff)
    $service->invalidateGroup($repoPath, 'status');

    expect(Cache::has("gitty:{$hash}:status"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:diff"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:log"))->toBeTrue()
        ->and(Cache::has("gitty:{$hash}:branches"))->toBeTrue();
});

test('it invalidates history group', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';

    // Cache multiple keys
    $service->get($repoPath, 'log', fn () => 'log-value', 60);
    $service->get($repoPath, 'status', fn () => 'status-value', 60);

    $hash = md5($repoPath);

    // Invalidate 'history' group (log only)
    $service->invalidateGroup($repoPath, 'history');

    expect(Cache::has("gitty:{$hash}:log"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:status"))->toBeTrue();
});

test('it invalidates branches group', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';

    // Cache multiple keys
    $service->get($repoPath, 'branches', fn () => 'branches-value', 60);
    $service->get($repoPath, 'status', fn () => 'status-value', 60);

    $hash = md5($repoPath);

    // Invalidate 'branches' group
    $service->invalidateGroup($repoPath, 'branches');

    expect(Cache::has("gitty:{$hash}:branches"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:status"))->toBeTrue();
});

test('it invalidates stashes group', function () {
    $service = new GitCacheService;
    $repoPath = '/tmp/gitty-test-repo';

    // Cache multiple keys
    $service->get($repoPath, 'stashes', fn () => 'stashes-value', 60);
    $service->get($repoPath, 'status', fn () => 'status-value', 60);

    $hash = md5($repoPath);

    // Invalidate 'stashes' group
    $service->invalidateGroup($repoPath, 'stashes');

    expect(Cache::has("gitty:{$hash}:stashes"))->toBeFalse()
        ->and(Cache::has("gitty:{$hash}:status"))->toBeTrue();
});
