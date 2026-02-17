<?php

declare(strict_types=1);

use App\DTOs\Commit;
use App\DTOs\DiffResult;
use App\DTOs\GitStatus;
use App\Services\Git\GitService;
use Illuminate\Support\Facades\Process;
use Tests\Mocks\GitOutputFixtures;

test('it validates repository path has .git directory', function () {
    expect(fn () => new GitService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it parses porcelain v2 status with clean working tree', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusClean(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $status = $service->status();

    expect($status)->toBeInstanceOf(GitStatus::class)
        ->and($status->branch)->toBe('main')
        ->and($status->upstream)->toBe('origin/main')
        ->and($status->aheadBehind)->toBe(['ahead' => 0, 'behind' => 0])
        ->and($status->changedFiles)->toHaveCount(0);
});

test('it parses porcelain v2 status with unstaged changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusWithUnstagedChanges(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $status = $service->status();

    expect($status->changedFiles)->toHaveCount(2)
        ->and($status->changedFiles->first()['path'])->toBe('README.md')
        ->and($status->changedFiles->first()['indexStatus'])->toBe('.')
        ->and($status->changedFiles->first()['worktreeStatus'])->toBe('M');
});

test('it parses porcelain v2 status with staged changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusWithStagedChanges(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $status = $service->status();

    expect($status->changedFiles)->toHaveCount(2)
        ->and($status->changedFiles->first()['indexStatus'])->toBe('M')
        ->and($status->changedFiles->first()['worktreeStatus'])->toBe('.')
        ->and($status->changedFiles->last()['indexStatus'])->toBe('A');
});

test('it parses porcelain v2 status with renamed files', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusWithRenamedFiles(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $status = $service->status();

    expect($status->changedFiles)->toHaveCount(1)
        ->and($status->changedFiles->first()['indexStatus'])->toBe('R')
        ->and($status->changedFiles->first()['path'])->toBe('new-name.txt')
        ->and($status->changedFiles->first()['oldPath'])->toBe('old-name.txt');
});

test('it detects detached HEAD state', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusDetachedHead(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');

    expect($service->isDetachedHead())->toBeTrue()
        ->and($service->currentBranch())->toBe('(detached)');
});

test('it calculates ahead/behind commits', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusAheadBehind(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $aheadBehind = $service->aheadBehind();

    expect($aheadBehind)->toBe(['ahead' => 3, 'behind' => 2]);
});

test('it parses git log output', function () {
    Process::fake([
        'git log --oneline -n 10' => GitOutputFixtures::logOneline(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $commits = $service->log(10);

    expect($commits)->toHaveCount(6)
        ->and($commits->first())->toBeInstanceOf(Commit::class)
        ->and($commits->first()->shortSha)->toBe('a1b2c3d')
        ->and($commits->first()->message)->toBe('feat: add new feature');
});

test('it parses diff output', function () {
    Process::fake([
        'git diff' => GitOutputFixtures::diffUnstaged(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $diff = $service->diff();

    expect($diff)->toBeInstanceOf(DiffResult::class)
        ->and($diff->files)->toHaveCount(1)
        ->and($diff->files->first()->getDisplayPath())->toBe('README.md')
        ->and($diff->files->first()->additions)->toBe(3)
        ->and($diff->files->first()->deletions)->toBe(1);
});

test('it loads diff for untracked file', function () {
    Process::fake([
        'git diff -- new-file.txt' => '',
        'git status --porcelain=v2 -- new-file.txt' => GitOutputFixtures::statusWithSingleUntrackedFile(),
        'git diff --no-index -- /dev/null new-file.txt' => GitOutputFixtures::diffUntracked(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $diff = $service->diff('new-file.txt');

    expect($diff)->toBeInstanceOf(DiffResult::class)
        ->and($diff->files)->toHaveCount(1)
        ->and($diff->files->first()->status)->toBe('added')
        ->and($diff->files->first()->getDisplayPath())->toBe('new-file.txt')
        ->and($diff->files->first()->additions)->toBe(2)
        ->and($diff->files->first()->deletions)->toBe(0);
});
