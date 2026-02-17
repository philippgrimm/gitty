<?php

declare(strict_types=1);

use App\Services\Git\GitCommandRunner;
use Illuminate\Support\Facades\Process;
use Tests\Helpers\GitTestHelper;

beforeEach(function () {
    $this->testRepoPath = sys_get_temp_dir().'/gitty-test-runner-'.uniqid();
    GitTestHelper::createTestRepo($this->testRepoPath);
    $this->runner = new GitCommandRunner($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});

test('GitCommandRunner runs simple git command', function () {
    $result = $this->runner->run('status');
    expect($result->exitCode())->toBe(0);
});

test('GitCommandRunner runs command with arguments', function () {
    $result = $this->runner->run('log', ['--oneline', '-n', '1']);
    expect($result->exitCode())->toBe(0);
    expect($result->output())->toContain('Initial commit');
});

test('GitCommandRunner escapes arguments with escapeshellarg', function () {
    file_put_contents($this->testRepoPath.'/file with spaces.txt', 'content');

    $result = $this->runner->run('add', ['file with spaces.txt']);
    expect($result->exitCode())->toBe(0);

    $statusResult = $this->runner->run('status', ['--porcelain']);
    expect($statusResult->output())->toContain('file with spaces.txt');
});

test('GitCommandRunner runOrFail returns result on success', function () {
    $result = $this->runner->runOrFail('status');
    expect($result->exitCode())->toBe(0);
});

test('GitCommandRunner runOrFail throws on failure', function () {
    $this->runner->runOrFail('checkout', ['nonexistent-branch-xyz']);
})->throws(RuntimeException::class);

test('GitCommandRunner runOrFail includes error prefix in exception', function () {
    try {
        $this->runner->runOrFail('checkout', ['nonexistent-branch-xyz'], 'Branch switch failed');
        $this->fail('Expected RuntimeException');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('Branch switch failed');
    }
});

test('GitCommandRunner runWithInput pipes stdin correctly', function () {
    file_put_contents($this->testRepoPath.'/test.txt', "line1\nline2\n");
    Process::path($this->testRepoPath)->run('git add test.txt');
    Process::path($this->testRepoPath)->run('git commit -m "add test.txt"');

    file_put_contents($this->testRepoPath.'/test.txt', "line1\nline2\nline3\n");

    $diffResult = Process::path($this->testRepoPath)->run('git diff test.txt');
    $patch = $diffResult->output();

    $result = $this->runner->runWithInput('apply --cached', $patch);
    expect($result->exitCode())->toBe(0);
});

test('GitCommandRunner handles empty arguments array', function () {
    $result = $this->runner->run('status', []);
    expect($result->exitCode())->toBe(0);
});

test('GitCommandRunner can be constructed with repo path', function () {
    $runner = new GitCommandRunner($this->testRepoPath);
    expect($runner)->toBeInstanceOf(GitCommandRunner::class);
});
