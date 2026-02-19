# File History

## TL;DR

> **Quick Summary**: Add file history view so users can see all commits that touched a specific file, with per-commit diffs for that file.
> 
> **Deliverables**:
> - `GitService::fileLog()` method returning commits for a single file
> - File history UI accessible from staging panel context menu and diff viewer header
> - Per-commit diff for the selected file
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Original Request
Users should be able to view the commit history for a specific file — who changed it, when, and what changed each time.

### Research Findings
- `GitService::log()` at line 29 already accepts optional `$branch` param but NOT a file path filter
- Git supports `git log --follow -- {file}` for file-specific history including renames
- `CommitService::getCommitDiff()` (from plan #1) can be adapted to show single-file diff: `git diff {sha}~1..{sha} -- {file}`
- `DiffViewer.php:86-93` has `showBlame()` which dispatches `show-blame` — similar pattern for `show-file-history`
- `BlameView.php` provides an existing overlay panel pattern that can be reused

---

## Work Objectives

### Core Objective
Let users view all commits that modified a specific file, with the ability to see the diff for each commit.

### Concrete Deliverables
- `app/Services/Git/GitService.php` — new `fileLog(string $file, int $limit): Collection` method
- `app/Services/Git/CommitService.php` — new `getFileDiff(string $sha, string $file): DiffResult` method
- UI entry point: button in diff viewer header ("History" icon) and right-click context menu in staging panel
- File history panel (could reuse/extend HistoryPanel or create dedicated view)
- `tests/Feature/Services/GitServiceTest.php` — fileLog tests
- `tests/Feature/Livewire/` — file history UI tests

### Definition of Done
- [ ] User can right-click a file or click "History" in diff header to see file history
- [ ] File history shows commits with author, date, message
- [ ] Clicking a commit in file history shows that commit's diff for that specific file
- [ ] Follows rename history (`--follow`)

### Must Have
- File-specific commit list with author, date, subject
- Per-commit diff for the file
- Follow renames (`--follow` flag)
- Entry from diff viewer header

### Must NOT Have (Guardrails)
- No full repo history — only the selected file
- No editing capabilities
- No blame view integration (that's already separate)

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed.

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Foundation):
├── Task 1: Add fileLog() and getFileDiff() service methods [quick]
├── Task 2: Create FileHistory Livewire component + view [unspecified-high]

Wave 2 (Integration + tests):
├── Task 3: Wire into diff viewer header and staging context [quick]
├── Task 4: Pest tests for service and component [unspecified-high]
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | 2, 3, 4 | 1 |
| 2 | 1 | 3, 4 | 1 |
| 3 | 1, 2 | 4 | 2 |
| 4 | 1, 2, 3 | — | 2 |

---

## TODOs

- [ ] 1. Add fileLog() and getFileDiff() service methods

  **What to do**:
  - Add `fileLog(string $file, int $limit = 50): Collection` to `GitService` — runs `git log --follow --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n {limit} -- {file}`
  - Add `getFileDiff(string $sha, string $file): DiffResult` to `CommitService` — runs `git diff {sha}~1..{sha} -- {file}` and parses with `DiffResult::fromDiffOutput()`
  - Handle root commit edge case for getFileDiff

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/GitService.php:29-58` — Existing `log()` method pattern to follow
  - `app/Services/Git/CommitService.php` — Where to add `getFileDiff()`
  - `app/DTOs/DiffResult.php` — `fromDiffOutput()` parser
  - `app/DTOs/Commit.php` — DTO returned by `fileLog()`

  **Acceptance Criteria**:
  - [ ] `GitService::fileLog('path/to/file')` returns Collection of Commit DTOs
  - [ ] `CommitService::getFileDiff($sha, $file)` returns DiffResult with hunks for that file only
  - [ ] `--follow` flag tracks file renames

  **QA Scenarios**:

  ```
  Scenario: fileLog returns commits for a specific file
    Tool: Bash (php artisan tinker)
    Steps:
      1. Run: php artisan tinker --execute="$s = new \App\Services\Git\GitService('/path/repo'); echo $s->fileLog('composer.json', 5)->count();"
      2. Assert output is integer > 0
    Expected Result: Returns at least 1 commit
    Evidence: .sisyphus/evidence/task-1-file-log.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): add fileLog and getFileDiff service methods`
  - Files: `app/Services/Git/GitService.php`, `app/Services/Git/CommitService.php`

- [ ] 2. Create FileHistory Livewire component and Blade view

  **What to do**:
  - Create `app/Livewire/FileHistory.php` listening for `show-file-history` event with `file` param
  - Load file-specific commits via `GitService::fileLog()`
  - Allow selecting a commit to show its file-specific diff
  - Create `resources/views/livewire/file-history.blade.php` — commit list on left, diff on right (or stacked)
  - Panel appears as overlay in the diff viewer area (same pattern as BlameView)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1 once started)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 3, 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/BlameView.php` — Overlay panel pattern (listen for event, display file-specific data, close button)
  - `resources/views/livewire/blame-view.blade.php` — View template pattern
  - `resources/views/livewire/history-panel.blade.php` — Commit list rendering pattern
  - `resources/views/livewire/diff-viewer.blade.php` — Diff rendering pattern

  **Acceptance Criteria**:
  - [ ] Component mounts and listens for `show-file-history` event
  - [ ] Displays commit list for the file
  - [ ] Selecting a commit shows the file's diff at that commit
  - [ ] Close button works

  **QA Scenarios**:

  ```
  Scenario: File history panel shows commits
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Select a file in staging panel
      3. Click "History" button in diff viewer header
      4. Assert file history panel appears with commit list
      5. Click a commit in the list
      6. Assert diff content appears for that commit
    Expected Result: File-specific commit list with per-commit diffs
    Evidence: .sisyphus/evidence/task-2-file-history-panel.png
  ```

  **Commit**: YES
  - Message: `feat(panels): create FileHistory component with commit list and per-commit diffs`
  - Files: `app/Livewire/FileHistory.php`, `resources/views/livewire/file-history.blade.php`

- [ ] 3. Wire FileHistory into diff viewer header and staging panel

  **What to do**:
  - Add "History" icon button to diff viewer header (next to "Open in Editor" and "Blame")
  - Use `<x-phosphor-clock-counter-clockwise-light class="w-4 h-4" />` icon
  - On click dispatch `show-file-history` event with current file
  - Add to app layout alongside BlameView
  - Register in CommandPalette as "Show File History" command

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/diff-viewer.blade.php` — Header area with existing action buttons (Open in Editor, Blame)
  - `resources/views/livewire/app-layout.blade.php` — Where to add `<livewire:file-history />`
  - `app/Livewire/DiffViewer.php:86-93` — Existing `showBlame()` pattern to follow for `showFileHistory()`
  - `app/Livewire/CommandPalette.php` — Register new command

  **Acceptance Criteria**:
  - [ ] "History" button visible in diff viewer header when file is selected
  - [ ] Clicking it opens file history panel
  - [ ] Command palette includes "Show File History"

  **Commit**: YES
  - Message: `feat(panels): wire FileHistory into diff viewer header and command palette`
  - Files: `resources/views/livewire/diff-viewer.blade.php`, `resources/views/livewire/app-layout.blade.php`, `app/Livewire/DiffViewer.php`, `app/Livewire/CommandPalette.php`

- [ ] 4. Pest tests for file history service and component

  **What to do**:
  - Add tests to `tests/Feature/Services/GitServiceTest.php` for `fileLog()`
  - Create `tests/Feature/Livewire/FileHistoryTest.php`
  - Test: component mounts, receives event, loads commits, renders list, selects commit shows diff
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (last)
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 3

  **References**:
  - `tests/Feature/Livewire/HistoryPanelTest.php` — Test pattern for history-like component
  - `tests/Feature/Services/GitServiceTest.php` — Existing service tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=FileHistory` → all pass
  - [ ] `vendor/bin/pint --dirty --format agent` → no issues

  **Commit**: YES
  - Message: `test(panels): add tests for FileHistory component and fileLog service`
  - Files: `tests/Feature/Livewire/FileHistoryTest.php`, `tests/Feature/Services/GitServiceTest.php`

---

## Final Verification Wave

- [ ] F1. **Plan Compliance Audit** — `oracle`
- [ ] F2. **Code Quality Review** — `unspecified-high`
- [ ] F3. **Real Manual QA** — `unspecified-high` + `playwright` skill
- [ ] F4. **Scope Fidelity Check** — `deep`

---

## Commit Strategy

| After Task | Message | Verification |
|------------|---------|--------------|
| 1 | `feat(backend): add fileLog and getFileDiff service methods` | php artisan test --filter=GitService |
| 2 | `feat(panels): create FileHistory component` | Component renders |
| 3 | `feat(panels): wire FileHistory into diff viewer` | Playwright |
| 4 | `test(panels): add tests for FileHistory` | php artisan test --filter=FileHistory |

---

## Success Criteria

```bash
php artisan test --compact --filter=FileHistory  # Expected: all pass
php artisan test --compact --filter=GitService  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```
