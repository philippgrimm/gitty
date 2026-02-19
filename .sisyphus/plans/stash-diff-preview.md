# Stash Diff Preview

## TL;DR

> **Quick Summary**: Add the ability to preview stash contents (see what files and changes a stash contains) before applying or popping it.
> 
> **Deliverables**:
> - `StashService::stashShow()` method returning diff for a stash
> - Preview UI in the stash list (expandable or panel overlay)
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `StashService.php` has `stashList()`, `stashApply()`, `stashPop()`, `stashDrop()` — but no preview/show
- Git supports `git stash show -p stash@{N}` for full diff and `git stash show stash@{N}` for stat summary
- Currently stash items only show index and message, no file list or changes
- Stash operations are triggered from the staging panel (`StagingPanel.php`) and stash panel area
- `DiffResult::fromDiffOutput()` can parse stash show output

---

## Work Objectives

### Core Objective
Let users preview stash contents before deciding to apply, pop, or drop.

### Must Have
- File list with change stats (+/- counts)
- Full diff view per file (reuse diff rendering)
- Works for all stashes in the list

### Must NOT Have
- No editing stash contents
- No partial stash apply (that's a different feature)

---

## TODOs

- [ ] 1. Add StashService::stashShow() method

  **What to do**:
  - Add `stashShow(int $index): DiffResult` that runs `git stash show -p stash@{$index}` and parses with `DiffResult::fromDiffOutput()`
  - Add `stashStat(int $index): array` that runs `git stash show --stat stash@{$index}` for quick file list

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/StashService.php` — Add methods here
  - `app/DTOs/DiffResult.php` — `fromDiffOutput()` parser

  **Acceptance Criteria**:
  - [ ] `StashService::stashShow(0)` returns `DiffResult` with files and hunks
  - [ ] `StashService::stashStat(0)` returns file names with change counts

  **Commit**: YES
  - Message: `feat(backend): add StashService::stashShow and stashStat methods`
  - Files: `app/Services/Git/StashService.php`

- [ ] 2. Add stash preview UI

  **What to do**:
  - Add "Preview" / eye icon button to each stash item in the stash list
  - On click, show stash file list with expandable per-file diffs
  - Reuse diff rendering pattern from diff-viewer
  - Could be inline expansion below stash item or a modal/panel overlay
  - Use Catppuccin colors, file status dots for added/modified/deleted files

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `resources/views/livewire/staging-panel.blade.php` — Stash list rendering area
  - `resources/views/livewire/diff-viewer.blade.php` — Diff rendering pattern
  - `app/Livewire/StagingPanel.php` — Add stash preview methods

  **Acceptance Criteria**:
  - [ ] "Preview" button visible on stash items
  - [ ] Click shows file list with change stats
  - [ ] Can expand to see full diff per file

  **QA Scenarios**:

  ```
  Scenario: Preview stash contents
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with repo that has stashes
      2. Find stash list
      3. Click preview button on first stash
      4. Assert file list appears with file names
      5. Assert +/- counts visible
    Expected Result: Stash file list with change statistics displayed
    Evidence: .sisyphus/evidence/task-2-stash-preview.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add stash diff preview UI`
  - Files: Blade views, Livewire component

- [ ] 3. Pest tests for stash preview

  **What to do**:
  - Add tests to `tests/Feature/Services/StashServiceTest.php` for `stashShow()` and `stashStat()`
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `tests/Feature/Services/StashServiceTest.php` — Existing stash tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=StashService` → all pass

  **Commit**: YES
  - Message: `test(staging): add tests for stash preview`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=StashService  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```
