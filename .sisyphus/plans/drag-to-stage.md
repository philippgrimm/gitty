# Drag-to-Stage

## TL;DR

> **Quick Summary**: Add drag-and-drop support so users can drag files between the unstaged and staged sections of the staging panel to stage or unstage them.
> 
> **Deliverables**:
> - Drag-and-drop interaction between unstaged/staged sections
> - Visual drag indicator and drop zone highlighting
> - Multi-file drag support (for selected files)
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `StagingPanel.php` has `stageFile()`, `unstageFile()`, `stageSelected()`, `unstageSelected()` — all actions exist
- HTML5 Drag and Drop API works in Electron (Chromium-based)
- Alpine.js can handle `@dragstart`, `@dragover`, `@drop` events
- Need to handle: single file drag, multi-file drag (from selection), visual feedback
- Similar to Finder drag-and-drop UX

---

## Work Objectives

### Must Have
- Drag file items from unstaged to staged (stages them)
- Drag file items from staged to unstaged (unstages them)
- Visual drop zone indicator (highlight target section)
- Drag ghost showing file name
- Works for single files

### Must NOT Have
- No drag to external applications
- No drag from external (drag files from Finder into gitty)
- No reordering within same section

---

## TODOs

- [ ] 1. Implement drag-and-drop with Alpine.js

  **What to do**:
  - Add `draggable="true"` to file items in `staging-panel.blade.php`
  - Use Alpine.js `x-data` for drag state management
  - `@dragstart`: set drag data (file path, source section)
  - `@dragover.prevent`: enable drop zone, add visual highlight
  - `@dragleave`: remove highlight
  - `@drop`: call `$wire.stageFile()` or `$wire.unstageFile()` based on drop target
  - Drop zone highlight: `border-2 border-dashed border-[#084CCF] bg-[rgba(8,76,207,0.05)]`
  - Drag cursor: `cursor-grab` on items, `cursor-grabbing` while dragging
  - For multi-file drag: if file is in selected set, drag all selected files

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `resources/views/livewire/staging-panel.blade.php` — File item rendering, section containers
  - `app/Livewire/StagingPanel.php:91-108` — `stageFile()`, `unstageFile()` methods
  - `app/Livewire/StagingPanel.php:158-198` — `stageSelected()`, `unstageSelected()` for multi-file

  **Acceptance Criteria**:
  - [ ] Can drag file from unstaged to staged section
  - [ ] Can drag file from staged to unstaged section
  - [ ] Drop zone highlights on dragover
  - [ ] File actually stages/unstages on drop

  **QA Scenarios**:

  ```
  Scenario: Drag file from unstaged to staged
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with unstaged files
      2. Locate first unstaged file item
      3. Drag it to the staged section drop zone
      4. Assert file appears in staged section
      5. Assert file removed from unstaged section
    Expected Result: File moved to staged via drag-and-drop
    Evidence: .sisyphus/evidence/task-1-drag-stage.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add drag-and-drop between staging sections`
  - Files: `resources/views/livewire/staging-panel.blade.php`

- [ ] 2. Add drag visual styling

  **What to do**:
  - Add CSS classes for drag states to `resources/css/app.css`:
    - `.drag-over`: dashed border + accent tint background
    - `.dragging`: opacity reduction on source item
    - `.drag-ghost`: styling for the drag image
  - Add grab cursor on draggable items
  - Animate drop zone highlight transition

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `resources/css/app.css` — Animation definitions, existing utility classes

  **Acceptance Criteria**:
  - [ ] Drop zone visually highlighted during drag
  - [ ] Source item shows dragging state
  - [ ] Smooth transitions

  **Commit**: YES (groups with Task 1)
  - Message: `design(staging): add drag-and-drop visual styles`
  - Files: `resources/css/app.css`

- [ ] 3. Pest tests for drag-to-stage

  **What to do**:
  - Add tests to `tests/Feature/Livewire/StagingPanelTest.php` verifying draggable attributes render
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=StagingPanel` → all pass

  **Commit**: YES
  - Message: `test(staging): add tests for drag-to-stage`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=StagingPanel  # Expected: all pass
```
