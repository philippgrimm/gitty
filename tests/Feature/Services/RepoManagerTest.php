<?php

declare(strict_types=1);

use App\Models\Repository;
use App\Services\RepoManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('it validates .git directory exists when opening repo', function () {
    $service = new RepoManager;

    expect(fn () => $service->openRepo('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it creates a new repository record when opening a repo for the first time', function () {
    $service = new RepoManager;
    $repo = $service->openRepo($this->testRepoPath);

    expect($repo)->toBeInstanceOf(Repository::class)
        ->and($repo->path)->toBe($this->testRepoPath)
        ->and($repo->name)->toBe('gitty-test-repo')
        ->and($repo->last_opened_at)->not->toBeNull();

    $this->assertDatabaseHas('repositories', [
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
    ]);
});

test('it updates last_opened_at when opening an existing repo', function () {
    $repo = Repository::create([
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
        'last_opened_at' => now()->subDays(5),
    ]);

    $oldTimestamp = $repo->last_opened_at;

    sleep(1); // Ensure timestamp difference

    $service = new RepoManager;
    $updatedRepo = $service->openRepo($this->testRepoPath);

    expect($updatedRepo->id)->toBe($repo->id)
        ->and($updatedRepo->last_opened_at->isAfter($oldTimestamp))->toBeTrue();
});

test('it returns recent repositories sorted by last_opened_at descending', function () {
    Repository::create([
        'path' => '/path/repo1',
        'name' => 'repo1',
        'last_opened_at' => now()->subDays(5),
    ]);

    Repository::create([
        'path' => '/path/repo2',
        'name' => 'repo2',
        'last_opened_at' => now()->subDays(1),
    ]);

    Repository::create([
        'path' => '/path/repo3',
        'name' => 'repo3',
        'last_opened_at' => now()->subDays(10),
    ]);

    $service = new RepoManager;
    $recent = $service->recentRepos();

    expect($recent)->toHaveCount(3)
        ->and($recent->first()->name)->toBe('repo2')
        ->and($recent->last()->name)->toBe('repo3');
});

test('it limits recent repositories to specified count', function () {
    for ($i = 1; $i <= 25; $i++) {
        Repository::create([
            'path' => "/path/repo{$i}",
            'name' => "repo{$i}",
            'last_opened_at' => now()->subDays($i),
        ]);
    }

    $service = new RepoManager;
    $recent = $service->recentRepos(20);

    expect($recent)->toHaveCount(20);
});

test('it removes a repository from the database', function () {
    $repo = Repository::create([
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
        'last_opened_at' => now(),
    ]);

    $service = new RepoManager;
    $service->removeRepo($repo->id);

    $this->assertDatabaseMissing('repositories', [
        'id' => $repo->id,
    ]);
});

test('it stores current repo in cache', function () {
    $repo = Repository::create([
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
        'last_opened_at' => now(),
    ]);

    $service = new RepoManager;
    $service->setCurrentRepo($repo);

    expect(Cache::get('current_repo_id'))->toBe($repo->id);
});

test('it retrieves current repo from cache', function () {
    $repo = Repository::create([
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
        'last_opened_at' => now(),
    ]);

    Cache::put('current_repo_id', $repo->id);

    $service = new RepoManager;
    $currentRepo = $service->currentRepo();

    expect($currentRepo)->toBeInstanceOf(Repository::class)
        ->and($currentRepo->id)->toBe($repo->id);
});

test('it returns null when no current repo is set', function () {
    $service = new RepoManager;
    $currentRepo = $service->currentRepo();

    expect($currentRepo)->toBeNull();
});
