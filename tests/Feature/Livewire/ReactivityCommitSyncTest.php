<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use App\Livewire\SyncPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('commit panel dispatches status-updated with aheadBehind data after commit', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m *' => Process::result(''),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['aheadBehind']);
        });
});

test('commit panel dispatches status-updated with aheadBehind data after commitAndPush', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m *' => Process::result(''),
        'git push' => Process::result(''),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commitAndPush')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['aheadBehind']);
        });
});

test('sync panel handleCommitted listener refreshes aheadBehind when committed event fires', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0])
        ->dispatch('committed')
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0]);

    Process::assertRan('git status --porcelain=v2 --branch', 2);
});

test('sync panel refreshAheadBehind falls back to refreshAheadBehindData when empty aheadBehind is passed', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0])
        ->call('refreshAheadBehind', stagedCount: 0, aheadBehind: [])
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0]);

    Process::assertRan('git status --porcelain=v2 --branch', 2);
});

test('commit panel dispatches committed event after successful commit', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m *' => Process::result(''),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertDispatched('committed');
});
