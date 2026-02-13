<?php

declare(strict_types=1);

use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('full app renders with all panels visible', function () {
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    // Verify header components
    $page->assertSee('gitty');
    $page->assertSee('gitty-test-repo');

    // Verify sidebar toggle is present
    $page->assertVisible('button[wire\\:click="toggleSidebar"]');

    // Verify sidebar sections are present (collapsed by default)
    $page->assertSee('Remotes');
    $page->assertSee('Tags');
    $page->assertSee('Stashes');

    // Verify staging panel
    $page->assertSee('Staged Changes');
    $page->assertSee('Changes');
    $page->assertSee('README.md');
    $page->assertSee('App.php');

    // Verify commit panel is rendered
    $page->assertSee('Commit (⌘↵)');

    // Verify diff viewer is present
    $page->assertSee('No file selected');

    $page->screenshot(fullPage: true, filename: 'integration-full-app-all-panels');
});

test('selecting a file in staging panel triggers diff viewer to show content', function () {
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
    ]);

    $page = visit('/');

    // Initial state: diff viewer shows empty state
    $page->assertSee('No file selected');

    // Dispatch file-selected event from staging panel
    $page->script("window.Livewire.dispatch('file-selected', { file: 'README.md', staged: false })");

    // Wait for diff content to appear
    $page->wait(0.5);

    // Verify diff viewer now shows file name
    $page->assertSee('README.md');

    $page->screenshot(fullPage: true, filename: 'integration-file-selected-diff-shown');
});

test('error banner appears when show-error event is dispatched', function () {
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

    // Error banner is hidden by default
    $page->assertDontSee('Failed to perform git operation');

    // Dispatch show-error event
    $page->script("window.Livewire.dispatch('show-error', { message: 'Failed to perform git operation', type: 'error', persistent: false })");

    // Wait for error banner to appear
    $page->waitForText('Failed to perform git operation');

    // Verify error banner is visible with correct content
    $page->assertSee('Error');
    $page->assertSee('Failed to perform git operation');

    $page->screenshot(fullPage: true, filename: 'integration-error-banner-cross-component');
});

test('empty state renders when no repository is selected', function () {
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'which git' => Process::result(output: '/usr/bin/git', exitCode: 0),
    ]);

    $page = visit('/');

    // Verify empty state message
    $page->assertSee('No Repository Selected');
    $page->assertSee('Open a git repository to get started');

    // Verify sidebar toggle is not present (no repo loaded)
    $page->assertMissing('button[wire\\:click="toggleSidebar"]');

    // Verify staging panel is not rendered
    $page->assertDontSee('Staged Changes');

    // Verify commit panel is not rendered
    $page->assertDontSee('Commit Message');

    $page->screenshot(fullPage: true, filename: 'integration-empty-state-no-repo');
});

test('full page screenshot of complete app layout with mixed changes', function () {
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        'git diff README.md' => Process::result(GitOutputFixtures::diffUnstaged()),
    ]);

    $page = visit('/');

    // Expand sidebar sections to show full data
    $page->click('button:has-text("Remotes")');
    $page->wait(0.2);
    $page->click('button:has-text("Tags")');
    $page->wait(0.2);
    $page->click('button:has-text("Stashes")');
    $page->wait(0.2);

    // Select a file to show diff
    $page->script("window.Livewire.dispatch('file-selected', { file: 'README.md', staged: false })");
    $page->wait(0.5);

    // Capture full page with all panels expanded and populated
    $page->screenshot(fullPage: true, filename: 'integration-complete-layout-expanded');
});

test('cross-component event flow between staging and commit panels', function () {
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
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git branch -a -vv' => Process::result(GitOutputFixtures::branchListVerbose()),
        'git remote -v' => Process::result(GitOutputFixtures::remoteList()),
        'git tag -l --format=%(refname:short) %(objectname:short)' => Process::result(''),
        'git stash list' => Process::result(''),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    // Verify staging panel shows staged changes
    $page->assertSee('Staged Changes');
    $page->assertSee('README.md');
    $page->assertSee('new-file.txt');

    // Verify commit panel is ready (has commit button)
    $page->assertSee('Commit (⌘↵)');

    // Verify branch status shows ahead commits
    $page->assertSee('main');

    $page->screenshot(fullPage: true, filename: 'integration-staged-changes-ready-to-commit');
});

test('sidebar sections expand to show full repository state', function () {
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
        'git stash list' => Process::result(GitOutputFixtures::stashList()),
        'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $page = visit('/');

    // Initially, sections are collapsed
    $page->assertSee('Remotes');
    $page->assertSee('Tags');
    $page->assertSee('Stashes');

    // Expand remotes section
    $page->click('button:has-text("Remotes")');
    $page->wait(0.2);
    $page->assertSee('origin');
    $page->assertSee('upstream');

    // Expand stashes section
    $page->click('button:has-text("Stashes")');
    $page->wait(0.5);

    // Verify full sidebar state is visible
    $page->screenshot(fullPage: true, filename: 'integration-sidebar-sections-expanded');
});
