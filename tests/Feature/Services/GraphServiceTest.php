<?php

declare(strict_types=1);

use App\Services\Git\GraphService;
use Illuminate\Support\Facades\Process;

function historyCommand(int $limit, int $skip, string $scope = 'current'): string
{
    $command = "git log --graph --date-order --decorate=short --format='%x1e%H%x1f%P%x1f%an%x1f%ar%x1f%s%x1f%D' -n {$limit} --skip {$skip}";

    if ($scope === 'all') {
        $command .= ' --all';
    }

    return $command;
}

function historyLine(string $graphPrefix, string $sha, string $parents, string $author, string $date, string $message, string $refs = ''): string
{
    return $graphPrefix."\x1e{$sha}\x1f{$parents}\x1f{$author}\x1f{$date}\x1f{$message}\x1f{$refs}";
}

beforeEach(function () {
    $this->testRepoPath = '/tmp/test-repo-'.uniqid();
    mkdir($this->testRepoPath);
    mkdir($this->testRepoPath.'/.git');
});

afterEach(function () {
    if (is_dir($this->testRepoPath)) {
        exec("rm -rf {$this->testRepoPath}");
    }
});

test('constructor validates git repository', function () {
    $invalidPath = '/tmp/not-a-repo-'.uniqid();
    mkdir($invalidPath);

    expect(fn () => new GraphService($invalidPath))
        ->toThrow(\InvalidArgumentException::class, 'Not a valid git repository');

    rmdir($invalidPath);
});

test('getHistoryRows returns empty array for empty repository', function () {
    Process::fake([
        historyCommand(100, 0) => Process::result(output: ''),
    ]);

    $service = new GraphService($this->testRepoPath);
    $rows = $service->getHistoryRows(limit: 100, skip: 0);

    expect($rows)->toBeArray()
        ->and($rows)->toBeEmpty();
});

test('getHistoryRows returns empty array on git error', function () {
    Process::fake([
        historyCommand(100, 0) => Process::result(exitCode: 1, errorOutput: 'fatal: bad revision'),
    ]);

    $service = new GraphService($this->testRepoPath);
    $rows = $service->getHistoryRows(limit: 100, skip: 0);

    expect($rows)->toBeArray()
        ->and($rows)->toBeEmpty();
});

test('getHistoryRows parses linear history graph cells and metadata', function () {
    $output = implode("\n", [
        historyLine('* ', 'a1', 'b1', 'John Doe', '2 hours ago', 'Third commit', 'HEAD -> main, origin/main'),
        historyLine('* ', 'b1', 'c1', 'John Doe', '3 hours ago', 'Second commit'),
        historyLine('* ', 'c1', '', 'John Doe', '4 hours ago', 'Initial commit'),
    ]);

    Process::fake([
        historyCommand(100, 0) => Process::result(output: $output),
    ]);

    $service = new GraphService($this->testRepoPath);
    $rows = $service->getHistoryRows(limit: 100, skip: 0);

    expect($rows)->toHaveCount(3)
        ->and($rows[0]->sha)->toBe('a1')
        ->and($rows[0]->parents)->toBe(['b1'])
        ->and($rows[0]->refs)->toBe(['HEAD -> main', 'origin/main'])
        ->and($rows[0]->graphCells)->toBe(['*'])
        ->and($rows[0]->continuationCells)->toBe([])
        ->and($rows[2]->parents)->toBe([]);
});

test('getHistoryRows parses merge graph with continuation lines', function () {
    $output = implode("\n", [
        historyLine('*   ', 'm1', 'p1 p2', 'John Doe', '1 hour ago', 'Merge branch feature', 'HEAD -> main'),
        '|\\  ',
        historyLine('* | ', 'p1', 'b0', 'John Doe', '2 hours ago', 'Main commit'),
        historyLine('| * ', 'p2', 'b0', 'Jane Doe', '3 hours ago', 'Feature commit', 'feature'),
        '|/  ',
        historyLine('* ', 'b0', '', 'John Doe', '4 hours ago', 'Initial commit'),
    ]);

    Process::fake([
        historyCommand(100, 0) => Process::result(output: $output),
    ]);

    $service = new GraphService($this->testRepoPath);
    $rows = $service->getHistoryRows(limit: 100, skip: 0);

    expect($rows)->toHaveCount(4)
        ->and($rows[0]->sha)->toBe('m1')
        ->and($rows[0]->parents)->toBe(['p1', 'p2'])
        ->and($rows[0]->continuationCells)->toHaveCount(1)
        ->and($rows[0]->continuationCells[0])->toBe(['|', '\\'])
        ->and($rows[2]->graphCells)->toBe(['|', ' ', '*'])
        ->and($rows[2]->refs)->toBe(['feature'])
        ->and($rows[2]->continuationCells)->toHaveCount(1)
        ->and($rows[2]->continuationCells[0])->toBe(['|', '/']);
});

test('getHistoryRows parses octopus merge refs and parent list', function () {
    $output = implode("\n", [
        historyLine('*   ', 'o1', 'p1 p2 p3', 'John Doe', 'now', 'Octopus merge', 'HEAD -> main, tag: v2.0.0, origin/main'),
        historyLine('*   ', 'p1', 'b0', 'John Doe', '1 minute ago', 'Base main'),
    ]);

    Process::fake([
        historyCommand(100, 0) => Process::result(output: $output),
    ]);

    $service = new GraphService($this->testRepoPath);
    $rows = $service->getHistoryRows(limit: 100, skip: 0);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->parents)->toBe(['p1', 'p2', 'p3'])
        ->and($rows[0]->refs)->toBe(['HEAD -> main', 'tag: v2.0.0', 'origin/main']);
});

test('getHistoryRows supports all scope and skip values', function () {
    Process::fake([
        historyCommand(50, 20, 'all') => Process::result(output: historyLine('* ', 'a1', '', 'John Doe', 'now', 'Commit')),
    ]);

    $service = new GraphService($this->testRepoPath);
    $rows = $service->getHistoryRows(limit: 50, skip: 20, scope: 'all');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->sha)->toBe('a1');

    Process::assertRan(historyCommand(50, 20, 'all'));
});

test('getGraphData remains compatible with new history parser', function () {
    Process::fake([
        historyCommand(200, 0, 'all') => Process::result(output: historyLine('* ', 'a1', 'b1', 'John Doe', 'now', 'Commit', 'HEAD -> main')),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData(200);

    expect($graphData)->toHaveCount(1)
        ->and($graphData[0]->sha)->toBe('a1')
        ->and($graphData[0]->parents)->toBe(['b1'])
        ->and($graphData[0]->branch)->toBe('main')
        ->and($graphData[0]->lane)->toBe(0);
});
