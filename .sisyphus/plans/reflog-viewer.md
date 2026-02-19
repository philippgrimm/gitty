# Reflog Viewer

## TL;DR

> **Quick Summary**: Add a reflog viewer so users can see and recover from recent git operations — undo accidental resets, find lost commits, and restore deleted branches.
> 
> **Deliverables**:
> - `GitService::reflog()` method
> - Reflog DTO
> - Reflog panel UI accessible from history panel or command palette
> - "Checkout" action on reflog entries for recovery
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Research Findings
- No reflog support exists in the codebase
- Git reflog: `git reflog --format='%H|||%gs|||%gd|||%ci'` provides SHA, action, ref, date
- Reflog is essential for recovery (undo reset, find lost branches, recover deleted work)
- Can recover by checking out a reflog SHA: `git checkout {sha}` or `git reset --hard {sha}`
- Common reflog actions: checkout, commit, reset, merge, rebase, cherry-pick

---

## Work Objectives

### Must Have
- Reflog entry list with SHA, action description, ref, date
- "Checkout" action on entries (creates detached HEAD or new branch from that point)
- "Create branch here" action (creates branch at reflog SHA)
- Search/filter reflog entries
- Accessible from command palette and history panel

### Must NOT Have
- No reflog editing or pruning
- No automatic recovery suggestions
- No reflog for specific refs (just HEAD reflog)

---

## TODOs

- [ ] 1. Add reflog service method and DTO

  **What to do**:
  - Create `app/DTOs/ReflogEntry.php` with: sha, shortSha, action, description, date
  - Add `reflog(int $limit = 100): Collection` to `GitService`
  - Parse `git reflog --format='%H|||%gs|||%gd|||%ci' -n {limit}`
  - Add static factory `ReflogEntry::fromReflogLine(string $line)`

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/GitService.php:29-58` — `log()` method pattern
  - `app/DTOs/Commit.php` — DTO pattern with static factory
  - `app/DTOs/Stash.php` — Similar parsing pattern

  **Acceptance Criteria**:
  - [ ] `GitService::reflog()` returns Collection of ReflogEntry DTOs
  - [ ] Each entry has sha, action, description, date

  **Commit**: YES
  - Message: `feat(backend): add reflog support to GitService with ReflogEntry DTO`
  - Files: `app/DTOs/ReflogEntry.php`, `app/Services/Git/GitService.php`

- [ ] 2. Create ReflogViewer Livewire component

  **What to do**:
  - Create `app/Livewire/ReflogViewer.php` listening for `show-reflog` event
  - Load reflog entries on mount/event
  - Methods: `checkoutEntry(string $sha)`, `createBranchFromEntry(string $sha, string $branchName)`
  - Search filter for reflog entries (by action description)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1 once started)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 3, 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/HistoryPanel.php` — Commit list pattern
  - `app/Livewire/BlameView.php` — Overlay panel pattern

  **Acceptance Criteria**:
  - [ ] Component loads and displays reflog entries
  - [ ] Can checkout a reflog entry
  - [ ] Can create branch from reflog entry

  **Commit**: YES
  - Message: `feat(panels): create ReflogViewer Livewire component`
  - Files: `app/Livewire/ReflogViewer.php`

- [ ] 3. Build reflog Blade view and wire into app

  **What to do**:
  - Create `resources/views/livewire/reflog-viewer.blade.php`
  - Entry list: SHA (short), action icon, description, relative date
  - Action buttons: "Checkout", "Create Branch"
  - Search input at top
  - Wire into app-layout, accessible from command palette ("Show Reflog")
  - Add button in history panel header

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/history-panel.blade.php` — Commit list rendering
  - `resources/views/livewire/blame-view.blade.php` — Panel overlay
  - `app/Livewire/CommandPalette.php` — Add command

  **Acceptance Criteria**:
  - [ ] Reflog entries displayed with action descriptions
  - [ ] "Create Branch" shows input for branch name
  - [ ] Accessible from command palette

  **QA Scenarios**:

  ```
  Scenario: View reflog and create branch from entry
    Tool: Playwright (playwright skill)
    Steps:
      1. Open command palette
      2. Type "Reflog"
      3. Select "Show Reflog"
      4. Assert reflog entries visible with SHA and action descriptions
      5. Click "Create Branch" on an entry
      6. Type "recovery-branch" in branch name input
      7. Confirm
      8. Assert success notification
    Expected Result: Branch created at reflog entry's SHA
    Evidence: .sisyphus/evidence/task-3-reflog-viewer.png
  ```

  **Commit**: YES
  - Message: `feat(panels): build reflog viewer UI and wire into app`

- [ ] 4. Pest tests for reflog

  **What to do**:
  - Add tests to `tests/Feature/Services/GitServiceTest.php` for `reflog()`
  - Create `tests/Feature/Livewire/ReflogViewerTest.php`
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Acceptance Criteria**:
  - [ ] All reflog tests pass

  **Commit**: YES
  - Message: `test(panels): add tests for reflog viewer`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=Reflog  # Expected: all pass
php artisan test --compact --filter=GitService  # Expected: all pass
```
