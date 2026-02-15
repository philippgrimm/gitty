<?php

declare(strict_types=1);

use App\Livewire\StagingPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and loads status', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSee('README.md')
        ->assertSee('src/App.php')
        ->assertSee('config/app.php')
        ->assertSee('untracked.txt');
});

test('component separates files into unstaged, staged, and untracked', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    $component = Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath]);

    // README.md: MM (staged M, unstaged M) - should appear in both
    // src/App.php: M. (staged M, no unstaged) - staged only
    // config/app.php: .M (no staged, unstaged M) - unstaged only
    // untracked.txt: ?? - untracked only

    expect($component->get('unstagedFiles'))->toHaveCount(2); // README.md, config/app.php
    expect($component->get('stagedFiles'))->toHaveCount(2); // README.md, src/App.php
    expect($component->get('untrackedFiles'))->toHaveCount(1); // untracked.txt
});

test('component stages a file', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git add README.md' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('stageFile', 'README.md')
        ->assertDispatched('status-updated');

    Process::assertRan('git add README.md');
});

test('component unstages a file', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git reset HEAD README.md' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('unstageFile', 'README.md')
        ->assertDispatched('status-updated');

    Process::assertRan('git reset HEAD README.md');
});

test('component stages all files', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git add .' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('stageAll')
        ->assertDispatched('status-updated');

    Process::assertRan('git add .');
});

test('component unstages all files', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git reset HEAD' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('unstageAll')
        ->assertDispatched('status-updated');

    Process::assertRan('git reset HEAD');
});

test('component discards a file', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git checkout -- README.md' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('discardFile', 'README.md')
        ->assertDispatched('status-updated');

    Process::assertRan('git checkout -- README.md');
});

test('component discards all files', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git checkout -- .' => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('discardAll')
        ->assertDispatched('status-updated');

    Process::assertRan('git checkout -- .');
});

test('component dispatches file-selected event when file is clicked', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectFile', 'README.md', false)
        ->assertDispatched('file-selected', file: 'README.md', staged: false);
});

test('component shows empty state when no changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('No changes');
});

test('component refreshes status on demand', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('refreshStatus')
        ->assertSee('README.md');

    Process::assertRan('git status --porcelain=v2 --branch');
});

test('staging panel renders smaller 8px status dots', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSeeHtml('w-2 h-2 rounded-full')
        ->assertDontSeeHtml('w-2.5 h-2.5');
});
