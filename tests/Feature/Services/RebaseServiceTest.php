<?php

declare(strict_types=1);

use App\Services\Git\RebaseService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->testRepoPath = '/tmp/test-repo-'.uniqid();
    mkdir($this->testRepoPath);
    mkdir($this->testRepoPath.'/.git');
});

afterEach(function () {
    if (is_dir($this->testRepoPath)) {
        exec("rm -rf {$this->testRepoPath}");
    }
});

test('constructor validates git repository', function () {
    $invalidPath = '/tmp/not-a-repo-'.uniqid();
    mkdir($invalidPath);

    expect(fn () => new RebaseService($invalidPath))
        ->toThrow(\InvalidArgumentException::class, 'Not a valid git repository');

    rmdir($invalidPath);
});

test('isRebasing returns false when not rebasing', function () {
    $service = new RebaseService($this->testRepoPath);

    expect($service->isRebasing())->toBeFalse();
});

test('isRebasing returns true when rebase-merge directory exists', function () {
    mkdir($this->testRepoPath.'/.git/rebase-merge');
    $service = new RebaseService($this->testRepoPath);

    expect($service->isRebasing())->toBeTrue();
});

test('isRebasing returns true when rebase-apply directory exists', function () {
    mkdir($this->testRepoPath.'/.git/rebase-apply');
    $service = new RebaseService($this->testRepoPath);

    expect($service->isRebasing())->toBeTrue();
});

test('getRebaseCommits returns collection of commits', function () {
    Process::fake([
        'git log --oneline HEAD~5..HEAD' => Process::result(
            output: "abc1234 Commit 5\ndef5678 Commit 4\n123abcd Commit 3\n456efgh Commit 2\n789ijkl Commit 1"
        ),
    ]);

    $service = new RebaseService($this->testRepoPath);
    $commits = $service->getRebaseCommits('HEAD~5', 5);

    expect($commits)->toHaveCount(5)
        ->and($commits->first()['sha'])->toBe('789ijkl')
        ->and($commits->first()['message'])->toBe('Commit 1')
        ->and($commits->first()['action'])->toBe('pick')
        ->and($commits->last()['sha'])->toBe('abc1234')
        ->and($commits->last()['message'])->toBe('Commit 5');
});

test('getRebaseCommits throws exception on git error', function () {
    Process::fake([
        'git log --oneline HEAD~5..HEAD' => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: bad revision'
        ),
    ]);

    $service = new RebaseService($this->testRepoPath);

    expect(fn () => $service->getRebaseCommits('HEAD~5', 5))
        ->toThrow(\RuntimeException::class, 'Failed to get rebase commits');
});

test('startRebase executes git rebase with plan', function () {
    Process::fake([
        'git rebase -i *' => Process::result(),
    ]);

    $service = new RebaseService($this->testRepoPath);
    $plan = [
        ['sha' => 'abc1234', 'action' => 'pick'],
        ['sha' => 'def5678', 'action' => 'squash'],
        ['sha' => '123abcd', 'action' => 'drop'],
    ];

    $service->startRebase('HEAD~3', $plan);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git rebase -i HEAD~3');
    });
});

test('startRebase throws exception on conflict', function () {
    Process::fake([
        'git rebase -i *' => Process::result(
            exitCode: 1,
            errorOutput: 'CONFLICT (content): Merge conflict in file.txt'
        ),
    ]);

    $service = new RebaseService($this->testRepoPath);
    $plan = [['sha' => 'abc1234', 'action' => 'pick']];

    expect(fn () => $service->startRebase('HEAD~1', $plan))
        ->toThrow(\RuntimeException::class, 'Rebase failed due to conflicts');
});

test('continueRebase executes git rebase --continue', function () {
    Process::fake([
        'git rebase --continue' => Process::result(),
    ]);

    $service = new RebaseService($this->testRepoPath);
    $service->continueRebase();

    Process::assertRan('git rebase --continue');
});

test('continueRebase throws exception on error', function () {
    Process::fake([
        'git rebase --continue' => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: no rebase in progress'
        ),
    ]);

    $service = new RebaseService($this->testRepoPath);

    expect(fn () => $service->continueRebase())
        ->toThrow(\RuntimeException::class, 'Git rebase --continue failed');
});

test('abortRebase executes git rebase --abort', function () {
    Process::fake([
        'git rebase --abort' => Process::result(),
    ]);

    $service = new RebaseService($this->testRepoPath);
    $service->abortRebase();

    Process::assertRan('git rebase --abort');
});

test('abortRebase throws exception on error', function () {
    Process::fake([
        'git rebase --abort' => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: no rebase in progress'
        ),
    ]);

    $service = new RebaseService($this->testRepoPath);

    expect(fn () => $service->abortRebase())
        ->toThrow(\RuntimeException::class, 'Failed to abort rebase');
});
