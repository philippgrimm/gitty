# Consistent Destructive Confirmations

## TL;DR

> **Quick Summary**: Standardize all destructive operation confirmation dialogs across the app — discard, force push, hard reset, branch deletion, remote deletion — to use a consistent UI pattern, copy, and behavior.
> 
> **Deliverables**:
> - Shared confirmation dialog Blade component
> - All destructive operations use the same pattern
> - Consistent copy: action description, consequences, confirmation text
> - Settings respect (confirmDiscard, confirmForcePush)
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Research Findings
- `HistoryPanel.php:134-168` has reset modal with typed "DISCARD" confirmation
- `CommitPanel.php:329-355` has undo last commit confirmation
- `StagingPanel.php:133-141` has `discardFile()` with no confirmation (relies on setting)
- `BranchManager.php:105-126` has `deleteBranch()` with no confirmation
- `SettingsModal.php:21-23` has `confirmDiscard` and `confirmForcePush` settings
- Inconsistency: some operations confirm, some don't; different modal styles
- Need a shared `<x-confirm-dialog>` Blade component

---

## Work Objectives

### Core Objective
Create a consistent confirmation pattern for all destructive git operations.

### Must Have
- Shared `<x-confirm-dialog>` component with configurable:
  - Title, description, consequence warning
  - Confirm button text and variant (danger)
  - Optional typed confirmation (like "DISCARD")
  - Cancel button
- Applied to: discard file, discard all, hard reset, force push, delete branch, delete remote branch
- Respect existing settings: `confirmDiscard`, `confirmForcePush`
- Red accent for danger operations

### Must NOT Have
- No new settings (reuse existing confirmDiscard, confirmForcePush)
- No confirmation for non-destructive operations (stage, unstage, commit, pull)

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

---

## TODOs

- [ ] 1. Create shared confirm-dialog Blade component

  **What to do**:
  - Create `resources/views/components/confirm-dialog.blade.php` component
  - Props: `title`, `description`, `confirmText` (button label), `cancelText`, `variant` ('danger'|'warning'), `requireTyped` (optional string user must type), `wire:model` for visibility
  - Use `<flux:modal>` as the base
  - Danger variant: red confirm button (`variant="danger"`), warning icon
  - Include consequence text in muted/secondary color
  - Escape key and click-outside cancel
  - Focus trap inside dialog

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`fluxui-development`, `tailwindcss-development`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None

  **References**:
  - `resources/views/livewire/history-panel.blade.php` — Existing reset modal (inline, not component)
  - `resources/views/livewire/commit-panel.blade.php` — Existing undo confirmation
  - `AGENTS.md` — Color system (red for danger: `#d20f39`), Flux modal usage

  **Acceptance Criteria**:
  - [ ] Component renders with title, description, buttons
  - [ ] Danger variant has red button
  - [ ] Typed confirmation works when required
  - [ ] Escape/click-outside cancels

  **Commit**: YES
  - Message: `feat(polish): create shared confirm-dialog Blade component`
  - Files: `resources/views/components/confirm-dialog.blade.php`

- [ ] 2. Apply confirm-dialog to staging panel destructive operations

  **What to do**:
  - Replace inline confirmation in `StagingPanel` for: `discardFile()`, `discardAll()`, `discardSelected()`
  - Add confirmation state properties to StagingPanel if not present
  - Use `<x-confirm-dialog>` with appropriate messages
  - Respect `confirmDiscard` setting — skip dialog if setting is false
  - Messages: "Discard changes to {filename}? This cannot be undone."

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/StagingPanel.php:133-151` — `discardFile()`, `discardAll()`
  - `resources/views/livewire/staging-panel.blade.php` — Discard button rendering

  **Acceptance Criteria**:
  - [ ] Discard file shows confirm dialog (when setting enabled)
  - [ ] Discard all shows confirm dialog
  - [ ] Dialogs use shared component

  **Commit**: YES
  - Message: `feat(staging): apply consistent confirm dialogs to discard operations`

- [ ] 3. Apply confirm-dialog to history, branch, and sync operations

  **What to do**:
  - Replace inline reset modal in `HistoryPanel` with `<x-confirm-dialog requireTyped="DISCARD">` for hard reset
  - Add confirmation to `BranchManager::deleteBranch()` — "Delete branch {name}? This cannot be undone."
  - Add confirmation to force push in `SyncPanel` — respect `confirmForcePush` setting
  - Replace inline undo confirmation in `CommitPanel` with shared component

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/history-panel.blade.php` — Existing reset modal
  - `app/Livewire/HistoryPanel.php:134-168` — Reset flow
  - `app/Livewire/BranchManager.php:105-126` — Delete branch
  - `app/Livewire/CommitPanel.php:329-355` — Undo last commit

  **Acceptance Criteria**:
  - [ ] Hard reset uses shared confirm with typed "DISCARD"
  - [ ] Branch delete uses shared confirm
  - [ ] Force push uses shared confirm (when setting enabled)
  - [ ] All dialogs visually consistent

  **QA Scenarios**:

  ```
  Scenario: Consistent confirmation dialogs across operations
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Try to discard a file → assert confirm dialog appears with danger styling
      3. Cancel
      4. Try to delete a branch → assert confirm dialog appears
      5. Cancel
      6. Open reset modal → assert typed confirmation required for hard reset
      7. Assert all dialogs have same visual style (red button, similar layout)
    Expected Result: All destructive operations use same confirmation pattern
    Evidence: .sisyphus/evidence/task-3-consistent-confirms.png
  ```

  **Commit**: YES
  - Message: `feat(polish): apply consistent confirm dialogs to history, branch, sync`

- [ ] 4. Pest tests for confirmation dialogs

  **What to do**:
  - Add tests verifying confirm dialog component renders correctly
  - Add tests for each operation's confirmation flow (discard, delete branch, reset)
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 3

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → all pass (no regressions)

  **Commit**: YES
  - Message: `test(polish): add tests for consistent confirmation dialogs`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact  # Expected: all pass (no regressions from refactoring)
vendor/bin/pint --dirty --format agent  # Expected: no issues
```
