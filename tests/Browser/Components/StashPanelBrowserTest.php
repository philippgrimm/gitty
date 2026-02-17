<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('stash panel displays stash list', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Stashes');
    $page->assertSee('3');
    $page->click('button:has-text("Stashes")');

    $page->screenshot(fullPage: true, filename: 'stash-panel-list');
});

test('stash panel shows empty state when no stashes', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Stashes');
    $page->click('button:has-text("Stashes")');

    $page->assertSee('No stashes');

    $page->screenshot(fullPage: true, filename: 'stash-panel-empty');
});

test('stash panel shows stash count', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Stashes');
    $page->assertSee('3');

    $page->screenshot(fullPage: true, filename: 'stash-panel-count');
});
