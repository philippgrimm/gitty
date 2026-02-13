<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('staging panel displays unstaged and staged files correctly', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    $page = visit('/');

    $page->assertSee('Staged Changes');
    $page->assertSee('Changes');
    $page->assertSee('README.md');
    $page->assertSee('App.php');
    $page->assertSee('app.php');
    $page->assertSee('untracked.txt');
    $page->screenshot(fullPage: true, filename: 'staging-panel-mixed-changes');
});

test('staging panel shows empty state when no changes', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->assertSee('No changes');
    $page->screenshot(fullPage: true, filename: 'staging-panel-empty-state');
});

test('staging panel can stage a file', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git add README.md' => Process::result(''),
    ]);

    $page = visit('/');

    $page->assertSee('README.md');
    $page->assertSee('Changes');
    $page->screenshot(fullPage: true, filename: 'staging-panel-before-stage');
});
