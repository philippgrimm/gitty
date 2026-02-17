# Testing Guide

Comprehensive guide to the gitty test infrastructure, patterns, and best practices.

## Table of Contents

- [Test Overview](#test-overview)
- [Running Tests](#running-tests)
- [Test Organization](#test-organization)
- [Test Helpers](#test-helpers)
  - [GitTestHelper](#gittesthelper)
  - [GitOutputFixtures](#gitoutputfixtures)
  - [BrowserTestHelper](#browsertesthelper)
- [Testing Patterns](#testing-patterns)
  - [Testing Git Services](#testing-git-services)
  - [Testing Livewire Components](#testing-livewire-components)
  - [Testing DTOs](#testing-dtos)
  - [Browser Testing](#browser-testing)
- [Writing New Tests](#writing-new-tests)
- [Code Formatting](#code-formatting)

## Test Overview

gitty uses **Pest 4** as its testing framework. The test suite includes:

- **60 Feature tests** in `tests/Feature/`
- **3 Unit tests** in `tests/Unit/`
- **16 Browser tests** in `tests/Browser/`
- **Total: 79 test files** covering services, components, DTOs, and full-page integration

All tests follow Pest's functional syntax with `test()` and `expect()` assertions.

## Running Tests

Run all tests with compact output:

```bash
php artisan test --compact
```

Run a specific test file:

```bash
php artisan test --compact tests/Feature/Services/GitServiceTest.php
```

Run tests matching a filter:

```bash
php artisan test --compact --filter=stageFile
```

Run all tests in a directory:

```bash
php artisan test --compact tests/Feature/Services/
```

Run browser tests only:

```bash
php artisan test --compact tests/Browser/
```

## Test Organization

### Directory Structure

```
tests/
├── Browser/                    # Browser tests (Pest 4 browser testing)
│   ├── Components/             # Component-level browser tests
│   ├── Integration/            # Full-page integration tests
│   ├── Helpers/                # BrowserTestHelper
│   ├── Pest.php                # Browser test configuration
│   └── SmokeTest.php           # Homepage smoke test
├── Feature/                    # Feature tests
│   ├── Livewire/               # Livewire component tests
│   │   ├── Concerns/           # Trait tests (HandlesGitOperations)
│   │   ├── StagingPanelTest.php
│   │   ├── CommitPanelTest.php
│   │   ├── DiffViewerTest.php
│   │   └── ...
│   ├── Services/               # Service-level tests
│   │   ├── GitServiceTest.php
│   │   ├── StagingServiceTest.php
│   │   ├── BranchServiceTest.php
│   │   └── ...
│   └── ...                     # Other feature tests
├── Unit/                       # Unit tests
│   ├── DTOs/                   # DTO parsing tests
│   │   ├── ChangedFileTest.php
│   │   └── AheadBehindTest.php
│   └── Exceptions/             # Exception tests
├── Helpers/                    # Test helpers
│   └── GitTestHelper.php       # Git repo scaffolding
├── Mocks/                      # Mock data
│   └── GitOutputFixtures.php   # Fixed git output
├── Pest.php                    # Pest configuration
└── TestCase.php                # Base test case
```

### Naming Conventions

- **Feature tests**: `{ComponentName}Test.php` or `{ServiceName}Test.php`
- **Unit tests**: `{ClassName}Test.php`
- **Browser tests**: `{ComponentName}BrowserTest.php` or `{Feature}Test.php`
- **Test names**: Use descriptive strings with `test('it does something', ...)`

## Test Helpers

### GitTestHelper

**File:** `tests/Helpers/GitTestHelper.php`

Scaffolds temporary git repositories for service tests. Provides methods to create repos, add files, modify files, and simulate git states (conflicts, detached HEAD).

#### Methods

##### `createTestRepo(string $path): void`

Creates a fresh git repository with initial commit.

```php
use Tests\Helpers\GitTestHelper;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    GitTestHelper::createTestRepo($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});
```

**What it does:**
1. Deletes existing directory if present
2. Creates new directory
3. Runs `git init`
4. Configures `user.email` and `user.name`
5. Creates `README.md` with initial content
6. Commits with message "Initial commit"

##### `addTestFiles(string $repoPath, array $files): void`

Adds new files to the repository and stages them (does NOT commit).

```php
GitTestHelper::addTestFiles($this->testRepoPath, [
    'src/App.php' => '<?php\n\nclass App {}',
    'config/app.php' => '<?php\n\nreturn [];',
]);

// Files are now staged, ready to commit or test staging operations
```

##### `modifyTestFiles(string $repoPath, array $files): void`

Modifies existing files (does NOT stage or commit). Throws exception if file doesn't exist.

```php
GitTestHelper::modifyTestFiles($this->testRepoPath, [
    'README.md' => "# Updated Title\n\nNew content.",
]);

// File is modified in working tree, not staged
```

##### `createConflict(string $repoPath): void`

Creates a merge conflict by:
1. Creating a new branch `conflict-branch`
2. Adding `conflict.txt` with content from branch
3. Switching back to original branch
4. Adding `conflict.txt` with different content
5. Attempting to merge `conflict-branch` (leaves repo in conflicted state)

```php
GitTestHelper::createConflict($this->testRepoPath);

$service = new GitService($this->testRepoPath);
$status = $service->status();

expect($status->changedFiles->first()->isUnmerged())->toBeTrue();
```

##### `createDetachedHead(string $repoPath): void`

Puts the repository in detached HEAD state by checking out the current commit SHA.

```php
GitTestHelper::createDetachedHead($this->testRepoPath);

$service = new GitService($this->testRepoPath);
expect($service->isDetachedHead())->toBeTrue();
```

##### `cleanupTestRepo(string $path): void`

Deletes the test repository directory. Call in `afterEach()` to clean up.

```php
afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});
```

### GitOutputFixtures

**File:** `tests/Mocks/GitOutputFixtures.php`

Provides deterministic git command output for testing DTO parsing without real git operations. All output uses git's porcelain v2 format (machine-readable, stable across versions).

#### Status Fixtures

- `statusClean()`: Clean working tree, no changes
- `statusWithUnstagedChanges()`: 2 modified files (unstaged)
- `statusWithStagedChanges()`: 1 modified + 1 added (staged)
- `statusWithMixedChanges()`: Mixed staged/unstaged/untracked files
- `statusWithUntrackedFiles()`: 3 untracked files
- `statusWithSingleUntrackedFile()`: 1 untracked file
- `statusWithDeletedFiles()`: Staged and unstaged deletions
- `statusWithRenamedFiles()`: Renamed file (R100 similarity)
- `statusWithConflict()`: Unmerged file (UU status)
- `statusDetachedHead()`: Detached HEAD state
- `statusAheadBehind()`: Branch ahead by 3, behind by 2

#### Log Fixtures

- `logOneline()`: 6 commits in oneline format
- `logWithDetails()`: 3 commits with full details (author, date, message)
- `showCommit()`: Single commit with diff

#### Diff Fixtures

- `diffUnstaged()`: Unstaged changes to README.md
- `diffStaged()`: Staged changes to src/App.php
- `diffUntracked()`: New file diff

#### Branch Fixtures

- `branchList()`: Local and remote branches
- `branchListVerbose()`: Branches with commit messages

#### Other Fixtures

- `stashList()`: 3 stash entries
- `remoteList()`: 2 remotes (origin, upstream)
- `remoteListVerbose()`: Remotes with tracking info
- `tagList()`: 5 tags

#### Usage Example

```php
use Tests\Mocks\GitOutputFixtures;
use Illuminate\Support\Facades\Process;

test('it parses porcelain v2 status with staged changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusWithStagedChanges(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $status = $service->status();

    expect($status->changedFiles)->toHaveCount(2)
        ->and($status->changedFiles->first()->indexStatus)->toBe('M')
        ->and($status->changedFiles->last()->indexStatus)->toBe('A');
});
```

### BrowserTestHelper

**File:** `tests/Browser/Helpers/BrowserTestHelper.php`

Provides utilities for browser tests.

#### Constants

- `MOCK_REPO_PATH`: `/tmp/gitty-test-repo` (standard test repo path)
- `SCREENSHOTS_PATH`: `tests/Browser/screenshots/` (screenshot output directory)

#### Methods

##### `setupMockRepo(): void`

Creates the `.git` directory for the mock repository (minimal setup for browser tests).

```php
use Tests\Browser\Helpers\BrowserTestHelper;

test('homepage loads successfully', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $page = visit('/');
    $page->assertSee('No Repository Selected');
});
```

##### `getCommonProcessFakes(): array`

Returns common `Process::fake()` patterns for git commands.

```php
Process::fake(BrowserTestHelper::getCommonProcessFakes());
```

##### `ensureScreenshotsDirectory(): void`

Creates the screenshots directory if it doesn't exist.

## Testing Patterns

### Testing Git Services

Git services extend `AbstractGitService` and require a valid repository path. Use `Process::fake()` to mock git commands.

**File:** `tests/Feature/Services/GitServiceTest.php`

#### Pattern 1: Validate Repository Path

```php
test('it validates repository path has .git directory', function () {
    expect(fn () => new GitService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});
```

#### Pattern 2: Mock Git Output with Fixtures

```php
use Tests\Mocks\GitOutputFixtures;
use Illuminate\Support\Facades\Process;

test('it parses porcelain v2 status with clean working tree', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusClean(),
    ]);

    $service = new GitService('/tmp/gitty-test-repo');
    $status = $service->status();

    expect($status)->toBeInstanceOf(GitStatus::class)
        ->and($status->branch)->toBe('main')
        ->and($status->upstream)->toBe('origin/main')
        ->and($status->changedFiles)->toHaveCount(0);
});
```

#### Pattern 3: Assert Git Commands Ran

```php
test('it stages a single file', function () {
    Process::fake();

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->stageFile('README.md');

    Process::assertRan("git add 'README.md'");
});
```

#### Pattern 4: Assert Command with Closure

```php
test('stageFiles runs git add with multiple escaped paths', function () {
    Process::fake([
        'git add *' => Process::result(''),
    ]);

    $service = new StagingService('/tmp/gitty-test-repo');
    $service->stageFiles(['src/App.php', 'config/app.php', 'README.md']);

    Process::assertRan(function ($process) {
        return str_contains($process->command, 'git add')
            && str_contains($process->command, 'src/App.php')
            && str_contains($process->command, 'config/app.php')
            && str_contains($process->command, 'README.md');
    });
});
```

#### Pattern 5: Test with Real Git Repository

For complex operations (rebase, merge, conflict resolution), use `GitTestHelper` to create a real repository.

**File:** `tests/Feature/Services/ConflictServiceTest.php`

```php
use Tests\Helpers\GitTestHelper;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    GitTestHelper::createTestRepo($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});

test('it detects merge conflicts', function () {
    GitTestHelper::createConflict($this->testRepoPath);

    $service = new ConflictService($this->testRepoPath);
    $conflicts = $service->getConflicts();

    expect($conflicts)->toHaveCount(1)
        ->and($conflicts->first()->path)->toBe('conflict.txt');
});
```

### Testing Livewire Components

Livewire components are tested using `Livewire::test()` helper. Mock git operations with `Process::fake()`.

**File:** `tests/Feature/Livewire/StagingPanelTest.php`

#### Pattern 1: Component Mounting

```php
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts with repo path and loads status', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSee('README.md')
        ->assertSee('src/App.php');
});
```

#### Pattern 2: Test Component Actions

```php
test('component stages a file', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        "git add 'README.md'" => Process::result(''),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('stageFile', 'README.md')
        ->assertDispatched('status-updated');

    Process::assertRan("git add 'README.md'");
});
```

#### Pattern 3: Test Event Dispatching

```php
test('component dispatches file-selected event when file is clicked', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectFile', 'README.md', false)
        ->assertDispatched('file-selected', file: 'README.md', staged: false);
});
```

#### Pattern 4: Test Computed Properties

```php
test('component separates files into unstaged, staged, and untracked', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    $component = Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('unstagedFiles'))->toHaveCount(2);
    expect($component->get('stagedFiles'))->toHaveCount(2);
    expect($component->get('untrackedFiles'))->toHaveCount(1);
});
```

#### Pattern 5: Test Empty States

```php
test('component shows empty state when no changes', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSee('No changes');
});
```

#### Pattern 6: Test HTML Output

```php
test('staging panel renders smaller 8px status dots', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
    ]);

    Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSeeHtml('w-2 h-2 rounded-full')
        ->assertDontSeeHtml('w-2.5 h-2.5');
});
```

### Testing DTOs

DTOs are tested by providing raw git output and asserting parsed properties. No mocking required.

**File:** `tests/Unit/DTOs/ChangedFileTest.php`

#### Pattern 1: Test Constructor

```php
use App\DTOs\ChangedFile;

test('ChangedFile can be constructed with all properties', function () {
    $file = new ChangedFile(
        path: 'src/App.php',
        oldPath: null,
        indexStatus: 'M',
        worktreeStatus: '.',
    );

    expect($file->path)->toBe('src/App.php');
    expect($file->oldPath)->toBeNull();
    expect($file->indexStatus)->toBe('M');
    expect($file->worktreeStatus)->toBe('.');
});
```

#### Pattern 2: Test Helper Methods

```php
test('ChangedFile isStaged returns true for staged files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'M', worktreeStatus: '.');
    expect($file->isStaged())->toBeTrue();

    $file2 = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'A', worktreeStatus: '.');
    expect($file2->isStaged())->toBeTrue();
});

test('ChangedFile isStaged returns false for unstaged-only files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: '.', worktreeStatus: 'M');
    expect($file->isStaged())->toBeFalse();
});
```

#### Pattern 3: Test Status Labels

```php
test('ChangedFile statusLabel returns correct labels', function () {
    expect((new ChangedFile('f', null, 'M', '.'))->statusLabel())->toBe('modified');
    expect((new ChangedFile('f', null, 'A', '.'))->statusLabel())->toBe('added');
    expect((new ChangedFile('f', null, 'D', '.'))->statusLabel())->toBe('deleted');
    expect((new ChangedFile('f', null, 'R', '.'))->statusLabel())->toBe('renamed');
    expect((new ChangedFile('f', null, '?', '?'))->statusLabel())->toBe('untracked');
    expect((new ChangedFile('f', null, 'U', 'U'))->statusLabel())->toBe('unmerged');
});
```

#### Pattern 4: Test Factory Methods

For DTOs with factory methods (GitStatus, DiffResult, Commit), test parsing from raw git output.

```php
use App\DTOs\GitStatus;
use Tests\Mocks\GitOutputFixtures;

test('GitStatus parses porcelain v2 output', function () {
    $output = GitOutputFixtures::statusWithStagedChanges();
    $status = GitStatus::fromOutput($output);

    expect($status->branch)->toBe('main')
        ->and($status->upstream)->toBe('origin/main')
        ->and($status->aheadBehind->ahead)->toBe(1)
        ->and($status->aheadBehind->behind)->toBe(0)
        ->and($status->changedFiles)->toHaveCount(2);
});
```

### Browser Testing

Browser tests use Pest 4's browser testing capabilities to run full integration tests in real browsers.

**File:** `tests/Browser/SmokeTest.php`

#### Pattern 1: Basic Page Visit

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Browser\Helpers\BrowserTestHelper;

uses(RefreshDatabase::class);

test('homepage loads successfully', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->assertSee('No Repository Selected');
    $page->screenshot(fullPage: true, filename: 'homepage-smoke-test');
});
```

#### Pattern 2: Component Interaction

```php
test('staging panel stages a file', function () {
    BrowserTestHelper::setupMockRepo();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithUnstagedChanges()),
        "git add 'README.md'" => Process::result(''),
    ]);

    $page = visit('/');

    $page->click('[data-file="README.md"] [data-action="stage"]')
        ->waitForText('Staged')
        ->assertNoJavaScriptErrors();
});
```

#### Pattern 3: Screenshots

```php
test('diff viewer renders correctly', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
        'git diff' => Process::result(GitOutputFixtures::diffUnstaged()),
    ]);

    $page = visit('/');

    $page->click('[data-file="README.md"]')
        ->waitForText('README.md')
        ->screenshot(fullPage: true, filename: 'diff-viewer');
});
```

#### Pattern 4: Assert No JavaScript Errors

```php
test('no javascript errors on homepage', function () {
    BrowserTestHelper::setupMockRepo();

    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});
```

## Writing New Tests

Follow these steps when adding tests for new features:

### 1. Determine Test Type

- **Unit test**: Testing a single class in isolation (DTOs, exceptions)
- **Feature test**: Testing services or Livewire components with dependencies
- **Browser test**: Testing full-page interactions, UI behavior, JavaScript

### 2. Choose Test Location

- DTOs → `tests/Unit/DTOs/{ClassName}Test.php`
- Services → `tests/Feature/Services/{ServiceName}Test.php`
- Livewire components → `tests/Feature/Livewire/{ComponentName}Test.php`
- Browser tests → `tests/Browser/Components/{ComponentName}BrowserTest.php`

### 3. Create Test File

Use Artisan to create the test:

```bash
# Feature test
php artisan make:test --pest Feature/Services/MyServiceTest

# Unit test
php artisan make:test --pest --unit Unit/DTOs/MyDTOTest

# Browser test (create manually in tests/Browser/)
```

### 4. Set Up Test Fixtures

For service tests, use `Process::fake()` with `GitOutputFixtures`:

```php
use Tests\Mocks\GitOutputFixtures;
use Illuminate\Support\Facades\Process;

test('it does something', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => GitOutputFixtures::statusClean(),
    ]);

    // Test code here
});
```

For tests requiring real git operations, use `GitTestHelper`:

```php
use Tests\Helpers\GitTestHelper;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    GitTestHelper::createTestRepo($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});
```

### 5. Write Descriptive Test Names

Use clear, descriptive test names that explain what is being tested:

```php
// Good
test('it stages a single file', function () { ... });
test('component dispatches status-updated event after staging', function () { ... });
test('ChangedFile isStaged returns true for staged files', function () { ... });

// Bad
test('stage file', function () { ... });
test('test event', function () { ... });
test('test DTO', function () { ... });
```

### 6. Use Pest's Fluent Assertions

Chain assertions for readability:

```php
expect($status)->toBeInstanceOf(GitStatus::class)
    ->and($status->branch)->toBe('main')
    ->and($status->upstream)->toBe('origin/main')
    ->and($status->changedFiles)->toHaveCount(2);
```

### 7. Test Edge Cases

Don't just test the happy path. Test:

- Empty input
- Invalid input
- Missing files
- Conflicted states
- Detached HEAD
- Files with spaces in names
- Empty arrays

### 8. Assert Git Commands Ran

When testing services, verify the correct git commands were executed:

```php
Process::assertRan("git add 'README.md'");

// Or with a closure for complex assertions
Process::assertRan(function ($process) {
    return str_contains($process->command, 'git add')
        && str_contains($process->command, 'README.md');
});
```

### 9. Test Event Dispatching

For Livewire components, verify events are dispatched:

```php
Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
    ->call('stageFile', 'README.md')
    ->assertDispatched('status-updated');
```

### 10. Run Tests Before Committing

Always run tests before committing:

```bash
php artisan test --compact
```

Run only the tests you added:

```bash
php artisan test --compact --filter=MyNewTest
```

## Code Formatting

Before committing, format your test files with Pint:

```bash
vendor/bin/pint --dirty --format agent
```

This ensures your tests match the project's code style (PSR-12 with Laravel conventions).

## Additional Resources

- **Pest Documentation**: https://pestphp.com/docs
- **Laravel Testing**: https://laravel.com/docs/testing
- **Livewire Testing**: https://livewire.laravel.com/docs/testing
- **Process Faking**: https://laravel.com/docs/processes#faking-processes
