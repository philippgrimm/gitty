# Settings Shortcut (⌘,)

## TL;DR

> **Quick Summary**: Add the standard macOS `⌘,` keyboard shortcut to open the settings modal, matching platform conventions.
> 
> **Deliverables**:
> - `⌘,` keyboard shortcut registration
> - Dispatches `open-settings` event to SettingsModal
> - Test
> 
> **Estimated Effort**: Quick
> **Parallel Execution**: NO — 1 task
> **Critical Path**: Task 1

---

## Context

### Research Findings
- `SettingsModal.php:38-42` already listens for `open-settings` event via `#[On('open-settings')]`
- `resources/views/layouts/app.blade.php` has the keyboard shortcut registration area
- Currently settings is only accessible via command palette or menu
- `⌘,` is the universal macOS shortcut for preferences/settings
- Existing shortcuts use `@keydown.window` Alpine.js pattern

---

## Work Objectives

### Must Have
- `⌘,` opens settings modal
- No conflict with other shortcuts

### Must NOT Have
- No new UI elements
- No changes to SettingsModal component

---

## TODOs

- [ ] 1. Register ⌘, keyboard shortcut

  **What to do**:
  - Add keyboard listener in `resources/views/layouts/app.blade.php` for `Meta+,` (Cmd+comma)
  - Dispatch `open-settings` Livewire event: `Livewire.dispatch('open-settings')`
  - Prevent default browser behavior for this shortcut
  - Update `ShortcutHelp` component data to include ⌘, for Settings

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Single task
  - **Blocks**: None
  - **Blocked By**: None

  **References**:
  - `resources/views/layouts/app.blade.php` — Keyboard shortcut registration area
  - `app/Livewire/SettingsModal.php:38-42` — Already listens for `open-settings`
  - `app/Livewire/ShortcutHelp.php` — Shortcut help data to update

  **Acceptance Criteria**:
  - [ ] `⌘,` opens settings modal
  - [ ] No conflict with existing shortcuts
  - [ ] Listed in shortcut help

  **QA Scenarios**:

  ```
  Scenario: ⌘, opens settings
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Press Cmd+, (Meta+Comma)
      3. Assert settings modal appears
    Expected Result: Settings modal opens
    Evidence: .sisyphus/evidence/task-1-settings-shortcut.png
  ```

  **Commit**: YES
  - Message: `feat(polish): add ⌘, keyboard shortcut for settings`
  - Files: `resources/views/layouts/app.blade.php`, `app/Livewire/ShortcutHelp.php`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=ShortcutHelp  # Expected: all pass
php artisan test --compact --filter=SettingsModal  # Expected: all pass
```
