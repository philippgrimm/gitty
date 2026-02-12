<?php

declare(strict_types=1);

use App\DTOs\Branch;
use App\DTOs\MergeResult;
use App\Services\Git\BranchService;
use Illuminate\Support\Facades\Process;
use Tests\Mocks\GitOutputFixtures;

test('it validates repository path has .git directory', function () {
    expect(fn () => new BranchService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it lists all branches', function () {
    Process::fake([
        'git branch -a -vv' => GitOutputFixtures::branchListVerbose(),
    ]);

    $service = new BranchService('/tmp/gitty-test-repo');
    $branches = $service->branches();

    expect($branches)->toHaveCount(7)
        ->and($branches->first())->toBeInstanceOf(Branch::class)
        ->and($branches->first()->name)->toBe('main')
        ->and($branches->first()->isCurrent)->toBeTrue();
});

test('it switches to a branch', function () {
    Process::fake();

    $service = new BranchService('/tmp/gitty-test-repo');
    $service->switchBranch('feature/new-ui');

    Process::assertRan('git checkout feature/new-ui');
});

test('it creates a new branch', function () {
    Process::fake();

    $service = new BranchService('/tmp/gitty-test-repo');
    $service->createBranch('feature/test', 'main');

    Process::assertRan('git checkout -b feature/test main');
});

test('it deletes a branch', function () {
    Process::fake();

    $service = new BranchService('/tmp/gitty-test-repo');
    $service->deleteBranch('feature/old', false);

    Process::assertRan('git branch -d feature/old');
});

test('it force deletes a branch', function () {
    Process::fake();

    $service = new BranchService('/tmp/gitty-test-repo');
    $service->deleteBranch('feature/old', true);

    Process::assertRan('git branch -D feature/old');
});

test('it merges a branch', function () {
    Process::fake([
        'git merge feature/new-ui' => Process::result(
            output: 'Merge made by the \'recursive\' strategy.',
            exitCode: 0
        ),
    ]);

    $service = new BranchService('/tmp/gitty-test-repo');
    $result = $service->mergeBranch('feature/new-ui');

    expect($result)->toBeInstanceOf(MergeResult::class)
        ->and($result->success)->toBeTrue()
        ->and($result->hasConflicts)->toBeFalse();
});
