<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use App\Livewire\HistoryPanel;
use App\Livewire\RepoSidebar;
use App\Models\Setting;
use App\Services\Git\AbstractGitService;
use App\Services\Git\GitCacheService;
use App\Services\Git\GitConfigValidator;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Helpers\GitTestHelper;

uses(RefreshDatabase::class);

beforeEach(function () {
    GitConfigValidator::resetCache();

    $this->testRepoPath = sys_get_temp_dir().'/gitty-test-startup-'.uniqid();
    GitTestHelper::createTestRepo($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});

test('GitConfigValidator caches the git binary check result', function () {
    Process::fake([
        'which git' => '/usr/bin/git',
    ]);

    $result1 = GitConfigValidator::checkGitBinary();
    $result2 = GitConfigValidator::checkGitBinary();

    expect($result1)->toBeTrue();
    expect($result2)->toBeTrue();

    Process::assertRan('which git');
});

test('GitConfigValidator resetCache allows re-checking git binary', function () {
    Process::fake([
        'which git' => '/usr/bin/git',
    ]);

    $result1 = GitConfigValidator::checkGitBinary();
    expect($result1)->toBeTrue();

    GitConfigValidator::resetCache();

    Process::fake([
        'which git' => Process::result('', exitCode: 1),
    ]);

    $result2 = GitConfigValidator::checkGitBinary();
    expect($result2)->toBeFalse();
});

test('SettingsService batch loads all settings on first get call', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);
    Setting::create(['key' => 'auto_fetch_interval', 'value' => '300']);

    $service = new SettingsService;

    $theme = $service->get('theme');
    expect($theme)->toBe('light');

    $interval = $service->get('auto_fetch_interval');
    expect($interval)->toBe(300);
});

test('SettingsService get returns default when no settings exist', function () {
    $service = new SettingsService;

    $value = $service->get('theme', 'light');
    expect($value)->toBeString();
    expect($value)->toBe('dark');
});

test('SettingsService reset clears the internal cache', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);

    $service = new SettingsService;
    expect($service->get('theme'))->toBe('light');

    $service->reset();

    Setting::create(['key' => 'theme', 'value' => 'dark']);
    expect($service->get('theme'))->toBe('dark');
});

test('AbstractGitService accepts optional GitCacheService via constructor', function () {
    $customCache = new GitCacheService;

    $service = new class($this->testRepoPath, $customCache) extends AbstractGitService
    {
        public function getCache(): GitCacheService
        {
            return $this->cache;
        }
    };

    expect($service->getCache())->toBe($customCache);
});

test('AbstractGitService uses app-resolved GitCacheService when none provided', function () {
    $service = new class($this->testRepoPath) extends AbstractGitService
    {
        public function getCache(): GitCacheService
        {
            return $this->cache;
        }
    };

    expect($service->getCache())->toBeInstanceOf(GitCacheService::class);
});

test('HistoryPanel mount does not load commits', function () {
    $panel = new HistoryPanel;
    $panel->mount($this->testRepoPath);

    expect($panel->loaded)->toBeFalse();
    expect($panel->commitsCount)->toBe(0);
});

test('HistoryPanel activate sets loaded to true', function () {
    $panel = new HistoryPanel;
    $panel->mount($this->testRepoPath);

    expect($panel->loaded)->toBeFalse();

    $panel->activate();

    expect($panel->loaded)->toBeTrue();
    expect($panel->commitsCount)->toBeGreaterThan(0);
});

test('HistoryPanel activate is idempotent', function () {
    $panel = new HistoryPanel;
    $panel->mount($this->testRepoPath);

    $panel->activate();
    $countAfterFirst = $panel->commitsCount;

    $panel->activate();
    $countAfterSecond = $panel->commitsCount;

    expect($countAfterFirst)->toBe($countAfterSecond);
});

test('CommitPanel mount does not load commit history', function () {
    $panel = new CommitPanel;
    $panel->repoPath = $this->testRepoPath;
    $panel->mount();

    expect($panel->commitHistory)->toBeEmpty();
    expect($panel->storedHistory)->toBeEmpty();
});

test('CommitPanel loadHistoryData populates commit history', function () {
    $panel = new CommitPanel;
    $panel->repoPath = $this->testRepoPath;
    $panel->mount();

    expect($panel->commitHistory)->toBeEmpty();

    $panel->loadHistoryData();

    expect($panel->commitHistory)->not->toBeEmpty();
});

test('RepoSidebar mount loads branch data but not secondary data', function () {
    $sidebar = new RepoSidebar;
    $sidebar->repoPath = $this->testRepoPath;
    $sidebar->mount();

    expect($sidebar->branches)->not->toBeEmpty();
    expect($sidebar->currentBranch)->not->toBeEmpty();

    expect($sidebar->secondaryDataLoaded)->toBeFalse();
    expect($sidebar->remotes)->toBeEmpty();
    expect($sidebar->tags)->toBeEmpty();
    expect($sidebar->stashes)->toBeEmpty();
});

test('RepoSidebar loadSecondaryData populates remotes tags and stashes', function () {
    $sidebar = new RepoSidebar;
    $sidebar->repoPath = $this->testRepoPath;
    $sidebar->mount();

    expect($sidebar->secondaryDataLoaded)->toBeFalse();

    $sidebar->loadSecondaryData();

    expect($sidebar->secondaryDataLoaded)->toBeTrue();
});

test('RepoSidebar loadSecondaryData is idempotent', function () {
    $sidebar = new RepoSidebar;
    $sidebar->repoPath = $this->testRepoPath;
    $sidebar->mount();

    $sidebar->loadSecondaryData();
    expect($sidebar->secondaryDataLoaded)->toBeTrue();

    $sidebar->loadSecondaryData();
    expect($sidebar->secondaryDataLoaded)->toBeTrue();
});
