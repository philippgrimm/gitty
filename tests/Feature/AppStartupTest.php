<?php

declare(strict_types=1);

use App\Livewire\AppLayout;
use App\Models\Repository;
use App\Services\Git\GitConfigValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (!is_dir($this->testRepoPath . '/.git')) {
        mkdir($this->testRepoPath . '/.git', 0755, true);
    }
});

test('mounts with most recent repo when no path provided', function () {
    $repo = Repository::create([
        'name' => 'test-repo',
        'path' => $this->testRepoPath,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    Livewire::test(AppLayout::class)
        ->assertSet('repoPath', $this->testRepoPath);
});

test('shows empty state when no repos in database', function () {
    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    Livewire::test(AppLayout::class)
        ->assertSet('repoPath', '')
        ->assertSee('No Repository Selected');
});

test('detects missing git binary on startup and dispatches error', function () {
    Process::fake([
        'which git' => Process::result(output: '', exitCode: 1),
    ]);

    Livewire::test(AppLayout::class)
        ->assertDispatched('show-error', message: 'Git is not installed', type: 'error', persistent: true);
});

test('loads most recent repo even when invalid repo path provided', function () {
    $repo = Repository::create([
        'name' => 'test-repo',
        'path' => $this->testRepoPath,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    $invalidPath = '/tmp/invalid-repo';
    if (!is_dir($invalidPath)) {
        mkdir($invalidPath, 0755, true);
    }

    Livewire::test(AppLayout::class, ['repoPath' => $invalidPath])
        ->assertSet('repoPath', $this->testRepoPath);
});

test('skips auto-load when valid repo path provided', function () {
    $otherRepoPath = '/tmp/gitty-other-repo';
    if (!is_dir($otherRepoPath . '/.git')) {
        mkdir($otherRepoPath . '/.git', 0755, true);
    }

    $repo = Repository::create([
        'name' => 'other-repo',
        'path' => $otherRepoPath,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    Livewire::test(AppLayout::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath);
});

test('skips auto-load when most recent repo no longer has valid git directory', function () {
    $invalidRepoPath = '/tmp/gitty-deleted-repo';
    if (is_dir($invalidRepoPath)) {
        rmdir($invalidRepoPath);
    }

    $repo = Repository::create([
        'name' => 'deleted-repo',
        'path' => $invalidRepoPath,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    Livewire::test(AppLayout::class)
        ->assertSet('repoPath', '')
        ->assertSee('No Repository Selected');
});
