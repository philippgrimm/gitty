<?php

declare(strict_types=1);

use App\Livewire\AutoFetchIndicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('auto-fetch indicator component renders with inactive state', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $component = Livewire::test(AutoFetchIndicator::class, ['repoPath' => BrowserTestHelper::MOCK_REPO_PATH]);

    $component->assertSee('Auto-fetch off');
    $component->assertSet('isActive', false);
    $component->assertSet('isFetching', false);
});

test('auto-fetch indicator component shows active state when enabled', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repoHash = md5(BrowserTestHelper::MOCK_REPO_PATH);
    Cache::put('auto-fetch:'.$repoHash.':interval', 180);
    Cache::put('auto-fetch:'.$repoHash.':last-fetch', now()->subMinutes(5)->timestamp);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $component = Livewire::test(AutoFetchIndicator::class, ['repoPath' => BrowserTestHelper::MOCK_REPO_PATH]);

    $component->assertSee('5 minutes ago');
    $component->assertSet('isActive', true);
    $component->assertSet('isFetching', false);
});

test('auto-fetch indicator component shows paused state when queue is locked', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repoHash = md5(BrowserTestHelper::MOCK_REPO_PATH);
    Cache::put('auto-fetch:'.$repoHash.':interval', 180);
    Cache::lock('git-op-'.$repoHash, 30)->get();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $component = Livewire::test(AutoFetchIndicator::class, ['repoPath' => BrowserTestHelper::MOCK_REPO_PATH]);

    $component->assertSee('Paused');
    $component->assertSet('isActive', true);
    $component->assertSet('isQueueLocked', true);
});
