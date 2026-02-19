# History Hover Actions

## TL;DR

> **Quick Summary**: Add quick-action buttons that appear on hover over commit items in the history panel — checkout, cherry-pick, copy SHA, reset to here — reducing the need for right-click context menus.
> 
> **Deliverables**:
> - Hover-revealed action buttons on commit items
> - Actions: Copy SHA, Checkout, Cherry-pick, Reset, Revert
> - Tooltip on each action button
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2

---

## Context

### Research Findings
- `HistoryPanel.php` has `selectCommit()`, `promptReset()`, `promptRevert()`, `promptCherryPick()` — all the actions exist
- Current UX: user must click commit to select, then use separate buttons (if they exist)
- No hover-state actions currently on commit items
- Pattern: show icon buttons on the right side of the commit row on hover, like GitHub's commit list

---

## Work Objectives

### Must Have
- Hover-revealed action row on commit items (appears on right, pushes other content)
- Actions (left to right): Copy SHA, Cherry-pick, Revert, Reset
- Each action wrapped in `<flux:tooltip>`
- Actions hidden when not hovering
- Copy SHA uses Alpine.js clipboard: `navigator.clipboard.writeText()`

### Must NOT Have
- No new functionality — all actions already exist in the component
- No replacing existing click-to-select behavior

---

## TODOs

- [ ] 1. Add hover actions to history-panel Blade view

  **What to do**:
  - In `history-panel.blade.php`, add an action button row to each commit item
  - Use CSS `group/commit` on the commit row and `opacity-0 group-hover/commit:opacity-100` on the action container
  - Buttons: Copy SHA (clipboard icon), Cherry-pick (git-merge icon), Revert (arrow-counter-clockwise), Reset (arrow-bend-down-left)
  - Each button uses `wire:click` to call existing HistoryPanel methods
  - Copy SHA uses Alpine `@click="navigator.clipboard.writeText('{{ $commit->sha }}')"` — no Livewire roundtrip needed
  - All buttons wrapped in `<flux:tooltip>`
  - Size: `xs` square ghost buttons
  - Prevent click propagation so hovering+clicking action doesn't also select the commit

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 2
  - **Blocked By**: None

  **References**:
  - `resources/views/livewire/history-panel.blade.php` — Commit item rendering, existing `wire:click="selectCommit()"` handlers
  - `app/Livewire/HistoryPanel.php:134-238` — Existing action methods (promptReset, promptRevert, promptCherryPick)
  - `resources/views/livewire/staging-panel.blade.php` — File item hover action buttons pattern
  - `AGENTS.md` — Icon conventions, tooltip pattern, hover state colors

  **Acceptance Criteria**:
  - [ ] Action buttons appear on commit hover
  - [ ] Hidden when not hovering
  - [ ] Copy SHA copies to clipboard without Livewire roundtrip
  - [ ] Cherry-pick, Revert, Reset call existing methods
  - [ ] Click on action doesn't also select the commit

  **QA Scenarios**:

  ```
  Scenario: Hover actions appear on commit item
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with commits
      2. Hover over a commit in history panel
      3. Assert action buttons become visible (opacity transition)
      4. Click "Copy SHA" button
      5. Assert clipboard contains SHA (or assert no error)
    Expected Result: Action buttons visible on hover, copy works
    Evidence: .sisyphus/evidence/task-1-hover-actions.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add hover action buttons to history panel commits`
  - Files: `resources/views/livewire/history-panel.blade.php`

- [ ] 2. Pest tests for hover actions

  **What to do**:
  - Add tests to `tests/Feature/Livewire/HistoryPanelTest.php` verifying action buttons render in view
  - Test that calling action methods works (promptReset, etc. already tested, just verify wiring)
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Task 1

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=HistoryPanel` → all pass

  **Commit**: YES
  - Message: `test(panels): add tests for history hover actions`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=HistoryPanel  # Expected: all pass
```
