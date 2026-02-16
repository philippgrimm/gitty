# Stash Feature + Multi-Select File Interactions

## TL;DR

> **Quick Summary**: Add per-file git stash support (stash individual files or all at once, always including untracked), Cmd+Click/Shift+Click multi-select on the file list (VSCode-style), a right-click context menu for file actions, sidebar stash actions (apply/pop/drop), and bulk operations on selected files. TDD with Pest throughout.
>
> **Deliverables**:
> - Multi-select in staging panel (Cmd+Click toggle, Shift+Click range) with accent tint visual
> - Right-click context menu on files (Stage/Unstage, Stash, Discard)
> - Per-file stashing via `git stash push -u -- file1 file2` with auto-generated messages
> - Stash toolbar button (stash all or stash selected)
> - Sidebar stash list gains apply/pop/drop action buttons
> - Bulk stage/unstage/discard on selected files
> - Keyboard shortcuts: ⌘⇧S (stash), ⌘A (select all), Esc (clear selection)
> - Pest TDD tests for all backend logic
>
> **Estimated Effort**: Large
> **Parallel Execution**: YES — 3 waves
> **Critical Path**: Task 1 + Task 2 → Task 3 → Tasks 4, 5 → Task 7

---

## Context

### Original Request
Build the stash feature for gitty — stash individual files or all at once. Add multi-select (Cmd+Click) so actions (stage, stash, revert) apply to all selected files. Always include untracked files. Must not break the existing system.

### Interview Summary
**Key Discussions**:
- **Stash list placement**: Already in sidebar (RepoSidebar) — add apply/pop/drop actions there
- **Stash trigger**: Toolbar button for stash all/selected + right-click context menu for individual files
- **Context menu actions**: Stage/Unstage, Stash, Discard (no copy path, no open in Finder)
- **Multi-select visual**: Accent background tint (rgba(8,76,207,0.15))
- **Range selection**: Both Cmd+Click toggle AND Shift+Click range (full VSCode behavior)
- **Diff viewer**: Always shows last-clicked file's diff, even with multi-select active
- **Untracked files**: Always include (-u flag), remove the checkbox entirely
- **Stash message**: Auto-generate (no prompt modal), format described below
- **Test strategy**: TDD with Pest + Process::fake()

**Research Findings**:
- `StashPanel.php` exists but is orphaned (not mounted in layout) — sidebar replaces it
- `StashService` has stash/list/apply/pop/drop but NO per-file stash
- `StagingService` has stageFile/unstageFile/discardFile but NO bulk versions
- `StagingPanel` has no selection tracking — `selectFile()` just dispatches event
- `RepoSidebar` already loads stash data but shows read-only list (no action buttons)
- Alpine `x-data` with nested scope chain allows tree view to access parent selection state
- `wire:click` can access `$event.metaKey` / `$event.shiftKey` via Alpine/Livewire bridge

### Metis Review
**Identified Gaps** (addressed):
- Multi-select persistence → Clear on repo switch and after bulk actions
- Context menu right-click behavior → Follow VSCode pattern (unselected: clear+select; selected: keep)
- Stash message format → Defined concrete format below
- Cross-component refresh → Use Livewire events (`stash-created`, `status-updated`)
- File path safety → Use `escapeshellarg()` or array syntax for git commands
- Tree view multi-select → Works via Alpine scope chain, Shift+Click uses visible DOM order only
- Mixed staged/unstaged selection → Allowed for stash; stage/unstage context-aware per-file
- Stash pop conflicts → Error toast only, no conflict resolution UI

---

## Work Objectives

### Core Objective
Enable users to select multiple files in the staging panel and perform bulk git operations (stage, unstage, stash, discard) on the selection, with per-file stash support and a right-click context menu for quick actions.

### Concrete Deliverables
- `StashService::stashFiles()` method for per-file stashing
- `StagingService::stageFiles()`, `unstageFiles()`, `discardFiles()` bulk methods
- Alpine multi-select state in `staging-panel.blade.php` (and `file-tree.blade.php`)
- Right-click context menu component in staging panel
- Stash toolbar button in staging panel header
- Apply/Pop/Drop buttons on sidebar stash items
- Keyboard shortcuts in `app-layout.blade.php`
- Pest tests for all new service methods and Livewire component actions

### Definition of Done
- [ ] `php artisan test --compact` passes with all new and existing tests
- [ ] Cmd+Click toggles file selection in both flat and tree view
- [ ] Shift+Click selects range of files
- [ ] Right-click shows context menu at cursor with Stage/Unstage, Stash, Discard
- [ ] Stash button in toolbar stashes all (no selection) or selected files
- [ ] Sidebar stash list has working Apply, Pop, Drop buttons
- [ ] Single-click behavior (select file → show diff) unchanged
- [ ] Orphaned StashPanel component removed

### Must Have
- Multi-select with Cmd+Click and Shift+Click in flat view
- Multi-select in tree view
- Right-click context menu with Stage/Unstage, Stash, Discard
- Per-file stashing with auto-generated message
- Stash toolbar button (context-aware: all vs selected)
- Sidebar stash apply/pop/drop buttons
- Bulk stage/unstage/discard on selection
- Always include untracked (-u flag), no checkbox
- TDD with Pest tests
- Selection clears after bulk actions

### Must NOT Have (Guardrails)
- **No stash message prompt modal** — auto-generate only
- **No "Include Untracked" checkbox** — always `-u`
- **No partial/hunk stashing** — file-level only
- **No stash branch feature** — out of scope
- **No stash diff viewer** — read-only list with actions only
- **No nested submenus in context menu** — flat list of actions
- **No drag-and-drop selection** — Cmd/Shift click only
- **No Select All / Clear Selection toolbar buttons** — keyboard shortcuts only
- **No stash conflict resolution UI** — error toast on conflicts
- **No icons in context menu items** — text only, clean
- **Must NOT modify existing `selectFile()` → `file-selected` → DiffViewer flow** for single-click
- **Must NOT modify DiffViewer rendering logic** — only event handling if needed
- **Must NOT modify CommitPanel, RepoSwitcher, or BranchManager**
- **Must NOT refactor existing StashService methods** — only ADD new `stashFiles()` method
- **Must NOT change existing test assertions** — only add new tests

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks are verified by running commands or tools. No manual testing.

### Test Decision
- **Infrastructure exists**: YES (Pest 4 with Process::fake())
- **Automated tests**: TDD (Red-Green-Refactor)
- **Framework**: Pest 4 via `php artisan test --compact`
- **Existing test patterns**: `tests/Feature/Livewire/StagingPanelTest.php` uses `Process::fake()` + `Livewire::test()`

### TDD Workflow Per Task
1. **RED**: Write failing test first → `php artisan test --compact --filter=TestName` → FAIL
2. **GREEN**: Implement minimum code to pass → re-run → PASS
3. **REFACTOR**: Clean up while green → re-run → still PASS

### Stash Message Format (Concrete Specification)
- **Stash all**: `"WIP on {branch}"`
- **Stash selected, ≤3 files**: `"Stash: file1.php, file2.php, file3.php"`
- **Stash selected, >3 files**: `"Stash: {N} files on {branch}"`

Where `{branch}` is the current git branch name, and filenames are `basename()` only.

### Context Menu Right-Click Behavior (Concrete Specification)
- Right-click on **unselected** file → clear selection, select that file, show menu for it
- Right-click on **already selected** file → keep entire selection, show menu for all selected
- Menu items show count when multiple selected: "Stage 3 files", "Stash 3 files"
- Menu positioned at cursor coordinates (`position: fixed; left: Xpx; top: Ypx`)
- Menu closes on: click outside, Esc, selecting an action, scroll

### Selection Behavior (Concrete Specification)
- **Single click** (no modifier): Clear all selection, select that file, show its diff
- **Cmd+Click**: Toggle that file in/out of selection. Dispatch `file-selected` for diff of clicked file
- **Shift+Click**: Select range from last-clicked file to this file (inclusive). Uses visible DOM order
- **Selection clears**: After any bulk action (stage/unstage/stash/discard), on repo switch
- **Cross-section selection**: User can select files from BOTH staged and unstaged sections
- **Tree view**: Same behavior — Alpine scope chain gives tree items access to parent's `selectedFiles`

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
├── Task 1: Extend StashService + StagingService (backend, no deps)
├── Task 2: Multi-Select Foundation (Alpine state + visual + handlers)
└── Task 6: Sidebar Stash Actions (independent component)

Wave 2 (After Wave 1):
└── Task 3: Bulk Action Methods in StagingPanel (needs Tasks 1, 2)

Wave 3 (After Wave 2):
├── Task 4: Right-Click Context Menu (needs Task 3)
└── Task 5: Stash Toolbar Button (needs Task 3)

Wave 4 (After Wave 3):
└── Task 7: Keyboard Shortcuts + Cleanup (needs Tasks 4, 5)

Critical Path: Task 1 + Task 2 → Task 3 → Task 5 → Task 7
Parallel Speedup: ~40% faster than sequential
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | 3 | 2, 6 |
| 2 | None | 3 | 1, 6 |
| 3 | 1, 2 | 4, 5 | None |
| 4 | 3 | 7 | 5 |
| 5 | 3 | 7 | 4 |
| 6 | None | 7 | 1, 2 |
| 7 | 4, 5, 6 | None | None (final) |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Agents |
|------|-------|-------------------|
| 1 | 1, 2, 6 | Three parallel tasks: quick (Task 1), visual-engineering (Task 2), quick (Task 6) |
| 2 | 3 | unspecified-high (wires multi-select to backend) |
| 3 | 4, 5 | visual-engineering (Task 4), quick (Task 5) — parallel |
| 4 | 7 | quick (keyboard shortcuts + file deletion) |

---

## TODOs

- [x] 1. Extend StashService + StagingService with Bulk/Per-File Methods (TDD)

  **What to do**:

  **StashService — add `stashFiles()` method:**
  1. RED: Create test file `tests/Feature/Services/StashServiceTest.php`
     - Test: `stashFiles()` runs correct git command with file paths
     - Test: `stashFiles()` auto-generates message in correct format (≤3 files: filenames, >3: count)
     - Test: `stashFiles()` always includes `-u` flag
     - Test: `stashFiles()` invalidates `stashes` and `status` cache groups
     - Test: `stashFiles()` with empty array throws exception
     - Run: `php artisan test --compact --filter=StashServiceTest` → FAIL
  2. GREEN: Add `stashFiles(array $paths)` to `StashService.php`
     - Build command: `git stash push -u -m "{message}" -- {file1} {file2}`
     - Use `escapeshellarg()` for each file path to handle spaces/special chars
     - Auto-generate message using format spec (see Verification Strategy section)
     - Get current branch from `git rev-parse --abbrev-ref HEAD` for message
     - Invalidate `stashes` and `status` cache groups
     - Run: `php artisan test --compact --filter=StashServiceTest` → PASS
  3. REFACTOR: Extract message generation to private method `generateStashMessage(array $paths): string`

  **StagingService — add bulk methods:**
  1. RED: Add tests to `tests/Feature/Services/StagingServiceTest.php` (create if not exists)
     - Test: `stageFiles(array)` runs `git add` with multiple paths
     - Test: `unstageFiles(array)` runs `git reset HEAD` with multiple paths
     - Test: `discardFiles(array)` runs `git checkout --` with multiple paths
     - Test: Each bulk method invalidates `status` cache group
     - Test: Empty array throws exception for each method
     - Run: `php artisan test --compact --filter=StagingServiceTest` → FAIL
  2. GREEN: Add methods to `StagingService.php`:
     - `stageFiles(array $files)` → `git add {file1} {file2} ...`
     - `unstageFiles(array $files)` → `git reset HEAD -- {file1} {file2} ...`
     - `discardFiles(array $files)` → `git checkout -- {file1} {file2} ...`
     - Use `escapeshellarg()` for each path
     - Run: `php artisan test --compact --filter=StagingServiceTest` → PASS

  **Must NOT do**:
  - Must NOT modify existing `stash()`, `stageFile()`, `unstageFile()`, `discardFile()` methods
  - Must NOT change the `StashService` constructor or cache strategy
  - Must NOT add "include untracked" parameter — always `-u`

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Focused backend-only changes — adding methods to existing services with clear test patterns
  - **Skills**: [`pest-testing`]
    - `pest-testing`: TDD workflow, Pest 4 assertion syntax, Process::fake() mocking

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 6)
  - **Blocks**: Task 3
  - **Blocked By**: None

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Services/Git/StashService.php:25-37` — Existing `stash()` method pattern: command building, Process::path()->run(), cache invalidation
  - `app/Services/Git/StagingService.php` — Existing `stageFile()`, `unstageFile()`, `discardFile()` patterns
  - `app/Services/Git/GitCacheService.php` — Cache invalidation API: `invalidateGroup($repoPath, 'status')`

  **Test References** (testing patterns to follow):
  - `tests/Feature/Livewire/StagingPanelTest.php` — Process::fake() patterns with glob matchers, Livewire::test() structure
  - Follow the `beforeEach` pattern: create temp git repo, initialize it, set `$this->testRepoPath`

  **API References**:
  - Git stash push with pathspec: `git stash push -u -m "message" -- path1 path2` 
  - Git add with multiple files: `git add path1 path2`
  - Git reset with pathspec: `git reset HEAD -- path1 path2`
  - Git checkout with pathspec: `git checkout -- path1 path2`

  **Acceptance Criteria**:

  - [ ] Test file `tests/Feature/Services/StashServiceTest.php` exists with ≥5 test cases
  - [ ] Test file `tests/Feature/Services/StagingServiceTest.php` exists with ≥4 test cases  
  - [ ] `php artisan test --compact --filter=StashServiceTest` → PASS (all tests)
  - [ ] `php artisan test --compact --filter=StagingServiceTest` → PASS (all tests)
  - [ ] `StashService::stashFiles(['file1.php', 'file2.php'])` runs `git stash push -u -m "Stash: file1.php, file2.php" -- 'file1.php' 'file2.php'`
  - [ ] `StashService::stashFiles()` with >3 files generates `"Stash: N files on {branch}"`
  - [ ] `StagingService::stageFiles(['a.php', 'b.php'])` runs single `git add` command with both files
  - [ ] Empty array input throws `\InvalidArgumentException` for all new methods
  - [ ] All existing tests still pass: `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Verify stashFiles runs correct git command
    Tool: Bash (php artisan test)
    Preconditions: Test files created with Process::fake()
    Steps:
      1. Run: php artisan test --compact --filter=StashServiceTest
      2. Assert: Exit code 0
      3. Assert: Output contains "Tests: X passed"
      4. Assert: Output does NOT contain "FAILED"
    Expected Result: All StashService tests pass
    Evidence: Terminal output captured

  Scenario: Verify bulk StagingService methods
    Tool: Bash (php artisan test)
    Preconditions: Test files created
    Steps:
      1. Run: php artisan test --compact --filter=StagingServiceTest
      2. Assert: Exit code 0
      3. Assert: Output contains "Tests: X passed"
    Expected Result: All StagingService tests pass
    Evidence: Terminal output captured

  Scenario: Verify no regression in existing tests
    Tool: Bash (php artisan test)
    Preconditions: All new code written
    Steps:
      1. Run: php artisan test --compact
      2. Assert: Exit code 0
      3. Assert: No "FAILED" in output
    Expected Result: Full test suite passes
    Evidence: Terminal output captured
  ```

  **Commit**: YES
  - Message: `feat(backend): add per-file stash and bulk staging service methods`
  - Files: `app/Services/Git/StashService.php`, `app/Services/Git/StagingService.php`, `tests/Feature/Services/StashServiceTest.php`, `tests/Feature/Services/StagingServiceTest.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 2. Multi-Select Foundation in Staging Panel (Alpine State + Visual + Click Handlers)

  **What to do**:

  **Alpine State Layer:**
  1. Extend the existing `x-data` block in `staging-panel.blade.php` (lines 3-7) with selection state:
     ```javascript
     selectedFiles: [],           // Array of file path strings
     lastClickedFile: null,       // Path of last-clicked file (for Shift+Click anchor)
     
     isSelected(path) { return this.selectedFiles.includes(path); },
     
     handleFileClick(path, staged, event) {
         if (event.metaKey) {
             // Cmd+Click: toggle
             if (this.isSelected(path)) {
                 this.selectedFiles = this.selectedFiles.filter(f => f !== path);
             } else {
                 this.selectedFiles.push(path);
             }
         } else if (event.shiftKey && this.lastClickedFile) {
             // Shift+Click: range select using visible DOM order
             const items = [...this.$el.querySelectorAll('[data-file-path]')];
             const paths = items.map(el => el.dataset.filePath);
             const startIdx = paths.indexOf(this.lastClickedFile);
             const endIdx = paths.indexOf(path);
             if (startIdx !== -1 && endIdx !== -1) {
                 const [from, to] = [Math.min(startIdx, endIdx), Math.max(startIdx, endIdx)];
                 this.selectedFiles = paths.slice(from, to + 1);
             }
         } else {
             // Normal click: clear selection, select one
             this.selectedFiles = [path];
         }
         this.lastClickedFile = path;
         // Always show diff for clicked file
         $wire.selectFile(path, staged);
     },
     
     clearSelection() { this.selectedFiles = []; this.lastClickedFile = null; },
     ```

  2. Add `data-file-path="{{ $file['path'] }}"` attribute to every file item `<div>` in flat view (both staged and unstaged loops)

  3. Replace `wire:click="selectFile(...)"` on file items with Alpine handler:
     ```blade
     @click="handleFileClick('{{ $file['path'] }}', {{ $staged ? 'true' : 'false' }}, $event)"
     ```

  **Visual Selection State:**
  4. Add conditional accent tint to file items using Alpine `:class`:
     ```blade
     :class="{ 'bg-[rgba(8,76,207,0.15)]': isSelected('{{ $file['path'] }}') }"
     ```
     This replaces the plain `hover:bg-[#eff1f5]` when selected. Hover should still work on non-selected items.

  **Tree View Integration:**
  5. In `file-tree.blade.php`, add same `data-file-path` attribute and `:class` binding to file items
  6. Replace `wire:click="selectFile(...)"` with the same Alpine `@click="handleFileClick(...)"` call
  7. The tree view's own `x-data="{ expanded: true }"` scope can access parent's `selectedFiles` via Alpine scope chain

  **Must NOT do**:
  - Must NOT change the existing `selectFile()` PHP method on StagingPanel — it still dispatches `file-selected`
  - Must NOT add `$selectedFiles` as a Livewire public property (selection is Alpine client-side only for performance)
  - Must NOT modify DiffViewer component
  - Must NOT add checkboxes or left-border styling
  - Must NOT modify the action buttons (stage/unstage/discard) on hover — they stay as-is for single-file actions

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: UI/interaction layer — Alpine.js state management, visual selection styling, click handler logic
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: wire:click ↔ Alpine event bridge, Livewire component interaction
    - `tailwindcss-development`: Conditional classes, hover states, accent tint styling

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 6)
  - **Blocks**: Task 3
  - **Blocked By**: None

  **References**:

  **Pattern References** (existing code to follow):
  - `resources/views/livewire/staging-panel.blade.php:3-7` — Existing Alpine x-data block (discard modal state) — extend this, don't replace
  - `resources/views/livewire/staging-panel.blade.php:86-127` — Flat view staged file items with wire:click and hover actions
  - `resources/views/livewire/staging-panel.blade.php:144-198` — Flat view unstaged file items
  - `resources/views/components/file-tree.blade.php:1-50` — Tree view component with its own x-data for folder collapse
  - `resources/views/livewire/app-layout.blade.php:69-106` — Alpine x-data pattern for complex state (panel resizing)

  **API/Type References**:
  - `app/Livewire/StagingPanel.php:188-191` — `selectFile()` method signature: `selectFile(string $file, bool $staged): void`

  **Documentation References**:
  - AGENTS.md "Hover & Interaction States" section — hover on white uses `bg-[#eff1f5]` (Base)
  - AGENTS.md "Color System" — Accent Muted: `rgba(8, 76, 207, 0.15)` for selection background

  **Acceptance Criteria**:

  - [ ] Alpine x-data block in staging-panel.blade.php includes `selectedFiles`, `lastClickedFile`, `handleFileClick()`, `isSelected()`, `clearSelection()`
  - [ ] File items in flat view have `data-file-path` attribute
  - [ ] File items in tree view have `data-file-path` attribute
  - [ ] `wire:click="selectFile(...)"` replaced with `@click="handleFileClick(...)"` on all file items (flat + tree)
  - [ ] Selected files show `bg-[rgba(8,76,207,0.15)]` background
  - [ ] Non-selected files still show `hover:bg-[#eff1f5]` on hover
  - [ ] Existing `selectFile()` PHP method unchanged
  - [ ] `$wire.selectFile()` still called on every click (diff viewer still works)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Single click selects file and shows diff
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running (php artisan native:serve or php artisan serve --port=8321), repo open with changed files
    Steps:
      1. Navigate to app URL
      2. Wait for staging panel to load (file list visible)
      3. Click first file in "Changes" section (no modifier keys)
      4. Assert: Clicked file has accent background tint (rgba(8,76,207,0.15))
      5. Assert: Diff viewer shows content for that file
      6. Screenshot: .sisyphus/evidence/task-2-single-click.png
    Expected Result: File selected, diff shown
    Evidence: .sisyphus/evidence/task-2-single-click.png

  Scenario: Cmd+Click toggles multi-selection
    Tool: Playwright (playwright skill)
    Preconditions: Repo with 3+ changed files
    Steps:
      1. Click first file (normal click) → one file selected
      2. Cmd+Click second file → two files selected (both have accent tint)
      3. Cmd+Click first file again → deselected (only second file has accent tint)
      4. Assert: Diff viewer shows the last-clicked file's diff
      5. Screenshot: .sisyphus/evidence/task-2-cmd-click.png
    Expected Result: Multi-select works with Cmd+Click toggle
    Evidence: .sisyphus/evidence/task-2-cmd-click.png

  Scenario: Shift+Click selects range
    Tool: Playwright (playwright skill)
    Preconditions: Repo with 5+ changed files in "Changes" section
    Steps:
      1. Click first file (normal click)
      2. Shift+Click fourth file
      3. Assert: Files 1-4 all have accent background tint
      4. Assert: Files 5+ do NOT have accent tint
      5. Screenshot: .sisyphus/evidence/task-2-shift-click.png
    Expected Result: Range selection works
    Evidence: .sisyphus/evidence/task-2-shift-click.png

  Scenario: Normal click clears multi-select
    Tool: Playwright (playwright skill)
    Preconditions: Multiple files selected via Cmd+Click
    Steps:
      1. Select 3 files with Cmd+Click
      2. Normal click (no modifier) on a different file
      3. Assert: Only the clicked file has accent tint
      4. Assert: Previously selected files lost accent tint
    Expected Result: Normal click clears previous selection
    Evidence: .sisyphus/evidence/task-2-clear-select.png

  Scenario: Tree view multi-select works
    Tool: Playwright (playwright skill)
    Preconditions: Repo with changed files in multiple directories, tree view enabled
    Steps:
      1. Click tree view toggle button (folder icon in toolbar)
      2. Wait for tree view to render
      3. Cmd+Click two files in different folders
      4. Assert: Both files have accent background tint
      5. Screenshot: .sisyphus/evidence/task-2-tree-multiselect.png
    Expected Result: Multi-select works in tree view
    Evidence: .sisyphus/evidence/task-2-tree-multiselect.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add multi-select with Cmd+Click and Shift+Click`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 3. Bulk Action Methods + Wiring in StagingPanel (TDD)

  **What to do**:

  **TDD — Pest Tests First:**
  1. RED: Add tests to `tests/Feature/Livewire/StagingPanelTest.php`:
     - Test: `stageSelected(array $files)` calls `StagingService::stageFiles()` and dispatches `status-updated`
     - Test: `unstageSelected(array $files)` calls `StagingService::unstageFiles()` and dispatches `status-updated`
     - Test: `discardSelected(array $files)` calls `StagingService::discardFiles()` and dispatches `status-updated`
     - Test: `stashSelected(array $files)` calls `StashService::stashFiles()` and dispatches `stash-created` + `status-updated`
     - Test: `stashAll()` calls `StashService::stash()` with auto-generated message and dispatches events
     - Test: All bulk methods handle empty array gracefully (no-op or error)
     - Run: `php artisan test --compact --filter=StagingPanelTest` → FAIL (new tests fail)

  **Livewire Methods:**
  2. GREEN: Add methods to `StagingPanel.php`:
     - `stageSelected(array $files): void` — calls `StagingService::stageFiles()`, refreshes status, dispatches events
     - `unstageSelected(array $files): void` — calls `StagingService::unstageFiles()`, refreshes status, dispatches events
     - `discardSelected(array $files): void` — calls `StagingService::discardFiles()`, refreshes status, dispatches events
     - `stashSelected(array $files): void` — calls `StashService::stashFiles()`, refreshes status, dispatches `stash-created` + `status-updated`
     - `stashAll(): void` — calls `StashService::stash()` with auto-message (`"WIP on {branch}"`), dispatches events
     - All methods follow existing error handling pattern: try/catch → `GitErrorHandler::translate()` → `show-error` event
     - Run: `php artisan test --compact --filter=StagingPanelTest` → PASS

  **Wire Alpine to Livewire:**
  3. Update `staging-panel.blade.php` — modify the existing per-file hover action buttons to be selection-aware:
     - Stage button: If files are selected and this file is in selection, `@click.stop="$wire.stageSelected(selectedFiles); clearSelection()"`
     - Otherwise keep existing `wire:click.stop="stageFile('...')"` for single file
     - Same pattern for Unstage and Discard buttons
     - Add Alpine listener `@stash-created.window="clearSelection()"` on the root div
     - Add Alpine listener `@status-updated.window="clearSelection()"` on the root div (Metis guardrail: clear selection after actions)

  **Must NOT do**:
  - Must NOT modify existing `stageFile()`, `unstageFile()`, `discardFile()` methods (they work for single-file hover actions)
  - Must NOT replace hover action buttons entirely — they still work for single-file when nothing else selected
  - Must NOT add `$selectedFiles` as Livewire public property — Alpine passes it as method parameter

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Bridges frontend (Alpine) and backend (Livewire), requires understanding both sides and careful wiring
  - **Skills**: [`livewire-development`, `pest-testing`]
    - `livewire-development`: Livewire method creation, event dispatching, Alpine↔Livewire bridge ($wire)
    - `pest-testing`: TDD workflow, Process::fake(), Livewire::test()

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (sequential — depends on Tasks 1 and 2)
  - **Blocks**: Tasks 4, 5
  - **Blocked By**: Tasks 1, 2

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Livewire/StagingPanel.php:84-99` — `stageFile()` method pattern: try/catch, service call, refreshStatus, dispatch events
  - `app/Livewire/StagingPanel.php:119-134` — `stageAll()` method pattern (same structure as stageFile but for bulk)
  - `app/Livewire/StagingPanel.php:154-168` — `discardFile()` with error handling pattern
  - `app/Livewire/StashPanel.php:43-56` — `createStash()` pattern: call stash service, dispatch `status-updated`

  **API/Type References**:
  - `app/Services/Git/StagingService.php` — `stageFiles(array)`, `unstageFiles(array)`, `discardFiles(array)` from Task 1
  - `app/Services/Git/StashService.php` — `stashFiles(array)` from Task 1
  - `app/Services/Git/GitErrorHandler.php` — `translate()` for error message translation

  **Test References**:
  - `tests/Feature/Livewire/StagingPanelTest.php` — Existing test structure, Process::fake() setup, `Livewire::test()` assertions

  **Acceptance Criteria**:

  - [ ] `StagingPanel::stageSelected(['a.php', 'b.php'])` calls `StagingService::stageFiles()`
  - [ ] `StagingPanel::stashSelected(['a.php'])` calls `StashService::stashFiles()` with auto-generated message
  - [ ] `StagingPanel::stashAll()` calls `StashService::stash()` with `"WIP on {branch}"` message
  - [ ] All bulk methods dispatch `status-updated` event
  - [ ] `stashSelected()` and `stashAll()` additionally dispatch `stash-created` event
  - [ ] All bulk methods follow try/catch error handling pattern
  - [ ] Hover action buttons call bulk method when `selectedFiles.length > 0`, single-file method otherwise
  - [ ] Alpine `clearSelection()` called after `stash-created` and `status-updated` events
  - [ ] `php artisan test --compact --filter=StagingPanelTest` → PASS (existing + new tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Bulk stage via Pest test
    Tool: Bash (php artisan test)
    Preconditions: Test written with Process::fake()
    Steps:
      1. Run: php artisan test --compact --filter=StagingPanelTest
      2. Assert: Exit code 0
      3. Assert: Output shows all new tests passing
    Expected Result: All StagingPanel tests pass
    Evidence: Terminal output captured

  Scenario: Bulk stash clears selection in UI
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo with 3+ changed files
    Steps:
      1. Cmd+Click to select 2 files (both show accent tint)
      2. Click the Stash toolbar button (added in Task 5, but test this flow)
      3. Wait for status refresh (files disappear from list)
      4. Assert: No files have accent tint (selection cleared)
      5. Assert: Stashed files no longer in file list
    Expected Result: Bulk stash works and clears selection
    Evidence: .sisyphus/evidence/task-3-bulk-stash.png
  ```

  **Commit**: YES
  - Message: `feat(staging): wire multi-select to bulk stage/unstage/stash/discard actions`
  - Files: `app/Livewire/StagingPanel.php`, `resources/views/livewire/staging-panel.blade.php`, `tests/Feature/Livewire/StagingPanelTest.php`
  - Pre-commit: `php artisan test --compact`

---

- [ ] 4. Right-Click Context Menu

  **What to do**:

  **Alpine Context Menu State:**
  1. Add to staging-panel.blade.php's `x-data` block:
     ```javascript
     contextMenu: { show: false, x: 0, y: 0, targetFile: null, targetStaged: false },
     
     showContextMenu(path, staged, event) {
         event.preventDefault();
         // VSCode behavior: right-click on unselected clears + selects; on selected keeps selection
         if (!this.isSelected(path)) {
             this.selectedFiles = [path];
             this.lastClickedFile = path;
         }
         this.contextMenu = { show: true, x: event.clientX, y: event.clientY, targetFile: path, targetStaged: staged };
         // Show diff for right-clicked file
         $wire.selectFile(path, staged);
     },
     
     hideContextMenu() { this.contextMenu.show = false; },
     
     get contextMenuFiles() {
         return this.selectedFiles.length > 0 ? this.selectedFiles : [this.contextMenu.targetFile];
     },
     
     get contextMenuCount() { return this.contextMenuFiles.length; },
     
     get contextMenuIsStaged() {
         // Check if target file is in staged section
         return this.contextMenu.targetStaged;
     },
     ```

  **Context Menu HTML:**
  2. Add context menu markup at end of staging panel (before the discard modal), positioned with `position: fixed`:
     ```blade
     <div
         x-show="contextMenu.show"
         x-cloak
         @click.outside="hideContextMenu()"
         @keydown.escape.window="hideContextMenu()"
         @scroll.window="hideContextMenu()"
         :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 50;`"
         class="bg-white border border-[#ccd0da] rounded-lg shadow-lg py-1 min-w-[180px] font-mono text-sm"
     >
         <!-- Stage or Unstage (context-aware) -->
         <button x-show="!contextMenuIsStaged"
             @click="$wire.stageSelected(contextMenuFiles); clearSelection(); hideContextMenu()"
             class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]">
             Stage <span x-show="contextMenuCount > 1" x-text="contextMenuCount + ' files'"></span>
         </button>
         <button x-show="contextMenuIsStaged"
             @click="$wire.unstageSelected(contextMenuFiles); clearSelection(); hideContextMenu()"
             class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]">
             Unstage <span x-show="contextMenuCount > 1" x-text="contextMenuCount + ' files'"></span>
         </button>
         
         <!-- Divider -->
         <div class="border-t border-[#dce0e8] my-1"></div>
         
         <!-- Stash -->
         <button @click="$wire.stashSelected(contextMenuFiles); clearSelection(); hideContextMenu()"
             class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#4c4f69]">
             Stash <span x-show="contextMenuCount > 1" x-text="contextMenuCount + ' files'"></span>
         </button>
         
         <!-- Divider -->
         <div class="border-t border-[#dce0e8] my-1"></div>
         
         <!-- Discard -->
         <button x-show="!contextMenuIsStaged"
             @click="showDiscardModal = true; discardAll = false; discardTarget = contextMenuFiles; hideContextMenu()"
             class="w-full px-3 py-1.5 text-left hover:bg-[#eff1f5] text-[#d20f39]">
             Discard <span x-show="contextMenuCount > 1" x-text="contextMenuCount + ' files'"></span>
         </button>
     </div>
     ```

  **Right-Click Handler on File Items:**
  3. Add `@contextmenu="showContextMenu('{{ $file['path'] }}', {{ $staged ? 'true' : 'false' }}, $event)"` to file items in flat view and tree view

  **Edge Cases:**
  4. Handle menu positioning near viewport edges — if `contextMenu.x + 180 > window.innerWidth`, position from right. Same for bottom edge with menu height.
  5. Prevent default browser context menu on file items with the `@contextmenu` handler (it calls `event.preventDefault()`)
  6. Update the discard modal to handle array of files (currently expects single `discardTarget` string). When `discardTarget` is an array, show "Discard changes to N files" text.

  **Must NOT do**:
  - Must NOT add nested submenus
  - Must NOT add icons to menu items
  - Must NOT add keyboard shortcut labels in menu
  - Must NOT use `<flux:dropdown>` (doesn't support cursor positioning) — use custom positioned div
  - Must NOT show context menu on empty space (only on file items)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: UI component creation — positioned dropdown, event handling, conditional rendering, styling
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Alpine.js event handling, $wire bridge, Livewire dispatching
    - `tailwindcss-development`: Dropdown styling, shadows, borders, hover states matching Catppuccin palette

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Task 5)
  - **Blocks**: Task 7
  - **Blocked By**: Task 3

  **References**:

  **Pattern References** (existing code to follow):
  - `resources/views/livewire/staging-panel.blade.php:205-230` — Discard modal pattern (Alpine x-model, $wire calls, button layout)
  - `resources/views/livewire/stash-panel.blade.php:129-154` — Drop confirmation modal pattern

  **Documentation References**:
  - AGENTS.md "Hover & Interaction States" — hover is `bg-[#eff1f5]` on white background
  - AGENTS.md "Color System" — border `#ccd0da`, text primary `#4c4f69`, red `#d20f39` for discard

  **External References**:
  - VSCode context menu behavior: right-click unselected = clear+select, right-click selected = keep selection

  **Acceptance Criteria**:

  - [ ] Right-click on file shows context menu at cursor position
  - [ ] Context menu has: Stage/Unstage (context-aware), Stash, Discard
  - [ ] Right-click on unselected file: clears selection, selects that file
  - [ ] Right-click on selected file: keeps entire selection
  - [ ] Menu items show count when >1 file selected ("Stage 3 files")
  - [ ] Menu closes on: click outside, Esc, scroll, selecting action
  - [ ] Discard option shows red text (text-[#d20f39])
  - [ ] Context menu works in both flat view and tree view
  - [ ] Browser's native context menu is suppressed on file items
  - [ ] Discard modal updated to handle array of files

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Right-click shows context menu
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo with changed files
    Steps:
      1. Navigate to app
      2. Right-click on first unstaged file
      3. Assert: Context menu appears near cursor position
      4. Assert: Menu contains "Stage", "Stash", "Discard" items
      5. Assert: Native browser context menu does NOT appear
      6. Screenshot: .sisyphus/evidence/task-4-context-menu.png
    Expected Result: Custom context menu shown
    Evidence: .sisyphus/evidence/task-4-context-menu.png

  Scenario: Right-click on unselected file replaces selection
    Tool: Playwright (playwright skill)
    Preconditions: Two files Cmd+Click selected
    Steps:
      1. Cmd+Click files 1 and 2 (both have accent tint)
      2. Right-click on file 3 (not selected)
      3. Assert: Files 1, 2 lost accent tint
      4. Assert: File 3 has accent tint
      5. Assert: Context menu visible
    Expected Result: Selection cleared, right-clicked file selected
    Evidence: .sisyphus/evidence/task-4-rightclick-unselected.png

  Scenario: Right-click on selected file keeps selection
    Tool: Playwright (playwright skill)
    Preconditions: Two files Cmd+Click selected
    Steps:
      1. Cmd+Click files 1 and 2
      2. Right-click on file 1 (already selected)
      3. Assert: Both files still have accent tint
      4. Assert: Context menu shows "Stage 2 files"
    Expected Result: Selection preserved
    Evidence: .sisyphus/evidence/task-4-rightclick-selected.png

  Scenario: Context menu action executes and closes
    Tool: Playwright (playwright skill)
    Preconditions: Context menu open on unstaged file
    Steps:
      1. Right-click on unstaged file
      2. Click "Stage" in context menu
      3. Assert: Context menu disappears
      4. Assert: File moved to "Staged" section
      5. Assert: Selection cleared
    Expected Result: Action executed, menu closed
    Evidence: .sisyphus/evidence/task-4-action-execute.png

  Scenario: Esc closes context menu
    Tool: Playwright (playwright skill)
    Steps:
      1. Right-click on file (menu appears)
      2. Press Escape
      3. Assert: Context menu disappears
    Expected Result: Menu closed via Esc
    Evidence: .sisyphus/evidence/task-4-esc-close.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add right-click context menu with stage, stash, discard actions`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`
  - Pre-commit: `php artisan test --compact`

---

- [ ] 5. Stash Toolbar Button in Staging Panel

  **What to do**:

  1. Add a stash button to the staging panel toolbar (the `border-b border-[#ccd0da] px-4 h-10` div, lines 18-71 of staging-panel.blade.php):
     - Place it between the tree view toggle (left side) and the existing Stage All/Unstage All/Discard All buttons (right side)
     - Use Phosphor icon: `<x-phosphor-archive class="w-4 h-4" />` (stash = archive metaphor)
     - Wrap in `<flux:tooltip>`
     - Tooltip text is dynamic: "Stash All" when no selection, "Stash N files" when files selected

  2. Button click logic (Alpine):
     ```blade
     <flux:tooltip :content="selectedFiles.length > 0 ? 'Stash ' + selectedFiles.length + ' files' : 'Stash All'">
         <flux:button 
             @click="selectedFiles.length > 0 ? $wire.stashSelected(selectedFiles).then(() => clearSelection()) : $wire.stashAll()"
             variant="ghost" 
             size="xs"
             square
             class="text-[#9ca0b0] hover:text-[#6c6f85]"
         >
             <x-phosphor-archive class="w-4 h-4" />
         </flux:button>
     </flux:tooltip>
     ```
     Note: The exact syntax for dynamic Flux tooltip content with Alpine may need adjustment. If `:content` doesn't work with Alpine expressions, use `x-bind:content` or a plain HTML tooltip approach consistent with the codebase.

  3. Button should be disabled (via `wire:loading.attr="disabled"`) during stash operations.

  4. The button position: Add it to the right-side button group, before "Stage All":
     ```
     [tree-toggle]                [stash] [+stage-all] [-unstage-all] [🗑discard-all]
     ```

  **Must NOT do**:
  - Must NOT show stash message prompt modal — auto-generate message
  - Must NOT add a split button / dropdown for stash options — single button only
  - Must NOT modify the Stage All / Unstage All / Discard All buttons

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small UI addition — single button in existing toolbar
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Wire button click to Livewire method
    - `fluxui-development`: Flux button component, tooltip, variant, sizing

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Task 4)
  - **Blocks**: Task 7
  - **Blocked By**: Task 3

  **References**:

  **Pattern References** (existing code to follow):
  - `resources/views/livewire/staging-panel.blade.php:32-69` — Existing toolbar buttons pattern (Stage All, Unstage All, Discard All)
  - `resources/views/livewire/staging-panel.blade.php:33-45` — Stage All button: `<flux:tooltip>` → `<flux:button>` → Phosphor icon

  **Documentation References**:
  - AGENTS.md "Button Sizes" — `size="xs"` for header icon buttons (36px height)
  - AGENTS.md "Icons: Phosphor Light" — regular phosphor for file actions
  - AGENTS.md "Tooltips on Action Buttons" — all icon-only buttons must be wrapped in `<flux:tooltip>`

  **Acceptance Criteria**:

  - [ ] Stash button visible in toolbar between tree-toggle and Stage All
  - [ ] Button uses `<x-phosphor-archive>` icon
  - [ ] Button wrapped in `<flux:tooltip>` with dynamic content
  - [ ] No selection: clicking stashes ALL changes (calls `stashAll()`)
  - [ ] With selection: clicking stashes selected files (calls `stashSelected(selectedFiles)`)
  - [ ] Button disabled during operation (wire:loading)
  - [ ] Follows existing button styling pattern (variant="ghost", size="xs", square)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Stash All button works with no selection
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo with 3 changed files, no files selected
    Steps:
      1. Navigate to app
      2. Verify 3 files in "Changes" section
      3. Hover stash button → tooltip shows "Stash All"
      4. Click stash button
      5. Wait for file list to refresh (changes section empty or reduced)
      6. Assert: Files disappeared from staging panel
      7. Screenshot: .sisyphus/evidence/task-5-stash-all.png
    Expected Result: All files stashed
    Evidence: .sisyphus/evidence/task-5-stash-all.png

  Scenario: Stash Selected button works with selection
    Tool: Playwright (playwright skill)
    Preconditions: Repo with 4 changed files
    Steps:
      1. Cmd+Click files 1 and 2 (selected)
      2. Hover stash button → tooltip shows "Stash 2 files"
      3. Click stash button
      4. Wait for refresh
      5. Assert: Files 1, 2 gone from list
      6. Assert: Files 3, 4 still in list
      7. Assert: Selection cleared (no accent tint)
    Expected Result: Only selected files stashed
    Evidence: .sisyphus/evidence/task-5-stash-selected.png
  ```

  **Commit**: YES (group with Task 4)
  - Message: `feat(staging): add stash toolbar button with selection awareness`
  - Files: `resources/views/livewire/staging-panel.blade.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 6. Sidebar Stash Actions — Apply, Pop, Drop (TDD)

  **What to do**:

  **TDD — Tests First:**
  1. RED: Create/extend `tests/Feature/Livewire/RepoSidebarTest.php`:
     - Test: `applyStash(int $index)` runs `git stash apply stash@{N}` and dispatches `status-updated`
     - Test: `popStash(int $index)` runs `git stash pop stash@{N}` and dispatches `status-updated`
     - Test: `dropStash(int $index)` runs `git stash drop stash@{N}` and dispatches `status-updated`
     - Test: Apply/Pop dispatch `refresh-staging` event
     - Test: All methods handle errors (bad stash index) with error toast
     - Run: `php artisan test --compact --filter=RepoSidebarTest` → FAIL

  **Livewire Methods:**
  2. GREEN: Add methods to `RepoSidebar.php`:
     - `applyStash(int $index): void` — calls `StashService::stashApply($index)`, dispatches events
     - `popStash(int $index): void` — calls `StashService::stashPop($index)`, dispatches events
     - `dropStash(int $index): void` — calls `StashService::stashDrop($index)`, dispatches events
     - Follow error handling pattern from `StagingPanel` (try/catch, GitErrorHandler, show-error)
     - Refresh sidebar after each action: `$this->refreshSidebar()`
     - Dispatch: `status-updated` + `refresh-staging` (so staging panel picks up restored files)
     - Run: `php artisan test --compact --filter=RepoSidebarTest` → PASS

  **Blade Template — Action Buttons:**
  3. Update `repo-sidebar.blade.php` stash items (lines 82-98) to add hover-revealed action buttons:
     - Apply button: `<flux:tooltip content="Apply (keep in list)">` + `<flux:button wire:click="applyStash({{ $stash['index'] }})">`
     - Pop button: `<flux:tooltip content="Pop (apply and remove)">` + `<flux:button wire:click="popStash({{ $stash['index'] }})">`
     - Drop button: Opens confirmation modal (destructive action)

  4. Add drop confirmation modal to sidebar (same pattern as staging panel discard modal):
     ```blade
     <flux:modal x-model="confirmDropIndex !== null" class="space-y-6">
         <!-- "Drop Stash?" heading, warning text, Cancel + Drop buttons -->
     </flux:modal>
     ```
     Add `confirmDropIndex: null` to the existing Alpine x-data block (line 8).

  5. Add `#[On('stash-created')]` listener to `RepoSidebar.php` that calls `refreshSidebar()` — so when stash is created from staging panel, sidebar updates.

  **Must NOT do**:
  - Must NOT add stash diff viewer (read-only list only)
  - Must NOT add stash renaming
  - Must NOT modify existing `refreshSidebar()` method
  - Must NOT change the collapsible section structure (Remotes/Tags/Stashes)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Adding methods to existing component + hover buttons to existing list — well-defined pattern
  - **Skills**: [`livewire-development`, `pest-testing`]
    - `livewire-development`: Livewire methods, event dispatching, #[On] listeners
    - `pest-testing`: TDD workflow, Process::fake()

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: Task 7
  - **Blocked By**: None

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Livewire/StashPanel.php:58-89` — `applyStash()`, `popStash()`, `dropStash()` method patterns (COPY these exactly)
  - `resources/views/livewire/stash-panel.blade.php:59-87` — Action button layout pattern (hover-revealed, tooltips)
  - `resources/views/livewire/stash-panel.blade.php:129-154` — Drop confirmation modal pattern
  - `resources/views/livewire/repo-sidebar.blade.php:82-98` — Current stash list rendering (add buttons here)
  - `resources/views/livewire/repo-sidebar.blade.php:3-8` — Alpine x-data block (add `confirmDropIndex: null`)

  **API/Type References**:
  - `app/Services/Git/StashService.php:54-74` — `stashApply()`, `stashPop()`, `stashDrop()` methods (already exist, just call them)

  **Acceptance Criteria**:

  - [ ] `RepoSidebar::applyStash(0)` calls `StashService::stashApply(0)` and dispatches `status-updated`
  - [ ] `RepoSidebar::popStash(0)` calls `StashService::stashPop(0)` and dispatches `status-updated` + `refresh-staging`
  - [ ] `RepoSidebar::dropStash(0)` calls `StashService::stashDrop(0)` and dispatches `status-updated`
  - [ ] Drop shows confirmation modal before executing
  - [ ] Error handling with `GitErrorHandler::translate()` + `show-error` dispatch
  - [ ] `#[On('stash-created')]` listener on RepoSidebar refreshes stash list
  - [ ] Action buttons visible on hover in sidebar stash items
  - [ ] `php artisan test --compact --filter=RepoSidebarTest` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Sidebar stash actions via Pest
    Tool: Bash (php artisan test)
    Steps:
      1. Run: php artisan test --compact --filter=RepoSidebarTest
      2. Assert: Exit code 0
      3. Assert: All tests pass
    Expected Result: RepoSidebar tests pass
    Evidence: Terminal output captured

  Scenario: Apply stash from sidebar
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, at least 1 stash exists, sidebar open
    Steps:
      1. Open sidebar (⌘B if collapsed)
      2. Expand "Stashes" section in sidebar
      3. Hover over first stash item
      4. Assert: Apply, Pop, Drop buttons appear
      5. Click Apply button
      6. Wait for staging panel refresh
      7. Assert: Stash still in list (apply keeps it)
      8. Assert: Files from stash appear in staging panel
      9. Screenshot: .sisyphus/evidence/task-6-apply-stash.png
    Expected Result: Stash applied, remains in list
    Evidence: .sisyphus/evidence/task-6-apply-stash.png

  Scenario: Drop stash shows confirmation
    Tool: Playwright (playwright skill)
    Preconditions: Stash exists in sidebar
    Steps:
      1. Hover over stash → click Drop button
      2. Assert: Confirmation modal appears with "Drop Stash?" heading
      3. Click "Drop" in modal
      4. Assert: Stash removed from list
      5. Assert: Modal closed
    Expected Result: Stash dropped after confirmation
    Evidence: .sisyphus/evidence/task-6-drop-confirm.png
  ```

  **Commit**: YES
  - Message: `feat(sidebar): add apply, pop, drop actions to stash list`
  - Files: `app/Livewire/RepoSidebar.php`, `resources/views/livewire/repo-sidebar.blade.php`, `tests/Feature/Livewire/RepoSidebarTest.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 7. Keyboard Shortcuts + Cleanup

  **What to do**:

  **Keyboard Shortcuts:**
  1. Add to `app-layout.blade.php` (alongside existing keyboard handlers, lines 3-8):
     ```blade
     @keydown.window.meta.shift.s.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-stash')"
     @keydown.window.meta.a.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-select-all')"
     ```
     Note: Esc is already handled on line 8 (`@keydown.window.escape.prevent`) — extend it or add selection clear to staging panel's Esc handler.

  2. Add listeners to `StagingPanel.php`:
     - `#[On('keyboard-stash')]` → calls `stashAll()` when no selection, or `stashSelected()` with current selection
     - `#[On('keyboard-select-all')]` → Not a Livewire concern (Alpine handles selection) — dispatch from layout to Alpine via custom event, staging panel listens with `@keyboard-select-all.window="selectAllFiles()"`

  3. Add Alpine method `selectAllFiles()` in staging panel x-data:
     ```javascript
     selectAllFiles() {
         const items = [...this.$el.querySelectorAll('[data-file-path]')];
         this.selectedFiles = items.map(el => el.dataset.filePath);
     }
     ```

  4. Update Esc handler in staging panel: when Esc is pressed, if `selectedFiles.length > 0`, clear selection first (don't close modals if selection active). Add `@keyboard-escape.window="clearSelection()"` to staging panel root div.

  **Stash Shortcut Wiring:**
  5. The `keyboard-stash` event needs to pass selection from Alpine to Livewire. Since selection is Alpine-only, the Alpine listener dispatches a Livewire call:
     ```blade
     @keyboard-stash.window="selectedFiles.length > 0 ? $wire.stashSelected(selectedFiles).then(() => clearSelection()) : $wire.stashAll()"
     ```

  **Cleanup — Remove Orphaned StashPanel:**
  6. Delete `app/Livewire/StashPanel.php` — completely replaced by RepoSidebar stash actions
  7. Delete `resources/views/livewire/stash-panel.blade.php` — no longer needed
  8. Verify no references to `StashPanel` remain anywhere (grep for `StashPanel`, `stash-panel`)

  **Final Verification:**
  9. Run full test suite: `php artisan test --compact` → ALL PASS
  10. Run Pint: `vendor/bin/pint --dirty --format agent`

  **Must NOT do**:
  - Must NOT add Select All / Clear Selection toolbar buttons (keyboard shortcuts only)
  - Must NOT modify existing keyboard shortcut bindings
  - Must NOT delete StashService (still used by RepoSidebar and StagingPanel)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small changes across 3 files + file deletion — straightforward
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Livewire event dispatching, #[On] listeners, keyboard handler patterns

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 4 (sequential — final task)
  - **Blocks**: None (final)
  - **Blocked By**: Tasks 4, 5, 6

  **References**:

  **Pattern References** (existing code to follow):
  - `resources/views/livewire/app-layout.blade.php:3-8` — Keyboard shortcut pattern: `@keydown.window.meta.*.prevent` + dispatch
  - `app/Livewire/StagingPanel.php:118` — `#[On('keyboard-stage-all')]` listener pattern
  - `app/Livewire/StagingPanel.php:136` — `#[On('keyboard-unstage-all')]` listener pattern

  **Acceptance Criteria**:

  - [ ] `⌘⇧S` stashes all (no selection) or selected files (with selection)
  - [ ] `⌘A` selects all visible files in staging panel
  - [ ] `Esc` clears file selection (when selection active)
  - [ ] `StashPanel.php` deleted
  - [ ] `stash-panel.blade.php` deleted
  - [ ] `grep -r "StashPanel" app/ resources/` returns zero results
  - [ ] `php artisan test --compact` → ALL PASS
  - [ ] `vendor/bin/pint --dirty --format agent` → no issues

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: ⌘⇧S stashes all files
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo with changed files, no selection
    Steps:
      1. Navigate to app
      2. Verify files in "Changes" section
      3. Press ⌘⇧S (Meta+Shift+S)
      4. Wait for file list to refresh
      5. Assert: Changes section empty or shows "No changes"
    Expected Result: All changes stashed via keyboard
    Evidence: .sisyphus/evidence/task-7-shortcut-stash.png

  Scenario: ⌘A selects all files
    Tool: Playwright (playwright skill)
    Preconditions: Repo with 5 changed files
    Steps:
      1. Press ⌘A
      2. Assert: All 5 files have accent background tint
    Expected Result: All files selected
    Evidence: .sisyphus/evidence/task-7-select-all.png

  Scenario: Esc clears selection
    Tool: Playwright (playwright skill)
    Preconditions: 3 files selected via Cmd+Click
    Steps:
      1. Press Escape
      2. Assert: No files have accent tint
    Expected Result: Selection cleared
    Evidence: .sisyphus/evidence/task-7-esc-clear.png

  Scenario: Orphaned StashPanel removed
    Tool: Bash (grep)
    Steps:
      1. Run: test ! -f app/Livewire/StashPanel.php && echo "DELETED" || echo "EXISTS"
      2. Assert: Output is "DELETED"
      3. Run: test ! -f resources/views/livewire/stash-panel.blade.php && echo "DELETED" || echo "EXISTS"
      4. Assert: Output is "DELETED"
      5. Run: grep -r "StashPanel" app/ resources/ --include="*.php" --include="*.blade.php" | wc -l
      6. Assert: Output is "0"
    Expected Result: No StashPanel references remain
    Evidence: Terminal output captured

  Scenario: Full test suite passes
    Tool: Bash (php artisan test)
    Steps:
      1. Run: php artisan test --compact
      2. Assert: Exit code 0
      3. Assert: No "FAILED" in output
    Expected Result: All tests green
    Evidence: Terminal output captured

  Scenario: Code style passes
    Tool: Bash (vendor/bin/pint)
    Steps:
      1. Run: vendor/bin/pint --dirty --format agent
      2. Assert: No formatting issues reported
    Expected Result: Code style clean
    Evidence: Terminal output captured
  ```

  **Commit**: YES
  - Message: `feat(staging): add keyboard shortcuts for stash and selection, remove orphaned StashPanel`
  - Files: `resources/views/livewire/app-layout.blade.php`, `app/Livewire/StagingPanel.php`, `resources/views/livewire/staging-panel.blade.php` (deleted: `app/Livewire/StashPanel.php`, `resources/views/livewire/stash-panel.blade.php`)
  - Pre-commit: `php artisan test --compact`

---

## Commit Strategy

| After Task | Message | Key Files | Verification |
|------------|---------|-----------|--------------|
| 1 | `feat(backend): add per-file stash and bulk staging service methods` | StashService, StagingService, tests | `php artisan test --compact` |
| 2 | `feat(staging): add multi-select with Cmd+Click and Shift+Click` | staging-panel.blade, file-tree.blade | `php artisan test --compact` |
| 3 | `feat(staging): wire multi-select to bulk stage/unstage/stash/discard actions` | StagingPanel.php, tests | `php artisan test --compact` |
| 4 | `feat(staging): add right-click context menu with stage, stash, discard actions` | staging-panel.blade, file-tree.blade | `php artisan test --compact` |
| 5 | `feat(staging): add stash toolbar button with selection awareness` | staging-panel.blade | `php artisan test --compact` |
| 6 | `feat(sidebar): add apply, pop, drop actions to stash list` | RepoSidebar, repo-sidebar.blade, tests | `php artisan test --compact` |
| 7 | `feat(staging): add keyboard shortcuts for stash and selection, remove orphaned StashPanel` | app-layout.blade, StagingPanel, deleted files | `php artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact                                    # Expected: All tests pass
php artisan test --compact --filter=StashServiceTest          # Expected: ≥5 tests pass
php artisan test --compact --filter=StagingServiceTest        # Expected: ≥4 tests pass  
php artisan test --compact --filter=StagingPanelTest          # Expected: existing + new tests pass
php artisan test --compact --filter=RepoSidebarTest           # Expected: ≥5 tests pass
vendor/bin/pint --dirty --format agent                        # Expected: No issues
grep -r "StashPanel" app/ resources/ --include="*.php"        # Expected: 0 results
test ! -f app/Livewire/StashPanel.php && echo OK              # Expected: OK
```

### Final Checklist
- [ ] All "Must Have" items present and working
- [ ] All "Must NOT Have" items absent (no message prompt, no checkbox, no hunk stashing, etc.)
- [ ] All Pest tests pass
- [ ] Multi-select works in flat view AND tree view
- [ ] Context menu works on right-click
- [ ] Stash toolbar button is selection-aware
- [ ] Sidebar stash list has apply/pop/drop
- [ ] Keyboard shortcuts work (⌘⇧S, ⌘A, Esc)
- [ ] Orphaned StashPanel removed
- [ ] No regressions in existing functionality
