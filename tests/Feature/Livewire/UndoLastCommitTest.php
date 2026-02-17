<?php

declare(strict_types=1);

use App\Livewire\CommandPalette;
use App\Livewire\CommitPanel;
use App\Services\Git\CommitService;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('undoLastCommit runs git reset --soft HEAD~1', function () {
    Process::fake([
        'git reset --soft HEAD~1' => Process::result(''),
    ]);

    $service = new CommitService($this->testRepoPath);
    $service->undoLastCommit();

    Process::assertRan('git reset --soft HEAD~1');
});

test('undoLastCommit throws on failure', function () {
    Process::fake([
        'git reset --soft HEAD~1' => Process::result('error: reset failed', exitCode: 1),
    ]);

    $service = new CommitService($this->testRepoPath);
    $service->undoLastCommit();
})->throws(\RuntimeException::class);

test('isLastCommitMerge detects merge commits', function () {
    Process::fake([
        'git rev-parse HEAD^2 2>/dev/null' => Process::result('abc123'),
    ]);

    $service = new CommitService($this->testRepoPath);

    expect($service->isLastCommitMerge())->toBeTrue();
});

test('isLastCommitMerge returns false for regular commits', function () {
    Process::fake([
        'git rev-parse HEAD^2 2>/dev/null' => Process::result('', exitCode: 128),
    ]);

    $service = new CommitService($this->testRepoPath);

    expect($service->isLastCommitMerge())->toBeFalse();
});

test('promptUndoLastCommit blocks merge commits', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git rev-parse HEAD^2 2>/dev/null' => Process::result('abc123'),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('promptUndoLastCommit')
        ->assertSet('showUndoConfirmation', false)
        ->assertDispatched('show-error');
});

test('promptUndoLastCommit shows confirmation for regular commits', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git rev-parse HEAD^2 2>/dev/null' => Process::result('', exitCode: 128),
        'git rev-list --left-right --count *' => Process::result("0\t1\n"),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('promptUndoLastCommit')
        ->assertSet('showUndoConfirmation', true);
});

test('confirmUndoLastCommit executes undo and dispatches events', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git reset --soft HEAD~1' => Process::result(''),
        'git log -1 --pretty=%B' => Process::result("feat: previous commit\n"),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('showUndoConfirmation', true)
        ->call('confirmUndoLastCommit')
        ->assertSet('showUndoConfirmation', false)
        ->assertSet('message', 'feat: previous commit')
        ->assertDispatched('status-updated');
});

test('command palette has undo last commit entry', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    Livewire::test(CommandPalette::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('Undo Last Commit');
});
