<?php

declare(strict_types=1);

use App\Livewire\RepoSidebar;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component displays tags in sidebar', function () {
    Process::fake([
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(
            "v2.0.0|||abc1234|||2 days ago|||Major release\nv1.0.0|||def5678|||1 week ago|||Initial release"
        ),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('v2.0.0')
        ->assertSee('v1.0.0');
});

test('component creates tag via modal', function () {
    Process::fake([
        'git tag "v1.0.0"' => Process::result(''),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result('v1.0.0|||abc1234|||just now|||'),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->set('showCreateTagModal', true)
        ->set('newTagName', 'v1.0.0')
        ->call('createTag')
        ->assertSet('showCreateTagModal', false)
        ->assertSet('newTagName', '')
        ->assertDispatched('show-error', message: 'Tag created successfully', type: 'success');

    Process::assertRan('git tag "v1.0.0"');
});

test('component creates annotated tag with message', function () {
    Process::fake([
        'git tag -a "v2.0.0" -m "Major release"' => Process::result(''),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->set('showCreateTagModal', true)
        ->set('newTagName', 'v2.0.0')
        ->set('newTagMessage', 'Major release')
        ->call('createTag')
        ->assertDispatched('show-error', message: 'Tag created successfully', type: 'success');

    Process::assertRan('git tag -a "v2.0.0" -m "Major release"');
});

test('component creates tag at specific commit', function () {
    Process::fake([
        'git tag "v1.5.0" abc1234' => Process::result(''),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->set('showCreateTagModal', true)
        ->set('newTagName', 'v1.5.0')
        ->set('newTagCommit', 'abc1234')
        ->call('createTag')
        ->assertDispatched('show-error', message: 'Tag created successfully', type: 'success');

    Process::assertRan('git tag "v1.5.0" abc1234');
});

test('component deletes tag', function () {
    Process::fake([
        'git tag -d "v1.0.0"' => Process::result('Deleted tag v1.0.0'),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('deleteTag', 'v1.0.0')
        ->assertDispatched('show-error', message: "Tag 'v1.0.0' deleted", type: 'success');

    Process::assertRan('git tag -d "v1.0.0"');
});

test('component pushes tag to remote', function () {
    Process::fake([
        'git push origin "v1.0.0"' => Process::result(''),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->call('pushTag', 'v1.0.0')
        ->assertDispatched('show-error', message: "Tag 'v1.0.0' pushed to remote", type: 'success');

    Process::assertRan('git push origin "v1.0.0"');
});

test('component handles create tag modal via palette event', function () {
    Process::fake([
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('palette-create-tag')
        ->assertSet('showCreateTagModal', true);
});

test('component does not create tag with empty name', function () {
    Process::fake([
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->set('showCreateTagModal', true)
        ->set('newTagName', '   ')
        ->call('createTag')
        ->assertSet('showCreateTagModal', true);

    Process::assertNotRan('git tag');
});

test('component handles tag creation error', function () {
    Process::fake([
        'git tag "v1.0.0"' => Process::result('', 'fatal: tag already exists', 1),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git status --porcelain --branch' => Process::result('## main'),
        'git branch --format=%(refname:short)|||%(HEAD)|||%(objectname:short)' => Process::result('main|||*|||abc1234'),
        'git remote -v' => Process::result(''),
        'git stash list' => Process::result(''),
    ]);

    Livewire::test(RepoSidebar::class, ['repoPath' => $this->testRepoPath])
        ->set('showCreateTagModal', true)
        ->set('newTagName', 'v1.0.0')
        ->call('createTag')
        ->assertDispatched('show-error', type: 'error');
});
