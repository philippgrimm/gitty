# Force-Delete Unmerged Branch Confirmation

## TL;DR

> **Quick Summary**: When deleting a branch that isn't fully merged, catch the git error and show a confirmation modal asking the user if they want to force-delete it — instead of just showing an error toast.
> 
> **Deliverables**:
> - `GitErrorHandler::isNotFullyMergedError()` detection method
> - Force-delete confirmation modal in BranchManager
> - `forceDeleteBranch()` Livewire action
> - Pest tests covering the full flow
> 
> **Estimated Effort**: Quick
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Original Request
When deleting a branch that is not fully merged, the app shows an error toast. Instead, it should ask the user if they want to force-delete it.

### Interview Summary
**Key Discussions**:
- Both UI entry points (hover trash icon + right-click context menu) should show the modal — they already call the same `deleteBranch()` method, so this is automatic
- The `BranchService::deleteBranch()` already supports a `bool $force` parameter (`-d` vs `-D`)

**Research Findings**:
- `BranchManager::switchBranch()` already implements the exact same pattern: catch specific error → show modal → confirm action. This is the template.
- `GitErrorHandler` has `isDirtyTreeError()` as a precedent for `isNotFullyMergedError()`
- The auto-stash modal (`showAutoStashModal`) is the UI pattern to replicate
- Git's error message: `error: The branch 'X' is not fully merged.`

### Metis Review
**Identified Gaps** (addressed):
- Error detection: Added `isNotFullyMergedError()` to `GitErrorHandler` (matches `isDirtyTreeError()` pattern)
- Current branch protection: `deleteBranch()` already returns early for current branch — force-delete modal never triggers
- State cleanup: `cancelForceDelete()` resets state (matches `cancelAutoStash()` pattern)
- Remote branches: Not in scope — remote deletion uses different code path

---

## Work Objectives

### Core Objective
Replace the error toast for unmerged branch deletion with a confirmation modal that allows force-deleting.

### Concrete Deliverables
- `GitErrorHandler::isNotFullyMergedError()` static method
- `BranchManager` properties: `showForceDeleteModal`, `branchToForceDelete`
- `BranchManager::forceDeleteBranch()` and `cancelForceDelete()` methods
- `<flux:modal>` in `branch-manager.blade.php`
- Pest tests for: modal trigger, force delete, cancel, current branch protection

### Definition of Done
- [x] `php artisan test --compact --filter=BranchManager` → all pass
- [x] `php artisan test --compact --filter=GitErrorHandler` → all pass
- [x] `vendor/bin/pint --dirty --format agent` → no issues

### Must Have
- Only "not fully merged" errors trigger the modal — other delete errors still show toast
- Modal displays the branch name being deleted
- Modal closes and state resets after force-delete or cancel
- Existing delete behavior unchanged for fully-merged branches

### Must NOT Have (Guardrails)
- Do NOT show modal for current branch deletion (different error, handled before try/catch)
- Do NOT apply to remote branches (different deletion mechanism)
- Do NOT add "force delete" as a primary/direct action — only via modal after failed `-d`
- Do NOT skip the initial `-d` attempt — always try safe delete first
- Do NOT show unmerged commits in modal — just branch name and confirmation
- Do NOT add settings/preferences for this (no "always force delete" toggle)

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed.

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: YES (tests-after)
- **Framework**: Pest 4

### QA Policy
Every task includes agent-executed QA scenarios. Evidence saved to `.sisyphus/evidence/`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| PHP Service | Bash (pest) | Run filtered tests, assert pass |
| Livewire Component | Bash (pest) | Livewire test assertions |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately — error detection):
├── Task 1: Add isNotFullyMergedError() to GitErrorHandler [quick]

Wave 2 (After Wave 1 — component + view + tests):
├── Task 2: Add force-delete modal to BranchManager component + view [quick]
├── Task 3: Add Pest tests for force-delete flow [quick]

Wave FINAL (After ALL tasks):
├── Task F1: Run full test suite + pint [quick]
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | 2, 3 | 1 |
| 2 | 1 | F1 | 2 |
| 3 | 1 | F1 | 2 |
| F1 | 2, 3 | — | FINAL |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 1 | **1** | T1 → `quick` |
| 2 | **2** | T2 → `quick`, T3 → `quick` |
| FINAL | **1** | F1 → `quick` |

---

## TODOs

- [x] 1. Add `isNotFullyMergedError()` to GitErrorHandler

  **What to do**:
  - Add a new static method `isNotFullyMergedError(string $errorMessage): bool` to `GitErrorHandler`
  - Match the git error string `is not fully merged` (this appears in the message `error: The branch 'X' is not fully merged.`)
  - Follow the exact pattern of `isDirtyTreeError()` on line 69-73

  **Must NOT do**:
  - Do NOT modify `translate()` — this is a separate detection method
  - Do NOT add translations for this error — the modal replaces the need for a translated message

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single method addition to existing class, <10 lines
  - **Skills**: []
  - **Skills Evaluated but Omitted**:
    - `pest-testing`: Tests are in a separate task
    - `livewire-development`: No Livewire here

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (solo)
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/Git/GitErrorHandler.php:69-73` — `isDirtyTreeError()` method. Follow this exact structure: static method, `str_contains()` check, returns bool.

  **Why Each Reference Matters**:
  - The `isDirtyTreeError()` method is the 1:1 template. Same signature, same approach, just a different error string.

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Detects "not fully merged" error
    Tool: Bash (pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter=GitErrorHandler
    Expected Result: All tests pass including new test for isNotFullyMergedError
    Failure Indicators: Test failure or "not fully merged" not detected
    Evidence: .sisyphus/evidence/task-1-error-detection.txt

  Scenario: Does not false-positive on other errors
    Tool: Bash (pest)
    Preconditions: None
    Steps:
      1. Verify test includes negative case (e.g., "branch not found" returns false)
    Expected Result: isNotFullyMergedError returns false for unrelated errors
    Evidence: .sisyphus/evidence/task-1-no-false-positive.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): add isNotFullyMergedError detection to GitErrorHandler`
  - Files: `app/Services/Git/GitErrorHandler.php`, `tests/Feature/Services/GitErrorHandlerTest.php` (create if not exists, or add to existing)
  - Pre-commit: `php artisan test --compact --filter=GitErrorHandler`

- [x] 2. Add force-delete modal to BranchManager component and view

  **What to do**:
  - Add two public properties to `BranchManager`: `public bool $showForceDeleteModal = false;` and `public string $branchToForceDelete = '';`
  - Modify `deleteBranch()` catch block: detect `isNotFullyMergedError()` (like `switchBranch()` detects `isDirtyTreeError()` on line 80). If true, set `$this->branchToForceDelete = $name` and `$this->showForceDeleteModal = true`. Otherwise, show error toast as before.
  - Add `forceDeleteBranch(): void` method: set `$this->showForceDeleteModal = false`, call `$branchService->deleteBranch($this->branchToForceDelete, true)`, call `$this->refreshBranches()`, dispatch `status-updated`, reset `$this->branchToForceDelete = ''`. Wrap in try/catch same as `deleteBranch()`.
  - Add `cancelForceDelete(): void` method: set `$this->showForceDeleteModal = false` and `$this->branchToForceDelete = ''` (matches `cancelAutoStash()` on line 192-196).
  - Add `<flux:modal wire:model="showForceDeleteModal">` to `branch-manager.blade.php` right after the existing `showAutoStashModal` modal (line 222). Follow the same structure: `<flux:heading>`, `<flux:subheading>` with branch name, Cancel + danger confirm button.

  **Modal content**:
  - Heading: `Force Delete?`
  - Subheading: `The branch **{{ $branchToForceDelete }}** is not fully merged. Force-deleting it may cause you to lose commits. Continue?`
  - Cancel button: `<flux:button variant="ghost" wire:click="cancelForceDelete">Cancel</flux:button>`
  - Confirm button: `<flux:button variant="danger" wire:click="forceDeleteBranch">Force Delete</flux:button>` (use `variant="danger"` since this is destructive)

  **Must NOT do**:
  - Do NOT change the existing fully-merged delete flow
  - Do NOT use `variant="primary"` on the confirm button — this is a destructive action, use `variant="danger"`
  - Do NOT add any settings or configuration options

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Follows a 1:1 existing pattern (auto-stash modal), mechanical changes
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Livewire component modifications, wire:model, wire:click
    - `fluxui-development`: flux:modal, flux:button, flux:heading, flux:subheading
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No custom styling needed, follows existing patterns
    - `pest-testing`: Tests are in a separate task

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 3)
  - **Parallel Group**: Wave 2 (with Task 3)
  - **Blocks**: F1
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - `app/Livewire/BranchManager.php:69-88` — `switchBranch()` method. This is the 1:1 template: catch block checks `isDirtyTreeError()`, sets modal state. Replicate for `isNotFullyMergedError()`.
  - `app/Livewire/BranchManager.php:152-196` — `confirmAutoStash()` and `cancelAutoStash()`. Replicate this confirm/cancel pair for `forceDeleteBranch()`/`cancelForceDelete()`.
  - `app/Livewire/BranchManager.php:32-34` — Properties `showAutoStashModal` and `autoStashTargetBranch`. Replicate as `showForceDeleteModal` and `branchToForceDelete`.
  - `resources/views/livewire/branch-manager.blade.php:204-222` — Auto-stash modal markup. Copy this structure for force-delete modal, placed directly after it.

  **API/Type References**:
  - `app/Services/Git/GitErrorHandler.php` — `isNotFullyMergedError()` (from Task 1)
  - `app/Services/Git/BranchService.php:54-65` — `deleteBranch(string $name, bool $force)` — call with `true` for force delete

  **Why Each Reference Matters**:
  - `switchBranch()` is the exact pattern: error detection → modal → confirm action. Copy the structure.
  - `confirmAutoStash()`/`cancelAutoStash()` show the confirm/cancel lifecycle. Follow the same state cleanup.
  - The blade modal is a copy-paste-modify from the auto-stash modal.

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Unmerged branch delete shows force-delete modal
    Tool: Bash (pest)
    Preconditions: Process::fake with git branch -d returning "not fully merged" error
    Steps:
      1. Call deleteBranch('feature/unmerged') on component
      2. Assert showForceDeleteModal === true
      3. Assert branchToForceDelete === 'feature/unmerged'
      4. Assert show-error NOT dispatched (no toast — modal instead)
    Expected Result: Modal state set, no error toast
    Evidence: .sisyphus/evidence/task-2-modal-trigger.txt

  Scenario: Force delete succeeds after confirmation
    Tool: Bash (pest)
    Preconditions: Process::fake with git branch -D returning success
    Steps:
      1. Set showForceDeleteModal = true, branchToForceDelete = 'feature/unmerged'
      2. Call forceDeleteBranch()
      3. Assert showForceDeleteModal === false
      4. Assert branchToForceDelete === ''
      5. Assert git branch -D was called
      6. Assert status-updated dispatched
    Expected Result: Branch force-deleted, modal closed, state reset
    Evidence: .sisyphus/evidence/task-2-force-delete.txt

  Scenario: Cancel resets state
    Tool: Bash (pest)
    Preconditions: showForceDeleteModal = true, branchToForceDelete = 'feature/unmerged'
    Steps:
      1. Call cancelForceDelete()
      2. Assert showForceDeleteModal === false
      3. Assert branchToForceDelete === ''
    Expected Result: State reset, no git command executed
    Evidence: .sisyphus/evidence/task-2-cancel.txt
  ```

  **Commit**: YES
  - Message: `feat(staging): add force-delete confirmation modal for unmerged branches`
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`
  - Pre-commit: `php artisan test --compact --filter=BranchManager`

- [x] 3. Add Pest tests for force-delete branch flow

  **What to do**:
  - Add tests to `tests/Feature/Livewire/BranchManagerTest.php` following existing patterns
  - Required tests:
    1. `test('deleteBranch shows force-delete modal when branch is not fully merged')` — Process::fake `git branch -d` returning error with "is not fully merged", assert `showForceDeleteModal === true`, `branchToForceDelete` set, `show-error` NOT dispatched
    2. `test('deleteBranch shows error toast for non-merge-related delete errors')` — Process::fake `git branch -d` returning a different error (e.g., "branch not found"), assert `showForceDeleteModal === false`, `show-error` dispatched
    3. `test('forceDeleteBranch calls git branch -D and resets state')` — Set modal state, call `forceDeleteBranch()`, assert `git branch -D` was called, modal closed, state reset, `status-updated` dispatched
    4. `test('cancelForceDelete resets state without action')` — Set modal state, call `cancelForceDelete()`, assert state reset, no git command ran
    5. `test('deleteBranch on current branch does not show force-delete modal')` — Call `deleteBranch('main')` (current branch), assert modal NOT shown, error set

  **Must NOT do**:
  - Do NOT modify or delete existing tests
  - Do NOT use real git operations — Process::fake only

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Following established test patterns, mechanical additions
  - **Skills**: [`pest-testing`, `livewire-development`]
    - `pest-testing`: Pest test syntax, assertions
    - `livewire-development`: Livewire::test assertions (assertSet, assertDispatched, call)
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: No UI work in tests

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2)
  - **Parallel Group**: Wave 2 (with Task 2)
  - **Blocks**: F1
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - `tests/Feature/Livewire/BranchManagerTest.php:72-97` — Existing `deleteBranch` tests (happy path + current branch). Add new tests directly after these.
  - `tests/Feature/Livewire/BranchManagerTest.php:206-243` — Auto-stash modal tests. These are the 1:1 template: Process::fake with error output, assert modal state, assert dispatched events. Replicate this exact style.
  - `tests/Feature/Livewire/BranchManagerTest.php:302-313` — `cancelAutoStash` test. Replicate for `cancelForceDelete`.

  **API/Type References**:
  - `tests/Mocks/GitOutputFixtures.php` — Provides test fixtures like `statusClean()`, `branchListVerbose()`. Use these existing fixtures.

  **Why Each Reference Matters**:
  - Lines 206-243 show the exact pattern for testing "error triggers modal": fake Process with errorOutput, call method, assertSet modal properties. Copy this structure.
  - Lines 302-313 show the cancel pattern: set state, call cancel, assert reset.

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All new tests pass
    Tool: Bash (pest)
    Preconditions: Tasks 1 and 2 complete
    Steps:
      1. Run: php artisan test --compact --filter=BranchManager
    Expected Result: All tests pass (existing + new), 0 failures
    Failure Indicators: Any test failure
    Evidence: .sisyphus/evidence/task-3-tests-pass.txt

  Scenario: Existing tests still pass (no regression)
    Tool: Bash (pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component deletes branch"
      2. Run: php artisan test --compact --filter="component prevents deleting current branch"
    Expected Result: Both existing tests still pass
    Evidence: .sisyphus/evidence/task-3-no-regression.txt
  ```

  **Commit**: YES
  - Message: `test(staging): add tests for force-delete unmerged branch flow`
  - Files: `tests/Feature/Livewire/BranchManagerTest.php`
  - Pre-commit: `php artisan test --compact --filter=BranchManager`

---

## Final Verification Wave

- [x] F1. **Full Test Suite + Lint** — `quick`
  Run `php artisan test --compact` to verify no regressions across the entire suite. Run `vendor/bin/pint --dirty --format agent` to verify code style. Run `php artisan test --compact --filter=BranchManager` and `php artisan test --compact --filter=GitErrorHandler` for targeted verification.
  Output: `Tests [PASS/FAIL] | Pint [PASS/FAIL] | VERDICT: APPROVE/REJECT`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `feat(backend): add isNotFullyMergedError detection to GitErrorHandler` | `app/Services/Git/GitErrorHandler.php`, tests | `php artisan test --compact --filter=GitErrorHandler` |
| 2 | `feat(staging): add force-delete confirmation modal for unmerged branches` | `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php` | `php artisan test --compact --filter=BranchManager` |
| 3 | `test(staging): add tests for force-delete unmerged branch flow` | `tests/Feature/Livewire/BranchManagerTest.php` | `php artisan test --compact --filter=BranchManager` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact --filter=BranchManager  # Expected: all pass
php artisan test --compact --filter=GitErrorHandler  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```

### Final Checklist
- [x] Deleting a fully-merged branch still works without modal
- [x] Deleting an unmerged branch shows force-delete confirmation modal
- [x] Confirming force-delete calls `git branch -D` and removes the branch
- [x] Cancelling the modal resets state without action
- [x] Deleting current branch shows error toast (not the modal)
- [x] Both hover trash icon and context menu trigger the same flow
- [x] All existing tests still pass
