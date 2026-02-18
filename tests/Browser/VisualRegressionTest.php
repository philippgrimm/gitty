<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

/**
 * Visual Regression Tests for Retro-Futurism Theme
 *
 * Captures screenshots of 5 key screens in both light and dark modes.
 */

// =============================================================================
// 1. EMPTY STATE (No repo selected, shows boot sequence result)
// =============================================================================

test('empty state - light mode', function () {
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    $page = visit('/');

    $page->assertSee('No Repository Selected');
    $page->assertSee('Open a git repository to get started');

    $page->screenshot(fullPage: true, filename: 'retro-theme-empty-state-light');
});

test('empty state - dark mode', function () {
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    $page = visit('/');

    // Toggle dark mode
    $page->script('document.documentElement.classList.add("dark")');
    $page->wait(0.2);

    $page->assertSee('No Repository Selected');
    $page->assertSee('Open a git repository to get started');

    $page->screenshot(fullPage: true, filename: 'retro-theme-empty-state-dark');
});

// =============================================================================
// 2. STAGING PANEL (With staged + unstaged files)
// =============================================================================

test('staging panel - light mode', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    $page->assertSee('Staged');
    $page->assertSee('Changes');
    $page->assertSee('README.md');

    $page->screenshot(fullPage: true, filename: 'retro-theme-staging-light');
});

test('staging panel - dark mode', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    // Toggle dark mode
    $page->script('document.documentElement.classList.add("dark")');
    $page->wait(0.2);

    $page->assertSee('Staged');
    $page->assertSee('Changes');
    $page->assertSee('README.md');

    $page->screenshot(fullPage: true, filename: 'retro-theme-staging-dark');
});

// =============================================================================
// 3. DIFF VIEWER (With a diff loaded)
// =============================================================================

test('diff viewer - light mode', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
        'git diff --cached README.md' => Process::result(''),
    ]);

    $page = visit('/');

    // Wait for file to potentially be selected (if the component auto-selects)
    $page->wait(0.3);

    $page->screenshot(fullPage: true, filename: 'retro-theme-diff-viewer-light');
});

test('diff viewer - dark mode', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
        'git diff --cached README.md' => Process::result(''),
    ]);

    $page = visit('/');

    // Toggle dark mode
    $page->script('document.documentElement.classList.add("dark")');
    $page->wait(0.3);

    $page->screenshot(fullPage: true, filename: 'retro-theme-diff-viewer-dark');
});

// =============================================================================
// 4. COMMIT PANEL (With commit message)
// =============================================================================

test('commit panel - light mode', function () {
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

    $page->assertSee('Commit (⌘↵)');

    $page->screenshot(fullPage: true, filename: 'retro-theme-commit-panel-light');
});

test('commit panel - dark mode', function () {
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

    // Toggle dark mode
    $page->script('document.documentElement.classList.add("dark")');
    $page->wait(0.2);

    $page->assertSee('Commit (⌘↵)');

    $page->screenshot(fullPage: true, filename: 'retro-theme-commit-panel-dark');
});

// =============================================================================
// 5. FULL APP LAYOUT (Complete app with repo open)
// =============================================================================

test('full app layout - light mode', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
        'git diff --cached README.md' => Process::result(''),
    ]);

    $page = visit('/');

    $page->assertSee('gitty-test-repo');
    $page->assertVisible('button[wire\\:click="toggleSidebar"]');

    $page->screenshot(fullPage: true, filename: 'retro-theme-full-app-light');
});

test('full app layout - dark mode', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $repo = Repository::create([
        'name' => 'gitty-test-repo',
        'path' => BrowserTestHelper::MOCK_REPO_PATH,
        'last_opened_at' => now(),
    ]);

    cache()->put('current_repo_id', $repo->id);

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
        'git diff --cached README.md' => Process::result(''),
    ]);

    $page = visit('/');

    // Toggle dark mode
    $page->script('document.documentElement.classList.add("dark")');
    $page->wait(0.2);

    $page->assertSee('gitty-test-repo');
    $page->assertVisible('button[wire\\:click="toggleSidebar"]');

    $page->screenshot(fullPage: true, filename: 'retro-theme-full-app-dark');
});
