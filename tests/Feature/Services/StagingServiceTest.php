<?php

declare(strict_types=1);

use App\Services\Git\StagingService;
use Illuminate\Support\Facades\Process;

test('it validates repository path has .git directory', function () {
    expect(fn () => new StagingService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it stages a single file', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->stageFile('README.md');

    Process::assertRan('git add README.md');
});

test('it unstages a single file', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->unstageFile('README.md');

    Process::assertRan('git reset HEAD README.md');
});

test('it stages all files', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->stageAll();

    Process::assertRan('git add .');
});

test('it unstages all files', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->unstageAll();

    Process::assertRan('git reset HEAD');
});

test('it discards changes to a single file', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->discardFile('README.md');

    Process::assertRan('git checkout -- README.md');
});

test('it discards all changes', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->discardAll();

    Process::assertRan('git checkout -- .');
});
