<?php

declare(strict_types=1);

use App\Livewire\BranchManager;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and loads branches', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusAheadBehind()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusAheadBehind()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    $component = Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('aheadBehind'))->toBe(['ahead' => 3, 'behind' => 2]);
    $component->assertSee('↑3')->assertSee('↓2');
});

test('component switches to another branch', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusAheadBehind()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('deleteBranch', 'main')
        ->assertSet('error', 'Cannot delete the current branch');

    Process::assertNotRan('git branch -d main');
});

test('component merges branch successfully', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusDetachedHead()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('isDetachedHead', true)
        ->assertSee('HEAD detached')
        ->assertSee('Create branch here');
});

test('component refreshes branches on demand', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshBranches')
        ->assertSee('main');

    Process::assertRan('git branch -a -vv');
});

test('context menu trigger exists on non-current local branches', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->assertSeeHtml('x-on:contextmenu');
});

test('context menu contains merge action for current branch', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('Merge into main');
});

test('component dispatches success toast on successful merge', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git merge feature/new-ui' => Process::result('Fast-forward merge completed'),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('mergeBranch', 'feature/new-ui')
        ->assertDispatched('show-error', function (string $event, array $params): bool {
            return $params['type'] === 'success'
                && str_contains($params['message'], 'feature/new-ui')
                && str_contains($params['message'], 'main');
        });
});

test('component does not dispatch success toast when merge has conflicts', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git merge feature/new-ui' => function () {
            return Process::result('CONFLICT (content): Merge conflict in README.md', exitCode: 1);
        },
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('mergeBranch', 'feature/new-ui')
        ->assertNotDispatched('show-error', function (string $event, array $params): bool {
            return $params['type'] === 'success';
        });
});

test('switchBranch shows auto-stash modal when checkout fails due to dirty tree', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git checkout feature/new-ui' => function () {
            return Process::result(
                output: '',
                errorOutput: "error: Your local changes to the following files would be overwritten by checkout\nPlease commit your changes or stash them before you switch branches.",
                exitCode: 1
            );
        },
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('switchBranch', 'feature/new-ui')
        ->assertSet('showAutoStashModal', true)
        ->assertSet('autoStashTargetBranch', 'feature/new-ui')
        ->assertNotDispatched('show-error');
});

test('switchBranch shows error toast for non-dirty-tree errors', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git checkout feature/nonexistent' => function () {
            return Process::result(
                output: '',
                errorOutput: "pathspec 'feature/nonexistent' did not match any file(s) known to git",
                exitCode: 1
            );
        },
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->call('switchBranch', 'feature/nonexistent')
        ->assertSet('showAutoStashModal', false)
        ->assertDispatched('show-error');
});

test('confirmAutoStash stashes switches and restores changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git stash push *' => Process::result('Saved working directory and index state'),
        'git checkout feature/new-ui' => Process::result('Switched to branch \'feature/new-ui\''),
        'git stash apply stash@{0}' => Process::result('On branch feature/new-ui\nChanges not staged for commit:\n  modified:   README.md'),
        'git stash drop stash@{0}' => Process::result('Dropped stash@{0}'),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->set('autoStashTargetBranch', 'feature/new-ui')
        ->set('showAutoStashModal', true)
        ->call('confirmAutoStash')
        ->assertSet('showAutoStashModal', false)
        ->assertDispatched('status-updated')
        ->assertDispatched('show-error', function (string $event, array $params): bool {
            return $params['type'] === 'success'
                && str_contains($params['message'], 'feature/new-ui')
                && str_contains($params['message'], 'changes restored');
        });

    Process::assertRan(fn ($process) => str_contains($process->command, 'git stash push'));
    Process::assertRan('git checkout feature/new-ui');
    Process::assertRan('git stash apply stash@{0}');
    Process::assertRan('git stash drop stash@{0}');
});

test('confirmAutoStash shows warning when stash apply conflicts', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git stash push *' => Process::result('Saved working directory and index state'),
        'git checkout feature/new-ui' => Process::result('Switched to branch \'feature/new-ui\''),
        'git stash apply stash@{0}' => function () {
            return Process::result(
                output: 'CONFLICT (content): Merge conflict in file.txt',
                errorOutput: '',
                exitCode: 1
            );
        },
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->set('autoStashTargetBranch', 'feature/new-ui')
        ->set('showAutoStashModal', true)
        ->call('confirmAutoStash')
        ->assertDispatched('show-error', function (string $event, array $params): bool {
            return $params['type'] === 'warning'
                && $params['persistent'] === true
                && str_contains($params['message'], 'conflicted')
                && str_contains($params['message'], 'stash preserved');
        });

    Process::assertNotRan('git stash drop stash@{0}');
});

test('cancelAutoStash resets state without action', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
    ]);

    Livewire::test(BranchManager::class, ['repoPath' => $this->testRepoPath])
        ->set('showAutoStashModal', true)
        ->set('autoStashTargetBranch', 'feature/new-ui')
        ->call('cancelAutoStash')
        ->assertSet('showAutoStashModal', false)
        ->assertSet('autoStashTargetBranch', '');
});
