<?php

use App\DTOs\GitStatus;
use App\Services\Git\BranchService;
use App\Services\Git\GitService;
use App\Services\Git\StagingService;
use Illuminate\Support\Facades\Process;
use Tests\Helpers\GitTestHelper;

beforeEach(function () {
    $this->testRepoPath = sys_get_temp_dir().'/gitty-test-edge-'.uniqid();
    GitTestHelper::createTestRepo($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});

test('GitService handles empty status output gracefully', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(''),
    ]);

    $service = new GitService($this->testRepoPath);
    $status = $service->status();

    expect($status)->toBeInstanceOf(GitStatus::class)
        ->and($status->changedFiles)->toBeEmpty();
});

test('GitService handles detached HEAD in status', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123def456\n# branch.head (detached)\n"
        ),
    ]);

    $service = new GitService($this->testRepoPath);
    $status = $service->status();

    expect($status)->toBeInstanceOf(GitStatus::class)
        ->and($status->branch)->toBe('(detached)');
});

test('BranchService handles empty branch output', function () {
    Process::fake([
        'git branch -a -vv' => Process::result(''),
    ]);

    $service = new BranchService($this->testRepoPath);
    $branches = $service->branches();

    expect($branches)->toHaveCount(0);
});

test('StagingService handles unicode filenames', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $service = new StagingService($this->testRepoPath);
    $service->stageFile('文件.txt');

    Process::assertRan(fn ($process) => str_contains($process->command, 'git add'));
});

test('StagingService handles filenames with emoji', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $service = new StagingService($this->testRepoPath);
    $service->stageFile('🎉 release.md');

    Process::assertRan(fn ($process) => str_contains($process->command, 'git add'));
});

test('GitService log handles empty repository', function () {
    Process::fake([
        'git log *' => Process::result('', 'fatal: your current branch does not have any commits yet', 128),
    ]);

    $service = new GitService($this->testRepoPath);
    $log = $service->log();

    expect($log)->toBeEmpty();
});

test('GitService status handles binary file changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123\n# branch.head main\n1 M. N... 100644 100644 100644 abc123 def456 image.png\n"
        ),
    ]);

    $service = new GitService($this->testRepoPath);
    $status = $service->status();

    expect($status->changedFiles)->toHaveCount(1);
});
