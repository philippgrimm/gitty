<?php

declare(strict_types=1);

use App\Services\Git\SearchService;
use Illuminate\Support\Facades\Process;

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

    expect(fn () => new SearchService($invalidPath))
        ->toThrow(\InvalidArgumentException::class, 'Not a valid git repository');

    rmdir($invalidPath);
});

test('searchCommits returns parsed results', function () {
    Process::fake([
        'git log --grep="feature" --format="%H|%h|%an|%ar|%s" -50' => Process::result(
            output: "1234567890abcdef1234567890abcdef12345678|1234567|John Doe|2 hours ago|Add feature X\nabcdef1234567890abcdef1234567890abcdef12|abcdef1|Jane Smith|1 day ago|Fix feature Y"
        ),
    ]);

    $service = new SearchService($this->testRepoPath);
    $results = $service->searchCommits('feature');

    expect($results)->toHaveCount(2)
        ->and($results->first()['sha'])->toBe('1234567890abcdef1234567890abcdef12345678')
        ->and($results->first()['shortSha'])->toBe('1234567')
        ->and($results->first()['author'])->toBe('John Doe')
        ->and($results->first()['date'])->toBe('2 hours ago')
        ->and($results->first()['message'])->toBe('Add feature X')
        ->and($results->last()['sha'])->toBe('abcdef1234567890abcdef1234567890abcdef12')
        ->and($results->last()['message'])->toBe('Fix feature Y');
});

test('searchCommits returns empty collection for empty query', function () {
    $service = new SearchService($this->testRepoPath);
    $results = $service->searchCommits('');

    expect($results)->toBeEmpty();
});

test('searchCommits throws exception on git error', function () {
    Process::fake([
        'git log --grep="test" --format="%H|%h|%an|%ar|%s" -50' => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: bad revision'
        ),
    ]);

    $service = new SearchService($this->testRepoPath);

    expect(fn () => $service->searchCommits('test'))
        ->toThrow(\RuntimeException::class, 'Git log --grep failed');
});

test('searchContent returns pickaxe results', function () {
    Process::fake([
        'git log -S "function" --format="%H|%h|%an|%ar|%s" -50' => Process::result(
            output: "fedcba0987654321fedcba0987654321fedcba09|fedcba0|Alice|3 days ago|Refactor function\n0987654321fedcba0987654321fedcba09876543|0987654|Bob|1 week ago|Add function tests"
        ),
    ]);

    $service = new SearchService($this->testRepoPath);
    $results = $service->searchContent('function');

    expect($results)->toHaveCount(2)
        ->and($results->first()['sha'])->toBe('fedcba0987654321fedcba0987654321fedcba09')
        ->and($results->first()['shortSha'])->toBe('fedcba0')
        ->and($results->first()['author'])->toBe('Alice')
        ->and($results->first()['message'])->toBe('Refactor function')
        ->and($results->last()['sha'])->toBe('0987654321fedcba0987654321fedcba09876543');
});

test('searchContent returns empty collection for empty query', function () {
    $service = new SearchService($this->testRepoPath);
    $results = $service->searchContent('');

    expect($results)->toBeEmpty();
});

test('searchContent throws exception on git error', function () {
    Process::fake([
        'git log -S "test" --format="%H|%h|%an|%ar|%s" -50' => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: invalid object'
        ),
    ]);

    $service = new SearchService($this->testRepoPath);

    expect(fn () => $service->searchContent('test'))
        ->toThrow(\RuntimeException::class, 'Git log -S failed');
});

test('searchFiles returns file paths', function () {
    Process::fake([
        'git ls-files "*component*"' => Process::result(
            output: "src/components/Button.php\nsrc/components/Modal.php\ntests/components/ButtonTest.php"
        ),
    ]);

    $service = new SearchService($this->testRepoPath);
    $results = $service->searchFiles('component');

    expect($results)->toHaveCount(3)
        ->and($results->first()['path'])->toBe('src/components/Button.php')
        ->and($results->get(1)['path'])->toBe('src/components/Modal.php')
        ->and($results->last()['path'])->toBe('tests/components/ButtonTest.php');
});

test('searchFiles returns empty collection for empty query', function () {
    $service = new SearchService($this->testRepoPath);
    $results = $service->searchFiles('');

    expect($results)->toBeEmpty();
});

test('searchFiles throws exception on git error', function () {
    Process::fake([
        'git ls-files "*test*"' => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: not a git repository'
        ),
    ]);

    $service = new SearchService($this->testRepoPath);

    expect(fn () => $service->searchFiles('test'))
        ->toThrow(\RuntimeException::class, 'Git ls-files failed');
});

test('searchCommits respects limit parameter', function () {
    Process::fake([
        'git log --grep="fix" --format="%H|%h|%an|%ar|%s" -10' => Process::result(
            output: '1111111111111111111111111111111111111111|1111111|Dev|now|Fix 1'
        ),
    ]);

    $service = new SearchService($this->testRepoPath);
    $results = $service->searchCommits('fix', 10);

    expect($results)->toHaveCount(1);

    Process::assertRan(function ($process) {
        return str_contains($process->command, '-10');
    });
});

test('searchContent respects limit parameter', function () {
    Process::fake([
        'git log -S "bug" --format="%H|%h|%an|%ar|%s" -25' => Process::result(
            output: '2222222222222222222222222222222222222222|2222222|Tester|yesterday|Fix bug'
        ),
    ]);

    $service = new SearchService($this->testRepoPath);
    $results = $service->searchContent('bug', 25);

    expect($results)->toHaveCount(1);

    Process::assertRan(function ($process) {
        return str_contains($process->command, '-25');
    });
});
