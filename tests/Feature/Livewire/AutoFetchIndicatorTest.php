<?php

declare(strict_types=1);

use App\Livewire\AutoFetchIndicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
    Cache::flush();
});

test('component mounts with repo path', function () {
    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('isActive', false)
        ->assertSet('isFetching', false);
});

test('component shows active status when auto-fetch is running', function () {
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 180);

    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshStatus')
        ->assertSet('isActive', true);
});

test('checkAndFetch executes fetch when should fetch returns true', function () {
    Process::fake([
        'git fetch --all' => Process::result("Fetching origin\nFetching upstream"),
    ]);

    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 60);
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subSeconds(61)->timestamp);

    $component = Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath]);

    // mount() already called checkAndFetch() once, so we need to set cache again for the explicit call
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subSeconds(61)->timestamp);

    $component->call('checkAndFetch')
        ->assertSet('lastError', '')
        ->assertDispatched('remote-updated');

    Process::assertRan('git fetch --all');
});

test('checkAndFetch skips fetch when should fetch returns false', function () {
    Process::fake();

    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 180);
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subSeconds(10)->timestamp);

    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->call('checkAndFetch')
        ->assertNotDispatched('remote-updated');

    Process::assertNothingRan();
});

test('checkAndFetch sets error when fetch fails', function () {
    Process::fake([
        'git fetch --all' => Process::result('fatal: Could not read from remote repository', exitCode: 1),
    ]);

    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 60);
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subSeconds(61)->timestamp);

    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->call('checkAndFetch')
        ->assertSet('lastError', 'fatal: Could not read from remote repository');
});

test('component shows last fetch time as relative string', function () {
    Process::fake([
        'git fetch --all' => Process::result('Fetching origin'),
    ]);

    // Set interval to 10 minutes (600 seconds) so shouldFetch() returns false during mount
    // (last-fetch is 5 minutes ago, which is less than 10 minutes)
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 600);
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subMinutes(5)->timestamp);

    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshStatus')
        ->assertSee('5 minutes ago');
});

test('component clears error after successful fetch', function () {
    Process::fake([
        'git fetch --all' => Process::result('Fetching origin'),
    ]);

    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 60);
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subSeconds(61)->timestamp);

    $component = Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath]);
    $component->set('lastError', 'Previous error');

    // mount() already called checkAndFetch() once, so we need to set cache again for the explicit call
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':last-fetch', now()->subSeconds(61)->timestamp);

    $component->call('checkAndFetch')
        ->assertSet('lastError', '');
});

test('component detects queue lock status', function () {
    Cache::put('auto-fetch:'.md5($this->testRepoPath).':interval', 180);
    Cache::lock('git-op-'.md5($this->testRepoPath), 30)->get();

    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshStatus')
        ->assertSet('isQueueLocked', true);
});
