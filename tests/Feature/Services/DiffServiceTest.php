<?php

declare(strict_types=1);

use App\DTOs\DiffFile;
use App\DTOs\DiffResult;
use App\DTOs\Hunk;
use App\Services\Git\DiffService;
use Illuminate\Support\Facades\Process;
use Tests\Mocks\GitOutputFixtures;

test('it validates repository path has .git directory', function () {
    expect(fn () => new DiffService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it parses unified diff format', function () {
    $service = new DiffService('/tmp/gitty-test-repo');
    $diff = $service->parseDiff(GitOutputFixtures::diffUnstaged());

    expect($diff)->toBeInstanceOf(DiffResult::class)
        ->and($diff->files)->toHaveCount(1)
        ->and($diff->files->first())->toBeInstanceOf(DiffFile::class)
        ->and($diff->files->first()->getDisplayPath())->toBe('README.md')
        ->and($diff->files->first()->additions)->toBe(3)
        ->and($diff->files->first()->deletions)->toBe(1);
});

test('it extracts hunks from diff file', function () {
    $service = new DiffService('/tmp/gitty-test-repo');
    $diff = $service->parseDiff(GitOutputFixtures::diffUnstaged());
    $file = $diff->files->first();

    $hunks = $service->extractHunks($file);

    expect($hunks)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($hunks)->toHaveCount(1)
        ->and($hunks->first())->toBeInstanceOf(Hunk::class)
        ->and($hunks->first()->oldStart)->toBe(1)
        ->and($hunks->first()->newStart)->toBe(1);
});

test('it renders diff as HTML with syntax highlighting', function () {
    $service = new DiffService('/tmp/gitty-test-repo');
    $diff = $service->parseDiff(GitOutputFixtures::diffUnstaged());

    $html = $service->renderDiffHtml($diff);

    expect($html)->toBeString()
        ->and($html)->toContain('README.md');
});

test('it stages a hunk', function () {
    Process::fake();

    $service = new DiffService('/tmp/gitty-test-repo');
    $diff = $service->parseDiff(GitOutputFixtures::diffUnstaged());
    $file = $diff->files->first();
    $hunk = $file->hunks->first();

    $service->stageHunk($file, $hunk);

    Process::assertRan(fn ($process) => str_contains($process->command, 'git apply --cached'));
});

test('it unstages a hunk', function () {
    Process::fake();

    $service = new DiffService('/tmp/gitty-test-repo');
    $diff = $service->parseDiff(GitOutputFixtures::diffStaged());
    $file = $diff->files->first();
    $hunk = $file->hunks->first();

    $service->unstageHunk($file, $hunk);

    Process::assertRan(fn ($process) => str_contains($process->command, 'git apply --cached --reverse'));
});
