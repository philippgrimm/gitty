<?php

declare(strict_types=1);

use App\Livewire\HistoryPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and loads commits', function () {
    Process::fake([
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 100" => Process::result(
            "abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main, origin/main\n".
            'def456ghi789|||Jane Smith|||jane@example.com|||1 day ago|||Add feature|||'
        ),
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 101" => Process::result(
            "abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main, origin/main\n".
            'def456ghi789|||Jane Smith|||jane@example.com|||1 day ago|||Add feature|||'
        ),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSee('John Doe')
        ->assertSee('Initial commit')
        ->assertSee('Jane Smith')
        ->assertSee('Add feature');
});

test('component shows empty state when no commits', function () {
    Process::fake([
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 100" => Process::result(''),
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 101" => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('No commits yet');
});

test('component loads more commits when requested', function () {
    Process::fake();

    $component = Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('commitsCount'))->toBeGreaterThanOrEqual(0);

    $component->call('loadMore')
        ->assertSet('page', 2);
});

test('component dispatches event when commit is selected', function () {
    Process::fake([
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 100" => Process::result(
            'abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main'
        ),
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 101" => Process::result(
            'abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main'
        ),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectCommit', 'abc123def456')
        ->assertSet('selectedCommitSha', 'abc123def456')
        ->assertDispatched('commit-selected', sha: 'abc123def456');
});

test('component refreshes commits on repo switch', function () {
    Process::fake([
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 100" => Process::result(
            'abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main'
        ),
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 101" => Process::result(
            'abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main'
        ),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('repo-switched', path: '/tmp/another-repo')
        ->assertSet('repoPath', '/tmp/another-repo')
        ->assertSet('page', 1)
        ->assertSet('selectedCommitSha', null);
});

test('component refreshes commits on status update', function () {
    Process::fake([
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 100" => Process::result(
            'abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main'
        ),
        "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n 101" => Process::result(
            'abc123def456|||John Doe|||john@example.com|||2 hours ago|||Initial commit|||HEAD -> main'
        ),
    ]);

    $component = Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectCommit', 'abc123def456')
        ->assertSet('selectedCommitSha', 'abc123def456');

    $component->dispatch('status-updated')
        ->assertSet('page', 1)
        ->assertSet('selectedCommitSha', null);
});

test('command palette includes toggle history command', function () {
    $commands = \App\Livewire\CommandPalette::getCommands();
    $toggleHistory = collect($commands)->firstWhere('id', 'toggle-history');

    expect($toggleHistory)->not->toBeNull()
        ->and($toggleHistory['label'])->toBe('Toggle History')
        ->and($toggleHistory['shortcut'])->toBe('⌘H')
        ->and($toggleHistory['event'])->toBe('toggle-history-panel');
});
