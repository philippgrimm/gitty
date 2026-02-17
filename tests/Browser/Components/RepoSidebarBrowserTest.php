<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('repo sidebar displays remotes section', function () {
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
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Remotes');
    $page->click('button:has-text("Remotes")');
    $page->assertSee('origin');

    $page->screenshot(fullPage: true, filename: 'repo-sidebar-remotes');
});

test('repo sidebar displays tags section', function () {
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
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result("v1.0.0|||a1b2c3d|||2 days ago|||Release v1.0.0\nv2.0.0|||d4e5f6g|||1 day ago|||Release v2.0.0"),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Tags');
    // Tags section uses a <div> with @click (not a <button>), click and wait for content
    $page->click('div.cursor-pointer:has-text("Tags")');
    $page->waitForText('v1.0.0');
    $page->assertSee('v2.0.0');

    $page->screenshot(fullPage: true, filename: 'repo-sidebar-tags');
});

test('repo sidebar displays stashes section', function () {
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
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Stashes');
    $page->click('button:has-text("Stashes")');

    $page->screenshot(fullPage: true, filename: 'repo-sidebar-stashes');
});

test('repo sidebar shows empty states for sections', function () {
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
        'git remote -v' => Process::result(''),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->click('button:has-text("Remotes")');
    $page->assertSee('No remotes');

    // Tags section uses a <div> with @click (not a <button>), click and wait for content
    $page->click('div.cursor-pointer:has-text("Tags")');
    $page->waitForText('No tags');

    $page->click('button:has-text("Stashes")');
    $page->assertSee('No stashes');

    $page->screenshot(fullPage: true, filename: 'repo-sidebar-empty-states');
});
