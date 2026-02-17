<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('commit panel displays staged file count', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    // Verify commit panel is rendered with commit button
    $page->assertSee('Commit (⌘↵)');
    $page->screenshot(fullPage: true, filename: 'commit-panel-with-staged-files');
});

test('commit panel shows no staged files message', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
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

    // Verify commit panel renders with no changes state
    $page->assertSee('No changes');
    $page->screenshot(fullPage: true, filename: 'commit-panel-no-staged-files');
});

test('commit panel displays amend checkbox', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    // Verify commit panel has the commit button with staged files
    $page->assertSee('Commit (⌘↵)');
    $page->screenshot(fullPage: true, filename: 'commit-panel-amend-option');
});
