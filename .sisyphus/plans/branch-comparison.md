# Branch Comparison

## TL;DR

> **Quick Summary**: Add a branch comparison view that shows the diff between two branches, letting users see what would change in a merge before executing it.
> 
> **Deliverables**:
> - `BranchService::compareBranches()` method
> - Branch comparison UI (select two branches, see diff)
> - File list with per-file diffs
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- No existing branch comparison in the app
- Git supports `git diff branch1..branch2` for comparing branches
- `git log branch1..branch2 --oneline` shows commits unique to branch2
- `BranchService::branches()` already returns all local and remote branches
- Could be accessed from branch manager context menu ("Compare with...") or as a dedicated view

---

## Work Objectives

### Core Objective
Let users compare two branches to see the diff between them, including commit list and file changes.

### Must Have
- Two-branch selector (base branch + compare branch)
- Commit list: commits in compare that aren't in base
- File list with status dots and +/- counts
- Per-file diff view
- Accessible from branch manager and command palette

### Must NOT Have
- No merge execution from comparison view
- No three-way diff
- No remote-only branch comparison (must have local tracking)

---

## TODOs

- [ ] 1. Add BranchService::compareBranches() methods

  **What to do**:
  - Add `compareBranches(string $base, string $compare): DiffResult` — runs `git diff {base}..{compare}` and parses with `DiffResult::fromDiffOutput()`
  - Add `compareCommits(string $base, string $compare): Collection` — runs `git log {base}..{compare} --format=...` returning commits unique to compare branch
  - Add `compareStats(string $base, string $compare): array` — runs `git diff --stat {base}..{compare}` for quick summary

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/BranchService.php` — Add methods here
  - `app/Services/Git/GitService.php:88-117` — `diff()` method pattern
  - `app/DTOs/DiffResult.php` — Diff parser
  - `app/DTOs/Commit.php` — Commit DTO for log

  **Acceptance Criteria**:
  - [ ] `compareBranches('main', 'feature')` returns DiffResult with changed files
  - [ ] `compareCommits('main', 'feature')` returns Collection of Commit DTOs

  **Commit**: YES
  - Message: `feat(backend): add branch comparison service methods`
  - Files: `app/Services/Git/BranchService.php`

- [ ] 2. Create BranchComparison Livewire component and view

  **What to do**:
  - Create `app/Livewire/BranchComparison.php` with base/compare branch selectors
  - Load branch list for selectors via `BranchService::branches()`
  - On branch selection, load comparison data
  - Create `resources/views/livewire/branch-comparison.blade.php` with:
    - Two branch dropdowns at top
    - Commit list (commits unique to compare branch)
    - File list with diffs (reuse diff rendering patterns)
  - Wire into app layout as an overlay/panel
  - Add to command palette: "Compare Branches"
  - Dispatch event from branch manager: "Compare with current" on right-click

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/BlameView.php` — Overlay panel pattern
  - `app/Livewire/BranchManager.php:201-209` — Branch list filtering pattern
  - `resources/views/livewire/diff-viewer.blade.php` — Diff rendering
  - `resources/views/livewire/history-panel.blade.php` — Commit list rendering

  **Acceptance Criteria**:
  - [ ] Two branch selectors with all available branches
  - [ ] Selecting branches shows commit list and file diff
  - [ ] Accessible from command palette
  - [ ] Can be closed/dismissed

  **QA Scenarios**:

  ```
  Scenario: Compare two branches
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Open command palette (Cmd+K)
      3. Type "Compare Branches"
      4. Select the command
      5. Select base branch (e.g., "main")
      6. Select compare branch (e.g., "feature")
      7. Assert comparison view shows commits and files
    Expected Result: Branch diff displayed with commits and changed files
    Evidence: .sisyphus/evidence/task-2-branch-comparison.png
  ```

  **Commit**: YES
  - Message: `feat(panels): create branch comparison view`
  - Files: `app/Livewire/BranchComparison.php`, `resources/views/livewire/branch-comparison.blade.php`, app-layout.blade.php

- [ ] 3. Pest tests for branch comparison

  **What to do**:
  - Add tests to `tests/Feature/Services/BranchServiceTest.php`
  - Create `tests/Feature/Livewire/BranchComparisonTest.php`
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2

  **Acceptance Criteria**:
  - [ ] All comparison tests pass

  **Commit**: YES
  - Message: `test(panels): add tests for branch comparison`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=BranchService  # Expected: all pass
php artisan test --compact --filter=BranchComparison  # Expected: all pass
```
