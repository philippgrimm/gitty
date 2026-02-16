<?php

declare(strict_types=1);

use App\Livewire\StashPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and loads stash list', function () {
    Process::fake([
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSee('feat: add new feature')
        ->assertSee('Temporary changes for testing')
        ->assertSee('fix: resolve parser bug');
});

test('component displays empty state when no stashes', function () {
    Process::fake([
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('No stashes');
});

test('component creates a stash with message', function () {
    Process::fake([
        'git stash list' => Process::result(''),
        'git stash push -m "Work in progress"' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('stashMessage', 'Work in progress')
        ->set('includeUntracked', false)
        ->call('createStash')
        ->assertSet('showCreateModal', false)
        ->assertSet('stashMessage', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git stash push -m "Work in progress"');
});

test('component creates a stash with untracked files included', function () {
    Process::fake([
        'git stash list' => Process::result(''),
        'git stash push -u -m "Include untracked"' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('stashMessage', 'Include untracked')
        ->set('includeUntracked', true)
        ->call('createStash')
        ->assertDispatched('status-updated');

    Process::assertRan('git stash push -u -m "Include untracked"');
});

test('component applies a stash', function () {
    Process::fake([
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash apply stash@{0}' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('applyStash', 0)
        ->assertDispatched('status-updated');

    Process::assertRan('git stash apply stash@{0}');
});

test('component pops a stash', function () {
    Process::fake([
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash pop stash@{1}' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('popStash', 1)
        ->assertDispatched('status-updated');

    Process::assertRan('git stash pop stash@{1}');
});

test('component drops a stash', function () {
    Process::fake([
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash drop stash@{2}' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('dropStash', 2)
        ->assertDispatched('status-updated');

    Process::assertRan('git stash drop stash@{2}');
});

test('component converts stash DTOs to arrays for Livewire', function () {
    Process::fake([
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    $component = Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath]);

    $stashes = $component->get('stashes');

    expect($stashes)->toBeArray();
    expect($stashes)->toHaveCount(3);
    expect($stashes[0])->toHaveKeys(['index', 'message', 'branch', 'sha']);
    expect($stashes[0]['index'])->toBe(0);
    expect($stashes[0]['message'])->toBe('feat: add new feature');
});

test('component refreshes stash list on demand', function () {
    Process::fake([
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshStashes')
        ->assertSee('feat: add new feature');

    Process::assertRan('git stash list');
});

test('component clears error before operations', function () {
    Process::fake([
        'git stash list' => Process::result(''),
        'git stash push -m "Test"' => Process::result(''),
    ]);

    Livewire::test(StashPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('error', 'Previous error')
        ->set('stashMessage', 'Test')
        ->call('createStash')
        ->assertSet('error', '');
});
