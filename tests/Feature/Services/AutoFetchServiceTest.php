<?php

declare(strict_types=1);

use App\Services\AutoFetchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
    Cache::flush();
});

test('start sets auto-fetch configuration in cache', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 180);

    expect($service->isRunning())->toBeTrue();
    expect($service->getNextFetchTime())->not->toBeNull();
});

test('stop clears auto-fetch state from cache', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 180);
    expect($service->isRunning())->toBeTrue();

    $service->stop();
    expect($service->isRunning())->toBeFalse();
    expect($service->getLastFetchTime())->toBeNull();
    expect($service->getNextFetchTime())->toBeNull();
});

test('shouldFetch returns false when not started', function () {
    $service = new AutoFetchService;
    expect($service->shouldFetch())->toBeFalse();
});

test('shouldFetch returns true when interval has elapsed', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 1); // 1 second interval (enforced to 60)

    Cache::put(
        'auto-fetch:'.md5($this->testRepoPath).':last-fetch',
        now()->subSeconds(61)->timestamp
    );

    expect($service->shouldFetch())->toBeTrue();
});

test('shouldFetch returns false when interval has not elapsed', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 180);

    // Set last fetch time to 1 second ago (interval is 180 seconds)
    Cache::put(
        'auto-fetch:'.md5($this->testRepoPath).':last-fetch',
        now()->subSeconds(1)->timestamp
    );

    expect($service->shouldFetch())->toBeFalse();
});

test('shouldFetch returns false when git operation queue is locked', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 1); // 1 second interval

    // Set last fetch time to 2 seconds ago
    Cache::put(
        'auto-fetch:'.md5($this->testRepoPath).':last-fetch',
        now()->subSeconds(2)->timestamp
    );

    // Lock the git operation queue
    Cache::lock('git-op-'.md5($this->testRepoPath), 30)->get();

    expect($service->shouldFetch())->toBeFalse();
});

test('executeFetch runs git fetch and returns success', function () {
    Process::fake([
        'git fetch --all' => Process::result("Fetching origin\nFetching upstream"),
    ]);

    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 180);
    $result = $service->executeFetch();

    expect($result['success'])->toBeTrue();
    expect($result['output'])->toContain('Fetching origin');
    expect($result['error'])->toBe('');
    expect($service->getLastFetchTime())->not->toBeNull();

    Process::assertRan('git fetch --all');
});

test('executeFetch returns error on failure', function () {
    Process::fake([
        'git fetch --all' => Process::result('fatal: Could not read from remote repository', exitCode: 1),
    ]);

    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 180);
    $result = $service->executeFetch();

    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Could not read from remote repository');
});

test('validates git repository on start', function () {
    $service = new AutoFetchService;
    expect(fn () => $service->start('/invalid/path', 180))
        ->toThrow(\InvalidArgumentException::class);
});

test('enforces minimum interval of 60 seconds', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 30); // Below minimum

    // Should be set to minimum of 60
    expect(Cache::get('auto-fetch:'.md5($this->testRepoPath).':interval'))->toBe(60);
});

test('interval of 0 disables auto-fetch', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 0);

    expect($service->isRunning())->toBeFalse();
});

test('getNextFetchTime calculates correctly', function () {
    $service = new AutoFetchService;
    $service->start($this->testRepoPath, 180);

    $lastFetch = now()->subSeconds(60);
    Cache::put(
        'auto-fetch:'.md5($this->testRepoPath).':last-fetch',
        $lastFetch->timestamp
    );

    $nextFetch = $service->getNextFetchTime();
    expect($nextFetch)->not->toBeNull();
    expect($nextFetch->timestamp)->toBe($lastFetch->addSeconds(180)->timestamp);
});
