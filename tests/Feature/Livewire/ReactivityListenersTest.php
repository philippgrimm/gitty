<?php

declare(strict_types=1);

use App\Livewire\AutoFetchIndicator;
use App\Livewire\BranchManager;
use App\Livewire\DiffViewer;
use App\Livewire\SearchPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('branch manager refreshes branches when status-updated event is received', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('status-updated')
        ->assertSet('currentBranch', 'main');

    Process::assertRan('git branch -a -vv', 2);
});

test('diff viewer reloads diff when file still has changes after status-updated', function () {
    Process::fake([
        "git diff -- 'README.md'" => Process::result(GitOutputFixtures::diffUnstaged()),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->set('file', 'README.md')
        ->set('isStaged', false)
        ->dispatch('status-updated')
        ->assertSet('file', 'README.md');
});

test('diff viewer clears diff when file has no more changes after status-updated', function () {
    Process::fake([
        "git diff -- 'README.md'" => Process::result(''),
        "git status --porcelain=v2 -- 'README.md'" => Process::result(''),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->set('file', 'README.md')
        ->set('isStaged', false)
        ->dispatch('status-updated')
        ->assertSet('file', null);
});

test('search panel clears results and updates repo path when repo-switched event is received', function () {
    Livewire::test(SearchPanel::class)
        ->set('results', [['type' => 'commit', 'sha' => 'abc123', 'message' => 'test commit']])
        ->set('query', 'test query')
        ->dispatch('repo-switched', path: '/new/repo/path')
        ->assertSet('results', [])
        ->assertSet('query', '')
        ->assertSet('selectedIndex', 0)
        ->assertSet('repoPath', '/new/repo/path');
});

test('auto fetch indicator responds to settings-updated event without error', function () {
    Process::fake();

    Livewire::test(AutoFetchIndicator::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('settings-updated')
        ->assertSet('repoPath', $this->testRepoPath);
});
