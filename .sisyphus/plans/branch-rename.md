# Branch Rename

## TL;DR

> **Quick Summary**: Add branch rename functionality to the branch manager so users can rename local branches without deleting and recreating them.
> 
> **Deliverables**:
> - `BranchService::renameBranch()` method
> - Rename action in branch manager dropdown
> - Inline rename input field
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Research Findings
- `BranchService.php` has `createBranch()`, `deleteBranch()`, `switchBranch()`, `mergeBranch()` — but no rename
- Git supports `git branch -m <old> <new>` for renaming branches
- `BranchManager.php` renders branch items with actions (switch, delete, merge) — add rename to this list
- Current workaround is delete + create, which loses tracking configuration

---

## Work Objectives

### Core Objective
Allow renaming local branches via `git branch -m`.

### Must Have
- Rename local branches
- Inline rename input (click rename → branch name becomes editable)
- Validation: no empty names, no duplicate names, no renaming current branch while checked out (actually git supports this)
- Error handling for invalid branch name characters

### Must NOT Have
- No remote branch renaming (that requires push + delete, different feature)
- No automatic upstream tracking update

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

---

## Execution Strategy

```
Wave 1 (Backend + Component):
├── Task 1: Add BranchService::renameBranch() [quick]
├── Task 2: Add rename action to BranchManager component [quick]

Wave 2 (View + tests):
├── Task 3: Add rename UI to branch-manager Blade view [visual-engineering]
├── Task 4: Pest tests [unspecified-high]
```

---

## TODOs

- [ ] 1. Add BranchService::renameBranch() method

  **What to do**:
  - Add `renameBranch(string $oldName, string $newName): void` to `BranchService`
  - Run `git branch -m {oldName} {newName}`
  - Invalidate branches cache group
  - Validate new name is not empty

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/BranchService.php:41-52` — `createBranch()` pattern to follow
  - `app/Services/Git/AbstractGitService.php` — Base class

  **Acceptance Criteria**:
  - [ ] `BranchService::renameBranch('old', 'new')` executes `git branch -m old new`
  - [ ] Cache invalidated after rename
  - [ ] Empty name throws exception

  **Commit**: YES
  - Message: `feat(backend): add BranchService::renameBranch method`
  - Files: `app/Services/Git/BranchService.php`

- [ ] 2. Add rename action to BranchManager Livewire component

  **What to do**:
  - Add `renameBranch(string $oldName, string $newName): void` method to `BranchManager.php`
  - Add `$renamingBranch` and `$newBranchName` public properties for inline editing state
  - Add `startRename(string $name)` to enter rename mode
  - Add `confirmRename()` to execute rename and refresh
  - Add `cancelRename()` to exit rename mode
  - Dispatch `status-updated` after successful rename

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 3, 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/BranchManager.php:69-88` — `switchBranch()` pattern (error handling, refresh, dispatch)
  - `app/Livewire/BranchManager.php:105-126` — `deleteBranch()` pattern

  **Acceptance Criteria**:
  - [ ] `startRename()` sets editing state
  - [ ] `confirmRename()` calls service, refreshes, dispatches event
  - [ ] `cancelRename()` clears editing state
  - [ ] Error handling matches existing pattern

  **Commit**: YES
  - Message: `feat(backend): add rename methods to BranchManager component`
  - Files: `app/Livewire/BranchManager.php`

- [ ] 3. Add rename UI to branch-manager Blade view

  **What to do**:
  - Add "Rename" option to branch context menu (next to Delete, Merge)
  - When rename mode active: replace branch name text with `<flux:input>` containing current name
  - Enter key or blur confirms, Escape cancels
  - Use `<x-phosphor-pencil-simple class="w-3.5 h-3.5" />` icon for rename action
  - Disable rename for remote branches (only local)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`fluxui-development`, `livewire-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/branch-manager.blade.php` — Branch item rendering, existing action buttons

  **Acceptance Criteria**:
  - [ ] "Rename" option visible in branch item actions (local branches only)
  - [ ] Inline input appears on rename click
  - [ ] Enter confirms, Escape cancels
  - [ ] Remote branches don't show rename option

  **QA Scenarios**:

  ```
  Scenario: Rename a local branch
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Open branch manager dropdown
      3. Find a non-current local branch
      4. Click rename action
      5. Clear input and type "renamed-branch"
      6. Press Enter
      7. Assert branch list shows "renamed-branch"
      8. Assert old name is gone
    Expected Result: Branch renamed successfully in list
    Evidence: .sisyphus/evidence/task-3-branch-rename.png
  ```

  **Commit**: YES
  - Message: `feat(header): add rename UI to branch manager`
  - Files: `resources/views/livewire/branch-manager.blade.php`

- [ ] 4. Pest tests for branch rename

  **What to do**:
  - Add tests to `tests/Feature/Services/BranchServiceTest.php` for `renameBranch()`
  - Add tests to `tests/Feature/Livewire/BranchManagerTest.php` for rename workflow
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 3

  **References**:
  - `tests/Feature/Services/BranchServiceTest.php` — Existing branch service tests
  - `tests/Feature/Livewire/BranchManagerTest.php` — Existing branch manager tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=BranchService` → all pass
  - [ ] `php artisan test --compact --filter=BranchManager` → all pass

  **Commit**: YES
  - Message: `test(header): add tests for branch rename`
  - Files: `tests/Feature/Services/BranchServiceTest.php`, `tests/Feature/Livewire/BranchManagerTest.php`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave (oracle, code quality, manual QA, scope check)

---

## Success Criteria

```bash
php artisan test --compact --filter=BranchService  # Expected: all pass
php artisan test --compact --filter=BranchManager  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```
