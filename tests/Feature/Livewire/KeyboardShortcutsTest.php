<?php

declare(strict_types=1);

use App\Livewire\AppLayout;
use App\Livewire\CommitPanel;
use App\Livewire\StagingPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $testRepoPath = '/tmp/gitty-test-repo';
    if (!is_dir($testRepoPath . '/.git')) {
        mkdir($testRepoPath, 0755, true);
        mkdir($testRepoPath . '/.git', 0755, true);
    }
    $this->testRepoPath = $testRepoPath;
});

test('commit panel responds to Cmd+Enter keyboard shortcut', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid a1b2c3d\n# branch.head main\n# branch.upstream origin/main\n# branch.ab +0 -0\n1 M. N... 100644 100644 100644 abc123 def456 file.txt\n"
        ),
        'git commit -m *' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'test commit')
        ->call('commit')
        ->assertDispatched('committed');
});

test('commit panel responds to Cmd+Shift+Enter for commit and push', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid a1b2c3d\n# branch.head main\n# branch.upstream origin/main\n# branch.ab +0 -0\n1 M. N... 100644 100644 100644 abc123 def456 file.txt\n"
        ),
        'git commit -m *' => Process::result(''),
        'git push' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'test commit')
        ->call('commitAndPush')
        ->assertDispatched('committed');
});

test('staging panel responds to Cmd+Shift+K for stage all', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid a1b2c3d\n# branch.head main\n# branch.upstream origin/main\n# branch.ab +0 -0\n1 .M N... 100644 100644 100644 abc123 def456 file.txt\n"
        ),
        'git add --all' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('stageAll')
        ->assertDispatched('status-updated');
});

test('staging panel responds to Cmd+Shift+U for unstage all', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(
            "# branch.oid a1b2c3d\n# branch.head main\n# branch.upstream origin/main\n# branch.ab +0 -0\n1 M. N... 100644 100644 100644 abc123 def456 file.txt\n"
        ),
        'git reset HEAD' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('unstageAll')
        ->assertDispatched('status-updated');
});

test('app layout responds to Cmd+B for toggle sidebar', function () {
    Livewire::test(AppLayout::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('sidebarCollapsed', false)
        ->call('toggleSidebar')
        ->assertSet('sidebarCollapsed', true)
        ->call('toggleSidebar')
        ->assertSet('sidebarCollapsed', false);
});
