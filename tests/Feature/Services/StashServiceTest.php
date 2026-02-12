<?php

declare(strict_types=1);

use App\DTOs\Stash;
use App\Services\Git\StashService;
use Illuminate\Support\Facades\Process;
use Tests\Mocks\GitOutputFixtures;

test('it validates repository path has .git directory', function () {
    expect(fn () => new StashService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it creates a stash', function () {
    Process::fake();

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stash('WIP: testing feature', false);

    Process::assertRan('git stash push -m "WIP: testing feature"');
});

test('it creates a stash with untracked files', function () {
    Process::fake();

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stash('WIP: testing feature', true);

    Process::assertRan('git stash push -u -m "WIP: testing feature"');
});

test('it lists all stashes', function () {
    Process::fake([
        'git stash list' => GitOutputFixtures::stashList(),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');
    $stashes = $service->stashList();

    expect($stashes)->toHaveCount(3)
        ->and($stashes->first())->toBeInstanceOf(Stash::class)
        ->and($stashes->first()->index)->toBe(0)
        ->and($stashes->first()->branch)->toBe('main');
});

test('it applies a stash', function () {
    Process::fake();

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashApply(0);

    Process::assertRan('git stash apply stash@{0}');
});

test('it pops a stash', function () {
    Process::fake();

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashPop(0);

    Process::assertRan('git stash pop stash@{0}');
});

test('it drops a stash', function () {
    Process::fake();

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashDrop(1);

    Process::assertRan('git stash drop stash@{1}');
});
