<?php

declare(strict_types=1);

use App\DTOs\DiffFile;
use App\DTOs\Hunk;
use App\DTOs\HunkLine;
use App\Services\Git\DiffService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    // Create a temporary git repository for testing
    $this->testRepo = '/tmp/gitty-test-repo-'.uniqid();
    mkdir($this->testRepo);
    Process::path($this->testRepo)->run('git init');
    Process::path($this->testRepo)->run('git config user.email "test@example.com"');
    Process::path($this->testRepo)->run('git config user.name "Test User"');
});

afterEach(function () {
    // Clean up test repository
    if (is_dir($this->testRepo)) {
        Process::run("rm -rf {$this->testRepo}");
    }
});

test('generateLinePatch creates valid patch for selected additions only', function () {
    $service = new DiffService($this->testRepo);

    // Create a hunk with mixed lines
    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('addition', 'line 2 added', null, 2),
        new HunkLine('addition', 'line 3 added', null, 3),
        new HunkLine('context', 'line 4', 2, 4),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 2,
        newStart: 1,
        newCount: 4,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 2,
        deletions: 0
    );

    // Select only the first addition (index 1)
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateLinePatch');
    $method->setAccessible(true);
    $patch = $method->invoke($service, $file, $hunk, [1]);

    expect($patch)->toContain('--- a/test.txt')
        ->and($patch)->toContain('+++ b/test.txt')
        ->and($patch)->toContain('+line 2 added')
        ->and($patch)->toContain(' line 3 added') // Unselected addition becomes context
        ->and($patch)->toContain(' line 1')
        ->and($patch)->toContain(' line 4');
});

test('generateLinePatch handles selected deletions only', function () {
    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('deletion', 'line 2 deleted', 2, null),
        new HunkLine('deletion', 'line 3 deleted', 3, null),
        new HunkLine('context', 'line 4', 4, 2),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 4,
        newStart: 1,
        newCount: 2,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 0,
        deletions: 2
    );

    // Select only the first deletion (index 1)
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateLinePatch');
    $method->setAccessible(true);
    $patch = $method->invoke($service, $file, $hunk, [1]);

    expect($patch)->toContain('-line 2 deleted')
        ->and($patch)->not->toContain('-line 3 deleted') // Unselected deletion omitted
        ->and($patch)->toContain(' line 1')
        ->and($patch)->toContain(' line 4');
});

test('generateLinePatch handles mixed selections', function () {
    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('deletion', 'line 2 deleted', 2, null),
        new HunkLine('addition', 'line 2 added', null, 2),
        new HunkLine('addition', 'line 3 added', null, 3),
        new HunkLine('context', 'line 4', 3, 4),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 3,
        newStart: 1,
        newCount: 4,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 2,
        deletions: 1
    );

    // Select deletion (index 1) and first addition (index 2)
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateLinePatch');
    $method->setAccessible(true);
    $patch = $method->invoke($service, $file, $hunk, [1, 2]);

    expect($patch)->toContain('-line 2 deleted')
        ->and($patch)->toContain('+line 2 added')
        ->and($patch)->toContain(' line 3 added') // Unselected addition becomes context
        ->and($patch)->not->toContain('+line 3 added'); // Should be context, not addition
});

test('stageLines stages only selected lines', function () {
    Process::fake();

    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('addition', 'line 2 added', null, 2),
        new HunkLine('addition', 'line 3 added', null, 3),
        new HunkLine('context', 'line 4', 2, 4),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 2,
        newStart: 1,
        newCount: 4,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 2,
        deletions: 0
    );

    $service->stageLines($file, $hunk, [1]);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git apply --cached --unidiff-zero -');
    });
});

test('unstageLines unstages only selected lines', function () {
    Process::fake();

    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('addition', 'line 2 added', null, 2),
        new HunkLine('addition', 'line 3 added', null, 3),
        new HunkLine('context', 'line 4', 2, 4),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 2,
        newStart: 1,
        newCount: 4,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 2,
        deletions: 0
    );

    $service->unstageLines($file, $hunk, [1]);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git apply --cached --unidiff-zero --reverse -');
    });
});

test('unselected additions become context in patch', function () {
    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('addition', 'line 1 added', null, 1),
        new HunkLine('addition', 'line 2 added', null, 2),
        new HunkLine('addition', 'line 3 added', null, 3),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 0,
        newStart: 1,
        newCount: 3,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 3,
        deletions: 0
    );

    // Select only middle line (index 1)
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateLinePatch');
    $method->setAccessible(true);
    $patch = $method->invoke($service, $file, $hunk, [1]);

    // Count occurrences of each type
    $additionCount = substr_count($patch, "\n+line");
    $contextCount = substr_count($patch, "\n line");

    expect($additionCount)->toBe(1) // Only selected line
        ->and($contextCount)->toBe(2) // Two unselected additions become context
        ->and($patch)->toContain('+line 2 added')
        ->and($patch)->toContain(' line 1 added')
        ->and($patch)->toContain(' line 3 added');
});

test('generateLinePatch recalculates line counts correctly', function () {
    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('deletion', 'line 2 deleted', 2, null),
        new HunkLine('addition', 'line 2 added', null, 2),
        new HunkLine('context', 'line 3', 3, 3),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 3,
        newStart: 1,
        newCount: 3,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 1,
        deletions: 1
    );

    // Select only the addition (index 2)
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateLinePatch');
    $method->setAccessible(true);
    $patch = $method->invoke($service, $file, $hunk, [2]);

    // Should have @@ -1,2 +1,3 @@ (2 old lines: context + unselected deletion becomes omitted, 3 new lines: 2 context + 1 addition)
    // Actually: oldCount = context lines (2), newCount = context lines (2) + selected additions (1) = 3
    expect($patch)->toContain('@@ -1,2 +1,3 @@');
});

test('context lines are never selectable', function () {
    $service = new DiffService($this->testRepo);

    $lines = collect([
        new HunkLine('context', 'line 1', 1, 1),
        new HunkLine('context', 'line 2', 2, 2),
        new HunkLine('addition', 'line 3 added', null, 3),
    ]);

    $hunk = new Hunk(
        oldStart: 1,
        oldCount: 2,
        newStart: 1,
        newCount: 3,
        header: '',
        lines: $lines
    );

    $file = new DiffFile(
        oldPath: 'test.txt',
        newPath: 'test.txt',
        status: 'M',
        isBinary: false,
        hunks: collect([$hunk]),
        additions: 1,
        deletions: 0
    );

    // Try to select context lines (indices 0, 1) - they should be included regardless
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateLinePatch');
    $method->setAccessible(true);
    $patch = $method->invoke($service, $file, $hunk, [0, 1]);

    // Context lines should always be present
    expect($patch)->toContain(' line 1')
        ->and($patch)->toContain(' line 2')
        // Addition should become context since it's not selected
        ->and($patch)->toContain(' line 3 added');
});
