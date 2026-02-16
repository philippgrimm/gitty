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

test('stageFiles runs git add with multiple escaped paths', function () {
    Process::fake([
        'git add *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->stageFiles(['src/App.php', 'config/app.php', 'README.md']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git add')
            && str_contains($process->command, 'src/App.php')
            && str_contains($process->command, 'config/app.php')
            && str_contains($process->command, 'README.md');
    });
});

test('unstageFiles runs git reset HEAD with multiple escaped paths', function () {
    Process::fake([
        'git reset HEAD *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->unstageFiles(['src/App.php', 'config/app.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git reset HEAD')
            && str_contains($process->command, 'src/App.php')
            && str_contains($process->command, 'config/app.php');
    });
});

test('discardFiles runs git checkout with multiple escaped paths', function () {
    Process::fake([
        'git checkout -- *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->discardFiles(['src/App.php', 'README.md']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git checkout --')
            && str_contains($process->command, 'src/App.php')
            && str_contains($process->command, 'README.md');
    });
});

test('stageFiles with empty array throws InvalidArgumentException', function () {
    $service = new StagingService('/tmp/gitty-test-repo');

    expect(fn () => $service->stageFiles([]))
        ->toThrow(\InvalidArgumentException::class, 'Cannot stage empty file list');
});

test('unstageFiles with empty array throws InvalidArgumentException', function () {
    $service = new StagingService('/tmp/gitty-test-repo');

    expect(fn () => $service->unstageFiles([]))
        ->toThrow(\InvalidArgumentException::class, 'Cannot unstage empty file list');
});

test('discardFiles with empty array throws InvalidArgumentException', function () {
    $service = new StagingService('/tmp/gitty-test-repo');

    expect(fn () => $service->discardFiles([]))
        ->toThrow(\InvalidArgumentException::class, 'Cannot discard empty file list');
});

test('stageFiles escapes file paths with spaces', function () {
    Process::fake([
        'git add *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->stageFiles(['path with spaces/file.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, "'path with spaces/file.php'")
            || str_contains($process->command, '"path with spaces/file.php"');
    });
});

test('unstageFiles escapes file paths with spaces', function () {
    Process::fake([
        'git reset HEAD *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->unstageFiles(['path with spaces/file.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, "'path with spaces/file.php'")
            || str_contains($process->command, '"path with spaces/file.php"');
    });
});

test('discardFiles escapes file paths with spaces', function () {
    Process::fake([
        'git checkout -- *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->discardFiles(['path with spaces/file.php']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, "'path with spaces/file.php'")
            || str_contains($process->command, '"path with spaces/file.php"');
    });
});
