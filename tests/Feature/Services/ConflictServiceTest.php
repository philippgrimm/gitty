<?php

declare(strict_types=1);

use App\DTOs\ConflictFile;
use App\Services\Git\ConflictService;
use Illuminate\Support\Facades\Process;

test('it validates repository path has .git directory', function () {
    expect(fn () => new ConflictService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it detects merge state when MERGE_HEAD exists', function () {
    $testRepo = '/tmp/gitty-test-repo';
    $mergeHeadPath = $testRepo.'/.git/MERGE_HEAD';

    if (! is_dir($testRepo.'/.git')) {
        mkdir($testRepo.'/.git', 0755, true);
    }

    file_put_contents($mergeHeadPath, 'abc123');

    $service = new ConflictService($testRepo);

    expect($service->isInMergeState())->toBeTrue();

    unlink($mergeHeadPath);
});

test('it detects no merge state when MERGE_HEAD does not exist', function () {
    $testRepo = '/tmp/gitty-test-repo';
    $mergeHeadPath = $testRepo.'/.git/MERGE_HEAD';

    if (file_exists($mergeHeadPath)) {
        unlink($mergeHeadPath);
    }

    $service = new ConflictService($testRepo);

    expect($service->isInMergeState())->toBeFalse();
});

test('it gets conflicted files from git status', function () {
    Process::fake([
        'git status --porcelain=v2' => "u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 file1.txt\nu AA N... 100644 100644 100644 000000 abc123 def456 ghi789 file2.php",
    ]);

    $service = new ConflictService('/tmp/gitty-test-repo');
    $files = $service->getConflictedFiles();

    expect($files)->toHaveCount(2)
        ->and($files->first()['path'])->toBe('file1.txt')
        ->and($files->first()['status'])->toBe('UU')
        ->and($files->last()['path'])->toBe('file2.php')
        ->and($files->last()['status'])->toBe('AA');
});

test('it resolves conflict by writing file and staging', function () {
    Process::fake();

    $testRepo = '/tmp/gitty-test-repo';
    $testFile = 'test.txt';
    $resolvedContent = 'resolved content';

    if (! is_dir($testRepo)) {
        mkdir($testRepo, 0755, true);
    }

    $service = new ConflictService($testRepo);
    $service->resolveConflict($testFile, $resolvedContent);

    expect(file_get_contents($testRepo.'/'.$testFile))->toBe($resolvedContent);

    Process::assertRan("git add \"{$testFile}\"");

    unlink($testRepo.'/'.$testFile);
});

test('it aborts merge', function () {
    Process::fake();

    $service = new ConflictService('/tmp/gitty-test-repo');
    $service->abortMerge();

    Process::assertRan('git merge --abort');
});

test('it gets merge head branch from MERGE_MSG', function () {
    $testRepo = '/tmp/gitty-test-repo';
    $mergeMsgPath = $testRepo.'/.git/MERGE_MSG';

    if (! is_dir($testRepo.'/.git')) {
        mkdir($testRepo.'/.git', 0755, true);
    }

    file_put_contents($mergeMsgPath, "Merge branch 'feature/test-branch'");

    $service = new ConflictService($testRepo);

    expect($service->getMergeHeadBranch())->toBe('feature/test-branch');

    unlink($mergeMsgPath);
});

test('it gets conflict versions for a file', function () {
    Process::fake([
        'git show :1:"test.txt" 2>/dev/null' => "base content\n",
        'git show :2:"test.txt" 2>/dev/null' => "ours content\n",
        'git show :3:"test.txt" 2>/dev/null' => "theirs content\n",
        'git diff --numstat HEAD -- "test.txt" 2>/dev/null' => Process::result(output: '1	1	test.txt', exitCode: 0),
        'git status --porcelain=v2' => 'u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 test.txt',
    ]);

    $service = new ConflictService('/tmp/gitty-test-repo');
    $conflictFile = $service->getConflictVersions('test.txt');

    expect($conflictFile)->toBeInstanceOf(ConflictFile::class)
        ->and($conflictFile->path)->toBe('test.txt')
        ->and($conflictFile->oursContent)->toBe("ours content\n")
        ->and($conflictFile->theirsContent)->toBe("theirs content\n")
        ->and($conflictFile->baseContent)->toBe("base content\n")
        ->and($conflictFile->isBinary)->toBeFalse();
});
