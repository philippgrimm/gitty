<?php

declare(strict_types=1);

use App\Livewire\RepoSidebar;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('stashes are loaded on mount via repo sidebar', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $stashes = $component->get('stashes');
    expect($stashes)->toBeArray();
    expect(count($stashes))->toBeGreaterThan(0);

    foreach ($stashes as $stash) {
        expect($stash)->toHaveKeys(['index', 'message', 'branch', 'sha']);
    }
});

test('empty stash list returns empty array', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $stashes = $component->get('stashes');
    expect($stashes)->toBeArray();
    expect($stashes)->toBeEmpty();
});

test('apply stash dispatches status-updated and refresh-staging', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash apply *' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('applyStash', 0)
        ->assertDispatched('status-updated')
        ->assertDispatched('refresh-staging');

    Process::assertRan('git stash apply stash@{0}');
});

test('pop stash dispatches status-updated and refresh-staging', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash pop *' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('popStash', 1)
        ->assertDispatched('status-updated')
        ->assertDispatched('refresh-staging');

    Process::assertRan('git stash pop stash@{1}');
});

test('drop stash dispatches status-updated', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash drop *' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('dropStash', 2)
        ->assertDispatched('status-updated');

    Process::assertRan('git stash drop stash@{2}');
});

test('stash apply error dispatches show-error event', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash apply *' => function () {
            throw new \Exception('error: Your local changes would be overwritten');
        },
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('applyStash', 0)
        ->assertDispatched('show-error');
});

test('stash data contains expected keys from stash list', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $stashes = $component->get('stashes');
    expect($stashes)->toBeArray();

    if (count($stashes) > 0) {
        expect($stashes[0])->toHaveKeys(['index', 'message', 'branch', 'sha']);
        expect($stashes[0]['index'])->toBe(0);
    }
});

test('stash-created event refreshes sidebar stash list', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);
    $component->dispatch('stash-created');
    expect($component->get('stashes'))->toBeArray();
});

test('pop stash runs git stash pop with correct index', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch *' => Process::result("* main\n"),
        'git remote -v' => Process::result(''),
        'git tag *' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git stash pop *' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('popStash', 0)
        ->assertDispatched('status-updated');

    Process::assertRan('git stash pop stash@{0}');
});
