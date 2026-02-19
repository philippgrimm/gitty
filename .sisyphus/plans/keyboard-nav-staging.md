# Keyboard Navigation in Staging Panel

## TL;DR

> **Quick Summary**: Add full keyboard navigation to the staging panel file list — arrow keys to navigate files, Space to stage/unstage, Enter to view diff, Delete to discard.
> 
> **Deliverables**:
> - Arrow key navigation through file items (up/down)
> - Space key toggles stage/unstage for focused file
> - Enter key selects file for diff viewing
> - Delete key discards focused file
> - Visual focus indicator (highlight ring)
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2

---

## Context

### Research Findings
- `StagingPanel.php` has `stageFile()`, `unstageFile()`, `discardFile()`, `selectFile()` — all actions exist
- Currently all interactions are mouse-only (click to select, click buttons to act)
- Alpine.js can handle keyboard events with `@keydown` directives
- Need to track a "focused file index" in Alpine state
- Should support both staged and unstaged sections independently

---

## Work Objectives

### Must Have
- Arrow Up/Down moves focus between file items
- Tab switches between unstaged and staged sections
- Space stages or unstages the focused file
- Enter selects focused file (shows diff)
- Delete/Backspace discards focused file (with confirmation if enabled)
- Visual focus ring on focused item (`ring-2 ring-[#084CCF]`)
- Focus doesn't interfere with commit message textarea

### Must NOT Have
- No drag-and-drop (that's a separate plan)
- No multi-select via keyboard (just single-item focus)
- No vim-style navigation (j/k)

---

## TODOs

- [ ] 1. Implement keyboard navigation with Alpine.js

  **What to do**:
  - Add Alpine.js `x-data` to staging panel container with `focusedIndex`, `focusedSection` ('unstaged'|'staged')
  - Add `@keydown` handlers on the staging panel container:
    - ArrowUp/ArrowDown: move focusedIndex within current section
    - Tab: switch between unstaged/staged sections
    - Space: call `$wire.stageFile()` or `$wire.unstageFile()` based on section
    - Enter: call `$wire.selectFile()`
    - Delete/Backspace: call `$wire.discardFile()` (only for unstaged)
  - Add `tabindex="0"` to the staging panel container for keyboard focus
  - Add visual focus indicator: `ring-2 ring-[#084CCF]` on focused file item
  - Prevent keyboard events from bubbling when commit textarea is focused
  - Handle edge cases: empty sections, section becomes empty after staging

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 2
  - **Blocked By**: None

  **References**:
  - `resources/views/livewire/staging-panel.blade.php` — File list rendering, section structure
  - `resources/views/layouts/app.blade.php` — Global keyboard shortcuts (ensure no conflicts)
  - `app/Livewire/StagingPanel.php:91-108` — `stageFile()`, `unstageFile()` methods
  - `AGENTS.md` — Accent color `#084CCF` for focus ring

  **Acceptance Criteria**:
  - [ ] Arrow keys navigate between file items
  - [ ] Space stages/unstages focused file
  - [ ] Enter selects file for diff
  - [ ] Visual focus indicator visible
  - [ ] Keyboard doesn't interfere with commit textarea

  **QA Scenarios**:

  ```
  Scenario: Navigate and stage file with keyboard
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with modified files
      2. Click staging panel to focus it
      3. Press ArrowDown to focus first unstaged file
      4. Assert focus ring visible on first file
      5. Press Space to stage the file
      6. Assert file moves to staged section
      7. Press Tab to switch to staged section
      8. Press ArrowDown to focus staged file
      9. Press Space to unstage
      10. Assert file returns to unstaged
    Expected Result: Full keyboard-driven staging workflow works
    Evidence: .sisyphus/evidence/task-1-keyboard-nav.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add keyboard navigation to staging panel`
  - Files: `resources/views/livewire/staging-panel.blade.php`

- [ ] 2. Pest tests for keyboard navigation

  **What to do**:
  - Add tests to `tests/Feature/Livewire/StagingPanelTest.php` verifying keyboard elements render
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Task 1

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=StagingPanel` → all pass

  **Commit**: YES
  - Message: `test(staging): add tests for keyboard navigation`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=StagingPanel  # Expected: all pass
```
