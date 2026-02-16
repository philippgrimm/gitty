<?php

declare(strict_types=1);

use App\Livewire\RepoSwitcher;
use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with empty state when no repo is open', function () {
    Livewire::test(RepoSwitcher::class)
        ->assertSet('currentRepoPath', '')
        ->assertSet('currentRepoName', '')
        ->assertSet('recentRepos', [])
        ->assertSee('No repository open');
});

test('component displays current repo when one is open', function () {
    $repo = Repository::create([
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Livewire::test(RepoSwitcher::class)
        ->assertSet('currentRepoPath', $this->testRepoPath)
        ->assertSet('currentRepoName', 'gitty-test-repo')
        ->assertSee('gitty-test-repo');
});

test('component displays recent repositories', function () {
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

    Livewire::test(RepoSwitcher::class)
        ->assertSee('repo1')
        ->assertSee('repo2');
});

test('component switches to a different repository', function () {
    $repo1 = Repository::create([
        'path' => $this->testRepoPath,
        'name' => 'gitty-test-repo',
        'last_opened_at' => now()->subDays(1),
    ]);

    $repo2 = Repository::create([
        'path' => '/tmp/other-repo',
        'name' => 'other-repo',
        'last_opened_at' => now()->subDays(2),
    ]);

    if (! is_dir('/tmp/other-repo/.git')) {
        mkdir('/tmp/other-repo/.git', 0755, true);
    }

    cache()->put('current_repo_id', $repo1->id);

    Livewire::test(RepoSwitcher::class)
        ->call('switchRepo', $repo2->id)
        ->assertSet('currentRepoPath', '/tmp/other-repo')
        ->assertSet('currentRepoName', 'other-repo')
        ->assertDispatched('repo-switched', path: '/tmp/other-repo');
});

test('component removes a repository from recent list', function () {
    $repo = Repository::create([
        'path' => '/path/repo1',
        'name' => 'repo1',
        'last_opened_at' => now(),
    ]);

    Livewire::test(RepoSwitcher::class)
        ->assertSee('repo1')
        ->call('removeRecentRepo', $repo->id)
        ->assertDontSee('repo1');
});

test('component handles invalid repository path when switching', function () {
    $repo = Repository::create([
        'path' => '/invalid/path',
        'name' => 'invalid-repo',
        'last_opened_at' => now(),
    ]);

    Livewire::test(RepoSwitcher::class)
        ->call('switchRepo', $repo->id)
        ->assertSet('error', 'Repository path does not exist or is not a valid git repository');
});

test('component dispatches repo-switched event when opening a repo', function () {
    Livewire::test(RepoSwitcher::class)
        ->call('openRepo', $this->testRepoPath)
        ->assertDispatched('repo-switched', path: $this->testRepoPath);
});

test('component handles error when opening invalid repo path', function () {
    Livewire::test(RepoSwitcher::class)
        ->call('openRepo', '/invalid/path')
        ->assertSet('error', 'Not a valid git repository: /invalid/path');
});
