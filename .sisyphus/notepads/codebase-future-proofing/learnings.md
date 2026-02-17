
## Task 2: Pint Baseline (2026-02-17)
- **Finding:** Codebase already 100% Pint-compliant
- **Evidence:** Both `--format agent` and `--test` modes returned `{"result":"pass"}`
- **Implication:** Code style discipline is already strong; refactoring won't introduce style debt
- **Tooling:** Pint's `--format agent` mode provides clean JSON output for automation

## GitService + StagingService → AbstractGitService Migration

### Successfully Completed
Migrated both GitService and StagingService to extend AbstractGitService, eliminating ~40 lines of duplicate code.

### Migration Pattern
1. Change class: `class Service extends AbstractGitService`
2. Remove constructor (inherited)
3. Remove `private GitCacheService $cache;` property (inherited as `protected`)
4. Remove `use Illuminate\Support\Facades\Process;` import
5. Replace `Process::path($this->repoPath)->run('git ...')` with `$this->commandRunner->run('...')`
   - Remove "git" prefix from command
   - Pass file paths as array args for automatic escaping
   - Remove manual `escapeshellarg()` calls

### Command Conversion Examples
```php
// BEFORE
Process::path($this->repoPath)->run('git status --porcelain=v2 --branch')
// AFTER
$this->commandRunner->run('status --porcelain=v2 --branch')

// BEFORE (unsafe - no escaping)
Process::path($this->repoPath)->run("git add {$file}")
// AFTER (safe - automatic escaping)
$this->commandRunner->run('add', [$file])

// BEFORE (manual escaping)
$escapedFiles = array_map(fn($file) => escapeshellarg($file), $files);
$filesString = implode(' ', $escapedFiles);
Process::path($this->repoPath)->run("git add {$filesString}");
// AFTER (automatic escaping)
$this->commandRunner->run('add', $files)
```

### Test Updates - CRITICAL
**escapeshellarg adds quotes**, so Process::fake() and Process::assertRan() with exact strings need updating:

```php
// OLD TEST
Process::assertRan('git add README.md');
// NEW TEST
Process::assertRan("git add 'README.md'");  // Note the quotes!
```

Tests using callbacks with `str_contains()` work without changes:
```php
Process::assertRan(fn($p) => str_contains($p->command, 'git add') && str_contains($p->command, 'README.md'))
```

### Results
- GitService: 16/16 tests ✓
- StagingService: 16/16 tests ✓  
- Commit: da9b992
- Lines saved: ~40 (constructors + manual escaping)
- Security: Improved (automatic arg escaping)

### Next Services to Migrate
Still using old pattern (check with `grep "public function __construct" app/Services/Git/*.php`):
- CommitService
- BranchService
- DiffService
- RemoteService
- TagService
- RebaseService
- etc.


## Test Fixes for GitCommandRunner Migration (Task 9)

**Problem**: 3 tests in `StagingPanelTest.php` failed after migrating `StagingService` to use `GitCommandRunner` because `escapeshellarg()` wraps file arguments in single quotes.

**Solution**: Updated command string expectations in both `Process::fake()` keys and `Process::assertRan()` assertions:
- `'git add README.md'` → `"git add 'README.md'"`
- `'git reset HEAD README.md'` → `"git reset HEAD 'README.md'"`
- `'git checkout -- README.md'` → `"git checkout -- 'README.md'"`

**Key Insight**: When using exact string matching in `Process::fake()` and `Process::assertRan()`, the command string must match EXACTLY what `GitCommandRunner` produces, including the single quotes from `escapeshellarg()`.

**Tests using closure-based assertions** (e.g., `fn($p) => str_contains($p->command, 'git add')`) were unaffected because they match partial strings, not exact commands.

**Result**: All 18 StagingPanelTest tests now pass.

## BranchService + CommitService → AbstractGitService Migration (Task 10)

**Completed**: Successfully migrated both BranchService and CommitService to extend AbstractGitService, following the established pattern from GitService/StagingService.

### Changes to BranchService
1. Extended `AbstractGitService` (removed constructor + cache property)
2. Removed `use Illuminate\Support\Facades\Process;` import
3. Converted all Process calls to commandRunner:
   - `branches()`: `Process::path(...)->run('git branch -a -vv')` → `$this->commandRunner->run('branch -a -vv')`
   - `switchBranch()`: `run("git checkout {$name}")` → `run('checkout', [$name])`
   - `createBranch()`: `run("git checkout -b {$name} {$from}")` → `run('checkout -b', [$name, $from])`
   - `deleteBranch()`: `run("git branch {$flag} {$name}")` → `run("branch {$flag}", [$name])` (flag is internal, not user input)
   - `mergeBranch()`: `run("git merge {$name}")` → `run('merge', [$name])`
4. Updated test assertions for escapeshellarg quotes (e.g., `"git checkout 'feature/new-ui'"`)

### Changes to CommitService
1. Extended `AbstractGitService` (removed constructor + cache property)
2. Removed `use Illuminate\Support\Facades\Process;` import
3. **Removed `runCommit()` helper method** — replaced with direct `runOrFail()` calls
4. Converted all Process calls to commandRunner:
   - `commit()`: `runCommit("git commit -m \"{$message}\"", ...)` → `$this->commandRunner->runOrFail('commit -m', [$message], 'Git commit failed')`
   - `commitAmend()`: Similar pattern
   - `commitAndPush()`: Split into commit + push, both using `runOrFail()`
   - `lastCommitMessage()`: `Process::path(...)->run('git log -1 --pretty=%B')` → `$this->commandRunner->run('log -1 --pretty=%B')`
   - `undoLastCommit()`: `Process::path(...)->run('git reset --soft HEAD~1')` → `$this->commandRunner->runOrFail('reset --soft HEAD~1', [], 'Git reset failed')`
   - `cherryPick()`: `run("git cherry-pick {$sha}")` → `run('cherry-pick', [$sha])`
   - `cherryPickAbort()`: `run('git cherry-pick --abort')` → `runOrFail('cherry-pick --abort', [], 'Git cherry-pick abort failed')`
   - `cherryPickContinue()`: `run('git cherry-pick --continue')` → `runOrFail('cherry-pick --continue', [], 'Git cherry-pick continue failed')`
5. **Fixed `isLastCommitPushed()`**: Previously created `new GitService($this->repoPath)` internally. Refactored to use `$this->commandRunner->run('status --porcelain=v2 --branch')` directly, parsing the output inline with regex.
6. Updated test assertions for escapeshellarg quotes (commit messages: double quotes → single quotes)

### Test Updates for escapeshellarg
**BranchServiceTest:**
- `'git checkout feature/new-ui'` → `"git checkout 'feature/new-ui'"`
- `'git checkout -b feature/test main'` → `"git checkout -b 'feature/test' 'main'"`
- `'git branch -d feature/old'` → `"git branch -d 'feature/old'"`
- `'git branch -D feature/old'` → `"git branch -D 'feature/old'"`
- `'git merge feature/new-ui'` → `"git merge 'feature/new-ui'"`

**CommitServiceTest:**
- `'git commit -m "feat: add new feature"'` → `"git commit -m 'feat: add new feature'"`
- `'git commit --amend -m "feat: updated feature"'` → `"git commit --amend -m 'feat: updated feature'"`
- `'git commit -m "feat: add feature"'` → `"git commit -m 'feat: add feature'"`
- `'git push'` → unchanged (no args)
- `'git log -1 --pretty=%B'` → unchanged (no args)

### Results
- BranchService: 7/7 tests ✓
- CommitService: 5/5 tests ✓
- Lines removed: ~60 (2 constructors, 2 cache properties, 1 runCommit helper, Process imports)
- Security: Improved (automatic arg escaping for branch names, commit messages, SHAs)
- Dependency elimination: No more `new GitService()` calls inside CommitService

### Key Learning
**isLastCommitPushed() refactoring**: The original implementation created a new GitService instance to call `status()` and `aheadBehind()`. This was:
1. A hidden dependency (not visible in constructor)
2. Inefficient (creates cache, commandRunner, validates repo path again)
3. Duplicates the "status --porcelain=v2 --branch" parsing logic

**Better approach**: Call `$this->commandRunner->run('status --porcelain=v2 --branch')` directly and parse inline. This:
1. Uses existing commandRunner instance
2. Avoids duplicate object creation
3. Makes the dependency explicit
4. Eliminates tight coupling between services

### Remaining Services to Migrate
Still using old pattern (check with `grep "public function __construct" app/Services/Git/*.php`):
- DiffService
- RemoteService
- TagService
- RebaseService
- etc.

## RemoteService & StashService Migration (Completed)

Successfully migrated both services to extend `AbstractGitService`.

### RemoteService Changes:
- Removed constructor, Process import, and cache property
- Replaced all Process calls:
  - `remotes()`: `run('remote -v')` — no args
  - `push()`: `run('push', [$remote, $branch])` — both user input
  - `pull()`: `run('pull', [$remote, $branch])`
  - `fetch()`: `run('fetch', [$remote])`
  - `fetchAll()`: `run('fetch --all')` — no args

### StashService Changes:
- Removed constructor, Process import, and cache property
- Replaced all Process calls:
  - `stash()`: Used conditional subcommand `'stash push -u -m'` or `'stash push -m'`
  - `stashList()`: `run('stash list')`
  - `stashApply/Pop/Drop()`: Integer index interpolated into subcommand (safe)
  - `stashFiles()`: `run('stash push -u -m', [$message, '--', ...$paths])`
  - `generateStashMessage()`: `run('rev-parse --abbrev-ref HEAD')`

### Test Updates:
- **RemoteServiceTest**: Updated to expect single-quoted args:
  - `"git push 'origin' 'main'"`
  - `"git pull 'origin' 'main'"`
  - `"git fetch 'origin'"`
- **StashServiceTest**: Updated to expect single-quoted message:
  - `"git stash push -m 'WIP: testing feature'"`
  - `"git stash push -u -m 'WIP: testing feature'"`
- Closure-based assertions in stashFiles tests still work as expected

### Key Patterns:
- Flags in subcommand, user input in args array
- Integer values safe to interpolate
- escapeshellarg wraps in single quotes: `'value'`
- Array merge for variable-length args: `array_merge([$message, '--'], $paths)`

All tests pass. Migration complete.

## Task 13: Test Fix Success — DTO Migration Complete

### What Was Fixed
1. **StagingPanel.php** (lines 62-64): Changed array access `$file['key']` to property access `$file->key`
2. **CommitPanel.php** (line 50): Changed filter callback to use `$file->key` property access  
3. **GitServiceTest.php**: Updated assertions for new DTO types:
   - `aheadBehind` now expects `AheadBehind` object with `->ahead` and `->behind` properties
   - `changedFiles->first()['key']` changed to `changedFiles->first()->key`

### Why ArrayAccess Was Already Working
- `ChangedFile.php` already implemented `\ArrayAccess` interface (lines 7, 58-76)
- Used `readonly class` (PHP 8.4 supports interfaces on readonly classes)
- Set methods throw `LogicException('ChangedFile is immutable')` for proper readonly semantics

### Property Access Pattern (Best Practice)
**Prefer property access** (`$file->key`) over array access (`$file['key']`) for:
- Better IDE autocomplete and type inference
- Clearer intent in code review  
- Aligns with DTO nature of ChangedFile

**ArrayAccess retained for**:
- FileTreeBuilder compatibility (uses `->toArray()` internally)
- Legacy code during migration
- Collection methods that expect array-like behavior

### Test Results (100% Pass)
```
GitServiceTest:         16 passed (50 assertions) — 5.40s
StagingPanelTest:       18 passed (47 assertions) — 5.34s
CommitPanelTest:        21 passed (59 assertions) — 6.65s
BranchManagerTest:      19 passed (47 assertions) — 4.51s
StatusUpdatedEventTest:  7 passed (9 assertions)  — 2.61s

Total: 81 tests, 209 assertions — ALL PASS
```

### PHP 8.4 Capability Confirmed
```php
readonly class ChangedFile implements \ArrayAccess { ... }
```
This pattern works perfectly. The `readonly` modifier applies to **properties**, not the class's ability to implement interfaces.

### Files Changed (3)
- `app/Livewire/StagingPanel.php` — Property access in `refreshStatus()`
- `app/Livewire/CommitPanel.php` — Property access in `mount()` filter
- `tests/Feature/Services/GitServiceTest.php` — Updated assertions for DTO properties

### Migration Status
✅ **Task 11**: ChangedFile & AheadBehind DTOs created  
✅ **Task 12**: GitStatus refactored to use DTOs  
✅ **Task 13**: All tests fixed — DTO migration COMPLETE

## 2026-02-17: StagingPanel Trait Refactoring

Successfully refactored `app/Livewire/StagingPanel.php` to use the `HandlesGitOperations` trait:

### Results
- **Line count**: 351 → 285 lines (66 lines saved, 18.8% reduction)
- **Try/catch blocks**: 12 → 1 (only `refreshStatus()` keeps its own)
- **All 18 tests pass**: Behavior is 100% identical

### Implementation Pattern
Each of the 11 refactored methods now follows this clean pattern:

```php
public function stageFile(string $file): void
{
    $this->executeGitOperation(function () use ($file) {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->stageFile($file);
        $this->refreshStatus();
        $this->dispatchStatusUpdate();
    }, dispatchStatusUpdate: false);
}
```

### Key Decisions
1. **Created `dispatchStatusUpdate()` helper**: The trait's `executeGitOperation()` dispatches `status-updated` without parameters, but StagingPanel needs `stagedCount` and `aheadBehind` params. Solution was to add a private helper method.

2. **Always pass `dispatchStatusUpdate: false`**: This prevents the trait from dispatching the generic event, allowing manual dispatch with correct params inside the closure.

3. **Special cases preserved**:
   - `*Selected()` methods: Early return for empty arrays remains outside the closure
   - `stash*()` methods: Extra `stash-created` event dispatch preserved inside closure
   - `refreshStatus()`: Kept its own try/catch — different behavior (hash checking, early return)
   - `handleRefreshStaging()`: No changes needed — no try/catch to eliminate

### Refactored Methods (11 total)
- stageFile, unstageFile, stageAll, unstageAll
- discardFile, discardAll
- stageSelected, unstageSelected, discardSelected
- stashSelected, stashAll

### Pattern Benefits
- **Consistency**: All git operations now use the same error handling pattern
- **DRY**: Eliminated 11 identical try/catch blocks
- **Maintainability**: Single source of truth for error handling logic
- **Readability**: Each method is now 5-7 lines instead of 15-20

## Task 15: DiffViewer Refactoring (2026-02-17)

### Refactoring Pattern Applied
Successfully extracted duplicated DTO reconstruction logic from 4 methods in `DiffViewer.php`:
- Created `hydrateDiffFileAndHunk()` helper method (40 lines)
- Refactored `stageHunk()`, `unstageHunk()`, `stageSelectedLines()`, `unstageSelectedLines()`
- Applied `HandlesGitOperations` trait for standardized error handling
- Reduced file from 584 to 489 lines (95 line reduction)

### Key Implementation Details
1. **Helper Method Signature**: Returns tuple `array{0: \App\DTOs\DiffFile, 1: \App\DTOs\Hunk}`
2. **Trait Integration**: Used `dispatchStatusUpdate: false` since DiffViewer dispatches `refresh-staging` instead
3. **Arrow Function**: Used `fn()` syntax for single-line HunkLine mapping (cleaner than closure)
4. **Error Property**: DiffViewer already had `public string $error = ''` required by trait

### Pattern Reusability
This same refactoring pattern can be applied to any Livewire component with:
- Repeated DTO reconstruction from stored arrays
- Multiple methods with identical try/catch blocks
- Custom event dispatching (use `dispatchStatusUpdate: false`)

### Test Coverage
All 15 DiffViewerTest tests pass (62 assertions) — behavior 100% identical after refactoring.


## Task 8: SyncPanel Refactoring (2026-02-17)

### Successful Consolidation Pattern

Eliminated duplicated boilerplate across 5 sync methods by enhancing the `executeSyncOperation` helper to handle:
- Exit code checking (`if ($result->exitCode() !== 0)`)
- Error message extraction and throwing
- `operationOutput` storage
- `lastOperation` tracking
- State management (`isOperationRunning`)
- `refreshAheadBehindData()` calls
- Event dispatching

### Key Design Decisions

1. **Return Process from Callbacks**: Each sync method's callback now returns the Process result instead of handling it internally. This allows the helper to standardize exit code checking.

2. **Notification Timing**: Push/pull notifications happen AFTER `executeSyncOperation` returns, checking `$this->error` to only notify on success. Commit count is captured BEFORE the operation (when `aheadBehind['ahead']` still has the pre-push value).

3. **Branch Capture via Closure**: Used `use (&$currentBranch)` to capture branch name for notifications without having to return it from the helper.

4. **Layered Error Handling**: 
   - `executeSyncOperation` → delegates to trait's `executeGitOperation` (1 try/catch)
   - Sync methods throw `RuntimeException` on errors
   - Trait catches, translates via `GitErrorHandler`, sets `$this->error`, dispatches `show-error`

### Final Try/Catch Count

- **Before**: 7 blocks (5 in sync methods + 2 in mount/refreshAheadBehindData)
- **After**: 3 blocks (1 in trait's executeGitOperation + 2 in mount/refreshAheadBehindData)
- **Achievement**: ✅ ≤2 in component itself (mount + refreshAheadBehindData), with 1 delegated to trait

### Code Reduction

Each sync method reduced from ~15-20 lines to 3-10 lines:
- `syncFetch`: 9 lines → 3 lines (67% reduction)
- `syncFetchAll`: 9 lines → 3 lines (67% reduction)
- `syncForcePushWithLease`: 14 lines → 10 lines (29% reduction)
- `syncPush`: 20 lines → 17 lines (15% reduction, includes notification logic)
- `syncPull`: 17 lines → 14 lines (18% reduction, includes notification logic)

### Test Verification

All 11 SyncPanelTest tests pass, confirming:
- Identical behavior for all operations
- Correct error handling and message translation
- `isOperationRunning` flag management
- Event dispatching (status-updated, show-error)
- Detached HEAD prevention for push/pull/forcePush
- Operation output storage
