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

test('stashFiles runs correct git command with file paths', function () {
    Process::fake([
        'git stash push *' => Process::result(''),
        'git rev-parse --abbrev-ref HEAD' => Process::result("main\n"),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashFiles(['src/App.php', 'config/app.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git stash push')
            && str_contains($process->command, '-u')
            && str_contains($process->command, 'src/App.php')
            && str_contains($process->command, 'config/app.php');
    });
});

test('stashFiles auto-generates message with 3 or fewer files using basenames', function () {
    Process::fake([
        'git stash push *' => Process::result(''),
        'git rev-parse --abbrev-ref HEAD' => Process::result("main\n"),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashFiles(['src/App.php', 'config/app.php', 'README.md']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'Stash: App.php, app.php, README.md');
    });
});

test('stashFiles auto-generates message with more than 3 files using count and branch', function () {
    Process::fake([
        'git stash push *' => Process::result(''),
        'git rev-parse --abbrev-ref HEAD' => Process::result("feature/test\n"),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashFiles(['file1.php', 'file2.php', 'file3.php', 'file4.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'Stash: 4 files on feature/test');
    });
});

test('stashFiles always includes -u flag', function () {
    Process::fake([
        'git stash push *' => Process::result(''),
        'git rev-parse --abbrev-ref HEAD' => Process::result("main\n"),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashFiles(['src/App.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git stash push -u');
    });
});

test('stashFiles invalidates stashes and status cache groups', function () {
    Process::fake([
        'git stash push *' => Process::result(''),
        'git rev-parse --abbrev-ref HEAD' => Process::result("main\n"),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');

    // Pre-populate cache
    $service->stashList();

    $service->stashFiles(['src/App.php']);

    // Cache should be invalidated, so this should trigger a new git command
    Process::fake([
        'git stash list' => Process::result(''),
    ]);

    $service->stashList();
    Process::assertRan('git stash list');
});

test('stashFiles with empty array throws InvalidArgumentException', function () {
    $service = new StashService('/tmp/gitty-test-repo');

    expect(fn () => $service->stashFiles([]))
        ->toThrow(\InvalidArgumentException::class, 'Cannot stash empty file list');
});

test('stashFiles escapes file paths with spaces', function () {
    Process::fake([
        'git stash push *' => Process::result(''),
        'git rev-parse --abbrev-ref HEAD' => Process::result("main\n"),
    ]);

    $service = new StashService('/tmp/gitty-test-repo');
    $service->stashFiles(['path with spaces/file.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, "'path with spaces/file.php'")
            || str_contains($process->command, '"path with spaces/file.php"');
    });
});
