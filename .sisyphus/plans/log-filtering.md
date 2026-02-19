# Log Filtering

## TL;DR

> **Quick Summary**: Add filtering controls to the history panel so users can filter commits by author, date range, and search text in commit messages.
> 
> **Deliverables**:
> - Filter UI in history panel header (author dropdown, date pickers, search input)
> - `GitService::log()` extended with filter parameters
> - Filtered commit list rendering
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Research Findings
- `GitService::log()` at line 29 accepts `$limit` and `$branch` but no filters
- Git supports `--author=`, `--after=`, `--before=`, `--grep=` flags for log filtering
- `HistoryPanel.php` uses `GitService::log()` directly in `loadCommits()` at line 75
- UI space is available above the commit list (currently just the graph toggle)

---

## Work Objectives

### Core Objective
Let users filter the commit history by author, date range, and message search text.

### Must Have
- Author filter (dropdown of known authors from recent commits)
- Message search (text input, searches commit messages)
- Date range filter (after/before)
- Clear all filters button
- Filter state persists during session (not across app restarts)

### Must NOT Have
- No file path filter (that's the File History feature)
- No regex search (plain text only)
- No saved/preset filters

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

---

## Execution Strategy

```
Wave 1 (Backend + UI):
├── Task 1: Extend GitService::log() with filter params [quick]
├── Task 2: Add filter properties and methods to HistoryPanel [unspecified-high]

Wave 2 (View + tests):
├── Task 3: Build filter UI in history-panel Blade view [visual-engineering]
├── Task 4: Pest tests [unspecified-high]
```

---

## TODOs

- [ ] 1. Extend GitService::log() with filter parameters

  **What to do**:
  - Add optional parameters to `log()`: `?string $author = null`, `?string $after = null`, `?string $before = null`, `?string $grep = null`
  - Build git log command with `--author=`, `--after=`, `--before=`, `--grep=` flags as needed
  - Add `getAuthors(int $limit = 50): array` method that runs `git log --format='%an' | sort -u` to get unique author names
  - Update cache key to include filter params

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/GitService.php:29-58` — Existing `log()` method
  - `app/Services/Git/GitCacheService.php` — Cache key generation

  **Acceptance Criteria**:
  - [ ] `log(50, author: 'John')` returns only John's commits
  - [ ] `log(50, grep: 'fix')` returns only commits containing "fix"
  - [ ] `getAuthors()` returns unique author names

  **Commit**: YES
  - Message: `feat(backend): extend GitService::log with filter parameters`
  - Files: `app/Services/Git/GitService.php`

- [ ] 2. Add filter properties and methods to HistoryPanel

  **What to do**:
  - Add public properties: `$filterAuthor`, `$filterAfter`, `$filterBefore`, `$filterSearch`
  - Update `loadCommits()` to pass filter params to `GitService::log()`
  - Add `clearFilters()` method
  - Add `$authors` computed property loading from `getAuthors()`
  - Debounce search input (use `wire:model.live.debounce.300ms`)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1 once started)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 3, 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/HistoryPanel.php` — Component to modify (especially `loadCommits()` at line 71)
  - `app/Livewire/BranchManager.php:236-244` — Pattern for filtering collections with query

  **Acceptance Criteria**:
  - [ ] Filter properties affect which commits are loaded
  - [ ] `clearFilters()` resets all filters and reloads
  - [ ] Search debounced at 300ms

  **Commit**: YES
  - Message: `feat(backend): add filter properties and methods to HistoryPanel`
  - Files: `app/Livewire/HistoryPanel.php`

- [ ] 3. Build filter UI in history-panel Blade view

  **What to do**:
  - Add collapsible filter bar above commit list in `history-panel.blade.php`
  - Filter toggle button with `<x-phosphor-funnel-light class="w-4 h-4" />` icon
  - Author dropdown using `<flux:dropdown>` with author list
  - Search input: `<flux:input size="sm" placeholder="Search commits..." wire:model.live.debounce.300ms="filterSearch" />`
  - Date inputs for after/before (simple text inputs with date format hint, or native date input)
  - "Clear filters" button when any filter is active
  - Active filter count badge on the filter toggle button

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`fluxui-development`, `tailwindcss-development`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/history-panel.blade.php` — View to modify
  - `resources/views/livewire/branch-manager.blade.php` — Search input pattern with `wire:model.live`
  - `AGENTS.md` — Flux button variants, input patterns, Catppuccin colors

  **Acceptance Criteria**:
  - [ ] Filter bar toggles open/closed
  - [ ] Author dropdown populated with known authors
  - [ ] Search input filters commits by message
  - [ ] "Clear" button resets all filters
  - [ ] Active filter count shown on toggle button

  **QA Scenarios**:

  ```
  Scenario: Filter commits by search text
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with repo that has multiple commits
      2. Click filter toggle button
      3. Type "fix" into search input
      4. Wait 500ms for debounce
      5. Assert visible commits all contain "fix" in their message
    Expected Result: Only matching commits displayed
    Evidence: .sisyphus/evidence/task-3-log-filter-search.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add filter UI to history panel`
  - Files: `resources/views/livewire/history-panel.blade.php`

- [ ] 4. Pest tests for log filtering

  **What to do**:
  - Add tests to `tests/Feature/Services/GitServiceTest.php` for filtered log
  - Add tests to `tests/Feature/Livewire/HistoryPanelTest.php` for filter properties and clearing
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 3

  **References**:
  - `tests/Feature/Livewire/HistoryPanelTest.php` — Existing tests to extend
  - `tests/Feature/Services/GitServiceTest.php` — Existing service tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=HistoryPanel` → all pass
  - [ ] `php artisan test --compact --filter=GitService` → all pass

  **Commit**: YES
  - Message: `test(panels): add tests for log filtering`
  - Files: `tests/Feature/Livewire/HistoryPanelTest.php`, `tests/Feature/Services/GitServiceTest.php`

---

## Final Verification Wave

- [ ] F1. **Plan Compliance Audit** — `oracle`
- [ ] F2. **Code Quality Review** — `unspecified-high`
- [ ] F3. **Real Manual QA** — `unspecified-high` + `playwright` skill
- [ ] F4. **Scope Fidelity Check** — `deep`

---

## Success Criteria

```bash
php artisan test --compact --filter=HistoryPanel  # Expected: all pass
php artisan test --compact --filter=GitService  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```
