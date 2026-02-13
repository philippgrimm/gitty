<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('repo switcher displays current repository', function () {
    $testRepoPath = '/tmp/gitty-test-repo-'.uniqid();
    if (! is_dir($testRepoPath.'/.git')) {
        mkdir($testRepoPath.'/.git', 0755, true);
    }
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => $testRepoPath,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('gitty-test-repo');

    $page->screenshot(fullPage: true, filename: 'repo-switcher-current-repo');
});

test('repo switcher shows empty state when no repository open', function () {
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    $page = visit('/');

    $page->click('button:has-text("No repository open")');
    $page->assertSee('No repositories yet');
    $page->assertSee('Open Repository');

    $page->screenshot(fullPage: true, filename: 'repo-switcher-empty-state');
});

test('repo switcher displays recent repositories list', function () {
    $testRepoPath = '/tmp/gitty-test-repo-'.uniqid();
    if (! is_dir($testRepoPath.'/.git')) {
        mkdir($testRepoPath.'/.git', 0755, true);
    }
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo1 = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => $testRepoPath,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo1->id);

    Repository::create([
        'name' => 'other-repo',
        'path' => '/tmp/other-repo-'.uniqid(),
        'last_opened_at' => now()->subDays(1),
    ]);

    Repository::create([
        'name' => 'another-repo',
        'path' => '/tmp/another-repo-'.uniqid(),
        'last_opened_at' => now()->subDays(2),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->click('button:has-text("gitty-test-repo")');
    $page->assertSee('Recent Repositories');
    $page->assertSee('gitty-test-repo');
    $page->assertSee('other-repo');
    $page->assertSee('another-repo');
    $page->assertSee('Open Repository');

    $page->screenshot(fullPage: true, filename: 'repo-switcher-recent-list');
});
