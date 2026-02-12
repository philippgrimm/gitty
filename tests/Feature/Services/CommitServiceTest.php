<?php

declare(strict_types=1);

use App\Services\Git\CommitService;
use Illuminate\Support\Facades\Process;

test('it validates repository path has .git directory', function () {
    expect(fn () => new CommitService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it creates a commit with message', function () {
    Process::fake();

    $service = new CommitService('/tmp/gitty-test-repo');
    $service->commit('feat: add new feature');

    Process::assertRan('git commit -m "feat: add new feature"');
});

test('it amends the last commit', function () {
    Process::fake();

    $service = new CommitService('/tmp/gitty-test-repo');
    $service->commitAmend('feat: updated feature');

    Process::assertRan('git commit --amend -m "feat: updated feature"');
});

test('it commits and pushes', function () {
    Process::fake();

    $service = new CommitService('/tmp/gitty-test-repo');
    $service->commitAndPush('feat: add feature');

    Process::assertRan('git commit -m "feat: add feature"');
    Process::assertRan('git push');
});

test('it retrieves last commit message', function () {
    Process::fake([
        'git log -1 --pretty=%B' => 'feat: add new feature',
    ]);

    $service = new CommitService('/tmp/gitty-test-repo');
    $message = $service->lastCommitMessage();

    expect($message)->toBe('feat: add new feature');
});
