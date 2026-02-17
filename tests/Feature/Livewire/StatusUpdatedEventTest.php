<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use App\Livewire\StagingPanel;
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

test('staging panel dispatches status-updated with payload after stageFile', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git add README.md' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('stageFile', 'README.md')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['stagedCount']) && isset($params['aheadBehind']);
        });
});

test('staging panel dispatches status-updated with payload after unstageFile', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git reset HEAD README.md' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('unstageFile', 'README.md')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['stagedCount']) && isset($params['aheadBehind']);
        });
});

test('staging panel dispatches status-updated with payload after stageAll', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git add .' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('stageAll')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['stagedCount']) && isset($params['aheadBehind']);
        });
});

test('staging panel dispatches status-updated with payload after unstageAll', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git reset HEAD' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('unstageAll')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['stagedCount']) && isset($params['aheadBehind']);
        });
});

test('commit panel receives staged count from event params', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('stagedCount', 2);

    // Simulate status-updated event with new staged count
    $component->call('refreshStagedCount', stagedCount: 7, aheadBehind: ['ahead' => 2, 'behind' => 1])
        ->assertSet('stagedCount', 7);
});

test('sync panel receives ahead-behind from event params', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    $component = Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0]);

    // Simulate status-updated event with new ahead-behind data
    $component->call('refreshAheadBehind', stagedCount: 3, aheadBehind: ['ahead' => 5, 'behind' => 2])
        ->assertSet('aheadBehind', ['ahead' => 5, 'behind' => 2]);
});

test('staging panel handles refresh-staging event', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('handleRefreshStaging')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['stagedCount']) && isset($params['aheadBehind']);
        });
});
