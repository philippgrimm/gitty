<?php

declare(strict_types=1);

use App\Services\Git\ResetService;
use Illuminate\Support\Facades\Process;

test('it validates repository path has .git directory', function () {
    expect(fn () => new ResetService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('resetSoft runs git reset --soft', function () {
    Process::fake();

    $service = new ResetService('/tmp/gitty-test-repo');
    $service->resetSoft('abc123');

    Process::assertRan('git reset --soft abc123');
});

test('resetMixed runs git reset without flags', function () {
    Process::fake();

    $service = new ResetService('/tmp/gitty-test-repo');
    $service->resetMixed('abc123');

    Process::assertRan('git reset abc123');
});

test('resetHard runs git reset --hard', function () {
    Process::fake();

    $service = new ResetService('/tmp/gitty-test-repo');
    $service->resetHard('abc123');

    Process::assertRan('git reset --hard abc123');
});

test('revertCommit runs git revert --no-edit', function () {
    Process::fake();

    $service = new ResetService('/tmp/gitty-test-repo');
    $service->revertCommit('abc123');

    Process::assertRan('git revert abc123 --no-edit');
});

test('revertCommit throws RuntimeException on conflict', function () {
    Process::fake([
        'git revert abc123 --no-edit' => Process::result(
            output: '',
            errorOutput: 'error: could not revert abc123... conflict in file.txt',
            exitCode: 1
        ),
    ]);

    $service = new ResetService('/tmp/gitty-test-repo');

    expect(fn () => $service->revertCommit('abc123'))
        ->toThrow(RuntimeException::class, 'Revert failed due to conflicts');
});

test('resetSoft throws RuntimeException on failure', function () {
    Process::fake([
        'git reset --soft abc123' => Process::result(
            output: '',
            errorOutput: 'fatal: ambiguous argument',
            exitCode: 1
        ),
    ]);

    $service = new ResetService('/tmp/gitty-test-repo');

    expect(fn () => $service->resetSoft('abc123'))
        ->toThrow(RuntimeException::class, 'Git reset --soft failed');
});

test('resetMixed throws RuntimeException on failure', function () {
    Process::fake([
        'git reset abc123' => Process::result(
            output: '',
            errorOutput: 'fatal: ambiguous argument',
            exitCode: 1
        ),
    ]);

    $service = new ResetService('/tmp/gitty-test-repo');

    expect(fn () => $service->resetMixed('abc123'))
        ->toThrow(RuntimeException::class, 'Git reset failed');
});

test('resetHard throws RuntimeException on failure', function () {
    Process::fake([
        'git reset --hard abc123' => Process::result(
            output: '',
            errorOutput: 'fatal: ambiguous argument',
            exitCode: 1
        ),
    ]);

    $service = new ResetService('/tmp/gitty-test-repo');

    expect(fn () => $service->resetHard('abc123'))
        ->toThrow(RuntimeException::class, 'Git reset --hard failed');
});
