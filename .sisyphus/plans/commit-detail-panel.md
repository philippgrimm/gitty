# Commit Detail Panel

## TL;DR

> **Quick Summary**: Add a commit detail panel that shows full commit metadata, changed files, and diffs when a user clicks on a commit in the history panel. Currently clicking a commit dispatches `commit-selected` but nothing displays the details.
> 
> **Deliverables**:
> - New `CommitDetailPanel` Livewire component
> - `CommitService::getCommitDetail()` method returning full commit info
> - Blade view with metadata header, file list, and inline diffs
> - Pest tests for both service and Livewire component
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4 → Task 5

---

## Context

### Original Request
Add a commit detail panel so clicking a commit in the history shows its full information — author, date, message, changed files, and per-file diffs.

### Research Findings
- `HistoryPanel.php:105-109` already dispatches `commit-selected` event with SHA
- `DiffViewer.php` handles `file-selected` events and shows diffs — reuse diff rendering pattern
- `GitService::log()` returns `Commit` DTO with sha, author, email, date, message, refs
- `Commit` DTO (`app/DTOs/Commit.php`) may need body (multi-line message) and parent SHA fields
- `DiffResult::fromDiffOutput()` already parses raw diff output — reuse for commit diffs
- The app layout (`resources/views/livewire/app-layout.blade.php`) uses a 3-column layout: sidebar, staging+commit, diff viewer

---

## Work Objectives

### Core Objective
Show full commit details (metadata + file list + diffs) when a commit is selected in the history panel.

### Concrete Deliverables
- `app/Services/Git/CommitService.php` — new `getCommitDetail(string $sha)` method
- `app/DTOs/Commit.php` — extend with body, parentSha, stats
- `app/Livewire/CommitDetailPanel.php` — new Livewire component
- `resources/views/livewire/commit-detail-panel.blade.php` — view template
- `tests/Feature/Services/CommitServiceTest.php` — additional tests
- `tests/Feature/Livewire/CommitDetailPanelTest.php` — new test file

### Definition of Done
- [ ] Clicking a commit in history panel shows detail panel with author, date, full message, changed files
- [ ] Each changed file in detail shows its diff inline or on click
- [ ] Panel replaces/overlays the diff viewer area when viewing commit details
- [ ] Selecting a working-tree file dismisses commit detail and returns to live diff

### Must Have
- Full commit message (including body beyond first line)
- Author name, email, and date
- List of changed files with status indicators (same dots as staging panel)
- Per-file diff viewer (reusing existing diff rendering)
- Parent commit SHA link

### Must NOT Have (Guardrails)
- No editing/amending from the detail panel (that's in CommitPanel)
- No interactive rebase from this view
- No custom diff rendering — reuse existing diff-viewer patterns
- No fetching from remote for commit details

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed.

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

### QA Policy
Every task includes agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Foundation):
├── Task 1: Extend CommitService + Commit DTO [quick]
├── Task 2: Create CommitDetailPanel Livewire component [unspecified-high]

Wave 2 (After Wave 1 — view + integration + tests):
├── Task 3: Build commit-detail-panel Blade view [visual-engineering]
├── Task 4: Wire into app layout + event integration [quick]
├── Task 5: Tests for service and component [unspecified-high]
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | 2, 3, 5 | 1 |
| 2 | 1 | 3, 4, 5 | 1 |
| 3 | 1, 2 | 4 | 2 |
| 4 | 2, 3 | 5 | 2 |
| 5 | 1, 2, 4 | — | 2 |

---

## TODOs

- [ ] 1. Extend CommitService and Commit DTO for full commit details

  **What to do**:
  - Add `getCommitDetail(string $sha): Commit` to `CommitService` that runs `git show --stat --format='...' {sha}` to get full metadata + file stats
  - Add `getCommitDiff(string $sha): DiffResult` that runs `git diff {sha}~1..{sha}` and parses with `DiffResult::fromDiffOutput()`
  - Extend `Commit` DTO with optional `body` (full message beyond subject), `parentSha`, and `stats` (array of file change counts) properties
  - Handle edge case: root commit (no parent) — use `git diff --root {sha}`

  **Must NOT do**:
  - Don't modify existing `log()` format string — add separate method
  - Don't change `Commit::fromLogLine()` — add a new static factory `Commit::fromShowOutput()`

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Service test patterns needed

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2 once started)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 5
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/CommitService.php` — Add methods here, follows `AbstractGitService` pattern with `$this->commandRunner->run()`
  - `app/Services/Git/GitService.php:29-58` — Existing `log()` method pattern for parsing git output
  - `app/DTOs/Commit.php` — DTO to extend with body/parentSha/stats
  - `app/DTOs/DiffResult.php` — `fromDiffOutput()` static factory for parsing diffs
  - `app/Services/Git/AbstractGitService.php` — Base class providing `$this->commandRunner` and `$this->cache`

  **Acceptance Criteria**:
  - [ ] `CommitService::getCommitDetail($sha)` returns `Commit` with sha, author, email, date, message, body, parentSha
  - [ ] `CommitService::getCommitDiff($sha)` returns `DiffResult` with changed files and hunks
  - [ ] Root commit (no parent) doesn't throw exception

  **QA Scenarios**:

  ```
  Scenario: Get commit detail for a normal commit
    Tool: Bash (php artisan tinker)
    Preconditions: Test repo with at least 2 commits
    Steps:
      1. Run: php artisan tinker --execute="$s = new \App\Services\Git\CommitService('/path/to/repo'); $c = $s->getCommitDetail('HEAD'); echo $c->sha . '|' . $c->author . '|' . $c->body;"
      2. Assert output contains pipe-separated sha, author name, and body text
    Expected Result: Returns Commit DTO with all fields populated, body contains full message
    Failure Indicators: Exception thrown, null body, empty author
    Evidence: .sisyphus/evidence/task-1-commit-detail-normal.txt

  Scenario: Get commit diff returns file changes
    Tool: Bash (php artisan tinker)
    Preconditions: Test repo with commits that modify files
    Steps:
      1. Run: php artisan tinker --execute="$s = new \App\Services\Git\CommitService('/path/to/repo'); $d = $s->getCommitDiff('HEAD'); echo $d->files->count();"
      2. Assert output is integer > 0
    Expected Result: DiffResult with at least 1 file
    Evidence: .sisyphus/evidence/task-1-commit-diff.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): add CommitService::getCommitDetail and getCommitDiff methods`
  - Files: `app/Services/Git/CommitService.php`, `app/DTOs/Commit.php`

- [ ] 2. Create CommitDetailPanel Livewire component

  **What to do**:
  - Create `app/Livewire/CommitDetailPanel.php` extending `Component`
  - Listen for `commit-selected` event via `#[On('commit-selected')]`
  - Load commit detail and diff using `CommitService::getCommitDetail()` and `getCommitDiff()`
  - Store commit data, file list, and diff data as public properties
  - Add `selectFile(int $fileIndex)` to show per-file diff
  - Add `close()` to dismiss the panel
  - Listen for `file-selected` event to auto-close (return to working tree diff)

  **Must NOT do**:
  - No commit editing/amending functionality
  - No interaction with staging

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Livewire component creation, event handling, lifecycle

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 3, 4, 5
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/DiffViewer.php` — Pattern for loading diff data, storing as public arrays, event handling with `#[On()]`
  - `app/Livewire/HistoryPanel.php:105-109` — `selectCommit()` dispatches `commit-selected` event with `sha` parameter
  - `app/Livewire/BlameView.php` — Similar overlay panel pattern (shows on demand, can close)
  - `app/Livewire/Concerns/HandlesGitOperations.php` — Shared trait for git operation error handling

  **Acceptance Criteria**:
  - [ ] Component listens for `commit-selected` event
  - [ ] Loads and stores full commit metadata
  - [ ] Provides file list from commit diff
  - [ ] Can select individual files to view their diff
  - [ ] Can close/dismiss the panel

  **QA Scenarios**:

  ```
  Scenario: Component loads commit details on event
    Tool: Bash (php artisan test)
    Preconditions: Test file created
    Steps:
      1. Run: php artisan test --compact --filter=CommitDetailPanel
      2. Assert all tests pass
    Expected Result: All Livewire component tests pass
    Evidence: .sisyphus/evidence/task-2-component-tests.txt

  Scenario: Component renders without errors
    Tool: Bash (php artisan tinker)
    Preconditions: Component class exists
    Steps:
      1. Run: php artisan tinker --execute="echo class_exists(\App\Livewire\CommitDetailPanel::class) ? 'exists' : 'missing';"
      2. Assert output is "exists"
    Expected Result: Class exists and is loadable
    Evidence: .sisyphus/evidence/task-2-class-exists.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): create CommitDetailPanel Livewire component`
  - Files: `app/Livewire/CommitDetailPanel.php`

- [ ] 3. Build commit-detail-panel Blade view

  **What to do**:
  - Create `resources/views/livewire/commit-detail-panel.blade.php`
  - Header section: commit SHA (truncated, copyable), author avatar placeholder, author name + email, relative date, refs/tags
  - Full commit message section: subject line bold, body in `font-mono` below
  - Changed files list: reuse file status dot pattern from staging panel, show filename + additions/deletions count
  - Per-file diff display: when a file is selected, show its hunks using same rendering pattern as `diff-viewer.blade.php`
  - Use Catppuccin Latte colors: `bg-white` panel, `text-[#4c4f69]` primary, `text-[#6c6f85]` secondary
  - Close button in header using `<x-phosphor-x-light class="w-4 h-4" />`

  **Must NOT do**:
  - Don't create custom diff rendering — reuse or extract shared components from `diff-viewer.blade.php`
  - Don't use `<flux:badge>` for file status — use inline-styled divs per AGENTS.md
  - No dark mode styles (follow existing light-only pattern)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `fluxui-development`, `livewire-development`]
    - `tailwindcss-development`: Catppuccin color system, layout utilities
    - `fluxui-development`: Flux button/tooltip components
    - `livewire-development`: wire:click, wire:loading directives

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/diff-viewer.blade.php` — Diff rendering pattern to reuse (hunk display, line rendering, split view)
  - `resources/views/livewire/staging-panel.blade.php` — File list item pattern with status dots and hover states
  - `resources/views/livewire/blame-view.blade.php` — Overlay panel layout pattern
  - `resources/views/livewire/history-panel.blade.php` — Commit display pattern (sha, author, date formatting)
  - `resources/css/app.css` — `.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context` classes
  - `AGENTS.md` — Color system, icon conventions, status dot colors

  **Acceptance Criteria**:
  - [ ] View renders commit SHA, author, date, full message
  - [ ] Changed files listed with correct status dots (M=yellow, A=green, D=red)
  - [ ] Clicking a file shows its diff below the file list
  - [ ] Close button dismisses the panel
  - [ ] Styling matches Catppuccin Latte palette

  **QA Scenarios**:

  ```
  Scenario: Commit detail panel displays correctly
    Tool: Playwright (playwright skill)
    Preconditions: App running, repo with commits
    Steps:
      1. Navigate to app URL
      2. Click on a commit in the history panel (selector: '[wire\\:click*="selectCommit"]' first match)
      3. Wait for commit detail panel to appear (selector: '[data-commit-detail-panel]', timeout: 5s)
      4. Assert panel contains commit SHA text (7+ hex chars)
      5. Assert panel contains author name
      6. Assert panel contains file list with at least 1 file item
      7. Take screenshot
    Expected Result: Panel visible with commit metadata and file list
    Failure Indicators: Panel doesn't appear, missing metadata fields, no files listed
    Evidence: .sisyphus/evidence/task-3-commit-detail-display.png

  Scenario: Close button dismisses panel
    Tool: Playwright (playwright skill)
    Preconditions: Commit detail panel is open
    Steps:
      1. Click close button (selector: '[data-commit-detail-panel] [wire\\:click*="close"]')
      2. Wait 500ms
      3. Assert panel is no longer visible
    Expected Result: Panel dismissed, diff viewer area returns to default state
    Evidence: .sisyphus/evidence/task-3-close-panel.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add commit detail panel Blade view with metadata and file list`
  - Files: `resources/views/livewire/commit-detail-panel.blade.php`

- [ ] 4. Wire CommitDetailPanel into app layout and event flow

  **What to do**:
  - Add `<livewire:commit-detail-panel :repo-path="$repoPath" />` to app layout, positioned to overlay or replace the diff viewer area
  - Use Alpine.js `x-show` or Livewire conditional to toggle between DiffViewer and CommitDetailPanel
  - When `commit-selected` fires: show CommitDetailPanel, hide DiffViewer
  - When `file-selected` fires: hide CommitDetailPanel, show DiffViewer
  - Ensure keyboard shortcut `Esc` closes commit detail panel

  **Must NOT do**:
  - Don't restructure the entire app layout
  - Don't change existing DiffViewer behavior

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`livewire-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (after Task 3)
  - **Blocks**: Task 5
  - **Blocked By**: Tasks 2, 3

  **References**:
  - `resources/views/livewire/app-layout.blade.php` — Main layout file, 3-column grid
  - `resources/views/livewire/blame-view.blade.php` — Existing overlay pattern (conditional display)
  - `resources/views/layouts/app.blade.php` — Root HTML, keyboard shortcut listeners

  **Acceptance Criteria**:
  - [ ] CommitDetailPanel appears when commit is clicked in history
  - [ ] DiffViewer reappears when a file in staging is selected
  - [ ] Esc key closes commit detail panel
  - [ ] No layout shift or z-index conflicts

  **QA Scenarios**:

  ```
  Scenario: Toggle between commit detail and diff viewer
    Tool: Playwright (playwright skill)
    Preconditions: App running with repo that has commits and modified files
    Steps:
      1. Navigate to app
      2. Click a modified file in staging panel
      3. Assert diff viewer is visible (selector: '[data-diff-viewer]')
      4. Click a commit in history panel
      5. Assert commit detail panel is visible (selector: '[data-commit-detail-panel]')
      6. Assert diff viewer is hidden
      7. Click a modified file in staging panel again
      8. Assert diff viewer is visible again
      9. Assert commit detail panel is hidden
    Expected Result: Clean toggle between both panels with no layout glitches
    Evidence: .sisyphus/evidence/task-4-toggle-panels.png
  ```

  **Commit**: YES
  - Message: `feat(layout): integrate CommitDetailPanel into app layout with panel toggling`
  - Files: `resources/views/livewire/app-layout.blade.php`

- [ ] 5. Tests for CommitService detail methods and CommitDetailPanel component

  **What to do**:
  - Add tests to `tests/Feature/Services/CommitServiceTest.php` for `getCommitDetail()` and `getCommitDiff()`
  - Create `tests/Feature/Livewire/CommitDetailPanelTest.php` with Pest tests
  - Test: component mounts, receives `commit-selected` event, loads data, renders metadata, file selection works, close works
  - Use `Process::fake()` to mock git commands (follow existing test patterns)
  - Run `vendor/bin/pint --dirty` after writing tests

  **Must NOT do**:
  - Don't test git CLI directly — mock with Process::fake()
  - Don't delete or modify existing tests

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (last task)
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 4

  **References**:
  - `tests/Feature/Livewire/HistoryPanelTest.php` — Pattern for Livewire test with `Process::fake()`, `Livewire::test()`, event assertions
  - `tests/Feature/Services/CommitServiceTest.php` — Existing tests for CommitService
  - `tests/Feature/Livewire/DiffViewerTest.php` — Pattern for testing diff-related component

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=CommitDetailPanel` → all pass
  - [ ] `php artisan test --compact --filter=CommitService` → all pass (including new tests)
  - [ ] `vendor/bin/pint --dirty --format agent` → no formatting issues

  **QA Scenarios**:

  ```
  Scenario: All tests pass
    Tool: Bash
    Preconditions: All previous tasks complete
    Steps:
      1. Run: php artisan test --compact --filter=CommitDetailPanel
      2. Assert exit code 0 and "Tests: ... passed" in output
      3. Run: php artisan test --compact --filter=CommitService
      4. Assert exit code 0
    Expected Result: All tests pass with 0 failures
    Failure Indicators: Non-zero exit code, "FAILED" in output
    Evidence: .sisyphus/evidence/task-5-tests-pass.txt
  ```

  **Commit**: YES
  - Message: `test(panels): add tests for CommitDetailPanel and CommitService detail methods`
  - Files: `tests/Feature/Livewire/CommitDetailPanelTest.php`, `tests/Feature/Services/CommitServiceTest.php`

---

## Final Verification Wave

- [ ] F1. **Plan Compliance Audit** — `oracle`: Read plan, verify all Must Have items implemented, all Must NOT Have items absent, evidence files exist.
- [ ] F2. **Code Quality Review** — `unspecified-high`: Run `vendor/bin/pint --dirty --format agent`, `php artisan test --compact`. Check for AI slop patterns.
- [ ] F3. **Real Manual QA** — `unspecified-high` + `playwright` skill: Open app, click commits, verify panel display, test toggle, test close.
- [ ] F4. **Scope Fidelity Check** — `deep`: Verify each task's diff matches its spec. No scope creep, no cross-task contamination.

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `feat(backend): add CommitService::getCommitDetail and getCommitDiff methods` | CommitService.php, Commit.php | php artisan test --filter=CommitService |
| 2 | `feat(backend): create CommitDetailPanel Livewire component` | CommitDetailPanel.php | php artisan tinker class check |
| 3 | `feat(panels): add commit detail panel Blade view` | commit-detail-panel.blade.php | Visual check |
| 4 | `feat(layout): integrate CommitDetailPanel into app layout` | app-layout.blade.php | Playwright toggle test |
| 5 | `test(panels): add tests for CommitDetailPanel` | test files | php artisan test --filter=CommitDetailPanel |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact --filter=CommitDetailPanel  # Expected: all pass
php artisan test --compact --filter=CommitService  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```

### Final Checklist
- [ ] Clicking commit in history shows detail panel
- [ ] Detail panel shows author, date, SHA, full message, changed files
- [ ] Per-file diffs viewable from detail panel
- [ ] Panel dismissible via close button and Esc key
- [ ] Selecting working-tree file returns to DiffViewer
- [ ] All tests pass
