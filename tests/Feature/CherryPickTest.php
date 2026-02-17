<?php

declare(strict_types=1);

use App\DTOs\MergeResult;
use App\Livewire\HistoryPanel;
use App\Services\Git\CommitService;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

beforeEach(function () {
    $this->repoPath = sys_get_temp_dir().'/test-repo-'.uniqid();
    mkdir($this->repoPath);
    Process::path($this->repoPath)->run('git init');
    Process::path($this->repoPath)->run('git config user.email "test@example.com"');
    Process::path($this->repoPath)->run('git config user.name "Test User"');

    // Create initial commit
    file_put_contents($this->repoPath.'/file.txt', 'initial');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Initial commit"');
});

afterEach(function () {
    if (is_dir($this->repoPath)) {
        Process::path($this->repoPath)->run('rm -rf '.$this->repoPath);
    }
});

test('cherry-pick applies commit successfully', function () {
    // Create a second commit
    file_put_contents($this->repoPath.'/file.txt', 'modified');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Second commit"');

    // Get the SHA of the second commit
    $sha = trim(Process::path($this->repoPath)->run('git rev-parse HEAD')->output());

    // Reset to first commit
    Process::path($this->repoPath)->run('git reset --hard HEAD~1');

    // Cherry-pick the second commit
    $commitService = new CommitService($this->repoPath);
    $result = $commitService->cherryPick($sha);

    expect($result)->toBeInstanceOf(MergeResult::class);
    expect($result->success)->toBeTrue();
    expect($result->hasConflicts)->toBeFalse();
    expect(file_get_contents($this->repoPath.'/file.txt'))->toBe('modified');
});

test('cherry-pick detects conflicts', function () {
    // Create a second commit
    file_put_contents($this->repoPath.'/file.txt', 'branch-a');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Branch A commit"');

    // Get the SHA
    $sha = trim(Process::path($this->repoPath)->run('git rev-parse HEAD')->output());

    // Reset and create conflicting change
    Process::path($this->repoPath)->run('git reset --hard HEAD~1');
    file_put_contents($this->repoPath.'/file.txt', 'branch-b');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Branch B commit"');

    // Cherry-pick should conflict
    $commitService = new CommitService($this->repoPath);
    $result = $commitService->cherryPick($sha);

    expect($result->hasConflicts)->toBeTrue();
});

test('cherry-pick abort works', function () {
    // Create a second commit
    file_put_contents($this->repoPath.'/file.txt', 'branch-a');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Branch A commit"');

    $sha = trim(Process::path($this->repoPath)->run('git rev-parse HEAD')->output());

    // Reset and create conflicting change
    Process::path($this->repoPath)->run('git reset --hard HEAD~1');
    file_put_contents($this->repoPath.'/file.txt', 'branch-b');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Branch B commit"');

    // Cherry-pick to create conflict
    $commitService = new CommitService($this->repoPath);
    $commitService->cherryPick($sha);

    // Abort
    $commitService->cherryPickAbort();

    // Verify we're back to clean state
    $status = Process::path($this->repoPath)->run('git status --porcelain')->output();
    expect($status)->toBe('');
});

test('history panel renders cherry-pick modal', function () {
    Livewire::test(HistoryPanel::class, ['repoPath' => $this->repoPath])
        ->assertSet('showCherryPickModal', false)
        ->call('promptCherryPick', 'abc123', 'Test commit')
        ->assertSet('showCherryPickModal', true)
        ->assertSet('cherryPickTargetSha', 'abc123')
        ->assertSet('cherryPickTargetMessage', 'Test commit');
});

test('history panel dispatches status-updated on successful cherry-pick', function () {
    // Create a second commit
    file_put_contents($this->repoPath.'/file.txt', 'modified');
    Process::path($this->repoPath)->run('git add .');
    Process::path($this->repoPath)->run('git commit -m "Second commit"');

    $sha = trim(Process::path($this->repoPath)->run('git rev-parse HEAD')->output());

    // Reset to first commit
    Process::path($this->repoPath)->run('git reset --hard HEAD~1');

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->repoPath])
        ->set('cherryPickTargetSha', $sha)
        ->call('confirmCherryPick')
        ->assertDispatched('status-updated')
        ->assertDispatched('show-success');
});
