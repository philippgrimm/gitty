<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('app layout renders full layout with repository', function () {
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

    $page->assertSee('gitty');
    $page->assertSee('gitty-test-repo');
    $page->assertVisible('button[wire\\:click="toggleSidebar"]');

    $page->screenshot(fullPage: true, filename: 'app-layout-full');
});

test('app layout shows empty state when no repository selected', function () {
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    $page = visit('/');

    $page->assertSee('No Repository Selected');
    $page->assertSee('Open a git repository to get started');
    $page->assertMissing('button[wire\\:click="toggleSidebar"]');

    $page->screenshot(fullPage: true, filename: 'app-layout-empty-state');
});

test('app layout sidebar toggle works', function () {
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

    $page->assertVisible('[wire\\:key="repo-sidebar-'.$testRepoPath.'"]');
    $page->click('button[wire\\:click="toggleSidebar"]');
    $page->wait(0.3);

    $page->screenshot(fullPage: true, filename: 'app-layout-sidebar-collapsed');
});
