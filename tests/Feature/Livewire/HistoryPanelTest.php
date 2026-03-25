<?php

declare(strict_types=1);

use App\Livewire\HistoryPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

function historyPanelCommand(int $limit, int $skip, string $scope = 'current'): string
{
    $command = "git log --graph --date-order --decorate=short --format='%x1e%H%x1f%P%x1f%an%x1f%ar%x1f%s%x1f%D' -n {$limit} --skip {$skip}";

    if ($scope === 'all') {
        $command .= ' --all';
    }

    return $command;
}

function historyPanelLine(string $graphPrefix, string $sha, string $parents, string $author, string $date, string $message, string $refs = ''): string
{
    return $graphPrefix."\x1e{$sha}\x1f{$parents}\x1f{$author}\x1f{$date}\x1f{$message}\x1f{$refs}";
}

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and loads history rows', function () {
    Process::fake([
        historyPanelCommand(101, 0) => Process::result(implode("\n", [
            historyPanelLine('* ', 'abc123def456', 'def456ghi789', 'John Doe', '2 hours ago', 'Initial commit', 'HEAD -> main, origin/main'),
            historyPanelLine('* ', 'def456ghi789', '', 'Jane Smith', '1 day ago', 'Add feature'),
        ])),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('historyScope', 'current')
        ->assertSet('loaded', false)
        ->call('activate')
        ->assertSet('loaded', true)
        ->assertSee('John Doe')
        ->assertSee('Initial commit')
        ->assertSee('Jane Smith')
        ->assertSee('Add feature');
});

test('component shows empty state when no commits', function () {
    Process::fake([
        historyPanelCommand(101, 0) => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('No commits yet');
});

test('component loads more commits using skip pagination without duplicates', function () {
    $initialOutput = [];
    for ($index = 1; $index <= 101; $index++) {
        $sha = sprintf('sha%03d', $index);
        $parent = sprintf('sha%03d', $index + 1);
        $initialOutput[] = historyPanelLine('* ', $sha, $parent, 'John Doe', "{$index} hours ago", "Commit {$index}");
    }

    $pageTwoOutput = implode("\n", [
        historyPanelLine('* ', 'sha101', 'sha102', 'John Doe', '101 hours ago', 'Commit 101'),
        historyPanelLine('* ', 'sha102', 'sha103', 'John Doe', '102 hours ago', 'Commit 102'),
    ]);

    Process::fake([
        historyPanelCommand(101, 0) => Process::result(implode("\n", $initialOutput)),
        historyPanelCommand(101, 100) => Process::result($pageTwoOutput),
    ]);

    $component = Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('activate')
        ->assertSet('hasMore', true)
        ->assertSet('commitsCount', 100);

    $component->call('loadMore')
        ->assertSet('page', 2)
        ->assertSet('hasMore', false)
        ->assertSet('commitsCount', 102)
        ->assertSee('Commit 102');

    Process::assertRan(historyPanelCommand(101, 100));
});

test('component dispatches event when commit is selected', function () {
    Process::fake([
        historyPanelCommand(101, 0) => Process::result(historyPanelLine('* ', 'abc123def456', '', 'John Doe', '2 hours ago', 'Initial commit', 'HEAD -> main')),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectCommit', 'abc123def456')
        ->assertSet('selectedCommitSha', 'abc123def456')
        ->assertDispatched('commit-selected', sha: 'abc123def456');
});

test('component switches scope and resets page and selection', function () {
    Process::fake([
        historyPanelCommand(101, 0, 'current') => Process::result(historyPanelLine('* ', 'current1', '', 'John Doe', 'now', 'Current commit')),
        historyPanelCommand(101, 0, 'all') => Process::result(historyPanelLine('* ', 'all1', '', 'Jane Doe', 'now', 'All commit', 'origin/main')),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('selectedCommitSha', 'current1')
        ->set('page', 3)
        ->call('setHistoryScope', 'all')
        ->assertSet('historyScope', 'all')
        ->assertSet('page', 1)
        ->assertSet('selectedCommitSha', null)
        ->assertSee('All commit');

    Process::assertRan(historyPanelCommand(101, 0, 'all'));
});

test('component handles external commit-selected by falling back to all scope', function () {
    Process::fake([
        historyPanelCommand(101, 0, 'current') => Process::result(historyPanelLine('* ', 'current-only', '', 'John Doe', 'now', 'Current commit')),
        historyPanelCommand(101, 0, 'all') => Process::result(implode("\n", [
            historyPanelLine('* ', 'current-only', '', 'John Doe', 'now', 'Current commit'),
            historyPanelLine('* ', 'target-sha', '', 'Jane Doe', 'now', 'Target from other branch', 'origin/feature'),
        ])),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('commit-selected', sha: 'target-sha')
        ->assertSet('historyScope', 'all')
        ->assertSet('selectedCommitSha', 'target-sha')
        ->assertDispatched('scroll-history-to-commit', sha: 'target-sha')
        ->assertSee('Target from other branch');
});

test('component refreshes history on repo switch', function () {
    Process::fake([
        historyPanelCommand(101, 0, 'current') => Process::result(historyPanelLine('* ', 'abc123def456', '', 'John Doe', '2 hours ago', 'Initial commit', 'HEAD -> main')),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('repo-switched', path: '/tmp/another-repo')
        ->assertSet('repoPath', '/tmp/another-repo')
        ->assertSet('page', 1)
        ->assertSet('historyScope', 'current')
        ->assertSet('selectedCommitSha', null);
});

test('component refreshes history on status update', function () {
    Process::fake([
        historyPanelCommand(101, 0, 'current') => Process::result(historyPanelLine('* ', 'abc123def456', '', 'John Doe', '2 hours ago', 'Initial commit', 'HEAD -> main')),
    ]);

    $component = Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectCommit', 'abc123def456')
        ->assertSet('selectedCommitSha', 'abc123def456');

    $component->dispatch('status-updated')
        ->assertSet('page', 1)
        ->assertSet('selectedCommitSha', null);
});

test('graph toggle does not change loaded commit content', function () {
    Process::fake([
        historyPanelCommand(101, 0) => Process::result(historyPanelLine('* ', 'abc123def456', '', 'John Doe', '2 hours ago', 'Initial commit', 'HEAD -> main')),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('activate')
        ->assertSet('showGraph', true)
        ->set('showGraph', false)
        ->assertSet('showGraph', false)
        ->assertSee('Initial commit');
});

test('app layout switches to history panel on commit-selected event', function () {
    $layout = file_get_contents(resource_path('views/livewire/app-layout.blade.php'));

    expect($layout)->toContain("@commit-selected.window=\"activeRightPanel = 'history'\"");
});

test('command palette includes toggle history command', function () {
    $commands = \App\Livewire\CommandPalette::getCommands();
    $toggleHistory = collect($commands)->firstWhere('id', 'toggle-history');

    expect($toggleHistory)->not->toBeNull()
        ->and($toggleHistory['label'])->toBe('Toggle History')
        ->and($toggleHistory['shortcut'])->toBe('⌘H')
        ->and($toggleHistory['event'])->toBe('toggle-history-panel');
});
