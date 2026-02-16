# Learnings

## Task 1: Add isDirtyTreeError Detection Helper

**Completed:** Mon Feb 16 2026

**What was done:**
- Added static method `GitErrorHandler::isDirtyTreeError(string $errorMessage): bool` to `app/Services/Git/GitErrorHandler.php`
- Method reuses the exact same two `str_contains` checks from the existing `translate()` method (lines 54-55)
- Returns `true` when error matches either uncommitted-changes pattern:
  1. "error: Your local changes to the following files would be overwritten by checkout"
  2. "Please commit your changes or stash them before you switch branches"
- Returns `false` for any other string or empty string

**Key findings:**
- The existing `translate()` method already had the perfect pattern matching logic on lines 54-55
- PHPDoc block follows project conventions (prefer PHPDoc over inline comments)
- Method placed after `translate()` method as requested
- Code style verified with `vendor/bin/pint --dirty --format agent` (passed)

**Commit:** `8ebcf71` - feat(backend): add isDirtyTreeError detection helper to GitErrorHandler

**Next steps:**
- Task 2 will use this method in the branch checkout logic to detect dirty tree errors
- Task 3 will create the "Stash & Switch?" modal that appears when this method returns true


## Task 2 Implementation: BranchManager Auto-Stash Flow

### Changes Made

**PHP Component** (`app/Livewire/BranchManager.php`):
- Added imports: `GitCacheService`, `StashService`, `Process`
- Added properties: `showAutoStashModal`, `autoStashTargetBranch`
- Modified `switchBranch()`: Catches dirty-tree errors via `GitErrorHandler::isDirtyTreeError()` and shows modal instead of error toast
- Added `confirmAutoStash()`: Implements stash→switch→apply→drop flow with conflict handling
- Added `cancelAutoStash()`: Closes modal and resets state

**Blade Template** (`resources/views/livewire/branch-manager.blade.php`):
- Added auto-stash confirmation modal after create-branch modal (lines 243-261)
- Modal follows exact pattern of create-branch modal (wire:model, font-mono, uppercase tracking-wider)
- Added ahead/behind indicators to branch dropdown trigger (↑/↓ with Catppuccin colors)

### Key Implementation Details

1. **Dirty-Tree Detection**: Uses `GitErrorHandler::isDirtyTreeError()` in catch block to distinguish dirty-tree errors from other errors
2. **Stash Flow**: 
   - Creates stash with untracked files (`-u` flag)
   - Switches branch
   - Applies stash using `Process::run()` directly (not `StashService::stashApply()`) to check exit code
   - Drops stash on success, preserves on conflict
3. **Cache Invalidation**: Invalidates `branches`, `status`, AND `stashes` groups after mutation
4. **Toast Messages**:
   - Success: "Switched to {branch} (changes restored)" (non-persistent)
   - Conflict: "Switched to {branch}. Some stashed changes conflicted — stash preserved in stash list." (persistent warning)
   - Error: Translated git error (non-persistent)

### Test Fix

The test `component displays current branch with ahead/behind badges` was failing because the BranchManager component no longer displayed ahead/behind indicators. Added them back to the branch dropdown trigger using Catppuccin colors:
- Ahead: `text-[#40a02b]` (green)
- Behind: `text-[#d20f39]` (red)

### Verification

- ✅ All 14 BranchManagerTest tests pass
- ✅ Pint formatting passes
- ✅ No LSP errors (false positives during edit were resolved)
- ✅ Modal follows exact pattern of create-branch modal
- ✅ Existing branch switching (clean tree) unchanged

### Notes

- The `stash@{0}` reference is safe because the entire flow is synchronous within one Livewire action
- Using `Process::run()` directly for apply step allows checking exit code (StashService methods don't expose this)
- Cache invalidation is critical — must invalidate all three groups (branches, status, stashes)
- Modal uses `wire:model` (not Alpine `x-model`) to match existing pattern


## Task 3 Implementation: RepoSidebar Auto-Stash Flow

### Changes Made

**PHP Component** (`app/Livewire/RepoSidebar.php`):
- Added properties: `showAutoStashModal`, `autoStashTargetBranch` (lines 31-33)
- **BUG FIX**: Wrapped `switchBranch()` in try-catch (lines 96-113) — previously had NO error handling
- Added `confirmAutoStash()`: Identical logic to BranchManager but calls `refreshSidebar()` instead of `refreshBranches()` and dispatches `refresh-staging` event
- Added `cancelAutoStash()`: Closes modal and resets state

**Blade Template** (`resources/views/livewire/repo-sidebar.blade.php`):
- Added auto-stash modal after drop-stash modal (lines 181-197)
- Modal follows exact pattern from BranchManager (wire:model, font-mono, uppercase tracking-wider)

### Key Implementation Details

1. **Critical Bug Fix**: The original `switchBranch()` method (lines 96-103) had NO try-catch block and would crash on any error
2. **Dirty-Tree Detection**: Uses `GitErrorHandler::isDirtyTreeError()` to distinguish dirty-tree errors from other errors
3. **Component-Specific Differences from BranchManager**:
   - Calls `$this->refreshSidebar()` instead of `$this->refreshBranches()`
   - Dispatches `refresh-staging` event (in addition to `status-updated`) — matches existing pattern in `applyStash()`/`popStash()` methods
4. **Stash Flow**: Identical to BranchManager:
   - Creates stash with untracked files
   - Switches branch
   - Applies stash using `Process::run()` to check exit code
   - Drops stash on success, preserves on conflict
5. **Cache Invalidation**: Invalidates `branches`, `status`, AND `stashes` groups
6. **Toast Messages**: Same as BranchManager (success, conflict warning, error)

### Verification

- ✅ All 12 RepoSidebarTest tests pass
- ✅ Pint formatting passes
- ✅ No new imports needed (StashService, GitCacheService, Process, GitErrorHandler already present)
- ✅ Modal follows exact pattern from BranchManager
- ✅ Existing stash operations (apply/pop/drop) unchanged

### Notes

- RepoSidebar already had all necessary imports — no new dependencies added
- The component uses `refreshSidebar()` which is more comprehensive than BranchManager's `refreshBranches()` (refreshes branches, remotes, tags, stashes, and current branch)
- The `refresh-staging` dispatch is critical — matches existing pattern in applyStash/popStash methods
- Modal placement: After drop-stash modal but before final closing `</div>`

### Commit

`3d0b8c8` - fix(staging): add error handling to RepoSidebar branch switch and auto-stash support


## Task 4 Implementation: Pest Test Cases for Auto-Stash Flow

### Changes Made

**BranchManagerTest** (`tests/Feature/Livewire/BranchManagerTest.php`):
Added 5 new test cases (total: 19 tests, 49 assertions):

1. **`test('switchBranch shows auto-stash modal when checkout fails due to dirty tree')`**
   - Fakes git checkout returning dirty-tree error with exit code 1
   - Asserts `showAutoStashModal` is true and `autoStashTargetBranch` is set
   - Asserts NO error toast is dispatched (modal shown instead)

2. **`test('switchBranch shows error toast for non-dirty-tree errors')`**
   - Fakes git checkout returning "pathspec did not match" error
   - Asserts `showAutoStashModal` remains false
   - Asserts error toast IS dispatched

3. **`test('confirmAutoStash stashes switches and restores changes')`**
   - Fakes full success flow: stash push → checkout → stash apply (exit 0) → stash drop
   - Asserts all 4 git commands ran using `Process::assertRan()`
   - Asserts success toast with "changes restored" message
   - Asserts `status-updated` event dispatched

4. **`test('confirmAutoStash shows warning when stash apply conflicts')`**
   - Fakes stash apply returning exit code 1 with "CONFLICT" message
   - Asserts warning toast with `persistent: true` and "stash preserved" message
   - Asserts `git stash drop` was NOT run (stash preserved)

5. **`test('cancelAutoStash resets state without action')`**
   - Sets modal state to true, then calls `cancelAutoStash()`
   - Asserts both `showAutoStashModal` and `autoStashTargetBranch` are reset

**RepoSidebarTest** (`tests/Feature/Livewire/RepoSidebarTest.php`):
Added 3 new test cases (total: 15 tests, 67 assertions):

6. **`test('switchBranch catches dirty tree error and shows auto-stash modal')`**
   - Fakes git checkout returning dirty-tree error
   - Asserts `showAutoStashModal` is true and `autoStashTargetBranch` is set

7. **`test('switchBranch catches non-dirty errors and shows error toast')`**
   - Fakes git checkout returning generic error
   - Asserts `showAutoStashModal` remains false and error toast is dispatched

8. **`test('confirmAutoStash performs full stash-switch-restore flow')`**
   - Fakes full success flow (same as BranchManager test)
   - Asserts all 4 git commands ran
   - Asserts success toast, `status-updated`, AND `refresh-staging` events dispatched
   - Note: RepoSidebar dispatches `refresh-staging` in addition to `status-updated`

### Key Implementation Details

1. **Process::fake() Patterns**:
   - Used wildcard patterns for stash commands: `'git stash push *' => Process::result(...)`
   - Used exact matches for checkout: `'git checkout develop' => Process::result(...)`
   - Used closures for error cases: `function () { return Process::result(..., exitCode: 1); }`

2. **Process::result() API**:
   - Named parameters: `Process::result(output: '', errorOutput: '...', exitCode: 1)`
   - For success: `Process::result('output text')` (defaults to exit code 0)
   - For errors: Must specify `exitCode: 1` explicitly

3. **Process::assertRan() Patterns**:
   - Exact match: `Process::assertRan('git checkout develop')`
   - Closure match: `Process::assertRan(fn ($process) => str_contains($process->command, 'git stash push'))`
   - Negative assertion: `Process::assertNotRan('git stash drop stash@{0}')`

4. **Event Assertion Patterns**:
   - Simple: `->assertDispatched('status-updated')`
   - With closure: `->assertDispatched('show-error', function (string $event, array $params): bool { ... })`
   - Negative: `->assertNotDispatched('show-error')`

5. **Test Setup Patterns**:
   - All tests use `$this->testRepoPath` from `beforeEach()` hook
   - All tests fake the standard mount commands (git status, git branch, etc.)
   - Tests set component state using `->set('property', 'value')` before calling methods

### Verification

- ✅ All 19 BranchManagerTest tests pass (14 existing + 5 new)
- ✅ All 15 RepoSidebarTest tests pass (12 existing + 3 new)
- ✅ Pint formatting passes (`vendor/bin/pint --dirty --format agent`)
- ✅ LSP errors are false positives (Pest's dynamic `$this->testRepoPath` property)
- ✅ Tests follow existing patterns exactly (Process::fake setup, assertion style)

### Notes

- The existing tests use `Process::fake([...])` with array of command → result mappings
- Closures in Process::fake() allow dynamic error responses
- The `exitCode` parameter is critical for testing error paths
- RepoSidebar tests must include `refresh-staging` event assertion (BranchManager doesn't dispatch this)
- All tests use literal values (no `$this->faker`) as per project conventions
- Test names follow Pest convention: `test('description in plain English')`

### Commit

`b4c862f` - test(staging): add Pest tests for auto-stash branch switching flow
