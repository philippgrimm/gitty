# Tree View Folder Badges

## TL;DR

> **Quick Summary**: Add modified file count badges to folders in the tree view, so users can see at a glance how many changes are in each directory.
> 
> **Deliverables**:
> - File count badge on each folder in tree view
> - Color-coded by most severe status in folder
> - Updated `FileTreeBuilder` to compute counts
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `FileTreeBuilder.php` builds tree structure from flat file list — currently only stores file paths
- `resources/views/components/file-tree.blade.php` renders the recursive tree with folders and files
- Tree items have `type` (file/directory) and `children` — need to add `count` and `dominantStatus`
- Status priority for folder color: deleted > modified > added > untracked

---

## Work Objectives

### Must Have
- Numeric badge on folders showing count of changed files (recursive)
- Badge color matches most severe status in folder
- Updates when files are staged/unstaged
- Works in both staged and unstaged tree sections

### Must NOT Have
- No per-status breakdown on folders (just total count + dominant color)
- No nested folder expansion on badge click

---

## TODOs

- [ ] 1. Extend FileTreeBuilder to compute folder statistics

  **What to do**:
  - Modify `FileTreeBuilder::buildTree()` to add `fileCount` (recursive child count) and `dominantStatus` to directory nodes
  - Status priority: D (deleted) > M (modified) > A (added) > ? (untracked)
  - `dominantStatus` = highest priority status among all descendant files

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Helpers/FileTreeBuilder.php` — Current tree builder
  - `tests/Feature/FileTreeBuilderTest.php` — Existing tests

  **Acceptance Criteria**:
  - [ ] Directory nodes have `fileCount` with recursive count
  - [ ] `dominantStatus` reflects highest-priority status

  **Commit**: YES
  - Message: `feat(backend): add folder statistics to FileTreeBuilder`
  - Files: `app/Helpers/FileTreeBuilder.php`

- [ ] 2. Render folder badges in file-tree Blade component

  **What to do**:
  - In `file-tree.blade.php`, add badge next to folder names
  - Badge: small rounded pill showing count (e.g., "3")
  - Color based on `dominantStatus` using Catppuccin status colors (same as file status dots)
  - Use `bg-[{color}]15` for badge background (15% opacity), `text-[{color}]` for text
  - Size: `text-xs px-1.5 py-0.5 rounded-full`

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `resources/views/components/file-tree.blade.php` — Recursive tree component
  - `AGENTS.md` — Status dot colors, tree view rules, color system

  **Acceptance Criteria**:
  - [ ] Folder badges visible with file count
  - [ ] Badge color matches dominant status
  - [ ] Badges update on staging changes

  **QA Scenarios**:

  ```
  Scenario: Folder badges show correct counts
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with modified files in multiple directories
      2. Toggle tree view on
      3. Assert folder items have numeric badges
      4. Assert badge count matches number of files in folder
    Expected Result: Badges visible with correct counts and colors
    Evidence: .sisyphus/evidence/task-2-folder-badges.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add folder count badges to tree view`
  - Files: `resources/views/components/file-tree.blade.php`

- [ ] 3. Pest tests for folder badges

  **What to do**:
  - Extend `tests/Feature/FileTreeBuilderTest.php` for folder statistics
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=FileTreeBuilder` → all pass

  **Commit**: YES
  - Message: `test(staging): add tests for folder badge statistics`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=FileTreeBuilder  # Expected: all pass
```
