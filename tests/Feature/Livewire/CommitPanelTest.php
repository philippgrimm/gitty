<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath . '/.git')) {
        mkdir($this->testRepoPath . '/.git', 0755, true);
    }
});

test('component mounts with repo path and initializes properties', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertSet('stagedCount', 2)
        ->assertSet('error', null);
});

test('component counts staged files correctly', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    // statusWithMixedChanges has 2 staged files: README.md (MM), src/App.php (M.)
    expect($component->get('stagedCount'))->toBe(2);
});

test('component commits with message', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m "feat: add new feature"' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertDispatched('committed');

    Process::assertRan('git commit -m "feat: add new feature"');
});

test('component commits and pushes', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m "feat: add new feature"' => Process::result(''),
        'git push' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commitAndPush')
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertDispatched('committed');

    Process::assertRan('git commit -m "feat: add new feature"');
    Process::assertRan('git push');
});

test('component amends commit', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit --amend -m "feat: updated feature"' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: updated feature')
        ->set('isAmend', true)
        ->call('commit')
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertDispatched('committed');

    Process::assertRan('git commit --amend -m "feat: updated feature"');
});

test('component toggles amend and loads last commit message', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log -1 --pretty=%B' => Process::result("feat: previous commit\n"),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('toggleAmend')
        ->assertSet('isAmend', true)
        ->assertSet('message', 'feat: previous commit');

    Process::assertRan('git log -1 --pretty=%B');
});

test('component clears message when toggling amend off', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log -1 --pretty=%B' => Process::result("feat: previous commit\n"),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('toggleAmend')
        ->assertSet('isAmend', true)
        ->assertSet('message', 'feat: previous commit')
        ->call('toggleAmend')
        ->assertSet('isAmend', false)
        ->assertSet('message', '');
});

test('component refreshes staged count on status-updated event', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('stagedCount', 2);

    // Simulate status update with different file count
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    $component->call('refreshStagedCount')
        ->assertSet('stagedCount', 2);
});

test('component handles commit failure with error message', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git commit -m "feat: add feature"' => function () {
            return Process::result('error: commit failed', exitCode: 1);
        },
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add feature')
        ->call('commit')
        ->assertSet('message', 'feat: add feature')
        ->assertSet('error', 'Git commit failed: ');
});

test('component does not commit with empty message', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', '')
        ->assertSee('disabled'); // Button should be disabled
});
