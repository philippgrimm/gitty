<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and initializes properties', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    // statusWithMixedChanges has 2 staged files: README.md (MM), src/App.php (M.)
    expect($component->get('stagedCount'))->toBe(2);
});

test('component commits with message', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit -m 'feat: add new feature'" => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertDispatched('committed');

    Process::assertRan("git commit -m 'feat: add new feature'");
});

test('component commits and pushes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit -m 'feat: add new feature'" => Process::result(''),
        'git push' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commitAndPush')
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertDispatched('committed');

    Process::assertRan("git commit -m 'feat: add new feature'");
    Process::assertRan('git push');
});

test('component amends commit', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit --amend -m 'feat: updated feature'" => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: updated feature')
        ->set('isAmend', true)
        ->call('commit')
        ->assertSet('message', '')
        ->assertSet('isAmend', false)
        ->assertDispatched('committed');

    Process::assertRan("git commit --amend -m 'feat: updated feature'");
});

test('component toggles amend and loads last commit message', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('stagedCount', 2);

    // Simulate status-updated event with new count
    $component->call('refreshStagedCount', stagedCount: 5, aheadBehind: ['ahead' => 1, 'behind' => 0])
        ->assertSet('stagedCount', 5);
});

test('component handles commit failure with error message', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit -m 'feat: add feature'" => function () {
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', '')
        ->assertSee('disabled'); // Button should be disabled
});

test('component prefills commit message on feature branch with ticket', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranch()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'feat(JIRA-123): ')
        ->assertSet('currentPrefill', 'feat(JIRA-123): ');
});

test('component prefills commit message on bugfix branch with ticket', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnBugfixBranch()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'fix(PROJ-456): ')
        ->assertSet('currentPrefill', 'fix(PROJ-456): ');
});

test('component does not prefill on feature branch without ticket number', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranchNoTicket()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', '')
        ->assertSet('currentPrefill', '');
});

test('component re-prefills commit message after successful commit on feature branch', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranch()),
        'git commit -m *' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'feat(JIRA-123): ')
        ->set('message', 'feat(JIRA-123): add login page')
        ->call('commit')
        ->assertSet('message', 'feat(JIRA-123): ')
        ->assertDispatched('committed');
});

test('component re-prefills commit message after commit and push on feature branch', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranch()),
        'git commit -m *' => Process::result(''),
        'git push' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'feat(JIRA-123): ')
        ->set('message', 'feat(JIRA-123): add login page')
        ->call('commitAndPush')
        ->assertSet('message', 'feat(JIRA-123): ')
        ->assertDispatched('committed');
});

test('component restores prefill when toggling amend off on feature branch', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranch()),
        'git log -1 --pretty=%B' => Process::result("fix: previous commit\n"),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'feat(JIRA-123): ')
        ->call('toggleAmend')
        ->assertSet('isAmend', true)
        ->assertSet('message', 'fix: previous commit')
        ->call('toggleAmend')
        ->assertSet('isAmend', false)
        ->assertSet('message', 'feat(JIRA-123): ');
});

test('component does not overwrite user-typed message on branch switch', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranch()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'feat(JIRA-123): ')
        ->set('message', 'feat(JIRA-123): my custom message')
        ->call('refreshStagedCount', stagedCount: 3, aheadBehind: ['ahead' => 0, 'behind' => 0])
        ->assertSet('message', 'feat(JIRA-123): my custom message');
});

test('component loads commit history on mount', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('commitHistory'))->toHaveCount(6)
        ->and($component->get('commitHistory')[0])->toBe('feat: add new feature')
        ->and($component->get('commitHistory')[1])->toBe('fix: resolve bug in parser');
});

test('commit history contains max 10 entries', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    expect(count($component->get('commitHistory')))->toBeLessThanOrEqual(10);
});

test('commit history refreshes after successful commit', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
        'git commit -m *' => Process::result(''),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('commitHistory', fn ($history) => count($history) === 6)
        ->set('message', 'feat: add new feature')
        ->call('commit')
        ->assertDispatched('committed');

    // History was reloaded (loadCommitHistory called after commit)
    Process::assertRan(fn ($process) => str_contains($process->command, 'git log --oneline -n 10'));
});

test('commit history handles empty repo gracefully', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => fn () => Process::result('', exitCode: 128),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('commitHistory'))->toBe([]);
});
