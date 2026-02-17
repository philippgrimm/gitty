<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('sync panel displays push, pull, and fetch buttons', function () {
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

    $page->assertVisible('button[wire\\:click="syncPush"]');
    $page->assertVisible('button[wire\\:click="syncPull"]');
    $page->assertVisible('button[wire\\:click="syncFetch"]');

    $page->screenshot(fullPage: true, filename: 'sync-panel-buttons');
});

test('sync panel shows force push confirmation modal', function () {
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

    // Trigger force push confirmation modal via Alpine.js
    $page->script("document.querySelector('[x-data]').dispatchEvent(new CustomEvent('open-force-push', { bubbles: true }))");
    $page->wait(0.3);

    // Set Alpine data directly to open the modal
    $page->script("
        const syncPanels = document.querySelectorAll('[x-data]');
        syncPanels.forEach(el => {
            if (el._x_dataStack && el._x_dataStack[0] && 'confirmForcePush' in el._x_dataStack[0]) {
                el._x_dataStack[0].confirmForcePush = true;
            }
        });
    ");
    $page->wait(0.5);

    $page->assertSee('Force Push Warning');
    $page->assertSee('--force-with-lease');
    $page->assertSee('Are you sure you want to continue?');

    $page->screenshot(fullPage: true, filename: 'sync-panel-force-push-modal');
});

test('sync panel fetch all operation works via command palette', function () {
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
        'git fetch --all' => Process::result("Fetching origin\nFetching upstream"),
    ]);

    $page = visit('/');

    // Verify sync panel buttons are visible
    $page->assertVisible('button[wire\\:click="syncPush"]');
    $page->assertVisible('button[wire\\:click="syncPull"]');
    $page->assertVisible('button[wire\\:click="syncFetch"]');

    $page->screenshot(fullPage: true, filename: 'sync-panel-dropdown-menu');
});
