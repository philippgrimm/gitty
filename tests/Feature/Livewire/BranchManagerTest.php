<?php

declare(strict_types=1);

use App\Livewire\BranchManager;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath . '/.git')) {
        mkdir($this->testRepoPath . '/.git', 0755, true);
    }
});

test('component mounts with repo path and loads branches', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusAheadBehind()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('currentBranch', 'feature/updates')
        ->assertSet('isDetachedHead', false)
        ->assertSee('feature/updates')
        ->assertSee('main')
        ->assertSee('feature/new-ui');
});

test('component displays current branch with ahead/behind badges', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusAheadBehind()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    $component = Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('aheadBehind'))->toBe(['ahead' => 3, 'behind' => 2]);
    $component->assertSee('↑3')->assertSee('↓2');
});

test('component switches to another branch', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusAheadBehind()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git checkout main' => Process::result(''),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('switchBranch', 'main')
        ->assertDispatched('status-updated');

    Process::assertRan('git checkout main');
});

test('component creates new branch', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git checkout -b feature/new-feature main' => Process::result(''),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->set('newBranchName', 'feature/new-feature')
        ->set('baseBranch', 'main')
        ->call('createBranch')
        ->assertSet('showCreateModal', false)
        ->assertSet('newBranchName', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git checkout -b feature/new-feature main');
});

test('component deletes branch', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git branch -d feature/new-ui' => Process::result(''),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('deleteBranch', 'feature/new-ui')
        ->assertDispatched('status-updated');

    Process::assertRan('git branch -d feature/new-ui');
});

test('component prevents deleting current branch', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('deleteBranch', 'main')
        ->assertSet('error', 'Cannot delete the current branch');

    Process::assertNotRan('git branch -d main');
});

test('component merges branch successfully', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git merge feature/new-ui' => Process::result('Fast-forward merge completed'),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('mergeBranch', 'feature/new-ui')
        ->assertDispatched('status-updated')
        ->assertSet('error', '');

    Process::assertRan('git merge feature/new-ui');
});

test('component shows conflict warning when merge has conflicts', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git merge feature/new-ui' => function () {
            return Process::result('CONFLICT (content): Merge conflict in README.md', exitCode: 1);
        },
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('mergeBranch', 'feature/new-ui')
        ->assertSet('error', 'Merge conflicts detected in: README.md')
        ->assertDispatched('status-updated');
});

test('component shows detached HEAD warning', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusDetachedHead()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('isDetachedHead', true)
        ->assertSee('HEAD detached')
        ->assertSee('Create branch here');
});

test('component refreshes branches on demand', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshBranches')
        ->assertSee('main');

    Process::assertRan('git branch -a -vv');
});
