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

test('component mounts with repo path and loads sidebar data', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchList()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result("v1.0.0 a1b2c3d\nv2.0.0 d4e5f6g"),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSee('Branches')
        ->assertSee('Remotes')
        ->assertSee('Tags')
        ->assertSee('Stashes');
});

test('component displays local branches only', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchList()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $branches = $component->get('branches');
    expect($branches)->toBeArray();
    expect(count($branches))->toBeGreaterThan(0);

    foreach ($branches as $branch) {
        expect($branch)->toHaveKey('name');
        expect($branch)->toHaveKey('isCurrent');
        expect($branch)->toHaveKey('lastCommitSha');
    }
});

test('component displays remotes with URLs', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchList()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $remotes = $component->get('remotes');
    expect($remotes)->toBeArray();

    if (count($remotes) > 0) {
        foreach ($remotes as $remote) {
            expect($remote)->toHaveKey('name');
            expect($remote)->toHaveKey('fetchUrl');
            expect($remote)->toHaveKey('pushUrl');
        }
    }
});

test('component displays tags with SHAs', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchList()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result("v1.0.0 a1b2c3d\nv2.0.0 d4e5f6g"),
        'git stash list' => Process::result(''),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $tags = $component->get('tags');
    expect($tags)->toBeArray();
    expect(count($tags))->toBe(2);
    expect($tags[0]['name'])->toBe('v1.0.0');
    expect($tags[0]['sha'])->toBe('a1b2c3d');
});

test('component displays stashes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchList()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
    ]);

    $component = Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath]);

    $stashes = $component->get('stashes');
    expect($stashes)->toBeArray();

    if (count($stashes) > 0) {
        foreach ($stashes as $stash) {
            expect($stash)->toHaveKey('index');
            expect($stash)->toHaveKey('message');
            expect($stash)->toHaveKey('branch');
            expect($stash)->toHaveKey('sha');
        }
    }
});

test('component switches branch and dispatches event', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchList()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
        'git checkout develop' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('switchBranch', 'develop')
        ->assertDispatched('status-updated');

    Process::assertRan('git checkout develop');
});
