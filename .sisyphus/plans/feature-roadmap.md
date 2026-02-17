# Gitty Professional Feature Roadmap

## TL;DR

> **Quick Summary**: Add 19 missing features across 4 tiers to transform gitty from a staging-focused tool into a full professional macOS git client that competes with Fork and Tower. Progressive disclosure: simple by default, powerful when needed.
> 
> **Deliverables**:
> - Commit History/Log viewer with pagination
> - Undo Last Commit operation
> - 3-way Merge Conflict Resolution editor
> - Git Reset/Revert operations
> - Line-level staging in diff viewer
> - Side-by-side diff view toggle
> - Interactive Rebase UI
> - Cherry-pick support
> - File Blame/Annotation view
> - Visual Git Graph (branch topology)
> - Search (commits, files, content)
> - Image Diff viewer
> - Tag Management UI
> - Dark Mode (Catppuccin Mocha)
> - Commit Message Templates
> - Open in External Editor
> - Native macOS Notifications
> - Quick Commit / Message History
> - Keyboard Shortcuts expansion
> 
> **Estimated Effort**: XL (19 features, multi-week)
> **Parallel Execution**: YES — 5 waves
> **Critical Path**: Task 1 (History) → Task 10 (Graph) → Task 11 (Search)

---

## Context

### Original Request
User wants a detailed analysis of which features to add to make gitty a really cool and professional macOS git client.

### Interview Summary
**Key Discussions**:
- **Target User**: Progressive disclosure — simple by default, powerful when needed
- **Scope**: All 19 features in one comprehensive plan (Tiers 1-4)
- **Conflict Resolution**: Full 3-way merge editor (like Tower's Conflict Wizard)
- **Undo**: Simple "Undo Last Commit" via `git reset --soft HEAD~1` (not a broad undo system)
- **Test Strategy**: Tests after implementation using existing Pest infrastructure (53 tests exist)
- **UI Framework**: Livewire 4 + Flux UI Free + Alpine.js + Tailwind CSS v4 + Catppuccin Latte

**Research Findings**:
- **Competitive Analysis** of Fork, Tower, GitKraken, Sublime Merge, GitHub Desktop, Sourcetree, GitButler
- Tower's #1 selling point: "Undo ANYTHING" — user chose simpler undo-commit-only
- GitKraken's legendary git graph is the "wow factor" for visual git clients
- Sublime Merge's line-level staging and instant search are best-in-class
- Key differentiators across all pro clients: speed, visual clarity, safety (undo), line staging, conflict resolution

### Metis Review
**Identified Gaps** (addressed):
- Performance budgets needed per feature — added to guardrails
- Edge cases for undo (pushed commits, merge commits) — added constraints
- Scope creep risk on conflict resolution and git graph — locked down
- Missing acceptance criteria per feature — addressed in each TODO
- Assumption validation (git CLI, single-user, no LFS) — confirmed as defaults

---

## Work Objectives

### Core Objective
Transform gitty from a staging-focused git tool into a full professional macOS git client with 19 new features across history viewing, advanced git operations, diff enhancements, and UX polish.

### Concrete Deliverables
- 8+ new Livewire components (HistoryPanel, ConflictResolver, RebasePanel, BlameView, GitGraph, SearchPanel, ImageDiff, ShortcutHelp)
- 4+ new Git services (TagService, ResetService, RebaseService, BlameService, SearchService)
- Extended existing components (DiffViewer for line staging + side-by-side, CommitPanel for templates + history, StagingPanel for shortcuts)
- Dark mode theme (Catppuccin Mocha CSS variables)
- Tests for all new features (Pest feature tests)

### Definition of Done
- [ ] All 19 features implemented and accessible from UI
- [ ] All new features have Pest feature tests passing
- [ ] No existing tests broken (`php artisan test --compact` → all green)
- [ ] Dark mode toggle works (Latte ↔ Mocha)
- [ ] Command palette updated with new commands for all features

### Must Have
- Follow existing Livewire + Git Service + DTO architecture
- Use Catppuccin color palette (Latte for light, Mocha for dark)
- Use Flux UI Free components, Phosphor Icons
- Maintain all existing keyboard shortcuts
- Preserve existing event system (`status-updated`, `repo-switched`, etc.)

### Must NOT Have (Guardrails)
- **No new JS frameworks** — Alpine.js only, no React/Vue
- **No new state management** — Livewire server-side state only
- **No auto-merge** in conflict resolution — user must resolve manually
- **No custom themes** — only Catppuccin Latte (light) + Mocha (dark)
- **No plugin system** — hardcoded editor list, no extensibility API
- **No fuzzy search** — exact text + regex only
- **No signed commits** — GPG/SSH out of scope
- **No Git LFS** — out of scope
- **No submodule management** — out of scope
- **No shortcut customization** — hardcoded shortcuts only
- **No notification center** — transient macOS notifications only

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.

### Test Decision
- **Infrastructure exists**: YES (53 Pest tests, Feature + Browser tests)
- **Automated tests**: YES (Tests-after)
- **Framework**: Pest 4 via `php artisan test --compact`

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

**Verification Tool by Deliverable Type:**

| Type | Tool | How Agent Verifies |
|------|------|-------------------|
| **Livewire Components** | Pest (Livewire testing) | `Livewire::test(Component::class)->call()->assertSee()` |
| **Git Services** | Pest (Feature tests) | Create test repo, execute operations, assert state |
| **UI/Views** | Playwright (playwright skill) | Navigate, interact, assert DOM, screenshot |
| **CSS/Theme** | Playwright (playwright skill) | Toggle theme, screenshot, compare colors |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately — No Dependencies):
├── Task 1: Commit History View
├── Task 2: Undo Last Commit
├── Task 5: Line-level Staging
├── Task 14: Dark Mode
└── Task 19: Keyboard Shortcuts

Wave 2 (After Wave 1):
├── Task 3: Merge Conflict Resolution (depends: none, but complex — starts after Wave 1 frees capacity)
├── Task 4: Git Reset / Revert (depends: Task 1 for history context menu)
├── Task 6: Side-by-side Diff View
├── Task 13: Tag Management
└── Task 15: Commit Message Templates

Wave 3 (After Wave 2):
├── Task 7: Interactive Rebase (depends: Task 4 for reset operations)
├── Task 8: Cherry-pick (depends: Task 1 for history view context)
├── Task 9: File Blame View
├── Task 12: Image Diff
└── Task 16: Open in External Editor

Wave 4 (After Wave 3):
├── Task 10: Git Graph (depends: Task 1 for history data)
├── Task 11: Search (depends: Task 1 for history integration)
├── Task 17: Native macOS Notifications
└── Task 18: Quick Commit / Message History

Wave 5 (Final — Integration):
└── Task 20: Integration Testing & Polish
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 (History) | None | 4, 8, 10, 11 | 2, 5, 14, 19 |
| 2 (Undo) | None | None | 1, 5, 14, 19 |
| 3 (Conflicts) | None | 7 | 4, 6, 13, 15 |
| 4 (Reset/Revert) | 1 | 7 | 3, 6, 13, 15 |
| 5 (Line Staging) | None | None | 1, 2, 14, 19 |
| 6 (Side-by-side) | None | None | 3, 4, 13, 15 |
| 7 (Rebase) | 3, 4 | None | 8, 9, 12, 16 |
| 8 (Cherry-pick) | 1 | None | 7, 9, 12, 16 |
| 9 (Blame) | None | None | 7, 8, 12, 16 |
| 10 (Graph) | 1 | None | 11, 17, 18 |
| 11 (Search) | 1 | None | 10, 17, 18 |
| 12 (Image Diff) | None | None | 7, 8, 9, 16 |
| 13 (Tags) | None | None | 3, 4, 6, 15 |
| 14 (Dark Mode) | None | None | 1, 2, 5, 19 |
| 15 (Templates) | None | None | 3, 4, 6, 13 |
| 16 (Ext. Editor) | None | None | 7, 8, 9, 12 |
| 17 (Notifications) | None | None | 10, 11, 18 |
| 18 (Quick Commit) | None | None | 10, 11, 17 |
| 19 (Shortcuts) | None | None | 1, 2, 5, 14 |
| 20 (Integration) | All | None | None (final) |

---

## TODOs

### TIER 1 — TABLE STAKES

- [x] 1. Commit History / Log View

  **What to do**:
  - Create `app/Livewire/HistoryPanel.php` component
  - Create `resources/views/livewire/history-panel.blade.php` view
  - Add history panel to app layout as a toggleable panel (replaces or sits alongside diff viewer)
  - Use existing `GitService::log()` to fetch commits (returns `Collection<Commit>`)
  - Show list: short SHA (monospace), author, relative date, commit message, branch/tag refs
  - Implement infinite scroll pagination (load 100 commits initially, 50 more on scroll)
  - Add commit detail view (click commit → show full message, changed files, diff)
  - Add "Copy SHA" button on each commit row
  - Add toggle button in staging panel header or command palette to switch between diff view and history view
  - Register `keyboard-toggle-history` event, add to command palette
  - Write Pest feature tests after implementation

  **Must NOT do**:
  - Don't load entire history upfront (lazy pagination only)
  - Don't add author/date filtering (Task 11 Search handles this)
  - Don't add commit editing from history (separate features)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Full-stack feature — new service usage, Livewire component, Blade view, Alpine.js interactivity
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]
    - `livewire-development`: New Livewire component with reactive state, pagination, events
    - `tailwindcss-development`: Styling the commit list, detail view, status badges
    - `fluxui-development`: Buttons, tooltips, dropdowns for commit actions

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 5, 14, 19)
  - **Blocks**: Tasks 4 (Reset needs history context menu), 8 (Cherry-pick from history), 10 (Graph builds on history data), 11 (Search integrates with history)
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/StagingPanel.php` — Component structure with polling, Alpine.js interactivity, event dispatching
  - `app/Livewire/RepoSidebar.php` — Collapsible sections, context menus, stash management pattern
  - `resources/views/livewire/staging-panel.blade.php` — List rendering, hover actions, multi-select pattern

  **API/Type References**:
  - `app/Services/Git/GitService.php:log()` — Returns `Collection<Commit>`, accepts `$limit` and `$branch` params
  - `app/DTOs/Commit.php` — Has `sha`, `shortSha`, `message`, `author`, `email`, `date`, `refs` properties
  - `app/DTOs/Commit.php:fromLogLine()` — Parses `git log --oneline` format
  - `app/DTOs/Commit.php:fromDetailedOutput()` — Parses full commit metadata

  **Test References**:
  - `tests/Feature/Livewire/StagingPanelTest.php` — Livewire component test pattern with mock git repos
  - `tests/Feature/Services/GitServiceTest.php` — Service test pattern using GitTestHelper

  **Acceptance Criteria**:
  - [x] `HistoryPanel` Livewire component exists and renders
  - [x] Initial load shows up to 100 commits from `GitService::log()`
  - [x] Each commit row shows: short SHA, author, relative date, message (truncated)
  - [x] Clicking a commit dispatches `commit-selected` event with SHA
  - [x] Scrolling to bottom loads 50 more commits (pagination)
  - [x] Command palette has "Toggle History" command
  - [x] `php artisan test --compact --filter=HistoryPanel` → PASS

  **Agent-Executed QA Scenarios**:
  ```
  Scenario: History panel loads and shows commits
    Tool: Pest (Livewire testing)
    Steps:
      1. Create test repo with 5 commits using GitTestHelper
      2. Livewire::test(HistoryPanel::class, ['repoPath' => $testRepo])
      3. Assert component renders without errors
      4. Assert response contains commit SHA substrings
      5. Assert response contains commit messages
    Expected Result: Component renders with commit list

  Scenario: History panel shows empty state for new repo
    Tool: Pest (Livewire testing)
    Steps:
      1. Create test repo with 0 commits
      2. Livewire::test(HistoryPanel::class, ['repoPath' => $testRepo])
      3. Assert response contains empty state text
    Expected Result: Empty state shown when no commits
  ```

  **Commit**: YES
  - Message: `feat(history): add commit history panel with pagination`
  - Files: `app/Livewire/HistoryPanel.php`, `resources/views/livewire/history-panel.blade.php`, tests

---

- [x] 2. Undo Last Commit

  **What to do**:
  - Add `undoLastCommit(): void` method to `app/Services/Git/CommitService.php`
  - Implementation: `git reset --soft HEAD~1` (preserves staged changes)
  - Add `isLastCommitPushed(): bool` helper to check if HEAD is ahead of remote
  - Add "Undo Last Commit" to command palette in `CommandPalette.php` (with ⌘Z or ⌘⇧Z shortcut)
  - Add "Undo Last Commit" button in commit panel (visible after successful commit, auto-hides after 30s)
  - Show confirmation modal: "Undo last commit? Changes will return to staging area."
  - If commit is pushed: show warning "This commit has been pushed. Undoing requires force push."
  - Disable for merge commits (check parent count)
  - Dispatch `status-updated` after undo to refresh staging panel
  - Write Pest feature tests

  **Must NOT do**:
  - Don't allow undoing merge commits
  - Don't implement a broad undo system (only last commit)
  - Don't auto-force-push after undoing a pushed commit

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small, focused feature — one service method, one command palette entry, one modal
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Extend CommitPanel + CommandPalette components
    - `fluxui-development`: Confirmation modal with Flux UI

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 5, 14, 19)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/Git/CommitService.php:commit()` — Service method pattern with cache invalidation
  - `app/Livewire/CommandPalette.php:executeCommand()` — Command registration and execution
  - `resources/views/livewire/staging-panel.blade.php` — Discard confirmation modal pattern

  **API/Type References**:
  - `app/Services/Git/CommitService.php` — Add `undoLastCommit()` alongside existing `commit()`, `commitAmend()`
  - `app/Services/Git/GitService.php:aheadBehind()` — Check if commit is pushed (ahead > 0 means local-only)

  **Acceptance Criteria**:
  - [x] `CommitService::undoLastCommit()` runs `git reset --soft HEAD~1`
  - [x] Changes from undone commit appear in staging panel
  - [x] Command palette shows "Undo Last Commit" entry
  - [x] Confirmation modal appears before undo
  - [x] Warning shown when commit is already pushed
  - [x] Disabled for merge commits (shows error toast)
  - [ ] `php artisan test --compact --filter=UndoLastCommit` → PASS (test file missing — needs recreation)

  **Commit**: YES
  - Message: `feat(commit): add undo last commit with safety checks`
  - Files: `app/Services/Git/CommitService.php`, `app/Livewire/CommitPanel.php`, `app/Livewire/CommandPalette.php`, tests

---

- [ ] 3. Merge Conflict Resolution UI

  **What to do**:
  - Create `app/Livewire/ConflictResolver.php` component
  - Create `resources/views/livewire/conflict-resolver.blade.php` — 3-panel layout:
    - Left: "Ours" (current branch version)
    - Center: "Result" (merged output, editable)
    - Right: "Theirs" (incoming branch version)
  - Create `app/Services/Git/ConflictService.php`:
    - `getConflictedFiles(): Collection` — Parse `git status --porcelain` for unmerged entries (UU, AA, DD, etc.)
    - `getConflictVersions(string $file): array` — Extract ours/theirs/base using `git show :1:file` (base), `:2:file` (ours), `:3:file` (theirs)
    - `resolveConflict(string $file, string $resolvedContent): void` — Write resolved content and `git add` the file
    - `abortMerge(): void` — `git merge --abort`
  - Create `app/DTOs/ConflictFile.php` with `path`, `status`, `oursContent`, `theirsContent`, `baseContent`
  - Detect merge state on app load: if `.git/MERGE_HEAD` exists, show conflict resolver UI
  - Add "Accept Ours" / "Accept Theirs" / "Accept Both" quick buttons per conflict block
  - Add "Mark Resolved" button per file (stages the file)
  - Add "Abort Merge" button (with confirmation modal)
  - Add file list sidebar showing all conflicted files with resolution status
  - For binary conflicts: show "Choose Ours / Choose Theirs" only (no merge editor)
  - Write Pest feature tests

  **Must NOT do**:
  - Don't implement auto-merge strategies
  - Don't handle binary conflicts in the 3-way editor (simple choose only)
  - Don't add custom conflict marker parsing (use git's stage numbers)
  - Don't implement conflict prevention/detection

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Complex feature — new service, new DTO, new Livewire component, 3-panel layout, Alpine.js text editing
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]
    - `livewire-development`: New component with reactive conflict state, file list, resolution flow
    - `tailwindcss-development`: 3-panel layout, conflict highlighting (red/green/blue), responsive design
    - `fluxui-development`: Buttons, modals, tooltips for resolve/abort actions

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 6, 13, 15)
  - **Blocks**: Task 7 (Rebase needs conflict UI for mid-rebase conflicts)
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/DiffViewer.php` — Multi-panel diff display pattern with hunk rendering
  - `app/Services/Git/DiffService.php:stageHunk()` — `git apply --cached` pattern for partial staging
  - `app/DTOs/MergeResult.php` — Existing merge result with `hasConflicts`, `conflictFiles`

  **API/Type References**:
  - `app/DTOs/MergeResult.php` — `success: bool`, `hasConflicts: bool`, `conflictFiles: array`
  - `app/Services/Git/BranchService.php:mergeBranch()` — Returns `MergeResult`, detects conflicts
  - `app/Services/Git/GitService.php:status()` — Can detect unmerged files via porcelain v2

  **Acceptance Criteria**:
  - [ ] `ConflictService::getConflictedFiles()` returns unmerged files from `git status`
  - [ ] `ConflictService::getConflictVersions()` extracts ours/theirs/base content
  - [ ] 3-panel conflict view renders with ours (left), result (center), theirs (right)
  - [ ] "Accept Ours" / "Accept Theirs" buttons replace result content
  - [ ] "Mark Resolved" stages the file and updates conflict list
  - [ ] "Abort Merge" runs `git merge --abort` after confirmation
  - [ ] Conflict UI auto-appears when `.git/MERGE_HEAD` exists
  - [ ] Binary conflicts show "Choose Ours / Choose Theirs" only
  - [ ] `php artisan test --compact --filter=ConflictResolver` → PASS

  **Commit**: YES
  - Message: `feat(conflicts): add 3-way merge conflict resolution editor`
  - Files: `app/Services/Git/ConflictService.php`, `app/DTOs/ConflictFile.php`, `app/Livewire/ConflictResolver.php`, views, tests

---

- [ ] 4. Git Reset / Revert

  **What to do**:
  - Create `app/Services/Git/ResetService.php`:
    - `resetSoft(string $commitSha): void` — `git reset --soft <sha>` (keeps changes staged)
    - `resetMixed(string $commitSha): void` — `git reset <sha>` (unstages changes)
    - `resetHard(string $commitSha): void` — `git reset --hard <sha>` (discards all changes)
    - `revertCommit(string $commitSha): void` — `git revert <sha> --no-edit` (creates new commit)
  - Add context menu to commit rows in HistoryPanel (Task 1):
    - "Reset to this commit" → opens modal with soft/mixed/hard options
    - "Revert this commit" → confirmation modal
  - Add confirmation modals explaining each reset mode:
    - Soft: "Move HEAD here. All changes after this commit will be staged."
    - Mixed: "Move HEAD here. All changes after this commit will be unstaged."
    - Hard: "Move HEAD here. All changes after this commit will be PERMANENTLY LOST."
  - Hard reset: require typing "DISCARD" to confirm (extra safety)
  - If resetting pushed commits: warn about force push requirement
  - Dispatch `status-updated` and `refresh-staging` after operations
  - Write Pest feature tests

  **Must NOT do**:
  - Don't allow reset of commits without confirmation
  - Don't allow hard reset without extra confirmation (typing "DISCARD")
  - Don't implement `git reflog` UI (just the reset/revert operations)
  - Don't support range revert (single commit only)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Backend service + modals — straightforward git commands with safety UI
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Extend HistoryPanel with context menu actions, modals
    - `fluxui-development`: Confirmation modals with mode selection

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 3, 6, 13, 15)
  - **Blocks**: Task 7 (Rebase abort uses reset)
  - **Blocked By**: Task 1 (needs HistoryPanel for context menu integration)

  **References**:

  **Pattern References**:
  - `app/Services/Git/CommitService.php` — Git command execution pattern with error handling
  - `resources/views/livewire/staging-panel.blade.php` — Discard confirmation modal (reuse pattern for hard reset)
  - `resources/views/livewire/sync-panel.blade.php` — Force push confirmation modal pattern

  **API/Type References**:
  - `app/Services/Git/GitCacheService.php:invalidateGroup()` — Invalidate `status`, `history`, `branches` after reset

  **Acceptance Criteria**:
  - [ ] `ResetService::resetSoft()` moves HEAD, keeps changes staged
  - [ ] `ResetService::resetMixed()` moves HEAD, unstages changes
  - [ ] `ResetService::resetHard()` moves HEAD, discards all changes
  - [ ] `ResetService::revertCommit()` creates a new revert commit
  - [ ] Confirmation modal shows mode explanation
  - [ ] Hard reset requires typing "DISCARD"
  - [ ] Warning shown when resetting pushed commits
  - [ ] `php artisan test --compact --filter=ResetService` → PASS

  **Commit**: YES
  - Message: `feat(history): add git reset and revert operations with safety modals`
  - Files: `app/Services/Git/ResetService.php`, extended HistoryPanel, tests

---

### TIER 2 — POWER USER ESSENTIALS

- [x] 5. Line-level Staging

  **What to do**:
  - Extend `app/Livewire/DiffViewer.php` to track selected lines within hunks
  - Add checkboxes (or clickable gutter) on each diff line for line selection
  - Extend `app/Services/Git/DiffService.php`:
    - `stageLines(DiffFile $file, Hunk $hunk, array $lineIndices): void` — Generate patch with only selected lines, apply via `git apply --cached`
    - `unstageLines(DiffFile $file, Hunk $hunk, array $lineIndices): void` — Reverse patch for selected lines
  - Update `resources/views/livewire/diff-viewer.blade.php`:
    - Add clickable line gutter (click to toggle line selection)
    - Shift+click for range selection
    - "Stage Selected Lines" button appears when lines are selected
    - Visual indicator (checkbox or highlight) on selected lines
  - Use Alpine.js for line selection state (client-side for responsiveness)
  - Generate proper unified diff patch with correct line numbers for partial hunk staging
  - Write Pest feature tests

  **Must NOT do**:
  - Don't allow staging partial lines (only full lines)
  - Don't allow editing hunk content
  - Don't create custom patch formats (use standard unified diff)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Complex diff manipulation — requires understanding unified diff format, patch generation, Alpine.js interactivity
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Extend DiffViewer with line selection state, new stage methods
    - `tailwindcss-development`: Line selection UI (checkboxes, highlights, hover states)

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2, 14, 19)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/Git/DiffService.php:stageHunk()` — Existing hunk staging via `git apply --cached`
  - `app/Services/Git/DiffService.php:generatePatch()` — Patch header construction pattern
  - `resources/views/livewire/diff-viewer.blade.php` — Hunk-level stage/unstage button pattern

  **API/Type References**:
  - `app/DTOs/Hunk.php` — `oldStart`, `oldCount`, `newStart`, `newCount`, `header`, `lines`
  - `app/DTOs/HunkLine.php` — `type` (addition/deletion/context), `content`, `oldLineNumber`, `newLineNumber`

  **Acceptance Criteria**:
  - [x] Diff viewer shows clickable gutter on each addition/deletion line
  - [x] Clicking a line toggles its selection (visual highlight)
  - [x] Shift+click selects range of lines
  - [x] "Stage Selected Lines" button appears when lines are selected
  - [x] `DiffService::stageLines()` generates valid patch for selected lines only
  - [x] Selected lines move from unstaged to staged after staging
  - [x] `php artisan test --compact --filter=LineStage` → PASS (8 tests, 25 assertions)

  **Commit**: YES
  - Message: `feat(diff): add line-level staging with clickable gutter`
  - Files: `app/Services/Git/DiffService.php`, `app/Livewire/DiffViewer.php`, view, tests

---

- [ ] 6. Side-by-side Diff View

  **What to do**:
  - Add `diffViewMode` property to `DiffViewer.php` — `'unified'` (default) or `'split'`
  - Add toggle button in diff viewer header (icon toggle: unified ↔ split)
  - Create split-view layout in `diff-viewer.blade.php`:
    - Left pane: old file (deletions highlighted, additions hidden)
    - Right pane: new file (additions highlighted, deletions hidden)
    - Synchronized scrolling via Alpine.js
    - Line numbers on both sides
  - Persist view mode preference in localStorage
  - Context lines shown on both sides, aligned by line number
  - Hunk stage/unstage buttons work in both modes
  - Write Pest feature tests

  **Must NOT do**:
  - Don't add character-level diff highlighting (too complex for v1)
  - Don't add word-wrap toggle (use existing `whitespace-pre-wrap`)
  - Don't break unified view — it must remain the default

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Complex visual layout — two synchronized scroll panes, aligned line numbers, responsive design
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Extend DiffViewer component with view mode toggle
    - `tailwindcss-development`: Split-pane layout, line alignment, responsive behavior

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 3, 4, 13, 15)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/views/livewire/diff-viewer.blade.php` — Current unified diff rendering with hunks/lines
  - `resources/css/app.css` — `.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context` styles
  - `resources/views/livewire/app-layout.blade.php:73-112` — Alpine.js panel resize with localStorage persistence

  **Acceptance Criteria**:
  - [ ] Toggle button switches between unified and split view
  - [ ] Split view shows old file (left) and new file (right) side by side
  - [ ] Scrolling is synchronized between panes
  - [ ] Line numbers align correctly across panes
  - [ ] Hunk staging works in both view modes
  - [ ] View mode persists across sessions (localStorage)
  - [ ] `php artisan test --compact --filter=SideBySideDiff` → PASS

  **Commit**: YES
  - Message: `feat(diff): add side-by-side diff view with synchronized scrolling`
  - Files: `app/Livewire/DiffViewer.php`, view, CSS, tests

---

- [ ] 7. Interactive Rebase

  **What to do**:
  - Create `app/Services/Git/RebaseService.php`:
    - `getRebaseCommits(string $onto, int $count): Collection` — List commits for rebase preview
    - `startRebase(string $onto, array $plan): void` — Execute `git rebase -i` with auto-generated todo
    - `continueRebase(): void` — `git rebase --continue`
    - `abortRebase(): void` — `git rebase --abort`
    - `isRebasing(): bool` — Check if `.git/rebase-merge/` or `.git/rebase-apply/` exists
  - Create `app/Livewire/RebasePanel.php` component:
    - Shows list of commits to rebase
    - Each commit has action dropdown: pick, squash, drop
    - Drag-and-drop reordering via Alpine.js
    - "Start Rebase" button
    - During rebase: show progress, conflict state, continue/abort buttons
  - Integrate with ConflictResolver (Task 3) for mid-rebase conflicts
  - Add "Interactive Rebase" to commit history context menu
  - Warn about force push requirement after rebase
  - Write Pest feature tests

  **Must NOT do**:
  - Don't support `edit` action (too complex — opens editor)
  - Don't support `reword` inline (use squash instead)
  - Don't support commit splitting
  - Don't allow rebase of pushed commits without force-push warning

  **Recommended Agent Profile**:
  - **Category**: `ultrabrain`
    - Reason: Complex git interaction — must generate rebase todo, handle mid-rebase state, integrate with conflict resolution
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]
    - `livewire-development`: New component with complex state management (rebase progress, conflict detection)
    - `tailwindcss-development`: Drag-and-drop list UI, action dropdowns, progress indicators
    - `fluxui-development`: Dropdown menus, modals, buttons for rebase actions

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 8, 9, 12, 16)
  - **Blocks**: None
  - **Blocked By**: Task 3 (Conflict UI for mid-rebase conflicts), Task 4 (Reset for abort)

  **References**:

  **Pattern References**:
  - `app/Services/Git/BranchService.php:mergeBranch()` — Returns MergeResult, conflict detection pattern
  - `app/Livewire/BranchManager.php` — Complex branch operations with modals, error handling
  - `resources/views/livewire/staging-panel.blade.php` — Multi-select, drag interaction patterns

  **Acceptance Criteria**:
  - [ ] `RebaseService::getRebaseCommits()` returns commits for rebase preview
  - [ ] Rebase panel shows commits with pick/squash/drop actions
  - [ ] Drag-and-drop reorders commits
  - [ ] "Start Rebase" executes `git rebase -i` with generated todo
  - [ ] Mid-rebase conflicts show conflict resolution UI
  - [ ] "Abort Rebase" runs `git rebase --abort`
  - [ ] Force push warning shown after successful rebase
  - [ ] `php artisan test --compact --filter=RebaseService` → PASS

  **Commit**: YES
  - Message: `feat(rebase): add interactive rebase with drag-and-drop reordering`
  - Files: `app/Services/Git/RebaseService.php`, `app/Livewire/RebasePanel.php`, view, tests

---

- [ ] 8. Cherry-pick

  **What to do**:
  - Add to `app/Services/Git/CommitService.php`:
    - `cherryPick(string $sha): MergeResult` — `git cherry-pick <sha>`, returns MergeResult for conflict handling
    - `cherryPickAbort(): void` — `git cherry-pick --abort`
    - `cherryPickContinue(): void` — `git cherry-pick --continue`
  - Add "Cherry-pick this commit" to HistoryPanel commit context menu
  - Show confirmation: "Cherry-pick commit abc1234 onto current branch?"
  - Handle conflicts: redirect to ConflictResolver (Task 3) if cherry-pick conflicts
  - Show success toast with new commit SHA
  - Dispatch `status-updated` after cherry-pick
  - Write Pest feature tests

  **Must NOT do**:
  - Don't support cherry-pick ranges (single commit only)
  - Don't support cherry-pick with `--no-commit` (always create commit)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small feature — one service method, one context menu entry, one confirmation modal
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Extend HistoryPanel with cherry-pick action

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 7, 9, 12, 16)
  - **Blocks**: None
  - **Blocked By**: Task 1 (needs HistoryPanel for context menu)

  **References**:

  **Pattern References**:
  - `app/Services/Git/BranchService.php:mergeBranch()` — Returns MergeResult, conflict detection
  - `app/DTOs/MergeResult.php` — Reuse for cherry-pick result

  **Acceptance Criteria**:
  - [ ] `CommitService::cherryPick()` runs `git cherry-pick <sha>`
  - [ ] Returns `MergeResult` with success/conflict info
  - [ ] Context menu shows "Cherry-pick" on commit rows
  - [ ] Confirmation modal before cherry-pick
  - [ ] Conflicts trigger ConflictResolver UI
  - [ ] `php artisan test --compact --filter=CherryPick` → PASS

  **Commit**: YES
  - Message: `feat(commit): add cherry-pick with conflict handling`
  - Files: `app/Services/Git/CommitService.php`, HistoryPanel extension, tests

---

- [ ] 9. File Blame / Annotation View

  **What to do**:
  - Create `app/Services/Git/BlameService.php`:
    - `blame(string $file): Collection` — Parse `git blame --porcelain <file>`, return blame annotations per line
  - Create `app/DTOs/BlameLine.php`:
    - `commitSha: string`, `author: string`, `date: string`, `lineNumber: int`, `content: string`
  - Create `app/Livewire/BlameView.php` component:
    - Shows file content with blame gutter (SHA, author, date per line group)
    - Color-code lines by commit (alternating subtle backgrounds per commit block)
    - Click SHA to jump to commit in history panel
    - Syntax highlighting (reuse highlight.js setup)
  - Add "Blame" button to diff viewer header (next to file path)
  - Add "View Blame" to staging panel file context menu
  - Write Pest feature tests

  **Must NOT do**:
  - Don't show blame for binary files
  - Don't implement "blame previous revision" (git blame -C)
  - Don't add inline commit details (just SHA + author + date in gutter)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: New service, DTO, component — parsing `git blame --porcelain` format, rendering annotated view
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: New component with blame data loading, commit selection events
    - `tailwindcss-development`: Blame gutter layout, alternating commit colors, line alignment

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 7, 8, 12, 16)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/Git/DiffService.php:parseDiff()` — Complex git output parsing pattern
  - `app/Livewire/DiffViewer.php` — File viewer component with syntax highlighting integration
  - `resources/js/app.js` — highlight.js setup for syntax highlighting

  **Acceptance Criteria**:
  - [ ] `BlameService::blame()` parses `git blame --porcelain` output
  - [ ] Blame view shows SHA, author, date gutter per line group
  - [ ] Lines color-coded by commit (alternating backgrounds)
  - [ ] Clicking SHA dispatches event to show commit in history
  - [ ] "Blame" button in diff viewer header opens blame view
  - [ ] `php artisan test --compact --filter=BlameService` → PASS

  **Commit**: YES
  - Message: `feat(blame): add file blame view with commit annotations`
  - Files: `app/Services/Git/BlameService.php`, `app/DTOs/BlameLine.php`, `app/Livewire/BlameView.php`, view, tests

---

### TIER 3 — PROFESSIONAL POLISH

- [ ] 10. Git Graph (Visual Branch Topology)

  **What to do**:
  - Create `app/Services/Git/GraphService.php`:
    - `getGraphData(int $limit = 200): array` — Parse `git log --all --graph --oneline --decorate` or use `git log --format` with parent info to build graph nodes
    - Each node: `sha`, `parents[]`, `children[]`, `branch`, `refs[]`, `message`, `author`, `date`
    - Calculate lane assignments (which column each branch occupies)
  - Integrate graph visualization into HistoryPanel (Task 1):
    - Left column: SVG-rendered graph lines (nodes + edges)
    - Right column: commit details (existing commit row layout)
    - Branch color coding (different color per branch lane)
    - Merge commits show converging lines
  - Use SVG for graph rendering (not canvas — better for Livewire updates)
  - Lazy load: render visible viewport + buffer, load more on scroll
  - Click node to show commit details
  - Write Pest feature tests

  **Must NOT do**:
  - Don't show stashes or reflog entries in graph
  - Don't implement real-time graph updates (refresh on user action)
  - Don't implement graph filtering (show all branches)
  - Don't use canvas rendering (SVG only for Livewire compatibility)

  **Recommended Agent Profile**:
  - **Category**: `ultrabrain`
    - Reason: Complex algorithm — lane assignment, SVG generation, topological sorting, performance with large repos
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Extend HistoryPanel with graph data, SVG rendering
    - `tailwindcss-development`: Graph styling, branch colors, node hover states

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 11, 17, 18)
  - **Blocks**: None
  - **Blocked By**: Task 1 (HistoryPanel must exist)

  **References**:

  **Pattern References**:
  - `app/Services/Git/GitService.php:log()` — Commit loading with caching
  - `app/DTOs/Commit.php` — Commit data model (extend with parent SHAs)

  **Acceptance Criteria**:
  - [ ] `GraphService::getGraphData()` returns nodes with parent relationships
  - [ ] SVG graph renders branch lines and merge points
  - [ ] Each branch gets a distinct color
  - [ ] Clicking a node selects the commit
  - [ ] Graph handles 200+ commits without lag
  - [ ] `php artisan test --compact --filter=GraphService` → PASS

  **Commit**: YES
  - Message: `feat(graph): add visual git graph with branch topology`
  - Files: `app/Services/Git/GraphService.php`, HistoryPanel extension, view, tests

---

- [ ] 11. Search

  **What to do**:
  - Create `app/Services/Git/SearchService.php`:
    - `searchCommits(string $query): Collection` — `git log --grep="<query>" --oneline -50`
    - `searchContent(string $query): Collection` — `git log -S "<query>" --oneline -50` (pickaxe search)
    - `searchFiles(string $query): Collection` — `git ls-files "*<query>*"` for filename search
  - Add search UI to command palette or as separate search bar:
    - Search input with scope selector (Commits / Content / Files)
    - Results list with keyboard navigation
    - Click result to navigate (commit → history, file → diff viewer)
  - Add ⌘F shortcut to open search
  - Write Pest feature tests

  **Must NOT do**:
  - Don't implement fuzzy search (exact + regex only)
  - Don't build a search index (use git commands directly)
  - Don't implement boolean operators or query DSL

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Service wraps git search commands, UI extends existing patterns
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Search component with debounced input, result loading
    - `fluxui-development`: Search input, result list, scope selector

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 10, 17, 18)
  - **Blocks**: None
  - **Blocked By**: Task 1 (for navigating to commits from search results)

  **References**:

  **Pattern References**:
  - `app/Livewire/CommandPalette.php` — Search input with debounce, keyboard navigation, filtered results
  - `app/Livewire/BranchManager.php` — Search/filter pattern with `branchQuery`

  **Acceptance Criteria**:
  - [ ] `SearchService::searchCommits()` returns commits matching grep query
  - [ ] `SearchService::searchContent()` returns commits changing matching content
  - [ ] `SearchService::searchFiles()` returns matching filenames
  - [ ] Search UI shows results with keyboard navigation
  - [ ] ⌘F opens search
  - [ ] `php artisan test --compact --filter=SearchService` → PASS

  **Commit**: YES
  - Message: `feat(search): add commit, content, and file search`
  - Files: `app/Services/Git/SearchService.php`, search UI component, tests

---

- [ ] 12. Image Diff

  **What to do**:
  - Extend `app/Livewire/DiffViewer.php` to detect image files by extension
  - For image files (PNG, JPG, GIF, SVG, WebP):
    - Show side-by-side image comparison instead of text diff
    - Old version: `git show HEAD:<file>` → base64 encode → display
    - New version: read from working tree → display
    - Show file size change (old size → new size, delta)
  - Add subtle overlay/slider mode: drag slider to reveal before/after (like GitHub's image diff)
  - For new images: show only new version with "NEW" badge
  - For deleted images: show only old version with "DELETED" badge
  - Write Pest feature tests

  **Must NOT do**:
  - Don't implement pixel-level diff overlays
  - Don't support video or audio files
  - Don't add zoom controls (browser native zoom is sufficient)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Visual feature — image display, side-by-side comparison, slider overlay
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Extend DiffViewer with image detection and rendering
    - `tailwindcss-development`: Image comparison layout, slider overlay, badges

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 7, 8, 9, 16)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/DiffViewer.php:$isBinary` — Existing binary file detection
  - `resources/views/livewire/diff-viewer.blade.php` — Binary file empty state (replace with image viewer)

  **Acceptance Criteria**:
  - [ ] Image files detected by extension (png, jpg, gif, svg, webp)
  - [ ] Side-by-side image comparison shown for modified images
  - [ ] File size change displayed (old → new, delta)
  - [ ] New images show single image with "NEW" badge
  - [ ] Deleted images show old version with "DELETED" badge
  - [ ] `php artisan test --compact --filter=ImageDiff` → PASS

  **Commit**: YES
  - Message: `feat(diff): add visual image diff comparison`
  - Files: `app/Livewire/DiffViewer.php`, view, tests

---

- [ ] 13. Tag Management UI

  **What to do**:
  - Create `app/Services/Git/TagService.php`:
    - `tags(): Collection` — `git tag -l --format='%(refname:short) %(objectname:short) %(creatordate:iso-strict) %(contents:subject)'` (cached 60s)
    - `createTag(string $name, ?string $message = null, ?string $commit = null): void` — Lightweight or annotated
    - `deleteTag(string $name): void` — `git tag -d <name>`
    - `pushTag(string $name, string $remote = 'origin'): void` — `git push <remote> <name>`
    - `pushAllTags(string $remote = 'origin'): void` — `git push <remote> --tags`
  - Extend `app/Livewire/RepoSidebar.php`:
    - Replace static tag list with interactive tag management
    - Right-click context menu on tags: "Push to Remote", "Delete"
    - "Create Tag" button in tags section header
    - Create tag modal: name input, optional message (annotated), optional commit SHA
  - Add tag commands to command palette
  - Write Pest feature tests

  **Must NOT do**:
  - Don't support signed tags (GPG out of scope)
  - Don't support tag verification
  - Don't add tag filtering/search (keep it simple)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Standard CRUD service + sidebar extension — follows existing patterns closely
  - **Skills**: [`livewire-development`, `fluxui-development`]
    - `livewire-development`: Extend RepoSidebar with tag actions, create modal
    - `fluxui-development`: Context menu, modals, input fields

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 3, 4, 6, 15)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/RepoSidebar.php` — Stash management pattern (context menu, apply/pop/drop actions)
  - `app/Services/Git/StashService.php` — CRUD service pattern with cache invalidation
  - `resources/views/livewire/repo-sidebar.blade.php` — Collapsible section with context menu

  **Acceptance Criteria**:
  - [ ] `TagService::tags()` returns tag list with metadata
  - [ ] `TagService::createTag()` creates lightweight or annotated tag
  - [ ] `TagService::deleteTag()` deletes local tag
  - [ ] `TagService::pushTag()` pushes tag to remote
  - [ ] Create tag modal with name + optional message
  - [ ] Context menu on tags: Push, Delete
  - [ ] `php artisan test --compact --filter=TagService` → PASS

  **Commit**: YES
  - Message: `feat(tags): add tag management with create, delete, push`
  - Files: `app/Services/Git/TagService.php`, RepoSidebar extension, view, tests

---

- [x] 14. Dark Mode (Catppuccin Mocha)

  **What to do**:
  - Add Catppuccin Mocha color values to `resources/css/app.css`:
    - `:root.dark` or `.dark` selector with Mocha palette overrides
    - Surface: Base `#1e1e2e`, Mantle `#181825`, Crust `#11111b`
    - Text: `#cdd6f4`, Subtext: `#a6adc8`, Overlay: `#7f849c`
    - Same semantic colors (green, red, yellow, etc.) but Mocha variants
  - Update `@theme {}` block with dark mode CSS variables
  - Add theme toggle to header (sun/moon icon button)
  - Add theme toggle to settings modal (replace disabled select)
  - Persist preference in SettingsService (`theme: 'light' | 'dark'`)
  - Follow macOS system preference by default (`prefers-color-scheme`)
  - Add `.dark` class to root element, use Tailwind `dark:` variant
  - Update all hardcoded hex values in Blade templates to use CSS variables or dark: variants
  - Update diff viewer styles for dark mode (green/red tints on dark backgrounds)
  - Update highlight.js theme for dark mode (Catppuccin Mocha syntax colors)
  - Write Pest feature tests

  **Must NOT do**:
  - Don't add custom theme creation
  - Don't add per-panel theming
  - Don't break light mode (Latte must remain identical)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Comprehensive CSS overhaul — every component needs dark mode variants, color system refactor
  - **Skills**: [`tailwindcss-development`, `livewire-development`]
    - `tailwindcss-development`: Dark mode CSS variables, Tailwind dark: variants, color system
    - `livewire-development`: Theme toggle component, settings integration

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2, 5, 19)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/css/app.css` — Current `:root {}` with Catppuccin Latte values
  - `app/Livewire/SettingsModal.php` — Has `theme` property (currently unused effectively)
  - `resources/css/app.css:232-273` — highlight.js Catppuccin Latte theme (needs Mocha counterpart)

  **External References**:
  - Catppuccin Mocha palette: https://catppuccin.com/palette/ — All hex values for dark variant
  - Tailwind dark mode docs: https://tailwindcss.com/docs/dark-mode

  **Acceptance Criteria**:
  - [x] `.dark` class on root element switches entire UI to Catppuccin Mocha
  - [x] Theme toggle in header switches between light/dark
  - [x] Theme preference persists via SettingsService
  - [x] System preference respected by default (`prefers-color-scheme`)
  - [x] All components render correctly in dark mode (14 blade files refactored)
  - [x] Diff viewer colors correct in dark mode (green/red tints on dark bg)
  - [x] Syntax highlighting uses Mocha colors in dark mode
  - [x] `php artisan test --compact --filter=ThemeToggle` → PASS (6 tests)

  **Commit**: YES
  - Message: `feat(theme): add dark mode with Catppuccin Mocha palette`
  - Files: `resources/css/app.css`, all blade views (dark: variants), SettingsModal, tests

---

### TIER 4 — DELIGHT & DIFFERENTIATION

- [ ] 15. Commit Message Templates / Conventional Commits

  **What to do**:
  - Extend `app/Livewire/CommitPanel.php`:
    - Add template dropdown button next to commit textarea
    - Load templates: first check repo `.gitmessage`, then global `~/.gitmessage`, then built-in defaults
    - Built-in Conventional Commits templates: `feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`
    - Click template to pre-fill commit message
    - Show scope hint based on changed files (e.g., files in `app/Services/` → suggest `(backend)`)
  - Add `commit_template_path` setting to SettingsService
  - Write Pest feature tests

  **Must NOT do**:
  - Don't implement template library/sharing
  - Don't enforce template format (suggestions only)
  - Don't add template variables (no `{branch}`, `{date}` substitution)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small UI extension — dropdown menu, template loading, pre-fill logic
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 3, 4, 6, 13)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/CommitPanel.php` — Commit message handling, amend toggle
  - `resources/views/livewire/commit-panel.blade.php` — Split button group pattern (reuse for template dropdown)

  **Acceptance Criteria**:
  - [ ] Template dropdown shows Conventional Commits types
  - [ ] Clicking template pre-fills commit message
  - [ ] Custom templates loaded from `.gitmessage` if present
  - [ ] `php artisan test --compact --filter=CommitTemplate` → PASS

  **Commit**: YES
  - Message: `feat(commit): add commit message templates with conventional commits`
  - Files: `app/Livewire/CommitPanel.php`, view, tests

---

- [ ] 16. Open in External Editor

  **What to do**:
  - Extend `app/Services/SettingsService.php` with editor detection:
    - Auto-detect installed editors: `which code`, `which cursor`, `which subl`
    - Map to commands: VS Code → `code <file>:<line>`, Cursor → `cursor <file>:<line>`, Sublime → `subl <file>:<line>`
  - Add "Open in Editor" button to diff viewer header (next to file path)
  - Add "Open in Editor" to staging panel file context menu
  - Add "Open Repo in Editor" to command palette
  - Open file at specific line when triggered from diff viewer (pass line number)
  - Use `Process::run()` to launch editor command
  - Write Pest feature tests

  **Must NOT do**:
  - Don't implement a plugin system for custom editors
  - Don't add more than 5 hardcoded editors (VS Code, Cursor, Sublime Text, PhpStorm, Zed)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small feature — shell command execution, a few UI buttons
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 7, 8, 9, 12)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Services/SettingsService.php` — Settings persistence pattern
  - `app/Livewire/DiffViewer.php` — File-specific actions in header

  **Acceptance Criteria**:
  - [ ] Editor auto-detection finds installed editors
  - [ ] "Open in Editor" button in diff viewer header
  - [ ] File opens at correct line in editor
  - [ ] "Open in Editor" in staging panel context menu
  - [ ] `php artisan test --compact --filter=ExternalEditor` → PASS

  **Commit**: YES
  - Message: `feat(editor): add open in external editor with line number support`
  - Files: SettingsService, DiffViewer, StagingPanel, CommandPalette, tests

---

- [ ] 17. Native macOS Notifications

  **What to do**:
  - Use NativePHP notification API to send macOS notifications
  - Trigger notifications for:
    - Push success: "Pushed N commits to origin/main"
    - Pull with new commits: "Pulled N new commits from origin/main"
    - Fetch found updates: "N new commits available on origin/main"
    - Auto-fetch errors: "Auto-fetch failed: <error>"
  - Add `notifications_enabled` setting to SettingsService (default: true)
  - Clicking notification should focus gitty window
  - Write Pest feature tests

  **Must NOT do**:
  - Don't implement a notification center (transient only)
  - Don't add notification history/log
  - Don't add notification sounds (use system default)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small integration — NativePHP notification API calls in existing event handlers
  - **Skills**: [`livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 10, 11, 18)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/SyncPanel.php` — Push/pull/fetch operations (add notification calls)
  - `app/Livewire/AutoFetchIndicator.php` — Auto-fetch success/failure handlers

  **External References**:
  - NativePHP Notifications: https://nativephp.com/docs/the-basics/notifications

  **Acceptance Criteria**:
  - [ ] Push success triggers macOS notification
  - [ ] Pull with new commits triggers notification
  - [ ] Auto-fetch errors trigger notification
  - [ ] Notifications can be disabled in settings
  - [ ] `php artisan test --compact --filter=Notification` → PASS

  **Commit**: YES
  - Message: `feat(notifications): add native macOS notifications for git operations`
  - Files: SyncPanel, AutoFetchIndicator, SettingsService, tests

---

- [ ] 18. Quick Commit / Message History

  **What to do**:
  - Extend `app/Livewire/CommitPanel.php`:
    - Store last 20 commit messages in database (new `CommitMessage` model or extend Settings)
    - Add ↑/↓ arrow key navigation in commit textarea to cycle through history
    - Show subtle hint: "↑↓ to cycle message history"
    - Add dropdown button showing recent messages (click to reuse)
  - Clear current message on successful commit, store it in history
  - Keyboard shortcut: ⌘L to focus commit message textarea
  - Write Pest feature tests

  **Must NOT do**:
  - Don't sync messages across repos (per-repo history only)
  - Don't implement message search
  - Don't persist more than 20 messages

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small extension — store/retrieve messages, keyboard navigation, dropdown
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 10, 11, 17)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/CommitPanel.php` — Current commit message handling
  - `app/Livewire/CommandPalette.php` — Keyboard navigation pattern (↑↓ arrows)
  - `app/Services/SettingsService.php` — Key/value persistence pattern

  **Acceptance Criteria**:
  - [ ] Last 20 commit messages stored per repo
  - [ ] ↑/↓ cycles through message history in textarea
  - [ ] Dropdown shows recent messages
  - [ ] Clicking a recent message fills textarea
  - [ ] ⌘L focuses commit message textarea
  - [ ] `php artisan test --compact --filter=CommitHistory` → PASS

  **Commit**: YES
  - Message: `feat(commit): add commit message history with arrow key cycling`
  - Files: CommitPanel, view, migration/model if needed, tests

---

- [x] 19. Keyboard Shortcuts Expansion

  **What to do**:
  - Add new shortcuts for all new features:
    - `⌘H` — Toggle history panel
    - `⌘F` — Open search
    - `⌘Z` — Undo last commit
    - `⌘G` — Toggle git graph
    - `⌘⇧B` — Open blame for current file
    - `⌘L` — Focus commit message
    - `⌘?` or `⌘/` — Show keyboard shortcuts help modal
  - Create `app/Livewire/ShortcutHelp.php` component:
    - Modal showing all available shortcuts organized by category
    - Searchable shortcut list
  - Audit all existing shortcuts for macOS system conflicts
  - Register all new shortcuts in `app-layout.blade.php` Alpine.js @keydown handlers
  - Add all new shortcuts to command palette entries
  - Update `resources/views/livewire/command-palette.blade.php` to show shortcuts
  - Write Pest feature tests

  **Must NOT do**:
  - Don't allow shortcut customization
  - Don't add vim-style modal keybindings
  - Don't conflict with macOS system shortcuts (⌘C, ⌘V, ⌘Q, etc.)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Adding keyboard event handlers and a simple help modal
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2, 5, 14)
  - **Blocks**: None
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php:3-12` — Existing @keydown handlers
  - `app/Livewire/CommandPalette.php` — Command registry with shortcut display
  - `resources/views/livewire/command-palette.blade.php` — Shortcut kbd rendering

  **Acceptance Criteria**:
  - [x] All new shortcuts registered in app-layout.blade.php
  - [x] `⌘/` opens keyboard shortcuts help modal
  - [x] Help modal shows all shortcuts organized by category
  - [x] No macOS system shortcut conflicts
  - [x] All new commands added to command palette with shortcut labels
  - [x] `php artisan test --compact --filter=ShortcutHelp` → PASS (4 tests, 9 assertions)

  **Commit**: YES
  - Message: `feat(shortcuts): add keyboard shortcuts for all new features and help modal`
  - Files: AppLayout, CommandPalette, ShortcutHelp component, view, tests

---

### INTEGRATION

- [ ] 20. Integration Testing & Polish

  **What to do**:
  - Run full test suite: `php artisan test --compact` → ALL PASS
  - Verify no existing tests broken by new features
  - Run `vendor/bin/pint --dirty --format agent` on all new/changed files
  - Verify dark mode works across ALL new components
  - Verify command palette has entries for ALL new features
  - Verify all new keyboard shortcuts work without conflicts
  - Verify all new components dispatch appropriate events (`status-updated`, etc.)
  - Cross-feature integration: History → Cherry-pick, History → Reset, Search → History, Graph → History

  **Must NOT do**:
  - Don't add new features in this task
  - Don't refactor existing code

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Full integration verification across all 19 features
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 5 (Sequential — final task)
  - **Blocks**: None (final task)
  - **Blocked By**: ALL previous tasks

  **References**:

  **Pattern References**:
  - `tests/Feature/` — All existing test patterns
  - `tests/Browser/` — Browser test patterns for integration

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → ALL PASS (0 failures)
  - [ ] `vendor/bin/pint --dirty --format agent` → no issues
  - [ ] Dark mode toggle works for all new components
  - [ ] Command palette has entries for all 19 features
  - [ ] All keyboard shortcuts functional without conflicts

  **Commit**: YES
  - Message: `test(integration): verify all new features work together`
  - Files: New integration tests

---

## Commit Strategy

| After Task | Message | Verification |
|------------|---------|--------------|
| 1 | `feat(history): add commit history panel with pagination` | `php artisan test --compact --filter=HistoryPanel` |
| 2 | `feat(commit): add undo last commit with safety checks` | `php artisan test --compact --filter=UndoLastCommit` |
| 3 | `feat(conflicts): add 3-way merge conflict resolution editor` | `php artisan test --compact --filter=ConflictResolver` |
| 4 | `feat(history): add git reset and revert operations with safety modals` | `php artisan test --compact --filter=ResetService` |
| 5 | `feat(diff): add line-level staging with clickable gutter` | `php artisan test --compact --filter=LineLevelStaging` |
| 6 | `feat(diff): add side-by-side diff view with synchronized scrolling` | `php artisan test --compact --filter=SideBySideDiff` |
| 7 | `feat(rebase): add interactive rebase with drag-and-drop reordering` | `php artisan test --compact --filter=RebaseService` |
| 8 | `feat(commit): add cherry-pick with conflict handling` | `php artisan test --compact --filter=CherryPick` |
| 9 | `feat(blame): add file blame view with commit annotations` | `php artisan test --compact --filter=BlameService` |
| 10 | `feat(graph): add visual git graph with branch topology` | `php artisan test --compact --filter=GraphService` |
| 11 | `feat(search): add commit, content, and file search` | `php artisan test --compact --filter=SearchService` |
| 12 | `feat(diff): add visual image diff comparison` | `php artisan test --compact --filter=ImageDiff` |
| 13 | `feat(tags): add tag management with create, delete, push` | `php artisan test --compact --filter=TagService` |
| 14 | `feat(theme): add dark mode with Catppuccin Mocha palette` | `php artisan test --compact --filter=DarkMode` |
| 15 | `feat(commit): add commit message templates with conventional commits` | `php artisan test --compact --filter=CommitTemplate` |
| 16 | `feat(editor): add open in external editor with line number support` | `php artisan test --compact --filter=ExternalEditor` |
| 17 | `feat(notifications): add native macOS notifications for git operations` | `php artisan test --compact --filter=Notification` |
| 18 | `feat(commit): add commit message history with arrow key cycling` | `php artisan test --compact --filter=CommitHistory` |
| 19 | `feat(shortcuts): add keyboard shortcuts for all new features and help modal` | `php artisan test --compact --filter=KeyboardShortcuts` |
| 20 | `test(integration): verify all new features work together` | `php artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact           # Expected: ALL PASS (0 failures)
vendor/bin/pint --dirty --format agent  # Expected: no formatting issues
```

### Final Checklist
- [ ] All 19 features implemented and accessible from UI
- [ ] Commit History panel shows log with pagination
- [ ] Undo Last Commit works with safety checks
- [ ] 3-way Merge Conflict Resolution editor functional
- [ ] Git Reset/Revert with confirmation modals
- [ ] Line-level staging with clickable gutter
- [ ] Side-by-side diff view with sync scrolling
- [ ] Interactive Rebase with drag-and-drop
- [ ] Cherry-pick with conflict handling
- [ ] File Blame view with commit annotations
- [ ] Git Graph renders branch topology
- [ ] Search finds commits, content, files
- [ ] Image Diff shows visual comparison
- [ ] Tag Management (create, delete, push)
- [ ] Dark Mode with Catppuccin Mocha
- [ ] Commit Templates with Conventional Commits
- [ ] Open in External Editor
- [ ] Native macOS Notifications
- [ ] Quick Commit / Message History
- [ ] All keyboard shortcuts working (no conflicts)
- [ ] Command palette updated with all new commands
- [ ] No existing features broken
- [ ] All tests pass
