<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('diff viewer shows empty state when no file selected', function () {
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

    $page->assertSee('No file selected');
    $page->screenshot(fullPage: true, filename: 'diff-viewer-empty-state');
});

test('diff viewer displays file changes when available', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
    ]);

    $page = visit('/');

    $page->assertSee('README.md');
    $page->screenshot(fullPage: true, filename: 'diff-viewer-with-changes');
});

test('diff viewer shows clean state for files without changes', function () {
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

    $page->assertSee('No file selected');
    $page->screenshot(fullPage: true, filename: 'diff-viewer-no-changes');
});
