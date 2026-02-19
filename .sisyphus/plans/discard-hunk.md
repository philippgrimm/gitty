# Discard Hunk

## TL;DR

> **Quick Summary**: Add a "Discard" button per hunk in the diff viewer so users can discard individual hunks of changes without discarding the entire file.
> 
> **Deliverables**:
> - `DiffService::discardHunk()` method using `git apply --reverse`
> - Discard button in diff viewer hunk header (next to Stage/Unstage)
> - Confirmation dialog for destructive operation
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `DiffViewer.php` has `stageHunk()` and `unstageHunk()` at lines 202-230 — discard follows same pattern
- `DiffService.php` has `stageHunk()` using `git apply --cached` and `unstageHunk()` using `git apply --cached --reverse`
- For discarding, we need `git apply --reverse` (without `--cached`) to apply reverse patch to working tree
- This is destructive — cannot be undone without stashing first
- `StagingPanel.php:133-141` has `discardFile()` but no per-hunk discard
- `SettingsModal.php:21` has `confirmDiscard` setting — should respect this

---

## Work Objectives

### Core Objective
Allow users to discard individual hunks from the diff viewer, reverting specific changes while keeping others.

### Must Have
- Discard button per hunk (for unstaged changes only)
- Confirmation dialog (respecting `confirmDiscard` setting)
- Working tree is modified (hunk is reversed)
- Diff view refreshes after discard

### Must NOT Have
- No discarding staged hunks (must unstage first)
- No multi-hunk discard in one action
- No undo (this is intentionally destructive)

---

## TODOs

- [ ] 1. Add DiffService::discardHunk() method

  **What to do**:
  - Add `discardHunk(DiffFile $file, Hunk $hunk): void` to `DiffService`
  - Generate patch using existing `generatePatch()` method
  - Apply with `git apply --reverse` (not `--cached` — applies to working tree)
  - Invalidate status cache

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/DiffService.php:24-33` — `stageHunk()` and `unstageHunk()` patterns
  - `app/Services/Git/DiffService.php:56-73` — `generatePatch()` helper

  **Acceptance Criteria**:
  - [ ] `DiffService::discardHunk($file, $hunk)` executes `git apply --reverse`
  - [ ] Working tree file is modified to remove the hunk's changes

  **Commit**: YES
  - Message: `feat(backend): add DiffService::discardHunk method`
  - Files: `app/Services/Git/DiffService.php`

- [ ] 2. Add discard hunk button to DiffViewer component and view

  **What to do**:
  - Add `discardHunk(int $fileIndex, int $hunkIndex): void` to `DiffViewer.php`
  - Follow same pattern as `stageHunk()` — hydrate DTOs, call service, refresh
  - Only show discard button for unstaged diffs (`!$isStaged`)
  - Add confirmation: check settings for `confirmDiscard`, if true show confirm dialog before proceeding
  - Add discard button to hunk header in `diff-viewer.blade.php` using `<x-phosphor-trash class="w-3.5 h-3.5" />` icon
  - Wrap in `<flux:tooltip content="Discard Hunk">`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/DiffViewer.php:202-214` — `stageHunk()` pattern to follow
  - `resources/views/livewire/diff-viewer.blade.php` — Hunk header area with existing stage/unstage buttons
  - `app/Livewire/SettingsModal.php:21` — `confirmDiscard` setting
  - `app/Services/SettingsService.php` — Reading settings

  **Acceptance Criteria**:
  - [ ] Discard button visible on unstaged hunks
  - [ ] Not visible on staged hunks
  - [ ] Confirmation dialog shown when `confirmDiscard` is true
  - [ ] Diff refreshes after discard

  **QA Scenarios**:

  ```
  Scenario: Discard a hunk from unstaged changes
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with modified files
      2. Select a modified file (unstaged)
      3. Find hunk header in diff viewer
      4. Click discard hunk button (trash icon)
      5. If confirmation dialog appears, confirm
      6. Assert diff view refreshes (hunk removed or file removed from changes)
    Expected Result: Hunk discarded, working tree updated
    Evidence: .sisyphus/evidence/task-2-discard-hunk.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add discard hunk button to diff viewer`
  - Files: `app/Livewire/DiffViewer.php`, `resources/views/livewire/diff-viewer.blade.php`

- [ ] 3. Pest tests for discard hunk

  **What to do**:
  - Add tests to `tests/Feature/Services/DiffServiceTest.php` for `discardHunk()`
  - Add tests to `tests/Feature/Livewire/DiffViewerTest.php` for discard action
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `tests/Feature/Services/DiffServiceTest.php`
  - `tests/Feature/Livewire/DiffViewerTest.php`

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=DiffService` → all pass
  - [ ] `php artisan test --compact --filter=DiffViewer` → all pass

  **Commit**: YES
  - Message: `test(panels): add tests for discard hunk`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=DiffService  # Expected: all pass
php artisan test --compact --filter=DiffViewer  # Expected: all pass
```
