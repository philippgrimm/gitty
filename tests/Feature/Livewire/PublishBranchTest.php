<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use App\Livewire\SyncPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

// ─────────────────────────────────────────────────────────────────────────────
// hasUpstream detection
// ─────────────────────────────────────────────────────────────────────────────

test('sync panel sets hasUpstream to false when branch has no upstream', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123\n# branch.head feature/new-branch\n"
        ),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('hasUpstream', false)
        ->assertSet('aheadBehind', ['ahead' => 0, 'behind' => 0]);
});

test('sync panel sets hasUpstream to true when branch has upstream', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('hasUpstream', true)
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0]);
});

// ─────────────────────────────────────────────────────────────────────────────
// publishBranch action
// ─────────────────────────────────────────────────────────────────────────────

test('sync panel publishBranch runs git push -u', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123\n# branch.head feature/new-branch\n"
        ),
        "git push -u 'origin' 'feature/new-branch'" => Process::result(''),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('hasUpstream', false)
        ->call('publishBranch')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan(fn ($p) => str_contains($p->command, 'push -u'));
});

test('sync panel syncPush calls publishBranch when no upstream', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123\n# branch.head feature/new-branch\n"
        ),
        "git push -u 'origin' 'feature/new-branch'" => Process::result(''),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('hasUpstream', false)
        ->call('syncPush')
        ->assertSet('lastOperation', 'publish')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan(fn ($p) => str_contains($p->command, 'push -u'));
    Process::assertNotRan(fn ($p) => $p->command === "git push 'origin' 'feature/new-branch'");
});

test('sync panel updates hasUpstream after publishing', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        "git push -u 'origin' 'main'" => Process::result(''),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('hasUpstream', false)
        ->call('publishBranch')
        ->assertSet('hasUpstream', true);
});

// ─────────────────────────────────────────────────────────────────────────────
// refreshAheadBehind with hasUpstream param
// ─────────────────────────────────────────────────────────────────────────────

test('sync panel refreshAheadBehind accepts hasUpstream param', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123\n# branch.head feature/new-branch\n"
        ),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('hasUpstream', false)
        ->call('refreshAheadBehind', stagedCount: 0, aheadBehind: ['ahead' => 1, 'behind' => 0], hasUpstream: true)
        ->assertSet('hasUpstream', true)
        ->assertSet('aheadBehind', ['ahead' => 1, 'behind' => 0]);
});

// ─────────────────────────────────────────────────────────────────────────────
// CommitPanel dispatches hasUpstream
// ─────────────────────────────────────────────────────────────────────────────

test('commit panel passes hasUpstream false in status-updated when no upstream', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid abc123\n# branch.head feature/new-branch\n1 M. N... 100644 100644 100644 b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1 d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3 README.md\n"
        ),
        'git commit -m *' => Process::result(''),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['hasUpstream']) && $params['hasUpstream'] === false;
        });
});

test('commit panel passes hasUpstream true when branch has upstream', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m *' => Process::result(''),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertDispatched('status-updated', function ($event, $params) {
            return isset($params['hasUpstream']) && $params['hasUpstream'] === true;
        });
});
