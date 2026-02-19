# Commit Message Guide Lines

## TL;DR

> **Quick Summary**: Add visual ruler lines at 50 and 72 characters in the commit message textarea to help users write well-formatted commit messages following git conventions.
> 
> **Deliverables**:
> - Visual character count indicators in commit panel textarea
> - Line at 50 chars (subject line limit) and 72 chars (body wrap limit)
> - Character counter showing current position
> - CSS/Alpine.js for the visual overlay
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2

---

## Context

### Research Findings
- `CommitPanel.php` manages the commit message textarea
- `resources/views/livewire/commit-panel.blade.php` has the textarea element
- Git convention: subject line ≤50 chars, body lines ≤72 chars
- Approach: use a monospace font overlay with CSS pseudo-elements or Alpine.js computed positions
- The textarea already uses `font-mono` (JetBrains Mono)

---

## Work Objectives

### Must Have
- Visual vertical line at column 50 (subject limit) — subtle, like an editor ruler
- Visual vertical line at column 72 (body wrap limit)
- Character counter showing current line length
- Color change when exceeding limits (yellow at 50+, red at 72+)

### Must NOT Have
- No hard character limits (don't prevent typing)
- No auto-wrapping
- No enforcement — purely visual guidance

---

## TODOs

- [ ] 1. Add guide lines CSS and Alpine.js character tracking

  **What to do**:
  - In `commit-panel.blade.php`, wrap textarea in a container with relative positioning
  - Use Alpine.js `x-data` to track cursor position and line lengths
  - Calculate character position using monospace font metrics (character width = constant)
  - Add CSS pseudo-element or `<div>` overlays for the 50-char and 72-char vertical lines
  - Lines should be subtle: `border-left: 1px dashed rgba(0,0,0,0.1)`
  - Add character counter below textarea: "Line 1: 42/50" or "Line 2: 68/72"
  - Color indicator: green (≤50), yellow (51-72), red (>72)
  - Add CSS classes to `resources/css/app.css` for guide line styling

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 2
  - **Blocked By**: None

  **References**:
  - `resources/views/livewire/commit-panel.blade.php` — Textarea element to enhance
  - `resources/css/app.css` — Add guide line CSS
  - `AGENTS.md` — Font system (JetBrains Mono for monospace), color system

  **Acceptance Criteria**:
  - [ ] Vertical guide lines visible at columns 50 and 72
  - [ ] Character counter updates as user types
  - [ ] Color changes at thresholds
  - [ ] Lines don't interfere with typing

  **QA Scenarios**:

  ```
  Scenario: Guide lines visible in commit textarea
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Focus commit message textarea
      3. Type a message longer than 50 characters
      4. Assert character counter shows count > 50
      5. Assert counter color changes (yellow indicator)
      6. Take screenshot showing guide lines
    Expected Result: Visual rulers at 50 and 72 chars, counter shows yellow
    Evidence: .sisyphus/evidence/task-1-guide-lines.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add commit message guide lines at 50/72 characters`
  - Files: `resources/views/livewire/commit-panel.blade.php`, `resources/css/app.css`

- [ ] 2. Pest tests for guide line rendering

  **What to do**:
  - Add tests to `tests/Feature/Livewire/CommitPanelTest.php` verifying textarea renders with guide line container
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Task 1

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=CommitPanel` → all pass

  **Commit**: YES
  - Message: `test(panels): add tests for commit message guide lines`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=CommitPanel  # Expected: all pass
```
