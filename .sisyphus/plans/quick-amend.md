# Quick Amend

## TL;DR

> **Quick Summary**: Add a one-click "Amend" button that stages all current changes and amends them into the last commit, keeping the same commit message. Faster than toggle amend → commit workflow.
> 
> **Deliverables**:
> - Quick amend action in commit panel
> - Safety checks (last commit not pushed, not a merge commit)
> - Keyboard shortcut
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `CommitPanel.php:221-232` has `toggleAmend()` which loads last commit message — but requires manual toggle + commit
- `CommitService.php:19-25` has `commitAmend()` — already supports amend
- `CommitService.php:52-68` has `isLastCommitPushed()` — can use for safety check
- `CommitService.php:70-75` has `isLastCommitMerge()` — can use for safety check
- Quick amend = stage all + amend with existing message in one click

---

## Work Objectives

### Must Have
- "Quick Amend" button (stages all changes + amends to last commit with same message)
- Safety: refuse if last commit is pushed to remote
- Safety: refuse if last commit is a merge commit
- Confirmation dialog (destructive operation)
- Keyboard shortcut registration

### Must NOT Have
- No message editing (that's the existing toggle amend flow)
- No interactive amend of specific files only

---

## TODOs

- [ ] 1. Add quickAmend method to CommitPanel

  **What to do**:
  - Add `quickAmend(): void` to `CommitPanel.php`
  - Check `isLastCommitPushed()` — if true, dispatch error
  - Check `isLastCommitMerge()` — if true, dispatch error
  - Stage all changes (`StagingService::stageAll()`)
  - Get last commit message (`CommitService::lastCommitMessage()`)
  - Amend commit (`CommitService::commitAmend($lastMessage)`)
  - Dispatch success notification
  - Refresh staging and history

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Livewire/CommitPanel.php:146-184` — `commit()` pattern to follow
  - `app/Livewire/CommitPanel.php:221-232` — Existing `toggleAmend()` for reference
  - `app/Services/Git/CommitService.php:19-25` — `commitAmend()`
  - `app/Services/Git/CommitService.php:52-75` — Safety checks
  - `app/Services/Git/StagingService.php` — `stageAll()`

  **Acceptance Criteria**:
  - [ ] `quickAmend()` stages all + amends with last message
  - [ ] Refuses when last commit is pushed
  - [ ] Refuses when last commit is merge

  **Commit**: YES
  - Message: `feat(backend): add quickAmend method to CommitPanel`
  - Files: `app/Livewire/CommitPanel.php`

- [ ] 2. Add quick amend UI and keyboard shortcut

  **What to do**:
  - Add "Quick Amend" button in commit panel (below commit button or in split button menu)
  - Use `<flux:menu.item icon="pencil-simple">Quick Amend</flux:menu.item>` in commit split button dropdown
  - Add confirmation dialog before executing
  - Register keyboard shortcut (e.g., `⌘⇧A`) in `app-layout.blade.php`
  - Add to command palette: "Quick Amend Last Commit"

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `fluxui-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `resources/views/livewire/commit-panel.blade.php` — Commit button area with split button
  - `resources/views/layouts/app.blade.php` — Keyboard shortcut registration
  - `app/Livewire/CommandPalette.php` — Command registration

  **Acceptance Criteria**:
  - [ ] "Quick Amend" in commit dropdown menu
  - [ ] Confirmation dialog before amend
  - [ ] Keyboard shortcut works
  - [ ] Available in command palette

  **QA Scenarios**:

  ```
  Scenario: Quick amend via dropdown
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with staged changes and at least 1 unpushed commit
      2. Click commit dropdown chevron
      3. Click "Quick Amend"
      4. Confirm in dialog
      5. Assert success notification
      6. Assert staging panel clears
    Expected Result: Changes amended to last commit
    Evidence: .sisyphus/evidence/task-2-quick-amend.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add quick amend UI and keyboard shortcut`

- [ ] 3. Pest tests for quick amend

  **What to do**:
  - Add tests to `tests/Feature/Livewire/CommitPanelTest.php` for `quickAmend()`
  - Test: success case, refuse when pushed, refuse when merge
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=CommitPanel` → all pass

  **Commit**: YES
  - Message: `test(panels): add tests for quick amend`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=CommitPanel  # Expected: all pass
```
