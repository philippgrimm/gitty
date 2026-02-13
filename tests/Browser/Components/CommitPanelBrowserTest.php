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

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    $page = visit('/');

    $page->assertSee('2 files staged');
    $page->screenshot(fullPage: true, filename: 'commit-panel-with-staged-files');
});

test('commit panel shows no staged files message', function () {
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

    $page->assertSee('No staged files');
    $page->screenshot(fullPage: true, filename: 'commit-panel-no-staged-files');
});

test('commit panel displays amend checkbox', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
    ]);

    $page = visit('/');

    $page->assertSee('Amend previous commit');
    $page->screenshot(fullPage: true, filename: 'commit-panel-amend-option');
});
