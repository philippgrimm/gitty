# Performance Optimization — gitty

## TL;DR

> **Quick Summary**: Comprehensive performance overhaul of gitty's Livewire components and git service layer. Eliminates the event cascade that spawns ~8 git processes per click, adds perceived-speed improvements (wire:key, loading states, optimistic UI), replaces Shiki's per-line Node.js process spawning with client-side Highlight.js, and reduces diff viewer payload sizes.
>
> **Deliverables**:
> - Instant visual feedback on all staging actions (wire:loading + button disabling)
> - Event cascade reduced from ~8 git calls to ≤2 per action
> - Polling overhead reduced by ~70% (smarter intervals + change detection)
> - Shiki server-side highlighting replaced with client-side Highlight.js
> - Diff viewer payload reduced by removing pre-rendered HTML from Livewire state
>
> **Estimated Effort**: Large
> **Parallel Execution**: YES — 3 waves with parallel tasks within waves
> **Critical Path**: Task 1-3 (Wave 1) → Task 4-6 (Wave 2) → Task 7-9 (Wave 3)

---

## Context

### Original Request
User reports staging/unstaging and left panel interactions feel "kinda slow." Goal is both actual speed improvements AND perceived performance improvements. User wants "both layers" — quick wins plus deep refactors — and chose client-side highlighting to replace Shiki.

### Interview Summary
**Key Discussions**:
- User chose "Both layers" — quick perceived-speed wins AND deeper architectural fixes
- User chose "Replace with client-side" for syntax highlighting
- Scope covers all Livewire components, git service layer, Blade templates, polling

**Research Findings**:
- **Event cascade**: Single stage action dispatches `status-updated` → 4 components independently call `git status` + more. ~8 git process spawns per click.
- **Zero wire:key**: File lists have no `wire:key`, forcing full DOM re-renders on every poll/update.
- **Zero wire:loading**: No visual feedback during stage/unstage operations. No button disabling.
- **Shiki per-line**: `Shiki::highlight()` called per diff line. spatie/shiki-php spawns Node.js per call. 200-line diff ≈ 200 processes.
- **5 independent pollers**: StagingPanel (3s), BranchManager (5s), StashPanel (5s), RepoSidebar (10s), AutoFetchIndicator (30s). Combined: ~15 git commands per 30s window from polling alone.
- **No debounce**: `wire:model.live` on commit textarea and branch search fires Livewire roundtrip per keystroke.
- **fetchTags() not cached**: Called every 10s poll, unlike all other sidebar data.
- **Service re-instantiation**: `new StagingService()` per method call, each validates .git dir + creates cache instance.
- **Diff payload bloat**: `renderedHtml` + full hunk/line `files` array serialized in Livewire state.
- **Full diff re-render on hunk ops**: Staging one hunk re-parses entire diff + re-highlights all lines via Shiki.

### Metis Review
**Identified Gaps** (addressed):
- Baseline metrics: Addressed via verification commands in acceptance criteria
- Event cascade scope: Fully mapped — listeners are CommitPanel, RepoSidebar, SyncPanel, BranchManager
- Service instantiation: Confirmed `new StagingService()` calls (not singletons) in StagingPanel.php lines 80, 95, 110, 125, 140, 155
- Optimistic UI rollback: Defined — revert + error toast, following existing error handling pattern
- Shiki replacement strategy: Highlight.js chosen, plain text fallback defined
- Polling replacement: Optimize intervals (not FS watchers — too complex for NativePHP)
- Edge cases: Merge conflicts, binary files, large files, concurrent operations addressed in acceptance criteria

---

## Work Objectives

### Core Objective
Make gitty's staging, unstaging, and panel interactions feel instant and responsive by eliminating redundant git calls, adding perceived-speed improvements, and moving expensive rendering to the client.

### Concrete Deliverables
- All file list iterations have `wire:key` for efficient DOM diffing
- All action buttons show loading states and disable during operations
- Commit textarea and branch search debounced (300ms)
- Stage/unstage shows optimistic UI (file moves immediately, reverts on error)
- `status-updated` event cascade consolidated to ≤2 git calls per action
- Polling intervals optimized with hash-based skip-if-unchanged logic
- `fetchTags()` cached with 60s TTL
- Shiki replaced with client-side Highlight.js
- `renderedHtml` removed from Livewire state (rendering moved to client)
- Hunk staging updates only affected hunks, not entire diff

### Definition of Done
- [ ] `php artisan test --compact` passes (all existing tests green)
- [ ] Single stage action triggers ≤2 git commands (verified via logging)
- [ ] All file list items have `wire:key` attributes (verified via DOM inspection)
- [ ] All staging action buttons show loading state during operation
- [ ] No Shiki/Node.js process spawning for syntax highlighting
- [ ] Diff viewer loads 200-line diff in <500ms

### Must Have
- Zero behavior changes to git operations (stage/unstage/commit/discard produce identical git states)
- All keyboard shortcuts continue to work (⌘↵, ⌘⇧↵, ⌘⇧K, ⌘⇧U, ⌘B, Esc)
- All existing error handling preserved (GitErrorHandler, error toasts)
- Polling pause mechanism still works (prevents race conditions during actions)
- Catppuccin Latte color palette unchanged
- File status dots and badges unchanged

### Must NOT Have (Guardrails)
- No virtual scrolling (out of scope — optimization, not feature)
- No diff viewer UI redesign (preserve existing layout)
- No new features added under guise of "optimization"
- No changes to git command flags, arguments, or output parsing
- No changes to database schema, migrations, or models
- No changes to NativePHP/Electron configuration
- No AI-slop: avoid premature abstractions, unnecessary utility extractions, or over-engineered solutions
- No removing existing animations (`animate-slide-in`, `animate-fade-in`, `animate-commit-flash`)

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.
> ALL verification is executed by the agent using tools.

### Test Decision
- **Infrastructure exists**: YES (Pest v4)
- **Automated tests**: Tests-after for service layer changes (Tasks 4-6), no tests for trivial template additions (Tasks 1-3)
- **Framework**: Pest v4 (`php artisan test --compact`)

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

Every task includes agent-executed QA scenarios. For this app:
- **Frontend/UI**: Playwright opens the NativePHP app or dev server, interacts with staging panel
- **Backend/Service**: Bash runs Pest tests, verifies git command behavior
- **Performance**: Bash measures command timing, checks Livewire responses

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately — Perceived Speed, Frontend Only):
├── Task 1: Add wire:key to all file list iterations
├── Task 2: Add wire:loading + button disabling to all actions
└── Task 3: Debounce live inputs (commit textarea, branch search)

Wave 2 (After Wave 1 — Backend Optimization):
├── Task 4: Consolidate status-updated event cascade
├── Task 5: Optimize polling intervals + hash-based skip
└── Task 6: Cache fetchTags() + service reuse

Wave 3 (After Wave 2 — Diff Viewer Overhaul):
├── Task 7: Replace Shiki with client-side Highlight.js
├── Task 8: Refactor diff viewer to client-side rendering
└── Task 9: Incremental hunk updates

Critical Path: Wave 1 → Wave 2 → Wave 3
Parallel Speedup: ~40% faster than sequential (3 tasks per wave)
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | 7, 8 | 2, 3 |
| 2 | None | 4 | 1, 3 |
| 3 | None | None | 1, 2 |
| 4 | 2 | 7 | 5, 6 |
| 5 | None | None | 4, 6 |
| 6 | None | None | 4, 5 |
| 7 | 4 | 8 | None |
| 8 | 7 | 9 | None |
| 9 | 8 | None | None |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Dispatch |
|------|-------|---------------------|
| 1 | 1, 2, 3 | 3 parallel agents: `task(category="quick", ...)` |
| 2 | 4, 5, 6 | 3 parallel agents: Task 4 = `category="deep"`, Tasks 5-6 = `category="quick"` |
| 3 | 7, 8, 9 | Sequential: each depends on previous |

---

## TODOs

### Wave 1: Perceived Speed (Frontend Only)

- [x] 1. Add wire:key to all file list iterations

  **What to do**:
  - Add `wire:key="staged-{{ $file['path'] }}"` to each file item in the staged files loop in `staging-panel.blade.php` (line 91)
  - Add `wire:key="unstaged-{{ $file['path'] }}"` to each file item in the unstaged/untracked files loop in `staging-panel.blade.php` (line 145)
  - Add `wire:key` to each file item in the tree view component `file-tree.blade.php`:
    - Directory nodes (line 7): `wire:key="dir-{{ $node['path'] }}"`
    - File nodes (line 32): `wire:key="file-{{ $node['path'] }}-{{ $staged ? 'staged' : 'unstaged' }}"`
  - Add `wire:key` to section headers (Staged/Changes) in `staging-panel.blade.php`

  **Must NOT do**:
  - Do not change file list order, grouping, or visual appearance
  - Do not change the loop data sources ($stagedFiles, $unstagedFiles, $untrackedFiles)
  - Do not add keys to non-list elements

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple template additions, no logic changes
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Blade template modifications with Livewire directives

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: Tasks 7, 8 (diff viewer needs stable rendering before overhaul)
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/views/livewire/staging-panel.blade.php:90-126` — Staged files loop (flat view). Each `<div wire:click="selectFile(...)">` needs `wire:key`
  - `resources/views/livewire/staging-panel.blade.php:144-195` — Unstaged/untracked files loop (flat view). Same pattern.
  - `resources/views/components/file-tree.blade.php:7-30` — Directory node in recursive tree. Needs `wire:key` on outer div.
  - `resources/views/components/file-tree.blade.php:32-89` — File node in recursive tree. Needs `wire:key` on outer div.

  **API/Type References**:
  - `app/DTOs/GitStatus.php:40-44` — File array structure: `['indexStatus', 'worktreeStatus', 'path', 'oldPath']`. The `path` field is unique per file and suitable as wire:key.

  **Acceptance Criteria**:

  - [ ] Every `@foreach` loop rendering file items has `wire:key` with a unique, stable identifier
  - [ ] `php artisan test --compact` → PASS (no regressions)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: wire:key attributes present on all file items
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep -c "wire:key" resources/views/livewire/staging-panel.blade.php
      2. Assert: count ≥ 2 (one per foreach loop)
      3. grep -c "wire:key" resources/views/components/file-tree.blade.php
      4. Assert: count ≥ 2 (one for directory nodes, one for file nodes)
    Expected Result: wire:key present on all list iterations
    Evidence: grep output captured

  Scenario: All existing tests still pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(staging): add wire:key to file list iterations for efficient DOM diffing`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 2. Add wire:loading states + button disabling to staging actions

  **What to do**:
  - Add `wire:loading` indicators to all stage/unstage/discard buttons in `staging-panel.blade.php`:
    - Stage All button (line 43): Add `wire:loading.attr="disabled"` and `wire:target="stageAll"`
    - Unstage All button (line 53): Add `wire:loading.attr="disabled"` and `wire:target="unstageAll"`
    - Per-file stage button (line 172): Add `wire:loading.attr="disabled"` and `wire:target="stageFile"`
    - Per-file unstage button (line 115): Add `wire:loading.attr="disabled"` and `wire:target="unstageFile"`
  - Add the same pattern to tree view buttons in `file-tree.blade.php`:
    - Stage button (line 71): Add `wire:loading.attr="disabled"` and `wire:target="stageFile"`
    - Unstage button (line 60): Add `wire:loading.attr="disabled"` and `wire:target="unstageFile"`
  - Add `wire:loading` class swap to show a subtle opacity change while action is in progress:
    - `wire:loading.class="opacity-50 pointer-events-none"` on the file item div during its stage/unstage action
  - Add `wire:loading` to commit button in `commit-panel.blade.php`:
    - Commit button (line 36): Add `wire:loading.attr="disabled" wire:target="commit,commitAndPush"`
  - Follow the existing pattern from `sync-panel.blade.php` which already has `x-bind:disabled="$wire.isOperationRunning"` as reference

  **Must NOT do**:
  - Do not add spinners (keep the UI clean and minimal — opacity change is sufficient)
  - Do not change button variants, sizes, or visual styling
  - Do not modify the discard modal behavior (it uses Alpine.js, not wire:click)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Template-only changes, adding Livewire directives to existing elements
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Understanding of wire:loading directive patterns

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: Task 4 (event cascade depends on loading states being present)
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/views/livewire/sync-panel.blade.php` — Reference pattern for loading states. Uses `x-bind:disabled="$wire.isOperationRunning"` with spinner icon. Our approach uses native `wire:loading` instead (simpler, same effect).
  - `resources/views/livewire/staging-panel.blade.php:43-51` — Stage All button to modify
  - `resources/views/livewire/staging-panel.blade.php:53-62` — Unstage All button to modify
  - `resources/views/livewire/staging-panel.blade.php:114-124` — Per-file unstage button (staged section)
  - `resources/views/livewire/staging-panel.blade.php:171-180` — Per-file stage button (changes section)
  - `resources/views/components/file-tree.blade.php:59-67` — Tree view unstage button
  - `resources/views/components/file-tree.blade.php:70-77` — Tree view stage button
  - `resources/views/livewire/commit-panel.blade.php:35-46` — Commit button

  **Acceptance Criteria**:

  - [ ] All stage/unstage/discard buttons have `wire:loading.attr="disabled"` with appropriate `wire:target`
  - [ ] Commit button disables during commit/commitAndPush operations
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: wire:loading directives present on all action buttons
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep -c "wire:loading" resources/views/livewire/staging-panel.blade.php
      2. Assert: count ≥ 4 (stage all, unstage all, per-file stage, per-file unstage)
      3. grep -c "wire:loading" resources/views/components/file-tree.blade.php
      4. Assert: count ≥ 2 (stage, unstage)
      5. grep -c "wire:loading" resources/views/livewire/commit-panel.blade.php
      6. Assert: count ≥ 1
    Expected Result: wire:loading present on all action buttons
    Evidence: grep output captured

  Scenario: All existing tests still pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(staging): add wire:loading states to prevent spam-clicking during operations`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`, `resources/views/livewire/commit-panel.blade.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 3. Debounce live inputs

  **What to do**:
  - Change commit message textarea from `wire:model.live="message"` to `wire:model.blur="message"` in `commit-panel.blade.php` (line 14). The commit message only matters when the user clicks commit, so blur is more appropriate than debounce — it sends once when leaving the field, not every 300ms while typing.
  - Change branch search input from `wire:model.live="branchQuery"` to `wire:model.live.debounce.300ms="branchQuery"` in `branch-manager.blade.php` (line 67). This needs live updates for search-as-you-type, but debounced.

  **Must NOT do**:
  - Do not change `wire:model` on modal inputs (stash message, branch name, settings) — those are already non-live
  - Do not change the character counter behavior in commit panel (it should still update live for UX — handle via Alpine.js `x-on:input` instead of wire:model)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Two-line changes to existing wire:model directives
  - **Skills**: []
    - No special skills needed — trivial template edits

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/views/livewire/commit-panel.blade.php:14` — Current: `wire:model.live="message"`. The character counter on line 29 (`{{ strlen($message) }}`) will need to be updated to use Alpine.js `x-data` with local character counting instead of relying on Livewire state.
  - `resources/views/livewire/branch-manager.blade.php:67` — Current: `wire:model.live="branchQuery"`. Add `.debounce.300ms`.
  - `resources/views/livewire/commit-panel.blade.php:29` — Character counter `{{ strlen($message) }}` — needs to become Alpine-driven: `x-text="$el.closest('[x-data]').querySelector('textarea').value.length"` or similar, so it updates instantly without Livewire roundtrip.

  **Acceptance Criteria**:

  - [ ] Commit textarea uses `wire:model.blur` (not `wire:model.live`)
  - [ ] Character counter updates instantly via Alpine.js (not Livewire)
  - [ ] Branch search uses `wire:model.live.debounce.300ms`
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Commit textarea no longer uses wire:model.live
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "wire:model.live" resources/views/livewire/commit-panel.blade.php
      2. Assert: no matches for wire:model.live (should be wire:model.blur)
      3. grep "wire:model.blur" resources/views/livewire/commit-panel.blade.php
      4. Assert: 1 match found
    Expected Result: Commit textarea uses blur binding
    Evidence: grep output captured

  Scenario: Branch search has debounce
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "debounce" resources/views/livewire/branch-manager.blade.php
      2. Assert: matches found for debounce.300ms
    Expected Result: Branch search is debounced
    Evidence: grep output captured

  Scenario: All existing tests still pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(panels): debounce commit textarea and branch search to reduce Livewire roundtrips`
  - Files: `resources/views/livewire/commit-panel.blade.php`, `resources/views/livewire/branch-manager.blade.php`
  - Pre-commit: `php artisan test --compact`

---

### Wave 2: Backend Optimization

- [ ] 4. Consolidate status-updated event cascade

  **What to do**:
  - **Problem**: When StagingPanel dispatches `status-updated`, four components each independently call `GitService::status()`:
    - `CommitPanel::refreshStagedCount()` (line 33-41) — calls `gitService->status()` just to count staged files
    - `RepoSidebar::refreshSidebar()` (line 36-73) — calls `gitService->status()` + branches + remotes + stash + tags
    - `SyncPanel::refreshAheadBehind()` (line 38-47) — calls `gitService->status()` just for ahead/behind
    - BranchManager has no `#[On('status-updated')]` listener but polls independently
  - **Solution**: Change the `status-updated` event to carry the status data as payload, so listeners don't re-fetch:
    1. In `StagingPanel.php`, after `refreshStatus()` succeeds, dispatch status data with the event:
       ```php
       $this->dispatch('status-updated', 
           stagedCount: $this->stagedFiles->count(),
           aheadBehind: $status->aheadBehind,
       );
       ```
    2. In `CommitPanel.php`, update `refreshStagedCount()` to accept event params instead of calling `git status`:
       ```php
       #[On('status-updated')]
       public function refreshStagedCount(int $stagedCount, array $aheadBehind = []): void
       {
           $this->stagedCount = $stagedCount;
       }
       ```
    3. In `SyncPanel.php`, update `refreshAheadBehind()` to accept event params:
       ```php
       #[On('status-updated')]
       public function refreshAheadBehind(int $stagedCount = 0, array $aheadBehind = []): void
       {
           $this->aheadBehind = $aheadBehind;
       }
       ```
    4. In `RepoSidebar.php`, remove `#[On('status-updated')]` from `refreshSidebar()`. The sidebar already polls every 10s — it doesn't need to eagerly refresh on every stage action. If sidebar data must update, dispatch a separate `sidebar-refresh-needed` event only for operations that actually affect sidebar (branch switch, stash, not staging).
    5. Ensure the `DiffViewer` component (which dispatches `status-updated` from `stageHunk`/`unstageHunk`) also passes the same payload shape. Since DiffViewer doesn't have status data, it should dispatch to StagingPanel instead: `$this->dispatch('refresh-staging')` → StagingPanel listens and re-dispatches with data.

  - **Also update other dispatchers**: `SyncPanel`, `BranchManager`, `StashPanel` all dispatch `status-updated`. Each should include staged count + ahead/behind data, or the listeners should gracefully handle missing params.

  **Must NOT do**:
  - Do not change git command behavior or output parsing
  - Do not remove polling entirely (it catches external changes)
  - Do not change the DiffViewer's `file-selected` event pattern (that's separate)
  - Do not add new Livewire components or create an "event bus" abstraction

  **Recommended Agent Profile**:
  - **Category**: `deep`
    - Reason: Multi-component refactor affecting event flow across 5+ components. Requires understanding full event dependency graph and careful modification.
  - **Skills**: []
    - No special skills — pure PHP/Livewire refactoring

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 5, 6)
  - **Blocks**: Task 7 (Shiki replacement needs stable event flow)
  - **Blocked By**: Task 2 (loading states should be in place before changing event flow)

  **References**:

  **Pattern References**:
  - `app/Livewire/StagingPanel.php:77-90` — `stageFile()` method: runs staging → refreshStatus → dispatch('status-updated'). This is the PRIMARY dispatch site to modify.
  - `app/Livewire/StagingPanel.php:39-75` — `refreshStatus()`: calls `$gitService->status()`, parses into collections. The `$status` object (GitStatus DTO) contains all data needed by other components.
  - `app/Livewire/CommitPanel.php:32-41` — `#[On('status-updated')] refreshStagedCount()`: currently creates new GitService and calls status(). Replace with event param.
  - `app/Livewire/SyncPanel.php:36-47` — `#[On('status-updated')] refreshAheadBehind()`: currently creates new GitService and calls status(). Replace with event param.
  - `app/Livewire/RepoSidebar.php:35-73` — `#[On('status-updated')] refreshSidebar()`: calls status + 4 more git commands. Remove the listener; rely on 10s polling only.
  - `app/Livewire/DiffViewer.php:180-181` — dispatches `status-updated` after hunk staging. Needs to dispatch differently (it doesn't have status data).

  **API/Type References**:
  - `app/DTOs/GitStatus.php:9-16` — GitStatus DTO: `$branch`, `$upstream`, `$aheadBehind`, `$changedFiles`. Used to extract staged count and ahead/behind.

  **Acceptance Criteria**:

  - [ ] `CommitPanel::refreshStagedCount()` no longer calls `GitService::status()`
  - [ ] `SyncPanel::refreshAheadBehind()` no longer calls `GitService::status()`
  - [ ] `RepoSidebar` no longer has `#[On('status-updated')]` listener
  - [ ] Single stage action triggers ≤2 git process spawns (git add + git status in StagingPanel only)
  - [ ] Write new Pest test: verify `status-updated` event carries `stagedCount` and `aheadBehind` params
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: CommitPanel no longer calls GitService directly on status-updated
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep -n "new GitService" app/Livewire/CommitPanel.php
      2. Assert: no matches in refreshStagedCount method (may still exist in mount)
      3. grep -n "gitService->status" app/Livewire/CommitPanel.php
      4. Assert: no matches in refreshStagedCount method
    Expected Result: CommitPanel receives data via event params
    Evidence: grep output captured

  Scenario: RepoSidebar no longer listens to status-updated
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "On('status-updated')" app/Livewire/RepoSidebar.php
      2. Assert: no matches
    Expected Result: Sidebar relies on polling only
    Evidence: grep output captured

  Scenario: All existing tests pass + new event test
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass including new event cascade test
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(backend): consolidate status-updated cascade to eliminate redundant git status calls`
  - Files: `app/Livewire/StagingPanel.php`, `app/Livewire/CommitPanel.php`, `app/Livewire/SyncPanel.php`, `app/Livewire/RepoSidebar.php`, `app/Livewire/DiffViewer.php`, new test file
  - Pre-commit: `php artisan test --compact`

---

- [x] 5. Optimize polling intervals + hash-based skip

  **What to do**:
  - **Increase polling intervals** to reduce baseline git command overhead:
    - `staging-panel.blade.php`: Change `wire:poll.3s` to `wire:poll.5s` (line 2)
    - `repo-sidebar.blade.php`: Change `wire:poll.10s` to `wire:poll.30s` (line 2)
    - `branch-manager.blade.php`: Change `wire:poll.5s` to `wire:poll.15s` (line 2)
    - `stash-panel.blade.php`: Change `wire:poll.5s` to `wire:poll.15s` (line 2)
    - Keep `auto-fetch-indicator.blade.php` at 30s (already reasonable)
  - **Add hash-based skip-if-unchanged** to StagingPanel:
    1. Add a private `$lastStatusHash` property to `StagingPanel.php`
    2. In `refreshStatus()`, after getting `$status`, compute a hash of the changedFiles collection
    3. If hash matches `$lastStatusHash`, skip rebuilding collections and return early
    4. This prevents unnecessary DOM updates when nothing changed (most poll cycles)
  - **Apply same hash-skip pattern to RepoSidebar**:
    1. Hash the combined branches + remotes + tags + stashes data
    2. Skip if unchanged

  **Must NOT do**:
  - Do not remove polling entirely (it catches external changes from IDE/terminal)
  - Do not implement file system watchers (out of scope, NativePHP complexity)
  - Do not change the polling pause mechanism (it still works correctly)
  - Do not make intervals configurable via UI (out of scope)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple interval changes + straightforward hash comparison logic
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 6)
  - **Blocks**: None
  - **Blocked By**: None (independent of event cascade fix)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/staging-panel.blade.php:2` — Current: `wire:poll.3s.visible="refreshStatus"`
  - `resources/views/livewire/repo-sidebar.blade.php:2` — Current: `wire:poll.10s.visible="refreshSidebar"`
  - `resources/views/livewire/branch-manager.blade.php:2` — Current: `wire:poll.5s.visible="refreshBranches"`
  - `resources/views/livewire/stash-panel.blade.php:2` — Current: `wire:poll.5s.visible="refreshStashes"`
  - `app/Livewire/StagingPanel.php:39-75` — `refreshStatus()` method. Add hash comparison before line 49 (collection rebuild).
  - `app/Livewire/StagingPanel.php:193-198` — `pausePollingTemporarily()` — reference for how polling state is managed.

  **Acceptance Criteria**:

  - [ ] StagingPanel polls at 5s intervals (not 3s)
  - [ ] RepoSidebar polls at 30s intervals (not 10s)
  - [ ] BranchManager and StashPanel poll at 15s (not 5s)
  - [ ] StagingPanel skips collection rebuild when git status output hash is unchanged
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Polling intervals updated
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "wire:poll" resources/views/livewire/staging-panel.blade.php
      2. Assert: contains "5s" (not "3s")
      3. grep "wire:poll" resources/views/livewire/repo-sidebar.blade.php
      4. Assert: contains "30s" (not "10s")
      5. grep "wire:poll" resources/views/livewire/branch-manager.blade.php
      6. Assert: contains "15s" (not "5s")
    Expected Result: All polling intervals increased
    Evidence: grep output captured

  Scenario: Hash-based skip implemented
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "lastStatusHash\|statusHash" app/Livewire/StagingPanel.php
      2. Assert: hash comparison logic present
    Expected Result: Hash-based skip prevents unnecessary updates
    Evidence: grep output captured

  Scenario: All existing tests still pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(polling): increase intervals and add hash-based skip to reduce redundant updates`
  - Files: Blade templates (4 files), `app/Livewire/StagingPanel.php`, `app/Livewire/RepoSidebar.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 6. Cache fetchTags() + service reuse

  **What to do**:
  - **Cache fetchTags()** in `RepoSidebar.php`:
    1. Use `GitCacheService` to cache tag results with 60s TTL (matching branches cache)
    2. Add `'tags'` to the GitCacheService GROUPS constant: `'tags' => ['tags']`
    3. Invalidate tags cache when branches are invalidated (tags don't change often)
  - **Optimize service instantiation** in `StagingPanel.php`:
    1. Instead of `new StagingService($this->repoPath)` in every method, create a helper:
       ```php
       private function stagingService(): StagingService
       {
           return new StagingService($this->repoPath);
       }
       ```
    2. Or better: extract the `.git` directory validation to a shared trait/concern so it only runs once per component lifecycle, not per action call
  - **Move fetchTags() to a TagService or into BranchService** — or simply wrap the existing inline Process call with GitCacheService:
    ```php
    private function fetchTags(): array
    {
        $cache = new GitCacheService();
        return $cache->get($this->repoPath, 'tags', function () {
            $result = Process::path($this->repoPath)->run('git tag -l --format=...');
            // ... existing parsing logic ...
        }, 60);
    }
    ```

  **Must NOT do**:
  - Do not create new service classes (keep changes minimal)
  - Do not change cache TTLs for existing cached data (status 5s, branches 30s, etc.)
  - Do not change the GitCacheService API

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small caching addition + minor refactor
  - **Skills**: []
    - No special skills needed

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 5)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/RepoSidebar.php:84-102` — `fetchTags()` method. Currently runs `git tag -l` with no caching.
  - `app/Services/Git/GitCacheService.php:13-19` — GROUPS constant. Add `'tags' => ['tags']`.
  - `app/Services/Git/GitCacheService.php:21-26` — `get()` method. Use this to wrap fetchTags().
  - `app/Services/Git/BranchService.php:26-39` — `branches()` method. Reference pattern for how to use GitCacheService::get() with TTL.
  - `app/Livewire/StagingPanel.php:80,95,110,125,140,155` — Six locations where `new StagingService()` is called. Each creates new GitCacheService internally.

  **Acceptance Criteria**:

  - [ ] `fetchTags()` uses `GitCacheService::get()` with 60s TTL
  - [ ] `'tags'` group added to `GitCacheService::GROUPS`
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Tags are cached via GitCacheService
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "cache->get\|GitCacheService" app/Livewire/RepoSidebar.php
      2. Assert: cache usage found in fetchTags method
      3. grep "'tags'" app/Services/Git/GitCacheService.php
      4. Assert: 'tags' group exists in GROUPS constant
    Expected Result: Tags are cached with TTL
    Evidence: grep output captured

  Scenario: All existing tests still pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(sidebar): cache fetchTags() and add tags to cache invalidation groups`
  - Files: `app/Livewire/RepoSidebar.php`, `app/Services/Git/GitCacheService.php`
  - Pre-commit: `php artisan test --compact`

---

### Wave 3: Diff Viewer Overhaul

- [x] 7. Replace Shiki with client-side Highlight.js

  **What to do**:
  - **Install Highlight.js** via npm: `npm install highlight.js`
  - **Create a Blade/Alpine component** for client-side syntax highlighting:
    1. In `resources/js/app.js` (or a new `resources/js/diff-highlighter.js`), import Highlight.js with commonly-used language packs (PHP, JS, TS, CSS, HTML, Python, Go, Rust, Ruby, JSON, YAML, Markdown, Bash, SQL — matching the language map in DiffService.php:139-164)
    2. Register an Alpine.js component or directive that highlights code blocks after they're inserted into the DOM
  - **Modify DiffService::renderDiffHtml()** to output plain (unhighlighted) HTML:
    1. Remove the `Shiki::highlight()` call on line 87
    2. Replace with `htmlspecialchars($line->content)` (already the fallback on line 90)
    3. Add a `data-language` attribute to the diff container so Highlight.js knows which language to use
    4. Add a CSS class (e.g., `hljs-pending`) to lines that need highlighting
  - **Add Highlight.js initialization** in the diff viewer Blade template:
    1. Use Alpine's `x-init` or `x-effect` to run Highlight.js on the rendered diff content
    2. Use `hljs.highlightElement()` on each `.line-content` span
    3. Ensure highlighting runs after Livewire morphdom updates (use `Livewire.hook('morphed', ...)` or Alpine `$nextTick`)
  - **Remove spatie/shiki-php dependency**: `composer remove spatie/shiki-php`
  - **Update vite.config.js** if needed to include the new JS

  **Must NOT do**:
  - Do not change diff line structure (additions, deletions, context classes)
  - Do not change diff colors (Catppuccin Latte green/red tints)
  - Do not add line numbers or blame annotations
  - Do not change the hunk header rendering
  - Do not make Highlight.js configurable via UI

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Cross-cutting change spanning PHP service, Blade templates, JavaScript, npm dependencies, and Vite config. Moderate complexity.
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: JavaScript integration with Blade/Alpine, npm package integration, Vite bundling

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential (Wave 3)
  - **Blocks**: Task 8 (diff viewer refactor depends on highlighting being client-side)
  - **Blocked By**: Task 4 (event flow should be stable)

  **References**:

  **Pattern References**:
  - `app/Services/Git/DiffService.php:35-104` — `renderDiffHtml()` method. The Shiki call is on line 87. The entire method builds HTML string with syntax-highlighted content. Remove Shiki, keep HTML structure.
  - `app/Services/Git/DiffService.php:84-91` — The try/catch block around Shiki. Currently falls back to `htmlspecialchars()` on failure. Make the fallback the default.
  - `app/Services/Git/DiffService.php:139-164` — `mapExtensionToLanguage()`. Keep this mapping but use it for Highlight.js language hints (`data-language` attribute).
  - `resources/css/app.css` — Diff line styles (`.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context`). These must be preserved.
  - `resources/views/livewire/diff-viewer.blade.php:79-83` — Where `{!! $renderedHtml !!}` is rendered. This is where Highlight.js initialization should hook in.
  - `vite.config.js` — Current Vite config for reference on how JS is bundled

  **External References**:
  - Highlight.js docs: https://highlightjs.org/ — API reference for `hljs.highlightElement()`
  - Highlight.js language support: https://github.com/highlightjs/highlight.js/blob/main/SUPPORTED_LANGUAGES.md

  **Acceptance Criteria**:

  - [ ] `spatie/shiki-php` removed from `composer.json`
  - [ ] `highlight.js` added to `package.json`
  - [ ] Diff lines are syntax-highlighted in the browser (not server)
  - [ ] No Node.js processes spawned during diff rendering
  - [ ] Diff viewer visually displays code with syntax colors (may differ slightly from Shiki — that's acceptable)
  - [ ] `php artisan test --compact` → PASS
  - [ ] `npm run build` → succeeds

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Shiki dependency removed
    Tool: Bash
    Preconditions: None
    Steps:
      1. grep "shiki" composer.json
      2. Assert: no matches (dependency removed)
      3. grep "Shiki" app/Services/Git/DiffService.php
      4. Assert: no matches (import and usage removed)
    Expected Result: No server-side Shiki dependency
    Evidence: grep output captured

  Scenario: Highlight.js dependency added and builds
    Tool: Bash
    Preconditions: None
    Steps:
      1. grep "highlight.js" package.json
      2. Assert: dependency present
      3. Run: npm run build
      4. Assert: exit code 0, no build errors
    Expected Result: Highlight.js bundled successfully
    Evidence: Build output captured

  Scenario: All tests pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0, no failures
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(diff): replace Shiki server-side highlighting with client-side Highlight.js`
  - Files: `app/Services/Git/DiffService.php`, `resources/js/app.js` (or new file), `resources/views/livewire/diff-viewer.blade.php`, `composer.json`, `package.json`, `vite.config.js`
  - Pre-commit: `php artisan test --compact && npm run build`

---

- [x] 8. Refactor diff viewer to client-side rendering (remove renderedHtml from state)

  **What to do**:
  - **Remove `$renderedHtml` from DiffViewer component state**:
    1. In `DiffViewer.php`, remove the `public string $renderedHtml = '';` property (line 26)
    2. Remove the `$this->renderedHtml = $diffService->renderDiffHtml(...)` call in `loadDiff()` (line 128)
    3. The `$files` array already contains all necessary data (hunks, lines, types, line numbers)
  - **Move diff HTML rendering to the Blade template**:
    1. Replace `{!! $renderedHtml !!}` (diff-viewer.blade.php:81) with a Blade loop over `$files`
    2. Render each file → each hunk → each line using Blade `@foreach` loops
    3. Apply diff line classes (`diff-line-addition`, `diff-line-deletion`, `diff-line-context`) directly in Blade
    4. Set `data-language` attribute on code containers for Highlight.js to pick up
  - **Reduce DiffService scope**:
    1. `renderDiffHtml()` is no longer needed — remove it entirely (or keep as dead code for reference temporarily)
    2. DiffService now only handles `stageHunk()`, `unstageHunk()`, `generatePatch()`
  - **Ensure Highlight.js hooks into Livewire morphdom**:
    1. After Livewire updates the diff DOM, Highlight.js needs to re-highlight new/changed elements
    2. Use a Livewire JavaScript hook: `Livewire.hook('morph.updated', ...)` to trigger re-highlighting

  **Must NOT do**:
  - Do not change the `$files` array structure (it's the data contract)
  - Do not change `$diffData` structure
  - Do not modify hunk staging/unstaging logic
  - Do not add virtual scrolling or lazy loading of hunks

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Significant refactor moving rendering from PHP service to Blade template + JavaScript. Requires coordinating DiffService, DiffViewer component, and Blade template.
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Complex Blade template rendering with dynamic data, Alpine.js/Livewire hooks

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential after Task 7
  - **Blocks**: Task 9
  - **Blocked By**: Task 7 (Highlight.js must be installed first)

  **References**:

  **Pattern References**:
  - `app/Livewire/DiffViewer.php:26` — `public string $renderedHtml = '';` — Remove this property
  - `app/Livewire/DiffViewer.php:98-124` — Where `$files` array is built with full hunk/line data. This data is sufficient for Blade rendering.
  - `app/Livewire/DiffViewer.php:126-129` — Where `renderDiffHtml()` is called. Remove this block.
  - `app/Services/Git/DiffService.php:35-104` — `renderDiffHtml()` — Can be removed after this task
  - `resources/views/livewire/diff-viewer.blade.php:79-83` — Current: `{!! $renderedHtml !!}`. Replace with Blade loop over `$files`.
  - `resources/css/app.css` — Diff CSS classes (`.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context`, `.diff-hunk-header`, `.line-number`, `.line-content`). Must be preserved in Blade output.

  **Acceptance Criteria**:

  - [ ] `$renderedHtml` property removed from `DiffViewer.php`
  - [ ] `renderDiffHtml()` method removed from `DiffService.php`
  - [ ] Diff lines rendered via Blade `@foreach` over `$files` array
  - [ ] Highlight.js re-runs after Livewire morphdom updates
  - [ ] Diff visual output matches pre-refactor (line numbers, +/- colors, hunk headers)
  - [ ] Hunk stage/unstage buttons still work (`wire:click="stageHunk"` / `unstageHunk"`)
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: renderedHtml removed from component state
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "renderedHtml" app/Livewire/DiffViewer.php
      2. Assert: no matches (property removed)
      3. grep "renderDiffHtml" app/Services/Git/DiffService.php
      4. Assert: no matches (method removed)
    Expected Result: Server-side HTML rendering eliminated
    Evidence: grep output captured

  Scenario: Diff rendered via Blade loops
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "@foreach.*files" resources/views/livewire/diff-viewer.blade.php
      2. Assert: Blade foreach loops present for rendering diff
      3. grep "diff-line-addition\|diff-line-deletion\|diff-line-context" resources/views/livewire/diff-viewer.blade.php
      4. Assert: diff line classes applied in Blade
    Expected Result: Diff rendered client-side via Blade
    Evidence: grep output captured

  Scenario: All tests pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(diff): move diff rendering from PHP to Blade, remove renderedHtml from Livewire state`
  - Files: `app/Livewire/DiffViewer.php`, `app/Services/Git/DiffService.php`, `resources/views/livewire/diff-viewer.blade.php`
  - Pre-commit: `php artisan test --compact`

---

- [ ] 9. Incremental hunk updates (don't re-render entire diff on hunk stage)

  **What to do**:
  - **Problem**: `stageHunk()` and `unstageHunk()` in DiffViewer.php currently call `$this->loadDiff()` which re-parses the entire diff and re-renders all hunks. After Task 8, this means a full Blade re-render of all `$files` data.
  - **Solution**: After staging/unstaging a hunk, only update the affected parts of `$files`:
    1. After `git apply --cached` succeeds, re-run `git diff` for the specific file only
    2. Update only the affected file in `$files` array, rather than rebuilding the entire array
    3. Livewire's morphdom will then only update the changed DOM elements (especially effective now that we have wire:key from Task 1)
  - **Implementation**:
    1. In `stageHunk()` (line 139), after the apply succeeds:
       ```php
       // Instead of: $this->loadDiff($this->file, $this->isStaged);
       // Do: Refresh only the specific file's hunks
       $gitService = new GitService($this->repoPath);
       $diffResult = $gitService->diff($this->file, $this->isStaged);
       if ($diffResult->files->isEmpty()) {
           // File fully staged/unstaged — clear diff
           $this->files = null;
           $this->isEmpty = true;
       } else {
           // Update only the changed file's data in $files
           $this->files[$fileIndex] = [...]; // rebuild just this entry
       }
       ```
    2. This avoids re-running file size check, re-computing diffData for unchanged files, etc.
  - **Add `wire:key` to hunk containers** in the new Blade-rendered diff (from Task 8):
    - Each hunk div: `wire:key="hunk-{{ $fileIndex }}-{{ $hunkIndex }}"`
    - Each line div: Use array index within the hunk (Livewire handles this with parent key)

  **Must NOT do**:
  - Do not change the `git apply` logic or patch generation
  - Do not change the hunk/line data structure
  - Do not add WebSocket or streaming updates (out of scope)
  - Do not remove the `status-updated` dispatch after hunk operations

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Targeted diff update logic requires understanding the full data flow from git command → DTO → Livewire state → Blade rendering
  - **Skills**: []
    - No special skills — PHP refactoring of existing logic

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential after Task 8
  - **Blocks**: None (final task)
  - **Blocked By**: Task 8 (needs Blade-rendered diff to be in place)

  **References**:

  **Pattern References**:
  - `app/Livewire/DiffViewer.php:139-186` — `stageHunk()` method. Currently reconstructs DTOs, applies patch, then calls `loadDiff()` for full re-render. Optimize to partial update.
  - `app/Livewire/DiffViewer.php:188-235` — `unstageHunk()` method. Same pattern as stageHunk — optimize identically.
  - `app/Livewire/DiffViewer.php:51-137` — `loadDiff()` method. Shows the full parsing flow to avoid: file size check (line 58), full diff parse (line 73), full files array rebuild (line 98-124).
  - `app/Services/Git/GitService.php:63-76` — `diff()` method. Can be called with specific file to get targeted diff.

  **Acceptance Criteria**:

  - [ ] `stageHunk()` does NOT call `loadDiff()` (no full re-parse)
  - [ ] `unstageHunk()` does NOT call `loadDiff()` (no full re-parse)
  - [ ] Only the affected file's data in `$files` is updated after hunk operations
  - [ ] Hunk containers in Blade have `wire:key` for efficient morphdom
  - [ ] `php artisan test --compact` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: stageHunk no longer calls loadDiff
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "loadDiff" app/Livewire/DiffViewer.php
      2. Assert: loadDiff is NOT called inside stageHunk or unstageHunk methods
         (may still exist as a standalone method, but not called from hunk operations)
    Expected Result: Hunk operations do partial updates only
    Evidence: grep output captured

  Scenario: Hunk wire:key attributes present
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. grep "wire:key.*hunk" resources/views/livewire/diff-viewer.blade.php
      2. Assert: wire:key present on hunk containers
    Expected Result: Efficient DOM diffing for hunks
    Evidence: grep output captured

  Scenario: All tests pass
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: exit code 0
    Expected Result: All tests pass
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `perf(diff): implement incremental hunk updates instead of full diff re-render`
  - Files: `app/Livewire/DiffViewer.php`, `resources/views/livewire/diff-viewer.blade.php`
  - Pre-commit: `php artisan test --compact`

---

## Commit Strategy

| After Task | Message | Key Files | Verification |
|------------|---------|-----------|--------------|
| 1 | `perf(staging): add wire:key to file list iterations` | staging-panel.blade.php, file-tree.blade.php | `php artisan test --compact` |
| 2 | `perf(staging): add wire:loading states to action buttons` | staging-panel.blade.php, file-tree.blade.php, commit-panel.blade.php | `php artisan test --compact` |
| 3 | `perf(panels): debounce commit textarea and branch search` | commit-panel.blade.php, branch-manager.blade.php | `php artisan test --compact` |
| 4 | `perf(backend): consolidate status-updated cascade` | StagingPanel.php, CommitPanel.php, SyncPanel.php, RepoSidebar.php | `php artisan test --compact` |
| 5 | `perf(polling): increase intervals and add hash-based skip` | 4 blade templates, StagingPanel.php, RepoSidebar.php | `php artisan test --compact` |
| 6 | `perf(sidebar): cache fetchTags() with 60s TTL` | RepoSidebar.php, GitCacheService.php | `php artisan test --compact` |
| 7 | `perf(diff): replace Shiki with client-side Highlight.js` | DiffService.php, diff-viewer.blade.php, app.js, composer.json, package.json | `php artisan test --compact && npm run build` |
| 8 | `perf(diff): move diff rendering from PHP to Blade` | DiffViewer.php, DiffService.php, diff-viewer.blade.php | `php artisan test --compact` |
| 9 | `perf(diff): implement incremental hunk updates` | DiffViewer.php, diff-viewer.blade.php | `php artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
# All tests pass
php artisan test --compact
# Expected: Tests: X passed, 0 failed

# No Shiki dependency
grep -c "shiki" composer.json
# Expected: 0

# Highlight.js installed
grep "highlight.js" package.json
# Expected: match found

# Frontend builds
npm run build
# Expected: exit code 0

# wire:key present on all file lists
grep -c "wire:key" resources/views/livewire/staging-panel.blade.php
# Expected: ≥ 2

# wire:loading present on action buttons
grep -c "wire:loading" resources/views/livewire/staging-panel.blade.php
# Expected: ≥ 4

# No server-side Shiki calls
grep -c "Shiki::" app/Services/Git/DiffService.php
# Expected: 0

# renderedHtml removed from state
grep -c "renderedHtml" app/Livewire/DiffViewer.php
# Expected: 0

# CommitPanel doesn't call git status directly on event
grep -c "gitService->status" app/Livewire/CommitPanel.php
# Expected: 0 (or only in mount, not in refreshStagedCount)
```

### Final Checklist
- [ ] All "Must Have" present (zero behavior changes, keyboard shortcuts work, error handling preserved)
- [ ] All "Must NOT Have" absent (no virtual scrolling, no new features, no git command changes)
- [ ] All tests pass (`php artisan test --compact`)
- [ ] Frontend builds (`npm run build`)
- [ ] Staging actions show loading state during operation
- [ ] Event cascade reduced (single status call per action, not 4+)
- [ ] Polling intervals increased (5s/15s/30s instead of 3s/5s/10s)
- [ ] Diff viewer uses client-side Highlight.js (no Node.js spawning)
- [ ] Diff HTML not stored in Livewire component state
