# Auto-Stash on Branch Switch

## TL;DR

> **Quick Summary**: When the user switches branches with uncommitted changes, try the checkout first (git carries changes silently when possible). If git rejects the checkout due to conflicts, show a confirmation modal offering to stash → switch → restore changes automatically. If restoring conflicts, warn via toast and preserve the stash.
> 
> **Deliverables**:
> - Smart branch switching: try checkout → prompt on failure → auto-stash+switch+restore
> - Confirmation modal UI in BranchManager (header dropdown)
> - RepoSidebar bug fix: add missing try-catch + same auto-stash flow
> - Pest test coverage for all paths
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES - 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4 → Task 5

---

## Context

### Original Request
Explore adding auto-stash behavior when switching branches that have uncommitted changes: automatically stash, switch, then pop the stash.

### Interview Summary
**Key Discussions**:
- **Behavior**: Try `git checkout` first (git carries changes when files don't conflict). Only stash when git rejects the checkout. Show a confirmation modal before stashing.
- **Settings**: Always-on behavior, no toggle needed.
- **Conflict on pop**: Show warning toast and preserve stash in list for manual resolution.
- **Scope**: Include both BranchManager (header) and RepoSidebar (sidebar), plus fix RepoSidebar's missing try-catch.
- **Tests**: Pest tests after implementation.

**Research Findings**:
- `StashService` already has full stash/pop/apply/drop infrastructure
- `GitErrorHandler::translate()` already detects "uncommitted changes" error pattern
- `stashPop()` and `stashApply()` don't check exit codes — need to handle conflict detection at the caller level
- GitKraken/Tower/Fork all use similar prompt-then-stash patterns
- Git allows carrying changes silently when modified files are identical across branches

### Metis Review
**Identified Gaps** (addressed):
- **stashApply/stashPop don't check exit codes**: Use `Process::run()` result directly in the Livewire component to detect conflicts, rather than modifying existing service methods.
- **Stash indexing safety**: Auto-stash always creates stash at `stash@{0}` and the entire stash→checkout→apply flow is synchronous within one Livewire action, so index 0 is safe. Use a distinctive message prefix ("Auto-stash: ") for identification.
- **Modal pattern**: BranchManager already uses `wire:model` for its create-branch modal — use the same pattern for the auto-stash modal.
- **RepoSidebar missing error handling**: `switchBranch()` at line 96 has no try-catch — would throw unhandled exception on dirty checkout.

---

## Work Objectives

### Core Objective
Add smart auto-stash behavior to branch switching so users never get blocked by uncommitted changes — try checkout first, prompt to stash only when needed, and automatically restore changes after switching.

### Concrete Deliverables
- Modified `BranchManager` component with auto-stash flow and confirmation modal
- Modified `RepoSidebar` component with try-catch fix and auto-stash flow
- Confirmation modal UI (flux:modal) in branch-manager blade template
- Confirmation modal UI in repo-sidebar blade template
- Pest tests covering: normal switch, auto-stash success, conflict on restore, user cancellation

### Definition of Done
- [ ] Switching branches with clean tree works as before (no modal, no stash)
- [ ] Switching branches with dirty tree that doesn't conflict carries changes silently (no modal)
- [ ] Switching branches with dirty tree that conflicts shows confirmation modal
- [ ] Confirming the modal: stashes, switches, restores changes, shows success toast
- [ ] If restore conflicts: switches successfully, shows warning toast, stash preserved in list
- [ ] Canceling the modal: nothing happens, stays on current branch
- [ ] Both BranchManager and RepoSidebar support the same flow
- [ ] RepoSidebar no longer crashes on dirty checkout (bug fix)
- [ ] All existing tests still pass
- [ ] New tests cover all paths

### Must Have
- Try checkout first (don't stash unnecessarily)
- Confirmation modal before stashing
- Success toast on successful stash+switch+restore
- Warning toast on conflict (stash preserved)
- Error handling for all git operations
- Cache invalidation for branches, status, and stashes

### Must NOT Have (Guardrails)
- No settings toggle for this feature (always on)
- No modification to existing `StashService` methods (use them as-is, handle exit codes at caller)
- No auto-pop without user confirmation (always show modal first)
- No 3-way merge UI or conflict resolution UI (out of scope — just warn and preserve stash)
- No "include untracked files" checkbox in the modal (always include untracked via `-u` flag, matching existing `stash()` behavior)
- No changes to the `GitErrorHandler::translate()` method
- Do NOT use `<flux:badge>` for anything — it doesn't match Catppuccin colors
- Do NOT add `!rounded-*` hacks — use Flux components properly
- Do NOT modify existing test files unless fixing imports

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.

### Test Decision
- **Infrastructure exists**: YES (Pest 4 with Laravel Process faking)
- **Automated tests**: Tests after implementation
- **Framework**: Pest via `php artisan test --compact`

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

Every task includes QA scenarios using Bash (running Pest tests) as the primary verification tool. The executing agent will run `php artisan test --compact --filter=<test>` to verify each deliverable.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
├── Task 1: BranchService — add isDirtyTreeError() detection helper
└── (sequential within wave)

Wave 2 (After Wave 1):
├── Task 2: BranchManager — auto-stash flow + modal UI
└── Task 3: RepoSidebar — bug fix + auto-stash flow + modal UI (PARALLEL with Task 2)

Wave 3 (After Wave 2):
└── Task 4: Pest tests for all paths

Wave 4 (After Wave 3):
└── Task 5: Final verification — run full test suite
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | 2, 3 | None |
| 2 | 1 | 4 | 3 |
| 3 | 1 | 4 | 2 |
| 4 | 2, 3 | 5 | None |
| 5 | 4 | None | None |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Agents |
|------|-------|-------------------|
| 1 | 1 | task(category="quick", load_skills=["livewire-development"]) |
| 2 | 2, 3 | task(category="unspecified-low", load_skills=["livewire-development", "fluxui-development"]) — parallel |
| 3 | 4 | task(category="unspecified-low", load_skills=["pest-testing", "livewire-development"]) |
| 4 | 5 | task(category="quick", load_skills=["pest-testing"]) |

---

## TODOs

- [x] 1. Add dirty-tree error detection helper to GitErrorHandler

  **What to do**:
  - Add a static method `GitErrorHandler::isDirtyTreeError(string $errorMessage): bool` that returns `true` when the error matches the uncommitted-changes patterns already identified in `translate()` (lines 54-56).
  - This allows callers to distinguish "dirty tree" errors from other git failures programmatically, without parsing translated user-friendly strings.
  - Keep the existing `translate()` method unchanged.

  **Must NOT do**:
  - Do NOT modify the existing `translate()` method
  - Do NOT add any new error patterns — reuse the two existing `str_contains` checks

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single method addition to an existing class, ~10 lines of code
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Needed for understanding the Laravel service pattern and Process facade usage

  **Parallelization**:
  - **Can Run In Parallel**: NO (foundation for Tasks 2 and 3)
  - **Parallel Group**: Wave 1 (solo)
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/Git/GitErrorHandler.php:53-57` — Existing uncommitted-changes detection patterns. The two `str_contains` checks on lines 54-55 are the exact patterns to extract into the new `isDirtyTreeError()` method.

  **API/Type References**:
  - `app/Services/Git/GitErrorHandler.php:12` — `translate()` method signature showing the static method pattern to follow.

  **Test References**:
  - `tests/Feature/Services/BranchServiceTest.php` — Shows how Process is faked for git commands. The new helper should be testable with simple string input (no Process mocking needed).

  **Acceptance Criteria**:

  - [ ] `GitErrorHandler::isDirtyTreeError('error: Your local changes to the following files would be overwritten by checkout')` returns `true`
  - [ ] `GitErrorHandler::isDirtyTreeError('Please commit your changes or stash them before you switch branches')` returns `true`
  - [ ] `GitErrorHandler::isDirtyTreeError('some other error')` returns `false`
  - [ ] `GitErrorHandler::isDirtyTreeError('')` returns `false`
  - [ ] Existing `translate()` method is untouched

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: isDirtyTreeError detects both error patterns
    Tool: Bash (php artisan tinker)
    Preconditions: Application is bootable
    Steps:
      1. Run: php artisan tinker --execute="echo App\Services\Git\GitErrorHandler::isDirtyTreeError('error: Your local changes to the following files would be overwritten by checkout') ? 'true' : 'false';"
      2. Assert: output contains "true"
      3. Run: php artisan tinker --execute="echo App\Services\Git\GitErrorHandler::isDirtyTreeError('Please commit your changes or stash them before you switch branches') ? 'true' : 'false';"
      4. Assert: output contains "true"
      5. Run: php artisan tinker --execute="echo App\Services\Git\GitErrorHandler::isDirtyTreeError('some random error') ? 'true' : 'false';"
      6. Assert: output contains "false"
    Expected Result: Method correctly distinguishes dirty-tree errors from other errors
    Evidence: Terminal output captured
  ```

  **Commit**: YES
  - Message: `feat(backend): add isDirtyTreeError detection helper to GitErrorHandler`
  - Files: `app/Services/Git/GitErrorHandler.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [x] 2. Add auto-stash flow and confirmation modal to BranchManager

  **What to do**:

  **PHP Component** (`app/Livewire/BranchManager.php`):
  - Add two public properties: `public bool $showAutoStashModal = false;` and `public string $autoStashTargetBranch = '';`
  - Modify `switchBranch(string $name)` method:
    1. Keep the try-catch structure.
    2. In the catch block, check `GitErrorHandler::isDirtyTreeError($e->getMessage())`.
    3. If true: set `$this->autoStashTargetBranch = $name` and `$this->showAutoStashModal = true` (instead of showing error toast).
    4. If false: keep existing error toast behavior.
  - Add new method `confirmAutoStash(): void`:
    1. Close modal: `$this->showAutoStashModal = false`
    2. Create stash: `$stashService->stash("Auto-stash: switching to {$this->autoStashTargetBranch}", true)` (true = include untracked)
    3. Switch branch: `$branchService->switchBranch($this->autoStashTargetBranch)`
    4. Try to restore: Run `Process::path($this->repoPath)->run("git stash apply stash@{0}")` directly (not via StashService, because we need the exit code)
    5. If exit code === 0: Drop the stash via `$stashService->stashDrop(0)`, dispatch success toast: `"Switched to {branch} (changes restored)"`
    6. If exit code !== 0: Do NOT drop stash. Dispatch warning toast (persistent): `"Switched to {branch}. Some stashed changes conflicted — stash preserved in stash list."`
    7. Invalidate cache groups: `branches`, `status`, `stashes`
    8. Dispatch `status-updated` event
    9. Reset `$this->autoStashTargetBranch = ''`
  - Add method `cancelAutoStash(): void` that resets `showAutoStashModal` and `autoStashTargetBranch`.

  **Blade Template** (`resources/views/livewire/branch-manager.blade.php`):
  - Add a `<flux:modal wire:model="showAutoStashModal">` before the closing `</div>` of the component (after the create-branch modal at line 231).
  - Follow the exact pattern of the create-branch modal (lines 194-231):
    - `<flux:heading size="lg" class="font-mono uppercase tracking-wider">Stash & Switch?</flux:heading>`
    - `<flux:subheading class="font-mono">You have uncommitted changes that conflict with <span class="text-[var(--text-primary)] font-bold">{{ $autoStashTargetBranch }}</span>. Stash them and switch?</flux:subheading>`
    - Two buttons: `<flux:button variant="ghost" wire:click="cancelAutoStash">Cancel</flux:button>` and `<flux:button variant="primary" wire:click="confirmAutoStash" class="uppercase tracking-wider">Stash & Switch</flux:button>`

  **Must NOT do**:
  - Do NOT modify `BranchService::switchBranch()` — keep it as-is
  - Do NOT modify `StashService` methods
  - Do NOT add settings/preferences
  - Do NOT add "include untracked" checkbox to the modal — always include untracked
  - Do NOT add wire:loading states to the modal buttons (keep it simple)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Moderate scope — PHP method changes + Blade modal addition, but follows existing patterns closely
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Core skill for modifying Livewire component, wire:model, dispatch events
    - `fluxui-development`: Needed for flux:modal, flux:heading, flux:subheading, flux:button components
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No new styling needed — follows existing patterns exactly

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Task 3)
  - **Blocks**: Task 4
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - `app/Livewire/BranchManager.php:71-85` — Current `switchBranch()` method with try-catch and error dispatch. Modify the catch block to check `isDirtyTreeError()`.
  - `app/Livewire/BranchManager.php:87-103` — `createBranch()` method showing the modal-close + action + refresh + dispatch pattern to follow for `confirmAutoStash()`.
  - `app/Livewire/BranchManager.php:22-23` — Existing boolean modal property `showCreateModal` and string property `newBranchName` — follow same pattern for `showAutoStashModal` and `autoStashTargetBranch`.

  **API/Type References**:
  - `app/Services/Git/GitErrorHandler.php` — `isDirtyTreeError()` static method (from Task 1). Use as: `GitErrorHandler::isDirtyTreeError($e->getMessage())`
  - `app/Services/Git/StashService.php:25-37` — `stash(string $message, bool $includeUntracked): void`. Call with `$stashService->stash("Auto-stash: switching to {$branch}", true)`.
  - `app/Services/Git/StashService.php:69-73` — `stashDrop(int $index): void`. Call with `$stashService->stashDrop(0)` after successful apply.

  **UI References**:
  - `resources/views/livewire/branch-manager.blade.php:194-231` — Create-branch modal. Copy this exact structure for the auto-stash modal: `<flux:modal wire:model="...">`, heading, subheading, button row.
  - `resources/views/livewire/staging-panel.blade.php:337-372` — Discard-changes modal. Alternative pattern reference showing Alpine.js `x-model` approach (NOT recommended here — use `wire:model` for consistency with BranchManager).

  **Acceptance Criteria**:

  - [ ] Clean branch switch (no changes) still works without modal
  - [ ] Dirty branch switch where git carries changes works without modal
  - [ ] Dirty branch switch where git rejects shows auto-stash modal
  - [ ] Modal shows target branch name
  - [ ] Clicking "Cancel" closes modal, no state change
  - [ ] Clicking "Stash & Switch" performs stash → switch → apply → drop → success toast
  - [ ] If apply conflicts: switch succeeds, warning toast shown, stash preserved
  - [ ] `status-updated` event dispatched after successful switch
  - [ ] `vendor/bin/pint --dirty --format agent` passes

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Clean branch switch works as before
    Tool: Bash (pest)
    Preconditions: Application bootable, tests runnable
    Steps:
      1. Run: php artisan test --compact --filter=BranchManagerTest
      2. Assert: All existing tests pass
    Expected Result: No regression in existing branch switching
    Evidence: Test output captured

  Scenario: Auto-stash modal properties exist
    Tool: Bash (tinker)
    Steps:
      1. Run: php artisan tinker --execute="$r = new ReflectionClass(App\Livewire\BranchManager::class); echo implode(',', array_map(fn($p) => $p->getName(), $r->getProperties(ReflectionProperty::IS_PUBLIC)));"
      2. Assert: output contains "showAutoStashModal"
      3. Assert: output contains "autoStashTargetBranch"
    Expected Result: New properties are defined on the component
    Evidence: Terminal output captured
  ```

  **Commit**: YES (groups with Task 3)
  - Message: `feat(staging): add auto-stash flow to BranchManager with confirmation modal`
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [x] 3. Fix RepoSidebar error handling and add auto-stash flow

  **What to do**:

  **PHP Component** (`app/Livewire/RepoSidebar.php`):
  - Add two public properties: `public bool $showAutoStashModal = false;` and `public string $autoStashTargetBranch = '';`
  - Wrap `switchBranch()` method (line 96-103) in try-catch, following the pattern of `applyStash()` at lines 105-116:
    1. In the catch block, check `GitErrorHandler::isDirtyTreeError($e->getMessage())`.
    2. If true: set `$this->autoStashTargetBranch = $name` and `$this->showAutoStashModal = true`.
    3. If false: dispatch `show-error` event with translated error (matching the pattern used by `applyStash()`, `popStash()`, `dropStash()` in the same component).
  - Add `confirmAutoStash(): void` method — IDENTICAL logic to BranchManager's version:
    1. Close modal
    2. Stash with message `"Auto-stash: switching to {$this->autoStashTargetBranch}"`, include untracked
    3. Switch branch
    4. Try `git stash apply stash@{0}` via Process
    5. If success: drop stash, success toast
    6. If conflict: warning toast (persistent), keep stash
    7. Invalidate caches, dispatch `status-updated`, dispatch `refresh-staging`
    8. Call `$this->refreshSidebar()`
    9. Reset properties
  - Add `cancelAutoStash(): void` method.

  **Blade Template** (`resources/views/livewire/repo-sidebar.blade.php`):
  - Add a `<flux:modal wire:model="showAutoStashModal">` at the end of the component template (after the existing stash drop confirmation modal).
  - Same structure as BranchManager modal: heading "Stash & Switch?", subheading with target branch name, Cancel + "Stash & Switch" buttons.

  **Must NOT do**:
  - Do NOT refactor BranchManager and RepoSidebar to share a trait or base class (keep them independent, matching existing codebase patterns — each Livewire component is self-contained)
  - Do NOT modify existing stash methods (applyStash, popStash, dropStash)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Same scope as Task 2 — PHP + Blade changes following established patterns
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Livewire component modification, event dispatch, wire:model
    - `fluxui-development`: flux:modal component usage
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No new styling

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Task 2)
  - **Blocks**: Task 4
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - `app/Livewire/RepoSidebar.php:96-103` — Current `switchBranch()` WITHOUT try-catch. This is the bug to fix. Wrap in try-catch.
  - `app/Livewire/RepoSidebar.php:105-116` — `applyStash()` method WITH try-catch. Use this as the exact error-handling pattern to replicate.
  - `app/Livewire/RepoSidebar.php:118-129` — `popStash()` method. Another example of the try-catch + dispatch pattern.
  - `app/Livewire/BranchManager.php:71-85` — BranchManager's `switchBranch()` for reference on the auto-stash modal trigger logic (this is what Task 2 implements — replicate the same logic here).

  **UI References**:
  - `resources/views/livewire/repo-sidebar.blade.php:154-170` — Existing stash drop confirmation modal. Place the new auto-stash modal after this one.
  - `resources/views/livewire/branch-manager.blade.php:194-231` — Create-branch modal structure to replicate.

  **Acceptance Criteria**:

  - [ ] `switchBranch()` no longer throws unhandled exception on dirty checkout (bug fix)
  - [ ] Non-dirty-tree errors show error toast (same as other methods in this component)
  - [ ] Dirty-tree error shows auto-stash modal
  - [ ] `confirmAutoStash()` performs stash → switch → apply → drop flow
  - [ ] Conflict on apply shows warning toast (persistent) and preserves stash
  - [ ] `refreshSidebar()` called after successful switch
  - [ ] `status-updated` and `refresh-staging` events dispatched
  - [ ] `vendor/bin/pint --dirty --format agent` passes

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: RepoSidebar switchBranch no longer crashes on dirty tree
    Tool: Bash (pest)
    Steps:
      1. Run: php artisan test --compact --filter=RepoSidebarTest
      2. Assert: All tests pass including any new dirty-tree test
    Expected Result: No unhandled exception, error is caught and handled
    Evidence: Test output captured

  Scenario: Auto-stash modal exists in sidebar template
    Tool: Bash (grep)
    Steps:
      1. Run: grep -c "showAutoStashModal" resources/views/livewire/repo-sidebar.blade.php
      2. Assert: count > 0
      3. Run: grep -c "confirmAutoStash" app/Livewire/RepoSidebar.php
      4. Assert: count > 0
    Expected Result: Auto-stash modal and methods exist
    Evidence: Grep output captured
  ```

  **Commit**: YES (groups with Task 2)
  - Message: `fix(staging): add error handling to RepoSidebar branch switch and auto-stash support`
  - Files: `app/Livewire/RepoSidebar.php`, `resources/views/livewire/repo-sidebar.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [x] 4. Add Pest tests for auto-stash branch switching

  **What to do**:

  **BranchManager Tests** (`tests/Feature/Livewire/BranchManagerTest.php`):
  Add the following test cases to the existing test file:
  
  1. **`test('switchBranch shows auto-stash modal when checkout fails due to dirty tree')`**:
     - Fake Process: `git checkout feature` returns exit code 1 with error "Your local changes to the following files would be overwritten by checkout"
     - Call `switchBranch('feature')`
     - Assert `showAutoStashModal` is `true`
     - Assert `autoStashTargetBranch` is `'feature'`
     - Assert `show-error` event was NOT dispatched (modal shown instead)
  
  2. **`test('switchBranch shows error toast for non-dirty-tree errors')`**:
     - Fake Process: `git checkout feature` returns exit code 1 with error "pathspec 'feature' did not match"
     - Call `switchBranch('feature')`
     - Assert `showAutoStashModal` is `false`
     - Assert `show-error` event WAS dispatched
  
  3. **`test('confirmAutoStash stashes switches and restores changes')`**:
     - Fake Process: `git stash push -u -m "Auto-stash: switching to feature"` → success, `git checkout feature` → success, `git stash apply stash@{0}` → success (exit code 0), `git stash drop stash@{0}` → success
     - Set `autoStashTargetBranch` to `'feature'`
     - Call `confirmAutoStash()`
     - Assert all four git commands ran in order
     - Assert `show-error` dispatched with type `'success'`
     - Assert `showAutoStashModal` is `false`
     - Assert `status-updated` dispatched
  
  4. **`test('confirmAutoStash shows warning when stash apply conflicts')`**:
     - Fake Process: stash push → success, checkout → success, `git stash apply stash@{0}` → exit code 1 with "CONFLICT"
     - Call `confirmAutoStash()`
     - Assert `git stash drop` was NOT called (stash preserved)
     - Assert `show-error` dispatched with type `'warning'` and persistent `true`
     - Assert `status-updated` dispatched (branch DID switch)
  
  5. **`test('cancelAutoStash resets state without action')`**:
     - Set `showAutoStashModal` to `true`, `autoStashTargetBranch` to `'feature'`
     - Call `cancelAutoStash()`
     - Assert `showAutoStashModal` is `false`
     - Assert `autoStashTargetBranch` is `''`
     - Assert no git commands ran (beyond mount)

  **RepoSidebar Tests** (`tests/Feature/Livewire/RepoSidebarTest.php`):
  Add analogous test cases:
  
  6. **`test('switchBranch catches dirty tree error and shows auto-stash modal')`**
  7. **`test('switchBranch catches non-dirty errors and shows error toast')`** — this also validates the bug fix
  8. **`test('confirmAutoStash performs full stash-switch-restore flow')`**

  **Must NOT do**:
  - Do NOT delete or modify existing tests
  - Do NOT create a new test file — add to existing files
  - Follow existing test patterns: `Process::fake()`, `Livewire::test()`, `->call()`, `->assertSet()`, `->assertDispatched()`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Multiple test cases but all follow the established Pest + Process::fake pattern
  - **Skills**: [`pest-testing`, `livewire-development`]
    - `pest-testing`: Core skill for writing Pest test cases with proper assertions
    - `livewire-development`: Needed for Livewire::test(), ->call(), ->assertDispatched() testing patterns
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: Tests are PHP-only, no Blade/UI testing needed here

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 3 (sequential after Tasks 2 and 3)
  - **Blocks**: Task 5
  - **Blocked By**: Tasks 2, 3

  **References**:

  **Test References**:
  - `tests/Feature/Livewire/BranchManagerTest.php:44-56` — Existing `switchBranch` test showing Process::fake pattern with `git checkout main` → success. Use this as template for the dirty-tree test, but with failure exit code.
  - `tests/Feature/Livewire/BranchManagerTest.php:58-74` — `createBranch` test showing Process::fake with multiple commands and assertDispatched. Pattern for `confirmAutoStash` test.
  - `tests/Feature/Livewire/RepoSidebarTest.php:120-135` — Existing `switchBranch` test for RepoSidebar. Use as template.
  - `tests/Feature/Livewire/RepoSidebarTest.php:210-229` — Error handling test for stash operations showing `Process::result('error', exitCode: 1)` pattern.
  - `tests/Mocks/GitOutputFixtures.php:243-248` — Stash list fixture data for faking stash responses.

  **Pattern References**:
  - `tests/Feature/Livewire/StagingPanelTest.php:205-218` — `stashSelected` test showing how stash operations are tested with Process::fake.

  **Acceptance Criteria**:

  - [ ] All new BranchManager tests pass: `php artisan test --compact --filter=BranchManagerTest`
  - [ ] All new RepoSidebar tests pass: `php artisan test --compact --filter=RepoSidebarTest`
  - [ ] All EXISTING tests still pass: `php artisan test --compact --filter=BranchManagerTest` and `php artisan test --compact --filter=RepoSidebarTest`
  - [ ] At least 8 new test cases added across both files
  - [ ] `vendor/bin/pint --dirty --format agent` passes

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: All BranchManager tests pass including new ones
    Tool: Bash (pest)
    Steps:
      1. Run: php artisan test --compact --filter=BranchManagerTest
      2. Assert: exit code 0
      3. Assert: output shows 0 failures
    Expected Result: All tests green
    Evidence: Test output captured

  Scenario: All RepoSidebar tests pass including new ones
    Tool: Bash (pest)
    Steps:
      1. Run: php artisan test --compact --filter=RepoSidebarTest
      2. Assert: exit code 0
      3. Assert: output shows 0 failures
    Expected Result: All tests green
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `test(staging): add Pest tests for auto-stash branch switching flow`
  - Files: `tests/Feature/Livewire/BranchManagerTest.php`, `tests/Feature/Livewire/RepoSidebarTest.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [x] 5. Final verification — run full test suite

  **What to do**:
  - Run the complete test suite: `php artisan test --compact`
  - Run Pint: `vendor/bin/pint --dirty --format agent`
  - Verify no regressions

  **Must NOT do**:
  - Do NOT modify any files in this task — it's verification only
  - If tests fail, debug and fix in this task

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Just running commands and verifying output
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Needed for interpreting test output and debugging failures

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 4 (final)
  - **Blocks**: None
  - **Blocked By**: Task 4

  **References**:

  **Test References**:
  - All test files in `tests/` directory

  **Acceptance Criteria**:

  - [ ] `php artisan test --compact` → 0 failures, 0 errors
  - [ ] `vendor/bin/pint --dirty --format agent` → no changes needed

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Full test suite passes
    Tool: Bash (pest)
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0
      3. Assert: output shows 0 failures, 0 errors
    Expected Result: All tests green, no regressions
    Evidence: Full test output captured

  Scenario: Code style passes
    Tool: Bash (pint)
    Steps:
      1. Run: vendor/bin/pint --dirty --format agent
      2. Assert: no files need formatting changes
    Expected Result: Code style clean
    Evidence: Pint output captured
  ```

  **Commit**: NO (verification only)

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `feat(backend): add isDirtyTreeError detection helper to GitErrorHandler` | `app/Services/Git/GitErrorHandler.php` | `vendor/bin/pint --dirty --format agent` |
| 2 | `feat(staging): add auto-stash flow to BranchManager with confirmation modal` | `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php` | `vendor/bin/pint --dirty --format agent` |
| 3 | `fix(staging): add error handling to RepoSidebar branch switch and auto-stash support` | `app/Livewire/RepoSidebar.php`, `resources/views/livewire/repo-sidebar.blade.php` | `vendor/bin/pint --dirty --format agent` |
| 4 | `test(staging): add Pest tests for auto-stash branch switching flow` | `tests/Feature/Livewire/BranchManagerTest.php`, `tests/Feature/Livewire/RepoSidebarTest.php` | `php artisan test --compact` |
| 5 | (no commit — verification only) | — | `php artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact --filter=BranchManagerTest  # Expected: all pass
php artisan test --compact --filter=RepoSidebarTest    # Expected: all pass  
php artisan test --compact                             # Expected: 0 failures
vendor/bin/pint --dirty --format agent                 # Expected: clean
```

### Final Checklist
- [ ] Clean branch switch still works (no modal, no stash)
- [ ] Dirty non-conflicting switch carries changes silently
- [ ] Dirty conflicting switch shows modal
- [ ] "Stash & Switch" performs full auto-stash flow
- [ ] Conflict on restore shows warning toast, preserves stash
- [ ] "Cancel" closes modal without action
- [ ] RepoSidebar no longer crashes on dirty checkout
- [ ] Both entry points (header + sidebar) have identical behavior
- [ ] All tests pass
- [ ] Code style passes (Pint)
