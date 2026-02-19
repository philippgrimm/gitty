# Squash Merge

## TL;DR

> **Quick Summary**: Add squash merge option to the branch manager so users can merge a branch into the current branch with all commits squashed into a single commit.
> 
> **Deliverables**:
> - `BranchService::squashMerge()` method
> - Squash merge option in branch manager merge menu
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `BranchService::mergeBranch()` at line 74 runs `git merge` with no options — only regular merge
- Git supports `git merge --squash {branch}` which stages all changes but doesn't auto-commit
- After squash merge, user needs to commit manually (the staging panel will show squashed changes)
- `BranchManager.php:128-150` has `mergeBranch()` — add `squashMerge()` alongside it
- `MergeResult` DTO already handles merge output parsing

---

## Work Objectives

### Core Objective
Add squash merge as an alternative to regular merge in the branch manager.

### Must Have
- Squash merge button/option on branch items
- After squash merge, changes appear as staged (user commits with custom message)
- Success/error notifications
- Conflict detection (squash merge can conflict too)

### Must NOT Have
- No auto-commit after squash (user should write the squash commit message)
- No rebase-and-merge (different operation)

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

---

## TODOs

- [ ] 1. Add BranchService::squashMerge() method

  **What to do**:
  - Add `squashMerge(string $name): MergeResult` to `BranchService`
  - Run `git merge --squash {name}`
  - Parse output with `MergeResult::fromMergeOutput()`
  - Invalidate status and history cache groups

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/BranchService.php:74-82` — Existing `mergeBranch()` pattern
  - `app/DTOs/MergeResult.php` — Output parser

  **Acceptance Criteria**:
  - [ ] `BranchService::squashMerge('feature')` executes `git merge --squash feature`
  - [ ] Returns `MergeResult` with conflict info if applicable
  - [ ] Cache groups invalidated

  **Commit**: YES
  - Message: `feat(backend): add BranchService::squashMerge method`
  - Files: `app/Services/Git/BranchService.php`

- [ ] 2. Add squash merge action to BranchManager and view

  **What to do**:
  - Add `squashMergeBranch(string $name): void` to `BranchManager.php`
  - Follow same pattern as `mergeBranch()` — try/catch, refresh, dispatch events
  - Show success message: "Squash merged {name} — changes are staged, commit when ready"
  - Add "Squash Merge" option to branch item menu in `branch-manager.blade.php`
  - Use `<flux:menu.item icon="arrows-merge">Squash Merge</flux:menu.item>` or similar

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/BranchManager.php:128-150` — `mergeBranch()` pattern
  - `resources/views/livewire/branch-manager.blade.php` — Branch item menu rendering

  **Acceptance Criteria**:
  - [ ] "Squash Merge" option visible in branch context menu
  - [ ] Clicking it executes squash merge and shows success notification
  - [ ] Staging panel shows squashed changes after merge

  **QA Scenarios**:

  ```
  Scenario: Squash merge a branch
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with repo that has a feature branch with commits
      2. Open branch manager
      3. Find the feature branch
      4. Click "Squash Merge" action
      5. Assert success notification appears
      6. Assert staging panel shows staged files
    Expected Result: Changes staged, no auto-commit, user can write message
    Evidence: .sisyphus/evidence/task-2-squash-merge.png
  ```

  **Commit**: YES
  - Message: `feat(header): add squash merge option to branch manager`
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`

- [ ] 3. Pest tests for squash merge

  **What to do**:
  - Add tests to `tests/Feature/Services/BranchServiceTest.php` for `squashMerge()`
  - Add tests to `tests/Feature/Livewire/BranchManagerTest.php` for squash merge action
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `tests/Feature/Services/BranchServiceTest.php`
  - `tests/Feature/Livewire/BranchManagerTest.php`

  **Acceptance Criteria**:
  - [ ] All branch-related tests pass

  **Commit**: YES
  - Message: `test(header): add tests for squash merge`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=BranchService  # Expected: all pass
php artisan test --compact --filter=BranchManager  # Expected: all pass
```
