# Gitty — Native Desktop Git Client

## TL;DR

> **Quick Summary**: Build "Gitty", a native macOS desktop git client using NativePHP (Electron) + Livewire + Flux UI that replicates the VS Code Git panel + GitLens staging/commit/branch/stash UX. Uses shell-out to git CLI for all git operations, server-rendered HTML diffs with Shiki syntax highlighting, and Livewire polling for auto-refresh.
>
> **Deliverables**:
> - Fully functional macOS desktop app (.dmg)
> - Staging panel with file-level and hunk-level staging/unstaging
> - Inline diff viewer with Shiki syntax highlighting
> - Commit flow (message, amend, commit, commit+push)
> - Branch management (switch, create, delete, merge)
> - Push/Pull/Fetch/Sync with progress indicators
> - Stash management (create, apply, pop, drop)
> - Repository sidebar (branches, remotes, tags, stashes)
> - Multi-repo quick switch with recent repos list
> - Auto-fetch from remotes (configurable interval)
> - Keyboard shortcuts (VS Code-compatible)
> - Full Pest test suite (TDD)
>
> **Estimated Effort**: Large (4-5 weeks)
> **Parallel Execution**: YES — 3 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4 → Task 5 → Task 6 → Task 7

---

## Context

### Original Request
Build a git client with the exact same user experience as the VS Code git panel + GitLens extension. For actual merging and diffing it could rely on an external editor. Everything else regarding staging, stashing, committing, pulling, and so on should be built in. Git graph is secondary. Should manage multiple repositories.

### Interview Summary
**Key Discussions**:
- **App type**: Native desktop app (NOT a TUI) using NativePHP + Livewire + Flux
- **NativePHP backend**: Electron (NativePHP v2 is Electron-only; user accepted trade-off over Tauri)
- **Git interaction**: Shell out to git CLI via Laravel Process facade (most reliable, like lazygit)
- **Diff rendering**: Server-rendered HTML with Shiki syntax highlighting (VS Code's own highlighter)
- **Filesystem watching**: Auto-refresh via polling .git/index with wire:poll
- **Multi-repo**: Single window, quick switch between recently-used repos (stored in SQLite)
- **External editor**: Only for merge conflict resolution — Gitty shows inline diffs itself
- **Auto-fetch**: Configurable interval (default 3 min)
- **Staging**: File-level + hunk-level for MVP; line-level deferred to post-MVP
- **Missing Flux components**: Build custom Livewire/Alpine.js components for file tree + commit history table
- **Test strategy**: TDD with Pest (test-first, RED-GREEN-REFACTOR)
- **Target platform**: macOS only for now

### Research Findings
- **NativePHP Desktop v2**: Production-ready, Electron-only, provides Window/Shell/ChildProcess/Dialog/Notification/MenuBar/GlobalShortcut facades. ~150-200MB bundle. 2-3s startup.
- **Livewire v4**: Non-blocking polling, 60% faster DOM updates, islands architecture. wire:poll for git status updates.
- **Flux UI free**: Buttons, Dropdowns, Inputs, Checkboxes, Modals, Badges, Tooltips. Missing: Table, Tabs, Tree view.
- **gitonomy/gitlib**: 17.9M installs, provides structured log/diff/blame API, shells out under the hood. Good diff parser for hunk extraction.
- **Shiki-php**: Server-side syntax highlighting matching VS Code themes. Benchmark needed for large diffs.
- **Performance**: Suitable for small-to-medium repos (<10K files). PHP single-threaded — async via Process::start() for long operations.

### Metis Review
**Identified Gaps** (addressed):
- **Concurrent git operations**: Implement operation queue with mutex per repo
- **Livewire polling + large diffs**: Use wire:poll.visible, virtual scrolling, debounce during operations
- **Git operations block UI**: Use Process::start() for async commands > 1s
- **Memory overhead**: Profile early, clear state on repo switch, target <500MB
- **Custom component complexity**: Start flat file list, no drag-to-stage
- **Edge cases**: Handle detached HEAD, merge conflicts, corrupted repos, missing git config
- **Git CLI compatibility**: Use --porcelain=v2 format, test on macOS git versions
- **Scope creep locked**: No graph, no blame, no file editing, no terminal, no remote integrations, no drag-and-drop

---

## Work Objectives

### Core Objective
Build a polished, VS Code-like native desktop git client for macOS that makes daily git operations (stage → commit → push) feel fast and intuitive, with full branch and stash management and a sidebar for repository navigation.

### Concrete Deliverables
- macOS .dmg installer for Gitty
- `GitService` layer: status, stage, unstage, commit, amend, push, pull, fetch, branch, stash, diff, log
- `DiffService`: parse git diff output, extract hunks, render with Shiki highlighting
- Livewire components: StagingPanel, CommitPanel, DiffViewer, BranchSidebar, StashPanel, RepoSwitcher, SettingsModal
- Custom Alpine.js components: FileTree (expand/collapse), KeyboardShortcuts
- SQLite schema for recent repos + app settings
- Pest test suite with 80%+ coverage on git service layer
- NativePHP window config, menus, dialogs, notifications, shortcuts

### Definition of Done
- [ ] `php artisan native:build --platform=mac` produces working .dmg
- [ ] Can open any git repo via file dialog, see status, stage/unstage files and hunks
- [ ] Can commit with message, amend, commit+push
- [ ] Can switch/create/delete/merge branches
- [ ] Can push/pull/fetch with progress indicators
- [ ] Can create/apply/pop/drop stashes
- [ ] Sidebar shows branches, remotes, tags, stashes
- [ ] Can switch between recent repos without restarting
- [ ] Auto-fetch works at configured interval
- [ ] All Pest tests pass
- [ ] App handles edge cases without crashing (detached HEAD, conflicts, corrupted repo, no git)

### Must Have
- File-level + hunk-level staging/unstaging
- Inline diff viewer with syntax highlighting
- Commit message input with amend support
- Branch management (switch, create, delete, merge)
- Push/Pull/Fetch with progress
- Stash CRUD
- Repository sidebar with branches/remotes/tags/stashes
- Multi-repo quick switch
- Auto-fetch from remotes
- Dark mode (Flux built-in)
- Keyboard shortcuts (Cmd+Enter = commit, etc.)
- Graceful error handling for git edge cases

### Must NOT Have (Guardrails)
- ❌ Commit graph visualization (post-MVP)
- ❌ Blame annotations (post-MVP)
- ❌ File/line history views (post-MVP)
- ❌ Commit search (post-MVP)
- ❌ Interactive rebase (post-MVP)
- ❌ Merge conflict resolution (external editor only)
- ❌ Line-level staging (hunk-level only for MVP)
- ❌ Drag-and-drop staging
- ❌ File editing (except commit message)
- ❌ Embedded terminal
- ❌ GitHub/GitLab/Bitbucket integrations
- ❌ Rich text commit messages (plain text only)
- ❌ File type icons (git status icons only)
- ❌ Git data stored in SQLite (ephemeral only — SQLite for app settings/repos only)
- ❌ More than 10 settings options
- ❌ Multi-window mode

---

## Verification Strategy (MANDATORY)

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.
> This is NOT conditional — it applies to EVERY task, regardless of test strategy.
>
> **FORBIDDEN** — acceptance criteria that require:
> - "User manually tests..."
> - "User visually confirms..."
> - "User interacts with..."
> - "Ask user to verify..."
> - ANY step where a human must perform an action
>
> **ALL verification is executed by the agent** using tools (Playwright, interactive_bash, curl, etc.). No exceptions.

### Test Decision
- **Infrastructure exists**: NO (greenfield — will be set up in Task 1)
- **Automated tests**: TDD (test-first, RED-GREEN-REFACTOR)
- **Framework**: Pest (PHP testing framework, Laravel-native)

### TDD Workflow

Each feature TODO follows RED-GREEN-REFACTOR:

**Task Structure:**
1. **RED**: Write failing test first
   - Test file: `tests/Feature/GitServiceTest.php` (or equivalent)
   - Test command: `php artisan test --filter=TestName`
   - Expected: FAIL (test exists, implementation doesn't)
2. **GREEN**: Implement minimum code to pass
   - Command: `php artisan test --filter=TestName`
   - Expected: PASS
3. **REFACTOR**: Clean up while keeping green
   - Command: `php artisan test`
   - Expected: ALL PASS

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

> Every task MUST include Agent-Executed QA Scenarios as verification.
> The executing agent DIRECTLY verifies the deliverable by running it.

**Verification Tool by Deliverable Type:**

| Type | Tool | How Agent Verifies |
|------|------|-------------------|
| **Desktop UI** | Playwright (playwright skill) | Launch NativePHP app, navigate, interact, assert DOM, screenshot |
| **Git Operations** | Bash (shell commands) | Run git commands against test repos, verify output |
| **Livewire Components** | Pest (Livewire::test()) | Programmatic component testing |
| **Laravel Services** | Pest (unit tests) | Direct service method testing with Process::fake() |
| **NativePHP Integration** | Bash + Playwright | Launch app, verify native features work |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
├── Task 1: Project scaffolding + NativePHP setup
└── (sequential foundation — nothing can parallelize yet)

Wave 2 (After Task 1-3 complete):
├── Task 4: Staging panel (file-level)
├── Task 8: Branch management service + UI
└── Task 10: Stash management service + UI

Wave 3 (After Wave 2):
├── Task 5: Diff viewer + Shiki highlighting
├── Task 9: Push/Pull/Fetch with progress
├── Task 11: Repository sidebar
└── Task 12: Custom file tree component

Wave 4 (After Wave 3):
├── Task 6: Hunk-level staging in diff viewer
├── Task 13: Multi-repo quick switch
├── Task 14: Auto-fetch background operation
└── Task 15: Settings panel

Wave 5 (After Wave 4):
├── Task 16: Keyboard shortcuts
├── Task 17: Error handling + edge cases
└── Task 18: Performance optimization

Wave 6 (Final):
└── Task 19: NativePHP packaging + macOS .dmg
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 (Scaffold) | None | 2, 3 | None |
| 2 (Test infra) | 1 | 3-19 | None |
| 3 (GitService) | 1, 2 | 4-19 | None |
| 4 (Staging panel) | 3 | 6 | 8, 10 |
| 5 (Diff viewer) | 3 | 6 | 9, 11, 12 |
| 6 (Hunk staging) | 4, 5 | 19 | 13, 14, 15 |
| 7 (Commit panel) | 3 | 19 | 4, 5 |
| 8 (Branch mgmt) | 3 | 19 | 4, 10 |
| 9 (Push/Pull) | 3 | 14, 19 | 5, 11, 12 |
| 10 (Stash mgmt) | 3 | 19 | 4, 8 |
| 11 (Repo sidebar) | 3 | 19 | 5, 9, 12 |
| 12 (File tree) | 3 | 19 | 5, 9, 11 |
| 13 (Multi-repo) | 3 | 19 | 6, 14, 15 |
| 14 (Auto-fetch) | 9 | 19 | 6, 13, 15 |
| 15 (Settings) | 3 | 19 | 6, 13, 14 |
| 16 (Shortcuts) | 7 | 19 | 17, 18 |
| 17 (Error handling) | 3 | 19 | 16, 18 |
| 18 (Performance) | 4, 5, 11 | 19 | 16, 17 |
| 19 (Packaging) | ALL | None | None |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Dispatch |
|------|-------|---------------------|
| 1 | 1, 2, 3 | Sequential — foundation must be solid |
| 2 | 4, 7, 8, 10 | `task(category="unspecified-high", load_skills=[], run_in_background=true)` × 4 |
| 3 | 5, 9, 11, 12 | `task(category="visual-engineering", load_skills=["frontend-ui-ux"], run_in_background=true)` for 5, 12; `unspecified-high` for 9, 11 |
| 4 | 6, 13, 14, 15 | parallel dispatch |
| 5 | 16, 17, 18 | parallel dispatch |
| 6 | 19 | sequential final task |

---

## TODOs

- [ ] 1. Project Scaffolding + NativePHP Setup

  **What to do**:
  - Create new Laravel project: `laravel new gitty`
  - Install NativePHP Desktop v2: `composer require nativephp/desktop && php artisan native:install`
  - Install Livewire: `composer require livewire/livewire`
  - Install Flux UI (free): `composer require livewire/flux`
  - Install gitonomy/gitlib: `composer require gitonomy/gitlib`
  - Install Shiki-php: `composer require spatie/shiki-php`
  - Configure NativePHP window in `app/Providers/NativeAppServiceProvider.php`:
    - Window size: 1200x800 (min: 900x600)
    - Title: "Gitty"
    - Dark mode support
  - Configure NativePHP menu bar with: File (Open Repo, Recent Repos, Settings, Quit), Git (Commit, Push, Pull, Fetch, Stash), Branch (Switch, Create, Delete, Merge), Help (About)
  - Set up SQLite database for app settings + recent repos
  - Create database migration: `repositories` table (id, path, name, last_opened_at) and `settings` table (id, key, value)
  - Run migrations
  - Create base Blade layout (`resources/views/layouts/app.blade.php`) with Flux styles + Alpine.js
  - Verify app launches with `php artisan native:run` and shows empty window

  **Must NOT do**:
  - Do NOT add any git functionality yet
  - Do NOT add Tailwind customization beyond Flux defaults
  - Do NOT set up CI/CD
  - Do NOT configure auto-updates

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Laravel scaffolding + NativePHP configuration requires framework knowledge but isn't visual-engineering
  - **Skills**: []
    - No special skills needed — standard Laravel setup
  - **Skills Evaluated but Omitted**:
    - `frontend-ui-ux`: Not needed yet — no UI components being built

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 1 (sequential)
  - **Blocks**: Tasks 2-19
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - NativePHP installation: `https://nativephp.com/docs/desktop/2/getting-started/installation` — Follow exact install steps
  - NativePHP window config: `https://nativephp.com/docs/desktop/2/the-basics/windows` — Window::open() API with width/height/title
  - NativePHP menus: `https://nativephp.com/docs/desktop/2/the-basics/menus` — Menu facade for native menu bar
  - Flux UI setup: `https://fluxui.dev/docs/getting-started` — Installation and Blade setup

  **API/Type References**:
  - Laravel migration API: Standard Laravel Schema::create for repositories + settings tables
  - NativePHP NativeAppServiceProvider: `boot()` method for window + menu configuration

  **External References**:
  - NativePHP Desktop v2 docs: `https://nativephp.com/docs/desktop/2`
  - Livewire v3 docs: `https://livewire.laravel.com/docs`
  - Flux UI docs: `https://fluxui.dev`
  - Shiki-php: `https://github.com/spatie/shiki-php`
  - gitonomy/gitlib: `https://github.com/gitonomy/gitlib`

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Laravel project created with all dependencies
    Tool: Bash
    Preconditions: PHP 8.2+, Composer, Node.js installed
    Steps:
      1. Run: composer show --installed | grep -c "nativephp/desktop\|livewire/livewire\|livewire/flux\|gitonomy/gitlib\|spatie/shiki-php"
      2. Assert: output is "5" (all 5 packages installed)
      3. Run: php artisan migrate:status
      4. Assert: repositories and settings tables show "Ran"
      5. Run: ls resources/views/layouts/app.blade.php
      6. Assert: file exists
    Expected Result: All dependencies installed, migrations run, layout file exists
    Evidence: Terminal output captured

  Scenario: NativePHP app launches successfully
    Tool: Bash
    Preconditions: Dependencies installed
    Steps:
      1. Run: php artisan native:run &
      2. Wait 10 seconds for app to start
      3. Run: lsof -i :8000 | grep LISTEN (or whichever port NativePHP uses)
      4. Assert: Process is listening
      5. Kill the process
    Expected Result: NativePHP launches without errors
    Evidence: Terminal output captured
  ```

  **Commit**: YES
  - Message: `feat(scaffold): initialize Laravel + NativePHP + Livewire + Flux project`
  - Files: All generated files
  - Pre-commit: `php artisan test` (should have 0 tests, 0 failures)

---

- [ ] 2. Test Infrastructure Setup (Pest + Fixtures)

  **What to do**:
  - Install Pest: `composer require pestphp/pest --dev && php artisan pest:install`
  - Install Pest Livewire plugin: `composer require pestphp/pest-plugin-livewire --dev`
  - Configure Pest in `tests/Pest.php` — set up test case base classes
  - Create test helper: `tests/Helpers/GitTestHelper.php` with methods:
    - `createTestRepo(string $path): void` — init git repo with initial commit
    - `addTestFiles(string $repoPath, array $files): void` — create files with content
    - `modifyTestFiles(string $repoPath, array $files): void` — modify existing files
    - `createConflict(string $repoPath): void` — create merge conflict state
    - `createDetachedHead(string $repoPath): void` — checkout specific commit
    - `cleanupTestRepo(string $path): void` — remove test repo
  - Create test fixtures directory: `tests/fixtures/`
  - Create fixture script `tests/fixtures/setup.sh` that creates repos with specific states:
    - Clean repo (no changes)
    - Repo with unstaged changes
    - Repo with staged changes
    - Repo with mixed staged/unstaged
    - Repo with merge conflict
    - Repo with detached HEAD
    - Repo with stashes
    - Repo with multiple branches
    - Repo with untracked files
    - Repo with deleted files
    - Repo with renamed files
    - Repo with binary files
  - Write a smoke test: `tests/Feature/SmokeTest.php` — verify Pest works
  - Configure `Process::fake()` patterns for git command mocking
  - Create `tests/Mocks/GitOutputFixtures.php` with sample git output strings for:
    - `git status --porcelain=v2`
    - `git log --format=...`
    - `git diff --no-color`
    - `git branch -a`
    - `git stash list`
    - `git remote -v`

  **Must NOT do**:
  - Do NOT write actual feature tests yet (those come with each feature task)
  - Do NOT set up browser/Playwright testing (later task)
  - Do NOT create Livewire component tests yet

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Test infrastructure setup requires understanding of Pest, Laravel testing conventions, and git edge cases
  - **Skills**: []
  - **Skills Evaluated but Omitted**:
    - `git-master`: We're testing git operations, not performing them on the project repo

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 1 (sequential after Task 1)
  - **Blocks**: Tasks 3-19
  - **Blocked By**: Task 1

  **References**:

  **External References**:
  - Pest docs: `https://pestphp.com/docs/installation` — Installation and configuration
  - Pest Livewire plugin: `https://pestphp.com/docs/plugins#livewire` — Livewire component testing
  - Laravel Process::fake(): `https://laravel.com/docs/12.x/processes#testing` — Mocking shell commands
  - git status --porcelain=v2 format: `https://git-scm.com/docs/git-status#_porcelain_format_version_2` — Output format reference

  **Acceptance Criteria**:

  - [ ] `php artisan test` runs and passes smoke test
  - [ ] `tests/Helpers/GitTestHelper.php` exists with all helper methods
  - [ ] `tests/fixtures/setup.sh` creates all fixture repos when run
  - [ ] `tests/Mocks/GitOutputFixtures.php` exists with all sample outputs

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Pest runs and passes smoke test
    Tool: Bash
    Preconditions: Task 1 complete
    Steps:
      1. Run: php artisan test --filter=SmokeTest
      2. Assert: Exit code 0
      3. Assert: Output contains "1 test" and "1 passed" (or similar)
    Expected Result: Pest is configured and smoke test passes
    Evidence: Terminal output captured

  Scenario: Test fixtures create valid git repos
    Tool: Bash
    Preconditions: tests/fixtures/setup.sh exists
    Steps:
      1. Run: bash tests/fixtures/setup.sh /tmp/gitty-fixtures
      2. Run: git -C /tmp/gitty-fixtures/clean-repo status
      3. Assert: output contains "nothing to commit"
      4. Run: git -C /tmp/gitty-fixtures/unstaged-repo status --porcelain
      5. Assert: output contains " M " (unstaged modification)
      6. Run: git -C /tmp/gitty-fixtures/conflict-repo status
      7. Assert: output contains "Unmerged" or "both modified"
      8. Run: git -C /tmp/gitty-fixtures/detached-repo symbolic-ref HEAD 2>&1
      9. Assert: exit code non-zero (detached HEAD)
      10. Cleanup: rm -rf /tmp/gitty-fixtures
    Expected Result: All fixture repos created with correct states
    Evidence: Terminal output captured
  ```

  **Commit**: YES
  - Message: `test(infra): set up Pest testing framework with git fixtures and mocks`
  - Files: `tests/`
  - Pre-commit: `php artisan test`

---

- [ ] 3. GitService Foundation (Core Git Operations Layer)

  **What to do**:
  - Create `app/Services/Git/GitService.php` — main git operations service:
    - `__construct(string $repoPath)` — validate path has .git directory
    - `status(): GitStatus` — parse `git status --porcelain=v2` output
    - `log(int $limit = 100, ?string $branch = null): Collection<Commit>` — parse git log
    - `diff(?string $file = null, bool $staged = false): DiffResult` — get diff output
    - `currentBranch(): string` — get current branch name (handle detached HEAD)
    - `isDetachedHead(): bool`
    - `aheadBehind(): array` — parse `git rev-list --left-right --count`
  - Create `app/Services/Git/StagingService.php`:
    - `stageFile(string $file): void`
    - `unstageFile(string $file): void`
    - `stageAll(): void`
    - `unstageAll(): void`
    - `discardFile(string $file): void`
    - `discardAll(): void`
  - Create `app/Services/Git/CommitService.php`:
    - `commit(string $message): void`
    - `commitAmend(string $message): void`
    - `commitAndPush(string $message): void`
    - `lastCommitMessage(): string`
  - Create `app/Services/Git/BranchService.php`:
    - `branches(): Collection<Branch>` — local + remote
    - `switchBranch(string $name): void`
    - `createBranch(string $name, ?string $from = null): void`
    - `deleteBranch(string $name, bool $force = false): void`
    - `mergeBranch(string $name): MergeResult`
  - Create `app/Services/Git/RemoteService.php`:
    - `push(?string $remote = null, ?string $branch = null): ProcessResult`
    - `pull(?string $remote = null, ?string $branch = null): ProcessResult`
    - `fetch(?string $remote = null): ProcessResult`
    - `fetchAll(): ProcessResult`
    - `remotes(): Collection<Remote>`
  - Create `app/Services/Git/StashService.php`:
    - `stash(?string $message = null, bool $includeUntracked = false): void`
    - `stashList(): Collection<Stash>`
    - `stashApply(int $index = 0): void`
    - `stashPop(int $index = 0): void`
    - `stashDrop(int $index = 0): void`
  - Create `app/Services/Git/DiffService.php`:
    - `parseDiff(string $rawDiff): DiffResult` — parse unified diff format into structured data
    - `extractHunks(DiffFile $file): Collection<Hunk>` — extract individual hunks
    - `renderDiffHtml(DiffResult $diff): string` — render diff as HTML with Shiki highlighting
    - `stageHunk(string $file, Hunk $hunk): void` — stage specific hunk via `git apply --cached`
    - `unstageHunk(string $file, Hunk $hunk): void` — unstage specific hunk
  - Create DTOs: `app/DTOs/GitStatus.php`, `Commit.php`, `Branch.php`, `Remote.php`, `Stash.php`, `DiffResult.php`, `DiffFile.php`, `Hunk.php`, `HunkLine.php`, `MergeResult.php`
  - Create `app/Services/Git/GitOperationQueue.php`:
    - Mutex-based queue (one git operation at a time per repo)
    - Uses Laravel Cache locks: `Cache::lock("git-{$repoPath}", 30)`
    - Methods: `execute(callable $operation): mixed`, `isLocked(): bool`
  - Create `app/Services/Git/GitConfigValidator.php`:
    - `validate(): array` — check user.name, user.email, git version
    - Returns array of warnings/errors
  - Register services in `AppServiceProvider`
  - Write Pest tests for ALL service methods (TDD — write tests FIRST):
    - `tests/Feature/Services/GitServiceTest.php`
    - `tests/Feature/Services/StagingServiceTest.php`
    - `tests/Feature/Services/CommitServiceTest.php`
    - `tests/Feature/Services/BranchServiceTest.php`
    - `tests/Feature/Services/RemoteServiceTest.php`
    - `tests/Feature/Services/StashServiceTest.php`
    - `tests/Feature/Services/DiffServiceTest.php`
    - `tests/Feature/Services/GitOperationQueueTest.php`
    - Use `Process::fake()` to mock git CLI output
    - Use GitOutputFixtures for expected outputs

  **Must NOT do**:
  - Do NOT create any Livewire components (those come in later tasks)
  - Do NOT create any views or Blade templates
  - Do NOT integrate with NativePHP APIs (later tasks)
  - Do NOT add caching yet (Task 18)
  - Do NOT handle async operations yet (Tasks 9, 14)

  **Recommended Agent Profile**:
  - **Category**: `ultrabrain`
    - Reason: Complex service layer with git CLI parsing, diff parsing, mutex queue, extensive test suite. Requires deep understanding of git internals and Laravel service patterns.
  - **Skills**: []
  - **Skills Evaluated but Omitted**:
    - `git-master`: Useful but the agent needs to write PHP code wrapping git, not run git directly

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 1 (sequential after Task 2)
  - **Blocks**: Tasks 4-19
  - **Blocked By**: Tasks 1, 2

  **References**:

  **External References**:
  - git status --porcelain=v2: `https://git-scm.com/docs/git-status#_porcelain_format_version_2`
  - git diff format: `https://git-scm.com/docs/diff-format`
  - git apply --cached: `https://git-scm.com/docs/git-apply` — for hunk-level staging
  - Laravel Process facade: `https://laravel.com/docs/12.x/processes`
  - Laravel Cache locks: `https://laravel.com/docs/12.x/cache#atomic-locks`
  - gitonomy/gitlib API: `https://github.com/gitonomy/gitlib` — reference for diff parsing patterns
  - Shiki-php usage: `https://github.com/spatie/shiki-php` — PHP syntax highlighting API

  **Acceptance Criteria**:

  **TDD (all tests written before implementation):**
  - [ ] `php artisan test --filter=GitServiceTest` → PASS (8+ tests)
  - [ ] `php artisan test --filter=StagingServiceTest` → PASS (6+ tests)
  - [ ] `php artisan test --filter=CommitServiceTest` → PASS (4+ tests)
  - [ ] `php artisan test --filter=BranchServiceTest` → PASS (6+ tests)
  - [ ] `php artisan test --filter=RemoteServiceTest` → PASS (5+ tests)
  - [ ] `php artisan test --filter=StashServiceTest` → PASS (5+ tests)
  - [ ] `php artisan test --filter=DiffServiceTest` → PASS (5+ tests)
  - [ ] `php artisan test --filter=GitOperationQueueTest` → PASS (3+ tests)
  - [ ] `php artisan test` → ALL PASS (42+ tests, 0 failures)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: GitService parses status correctly
    Tool: Bash
    Preconditions: Test repo created with modified + untracked files
    Steps:
      1. Run: php artisan test --filter="GitServiceTest::it_parses_porcelain_v2_status"
      2. Assert: exit code 0
      3. Assert: output contains "PASS"
    Expected Result: Status parsing works for all file states
    Evidence: Test output captured

  Scenario: DiffService parses hunks from unified diff
    Tool: Bash
    Preconditions: DiffService test exists with sample diff fixture
    Steps:
      1. Run: php artisan test --filter="DiffServiceTest::it_extracts_hunks_from_diff"
      2. Assert: exit code 0
      3. Run: php artisan test --filter="DiffServiceTest::it_renders_diff_html_with_shiki"
      4. Assert: exit code 0
    Expected Result: Hunks parsed correctly, HTML rendered with syntax highlighting
    Evidence: Test output captured

  Scenario: GitOperationQueue prevents concurrent operations
    Tool: Bash
    Preconditions: Queue test exists
    Steps:
      1. Run: php artisan test --filter="GitOperationQueueTest::it_prevents_concurrent_git_operations"
      2. Assert: exit code 0
    Expected Result: Second operation throws exception when first holds lock
    Evidence: Test output captured

  Scenario: Integration test with real git repo
    Tool: Bash
    Preconditions: None
    Steps:
      1. Create temp repo: git init /tmp/gitty-integration-test && cd /tmp/gitty-integration-test
      2. echo "hello" > test.txt && git add test.txt && git commit -m "init"
      3. echo "world" >> test.txt
      4. Run: php artisan tinker --execute="(new App\Services\Git\GitService('/tmp/gitty-integration-test'))->status()"
      5. Assert: output shows test.txt as modified
      6. Cleanup: rm -rf /tmp/gitty-integration-test
    Expected Result: GitService works with real git repo
    Evidence: Tinker output captured
  ```

  **Commit**: YES
  - Message: `feat(git): implement git service layer with full operation coverage`
  - Files: `app/Services/Git/`, `app/DTOs/`, `tests/Feature/Services/`
  - Pre-commit: `php artisan test`

---

- [ ] 4. Staging Panel — File-Level (Livewire Component)

  **What to do**:
  - Create Livewire component `app/Livewire/StagingPanel.php`:
    - Properties: `$repoPath`, `$changedFiles`, `$stagedFiles`, `$untrackedFiles`
    - Methods: `mount()`, `refreshStatus()`, `stageFile($file)`, `unstageFile($file)`, `stageAll()`, `unstageAll()`, `discardFile($file)`, `discardAll()`
    - Uses `GitService` and `StagingService`
    - Polling: `wire:poll.3s.visible="refreshStatus"` (only when window focused)
    - Debounce: pause polling for 5s after user action (prevent flash)
  - Create view `resources/views/livewire/staging-panel.blade.php`:
    - Two sections: "Changes" (unstaged) and "Staged Changes"
    - Each section header with file count and "Stage All" / "Unstage All" button
    - Each file row: status icon (M/A/D/R/U) + filename + action buttons (+/-/discard)
    - Status icons: modified (yellow ●), added (green +), deleted (red −), renamed (blue →), untracked (green U)
    - Use Flux components: `<flux:badge>` for status, `<flux:button>` for actions, `<flux:tooltip>` for full file paths
    - Confirmation dialog (Flux modal) before discard operations
    - Empty state: "No changes" message when working tree is clean
    - Click file name → dispatch event `'file-selected'` to open in diff viewer
  - Write Pest Livewire tests FIRST:
    - `tests/Feature/Livewire/StagingPanelTest.php`
    - Test: renders with changed files
    - Test: stage file moves it to staged section
    - Test: unstage file moves it back
    - Test: stage all moves all files
    - Test: discard shows confirmation modal
    - Test: empty state shown when no changes
    - Test: clicking file dispatches file-selected event
    - Use `Process::fake()` + `Livewire::test()`

  **Must NOT do**:
  - Do NOT implement hunk-level staging (Task 6)
  - Do NOT implement drag-and-drop
  - Do NOT implement file tree view (this is a flat list — tree comes in Task 12)
  - Do NOT add keyboard shortcuts yet (Task 16)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Livewire component with significant UI work — file lists, status badges, action buttons, modals
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Designing the staging panel layout to match VS Code's Source Control panel
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed for this task — using Pest Livewire testing

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 7, 8, 10)
  - **Blocks**: Task 6
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Services/Git/GitService.php:status()` — provides file status data for the panel
  - `app/Services/Git/StagingService.php` — stage/unstage/discard operations
  - `app/DTOs/GitStatus.php` — DTO for status data structure

  **External References**:
  - Livewire component docs: `https://livewire.laravel.com/docs/components`
  - Livewire polling: `https://livewire.laravel.com/docs/wire-poll`
  - Livewire::test(): `https://livewire.laravel.com/docs/testing`
  - Flux Badge: `https://fluxui.dev/components/badge`
  - Flux Button: `https://fluxui.dev/components/button`
  - Flux Modal: `https://fluxui.dev/components/modal`
  - VS Code Source Control panel: Reference for layout — two sections (Changes / Staged), file-level +/- icons, file count in header

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=StagingPanelTest` → PASS (7+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Staging panel shows modified files
    Tool: Pest (Livewire::test)
    Steps:
      1. Process::fake(['git status --porcelain=v2' => "1 .M N... 100644 ... file1.txt\n1 .M N... 100644 ... file2.txt"])
      2. Livewire::test(StagingPanel::class, ['repoPath' => '/tmp/test'])
      3. Assert: component renders "file1.txt" and "file2.txt"
      4. Assert: component renders "Changes (2)" section header
    Expected Result: Modified files displayed in Changes section
    Evidence: Test output

  Scenario: Stage file moves to Staged section
    Tool: Pest (Livewire::test)
    Steps:
      1. Process::fake git status with 2 unstaged files
      2. Livewire::test(StagingPanel)->call('stageFile', 'file1.txt')
      3. Assert: Process received 'git add file1.txt'
      4. Assert: component re-renders with file1.txt in Staged section
    Expected Result: File moves from Changes to Staged Changes
    Evidence: Test output

  Scenario: Discard shows confirmation modal
    Tool: Pest (Livewire::test)
    Steps:
      1. Livewire::test(StagingPanel)->call('discardFile', 'file1.txt')
      2. Assert: modal event dispatched
      3. Assert: git checkout not called yet (awaiting confirmation)
    Expected Result: Destructive action requires confirmation
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(ui): add file-level staging panel with status indicators`
  - Files: `app/Livewire/StagingPanel.php`, `resources/views/livewire/staging-panel.blade.php`, `tests/Feature/Livewire/StagingPanelTest.php`
  - Pre-commit: `php artisan test`

---

- [ ] 5. Diff Viewer with Shiki Syntax Highlighting

  **What to do**:
  - Create Livewire component `app/Livewire/DiffViewer.php`:
    - Properties: `$repoPath`, `$file`, `$diff`, `$renderedHtml`, `$isStaged`
    - Listens for `'file-selected'` event from StagingPanel
    - Methods: `mount()`, `loadDiff($file, $staged = false)`, `switchView($view)`
    - Uses `DiffService` to parse diff and render HTML
    - Supports unified diff view (default)
    - Shows file metadata: old path, new path, status, additions/deletions count
    - Lazy-loads hunks: render first 10 hunks, load more via `wire:click="loadMoreHunks"`
  - Create view `resources/views/livewire/diff-viewer.blade.php`:
    - Header: filename + status badge + additions (green +N) + deletions (red -N)
    - Diff body: rendered HTML from Shiki with line numbers
    - Line styling: green background for additions, red for deletions, neutral for context
    - Gutter: line numbers (old + new) on left side
    - Monospace font (SF Mono / Menlo)
    - Hunk headers: `@@ -x,y +a,b @@` styled as separators
    - "No file selected" empty state
    - "Binary file — cannot display diff" fallback
    - "Load more hunks" button at bottom if truncated
  - Configure Shiki-php themes:
    - Dark: `github-dark` (matches VS Code dark theme)
    - Light: `github-light` (matches VS Code light theme)
    - Auto-switch based on system/app theme
  - Write Pest tests FIRST:
    - `tests/Feature/Livewire/DiffViewerTest.php`
    - Test: renders diff HTML for selected file
    - Test: shows additions/deletions count
    - Test: shows empty state when no file selected
    - Test: shows binary file fallback
    - Test: lazy-loads hunks (first 10 only)
    - Test: load more hunks button works

  **Must NOT do**:
  - Do NOT implement hunk staging buttons in diff view yet (Task 6)
  - Do NOT implement side-by-side diff view (post-MVP)
  - Do NOT implement inline editing of diff
  - Do NOT implement line-level staging UI

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Core visual component — diff rendering with syntax highlighting, line numbers, hunk styling
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Making diffs look professional and readable, matching VS Code's diff styling

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 9, 11, 12)
  - **Blocks**: Task 6
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Services/Git/DiffService.php:parseDiff()` — provides structured diff data
  - `app/Services/Git/DiffService.php:renderDiffHtml()` — Shiki-highlighted HTML
  - `app/DTOs/DiffResult.php`, `DiffFile.php`, `Hunk.php`, `HunkLine.php` — data structures

  **External References**:
  - Shiki-php: `https://github.com/spatie/shiki-php` — Server-side highlighting API
  - VS Code diff styling: Reference for visual design — green/red backgrounds, line number gutters, hunk separators
  - Unified diff format: `https://www.gnu.org/software/diffutils/manual/html_node/Unified-Format.html`

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=DiffViewerTest` → PASS (6+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Diff viewer renders syntax-highlighted diff
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake Process with sample git diff output (PHP file with 3 hunks)
      2. Livewire::test(DiffViewer)->call('loadDiff', 'app/Service.php')
      3. Assert: rendered HTML contains <pre> with Shiki classes
      4. Assert: additions have green styling
      5. Assert: deletions have red styling
      6. Assert: line numbers present
    Expected Result: Diff renders with syntax highlighting and correct styling
    Evidence: Test output

  Scenario: Binary file shows fallback message
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake Process with "Binary files differ" diff output
      2. Livewire::test(DiffViewer)->call('loadDiff', 'image.png')
      3. Assert: component renders "Binary file — cannot display diff"
    Expected Result: Binary files handled gracefully
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(ui): add diff viewer with Shiki syntax highlighting`
  - Files: `app/Livewire/DiffViewer.php`, `resources/views/livewire/diff-viewer.blade.php`, `tests/Feature/Livewire/DiffViewerTest.php`
  - Pre-commit: `php artisan test`

---

- [ ] 6. Hunk-Level Staging in Diff Viewer

  **What to do**:
  - Extend `DiffViewer` component to add stage/unstage buttons per hunk:
    - Each hunk block gets a "+" (stage) or "-" (unstage) button in its header
    - Button appearance matches VS Code (appears on hover)
    - Method: `stageHunk(int $hunkIndex)`, `unstageHunk(int $hunkIndex)`
    - Uses `DiffService::stageHunk()` which calls `git apply --cached` with the hunk patch
  - Extend `DiffService`:
    - `generateHunkPatch(DiffFile $file, Hunk $hunk): string` — create patch string for a single hunk
    - Applies patch via: `echo $patch | git apply --cached -` (for staging)
    - Reverse patch via: `echo $patch | git apply --cached --reverse -` (for unstaging)
  - Update diff view Blade template:
    - Each `@@ ... @@` header gets a hoverable stage/unstage button
    - Visual feedback: staged hunks get a subtle green border
    - After staging a hunk, refresh the diff (re-render remaining unstaged hunks)
  - Write Pest tests FIRST:
    - Test: stage hunk calls git apply with correct patch
    - Test: unstage hunk calls git apply --reverse
    - Test: diff refreshes after hunk staging
    - Test: generateHunkPatch produces valid unified diff patch

  **Must NOT do**:
  - Do NOT implement line-level staging (post-MVP)
  - Do NOT implement "discard hunk" (nice-to-have, post-MVP)
  - Do NOT implement edit-before-stage

  **Recommended Agent Profile**:
  - **Category**: `ultrabrain`
    - Reason: Hunk patch generation + git apply is tricky — needs correct unified diff format, line number offsets, edge cases with file headers
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Hover-to-reveal stage buttons matching VS Code UX

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 13, 14, 15)
  - **Blocks**: Task 19
  - **Blocked By**: Tasks 4, 5

  **References**:

  **Pattern References**:
  - `app/Services/Git/DiffService.php` — existing diff parsing to extend
  - `app/Livewire/DiffViewer.php` — existing component to extend

  **External References**:
  - git apply --cached: `https://git-scm.com/docs/git-apply` — staging patches
  - Unified diff patch format: correct header format `diff --git a/file b/file` + `--- a/file` + `+++ b/file` + `@@ ... @@`

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter="DiffServiceTest::it_generates_valid_hunk_patch"` → PASS
  - [ ] `php artisan test --filter="DiffServiceTest::it_stages_hunk_via_git_apply"` → PASS
  - [ ] `php artisan test --filter="DiffViewerTest::it_stages_hunk_on_button_click"` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Stage individual hunk from multi-hunk diff
    Tool: Bash (integration test with real git repo)
    Steps:
      1. git init /tmp/hunk-test && cd /tmp/hunk-test
      2. printf "line1\nline2\nline3\nline4\nline5\nline6\nline7\nline8\nline9\nline10" > test.txt
      3. git add test.txt && git commit -m "init"
      4. sed -i '' 's/line2/CHANGED2/' test.txt && sed -i '' 's/line9/CHANGED9/' test.txt
      5. Verify 2 hunks exist: git diff test.txt | grep -c "@@" should be 2
      6. Stage first hunk only via DiffService::stageHunk()
      7. git diff --cached test.txt | grep -c "@@" should be 1 (only first hunk staged)
      8. git diff test.txt | grep -c "@@" should be 1 (second hunk still unstaged)
      9. Cleanup: rm -rf /tmp/hunk-test
    Expected Result: Individual hunk staged, other hunk remains unstaged
    Evidence: Terminal output captured
  ```

  **Commit**: YES (groups with Task 5)
  - Message: `feat(staging): add hunk-level staging/unstaging in diff viewer`
  - Files: `app/Services/Git/DiffService.php`, `app/Livewire/DiffViewer.php`, `resources/views/livewire/diff-viewer.blade.php`
  - Pre-commit: `php artisan test`

---

- [ ] 7. Commit Panel (Message Input + Commit Flow)

  **What to do**:
  - Create Livewire component `app/Livewire/CommitPanel.php`:
    - Properties: `$repoPath`, `$message`, `$isAmend`, `$stagedCount`, `$lastCommitMessage`
    - Methods: `commit()`, `commitAndPush()`, `amendCommit()`, `toggleAmend()`, `undoLastCommit()`
    - Validation: message required (min 1 char), warn if no staged files
    - Uses `CommitService`
    - After successful commit: clear message, dispatch `'committed'` event, show NativePHP notification
  - Create view `resources/views/livewire/commit-panel.blade.php`:
    - Textarea for commit message (auto-resizing, placeholder "Commit message")
    - Character count indicator
    - Amend checkbox: "Amend previous commit" — when toggled, loads last commit message into textarea
    - Commit button: primary action (Flux button)
    - Commit dropdown (Flux dropdown) with: Commit, Commit & Push, Amend
    - Disabled state when no staged files (button grayed out, tooltip "No staged changes")
    - Error display for failed commits (e.g., empty message, hooks failed)
    - Support `.gitmessage` template: load template content on mount if exists
  - Write Pest tests FIRST:
    - Test: commit calls git commit with message
    - Test: commit+push calls commit then push
    - Test: amend calls git commit --amend
    - Test: commit disabled when no staged files
    - Test: message cleared after successful commit
    - Test: .gitmessage template loaded on mount

  **Must NOT do**:
  - Do NOT add rich text editing
  - Do NOT add AI commit message generation
  - Do NOT add commit message history (up/down arrow)
  - Do NOT add sign-off or GPG signing

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Standard Livewire component with form logic, validation, and event dispatching
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Commit panel layout matching VS Code — textarea positioning, dropdown button

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 8, 10)
  - **Blocks**: Task 16
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Services/Git/CommitService.php` — commit/amend/commitAndPush methods
  - `app/Services/Git/StagingService.php` — check staged file count

  **External References**:
  - VS Code commit panel: Reference — textarea at top of Source Control, dropdown commit button below, amend checkbox
  - Flux Dropdown: `https://fluxui.dev/components/dropdown`
  - Livewire form validation: `https://livewire.laravel.com/docs/validation`
  - NativePHP Notification: `https://nativephp.com/docs/desktop/2/the-basics/notifications`

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=CommitPanelTest` → PASS (6+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Commit with message succeeds
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake Process for git commit, git status (1 staged file)
      2. Livewire::test(CommitPanel)->set('message', 'feat: add login')
      3. Call 'commit'
      4. Assert: Process received 'git commit -m "feat: add login"'
      5. Assert: message property is empty (cleared)
      6. Assert: 'committed' event dispatched
    Expected Result: Commit executed, message cleared, event dispatched
    Evidence: Test output

  Scenario: Commit button disabled when no staged files
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake Process for git status (0 staged files)
      2. Livewire::test(CommitPanel)
      3. Assert: commit button has disabled attribute
    Expected Result: Cannot commit without staged changes
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(ui): add commit panel with message input, amend, and commit+push`
  - Files: `app/Livewire/CommitPanel.php`, `resources/views/livewire/commit-panel.blade.php`, `tests/Feature/Livewire/CommitPanelTest.php`
  - Pre-commit: `php artisan test`

---

- [ ] 8. Branch Management (Service + UI)

  **What to do**:
  - Create Livewire component `app/Livewire/BranchManager.php`:
    - Properties: `$repoPath`, `$currentBranch`, `$branches`, `$aheadBehind`, `$showCreateModal`, `$newBranchName`, `$baseBranch`
    - Methods: `switchBranch($name)`, `createBranch()`, `deleteBranch($name)`, `mergeBranch($name)`, `refreshBranches()`
    - Uses `BranchService`
    - Branch switch: warn if working tree dirty (unsaved changes will be lost)
    - Delete: confirmation dialog, prevent deleting current branch
    - Merge: show result (success/conflict), handle merge conflicts (dispatch event to show conflict files in staging panel)
  - Create view `resources/views/livewire/branch-manager.blade.php`:
    - Current branch indicator (prominent, top of sidebar area)
    - Ahead/behind badges: ↑N ↓N (green/red)
    - Branch switcher: Flux dropdown with search/filter
    - Each branch: name + upstream status icon (no dot = up-to-date, green = ahead, red = behind, yellow = diverged, green+ = unpublished)
    - Context menu per branch: Checkout, Merge into current, Delete, Rename (post-MVP)
    - "Create Branch" button → Flux modal with: branch name input, base branch selector
    - Detached HEAD: show warning banner "HEAD detached at <sha>" with "Create branch here" button
  - Write Pest tests FIRST:
    - Test: renders current branch name
    - Test: switch branch calls git checkout
    - Test: create branch calls git checkout -b
    - Test: delete branch shows confirmation, calls git branch -d
    - Test: merge shows result
    - Test: detached HEAD shows warning

  **Must NOT do**:
  - Do NOT implement branch rename (post-MVP)
  - Do NOT implement branch comparison/diff view
  - Do NOT implement rebase (post-MVP)
  - Do NOT implement cherry-pick

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Standard Livewire component with form logic and git operations
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Branch status indicators and dropdown design

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 7, 10)
  - **Blocks**: Task 19
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Services/Git/BranchService.php` — branch operations
  - `app/DTOs/Branch.php` — branch data structure

  **External References**:
  - VS Code branch picker: Reference — dropdown with search, current branch marked ✓, upstream status icons
  - Flux Dropdown: `https://fluxui.dev/components/dropdown`

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=BranchManagerTest` → PASS (6+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Switch branch updates current branch display
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake git branch output with main + develop
      2. Livewire::test(BranchManager)->call('switchBranch', 'develop')
      3. Assert: Process received 'git checkout develop'
      4. Assert: currentBranch property is 'develop'
    Expected Result: Branch switched, UI updated
    Evidence: Test output

  Scenario: Detached HEAD shows warning
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake git symbolic-ref to fail (detached HEAD)
      2. Fake git rev-parse HEAD to return SHA
      3. Livewire::test(BranchManager)
      4. Assert: component renders "HEAD detached" warning
      5. Assert: "Create branch here" button visible
    Expected Result: Detached HEAD state communicated clearly
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(branches): add branch management with switch, create, delete, merge`
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`, `tests/Feature/Livewire/BranchManagerTest.php`
  - Pre-commit: `php artisan test`

---

- [ ] 9. Push/Pull/Fetch with Progress Indicators

  **What to do**:
  - Create Livewire component `app/Livewire/SyncPanel.php`:
    - Properties: `$repoPath`, `$syncStatus`, `$isOperationRunning`, `$operationOutput`, `$operationProgress`
    - Methods: `push()`, `pull()`, `fetch()`, `fetchAll()`, `sync()` (pull then push), `cancelOperation()`
    - Uses `RemoteService` with `Process::start()` for async operations
    - Progress: parse git progress output (e.g., "Receiving objects: 45%") and update progress bar
    - Show operation output in collapsible log area
    - Disable other git operations while push/pull/fetch is running (via GitOperationQueue)
    - Handle errors: auth failure, network error, conflict on pull, rejected push
    - Force push with lease: available via dropdown with scary confirmation dialog
  - Create view `resources/views/livewire/sync-panel.blade.php`:
    - Sync button area (3 buttons: Push ↑, Pull ↓, Fetch ↻)
    - Progress bar during operation (Flux-styled, indeterminate or percentage)
    - Ahead/behind indicator next to buttons (from BranchManager)
    - Operation log (collapsible): shows raw git output
    - Error banner: shows error message with retry button
    - "Force Push with Lease" in dropdown (with red text + confirmation modal)
  - Write Pest tests FIRST:
    - Test: push calls git push origin <branch>
    - Test: pull calls git pull origin <branch>
    - Test: fetch calls git fetch
    - Test: progress parsing extracts percentage
    - Test: error handling for auth failure
    - Test: force push shows confirmation

  **Must NOT do**:
  - Do NOT implement force push without --force-with-lease
  - Do NOT implement rebase on pull (post-MVP)
  - Do NOT implement push to different remote (post-MVP)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Async process management, progress parsing, error handling
  - **Skills**: []
  - **Skills Evaluated but Omitted**:
    - `frontend-ui-ux`: Progress bar is simple enough without specialized UI skill

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 5, 11, 12)
  - **Blocks**: Task 14
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Services/Git/RemoteService.php` — push/pull/fetch operations
  - `app/Services/Git/GitOperationQueue.php` — prevent concurrent operations

  **External References**:
  - Laravel Process async: `https://laravel.com/docs/12.x/processes#asynchronous-processes`
  - Git progress output format: `Receiving objects: 45% (100/222)` — parse with regex

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=SyncPanelTest` → PASS (6+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Pull with progress updates
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake Process::start with progress output
      2. Livewire::test(SyncPanel)->call('pull')
      3. Assert: isOperationRunning is true during operation
      4. Assert: operationProgress updates
      5. After completion: Assert: isOperationRunning is false
    Expected Result: Progress shown during pull
    Evidence: Test output

  Scenario: Push rejected shows error
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake Process to return non-zero exit with "rejected" in stderr
      2. Livewire::test(SyncPanel)->call('push')
      3. Assert: error message shown containing "rejected"
      4. Assert: "Force Push with Lease" option visible
    Expected Result: Rejection handled gracefully with force-push option
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(sync): add push/pull/fetch with progress indicators and error handling`
  - Files: `app/Livewire/SyncPanel.php`, `resources/views/livewire/sync-panel.blade.php`, `tests/Feature/Livewire/SyncPanelTest.php`
  - Pre-commit: `php artisan test`

---

- [ ] 10. Stash Management (Service + UI)

  **What to do**:
  - Create Livewire component `app/Livewire/StashPanel.php`:
    - Properties: `$repoPath`, `$stashes`, `$stashMessage`, `$includeUntracked`
    - Methods: `createStash()`, `applyStash($index)`, `popStash($index)`, `dropStash($index)`, `refreshStashes()`
    - Uses `StashService`
    - Create stash: modal with message input and "Include Untracked" checkbox
    - Apply/Pop: show stash content preview, handle conflicts
    - Drop: confirmation dialog
  - Create view `resources/views/livewire/stash-panel.blade.php`:
    - "Stash" button in header (creates stash with optional message)
    - Stash list: each stash shows index, message, branch, timestamp
    - Per-stash actions: Apply, Pop, Drop (as icon buttons or context menu)
    - Empty state: "No stashes" message
    - Stash create modal: message input + untracked checkbox + "Stash" button
  - Write Pest tests FIRST:
    - Test: create stash calls git stash push -m
    - Test: include untracked adds --include-untracked
    - Test: apply calls git stash apply stash@{N}
    - Test: pop calls git stash pop stash@{N}
    - Test: drop shows confirmation, calls git stash drop stash@{N}
    - Test: stash list parsed correctly

  **Must NOT do**:
  - Do NOT implement "Create branch from stash"
  - Do NOT implement stash diff preview (post-MVP)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Standard Livewire CRUD component
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 7, 8)
  - **Blocks**: Task 19
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Services/Git/StashService.php` — stash operations
  - `app/DTOs/Stash.php` — stash data structure

  **External References**:
  - VS Code stash UX: Stash dropdown in ... menu, stash picker with index + message + branch

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=StashPanelTest` → PASS (6+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Create stash with message
    Tool: Pest (Livewire::test)
    Steps:
      1. Livewire::test(StashPanel)->set('stashMessage', 'WIP: login feature')
      2. Call 'createStash'
      3. Assert: Process received 'git stash push -m "WIP: login feature"'
    Expected Result: Stash created with user message
    Evidence: Test output

  Scenario: Drop stash requires confirmation
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake git stash list with 2 stashes
      2. Livewire::test(StashPanel)->call('dropStash', 0)
      3. Assert: confirmation modal dispatched
      4. Assert: git stash drop NOT called yet
    Expected Result: Destructive action requires confirmation
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(stash): add stash management with create, apply, pop, drop`
  - Files: `app/Livewire/StashPanel.php`, `resources/views/livewire/stash-panel.blade.php`, `tests/Feature/Livewire/StashPanelTest.php`
  - Pre-commit: `php artisan test`

---

- [ ] 11. App Layout + Repository Sidebar

  **What to do**:
  - Create main app layout component `app/Livewire/AppLayout.php`:
    - Properties: `$repoPath`, `$currentView`, `$sidebarCollapsed`
    - Three-panel layout: sidebar (left) + staging/commit (center) + diff viewer (right)
    - Sidebar width: resizable (default 250px, min 200px, max 400px)
    - Center panel: staging panel on top, commit panel on bottom
    - Right panel: diff viewer (takes remaining space)
    - Responsive: sidebar collapses on narrow windows
  - Create Livewire component `app/Livewire/RepoSidebar.php`:
    - Properties: `$repoPath`, `$branches`, `$remotes`, `$tags`, `$stashes`
    - Collapsible sections (Alpine.js): Branches, Remotes, Tags, Stashes
    - Each section header: name + item count + collapse/expand toggle
    - Branches section: local branches with upstream status icons (same as Task 8)
    - Remotes section: grouped by remote name, shows remote branches
    - Tags section: tag name + target commit SHA (truncated)
    - Stashes section: stash index + message
    - Click interactions: click branch → switch, click remote branch → checkout, click stash → preview
    - Uses `GitService`, `BranchService`, `RemoteService`, `StashService`
    - Polling: `wire:poll.10s.visible="refreshSidebar"` (less frequent than staging panel)
  - Create views:
    - `resources/views/livewire/app-layout.blade.php` — three-panel layout with CSS Grid/Flexbox
    - `resources/views/livewire/repo-sidebar.blade.php` — collapsible tree sections
  - Write Pest tests FIRST:
    - Test: sidebar renders branches/remotes/tags/stashes sections
    - Test: sections are collapsible
    - Test: clicking branch dispatches switch event
    - Test: tags display correctly
    - Test: empty repo shows appropriate empty states

  **Must NOT do**:
  - Do NOT implement contributors section (post-MVP)
  - Do NOT implement search within sidebar
  - Do NOT implement branch comparison
  - Do NOT implement drag-and-drop

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Complex layout architecture — three-panel with resizable panes, collapsible sidebar sections
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Designing the VS Code-like three-panel layout, sidebar sections with icons and badges

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 5, 9, 12)
  - **Blocks**: Task 18
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - All service classes in `app/Services/Git/` — data sources for sidebar
  - All DTOs in `app/DTOs/` — data structures for display

  **External References**:
  - VS Code Source Control layout: Left sidebar + main panel (staging + commit) + right panel (diff)
  - GitLens Repository view: Collapsible sections for Branches, Remotes, Tags, Stashes, Contributors

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=RepoSidebarTest` → PASS (5+ tests)
  - [ ] `php artisan test --filter=AppLayoutTest` → PASS (3+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Three-panel layout renders correctly
    Tool: Playwright (playwright skill)
    Preconditions: NativePHP app running with test repo
    Steps:
      1. Launch app: php artisan native:run
      2. Open test repo via Dialog
      3. Assert: sidebar visible on left (width ~250px)
      4. Assert: staging panel visible in center
      5. Assert: diff viewer visible on right
      6. Screenshot: .sisyphus/evidence/task-11-layout.png
    Expected Result: Three-panel layout matches VS Code design
    Evidence: .sisyphus/evidence/task-11-layout.png

  Scenario: Sidebar sections collapse/expand
    Tool: Pest (Livewire::test)
    Steps:
      1. Fake git data with 3 branches, 2 remotes, 1 tag, 2 stashes
      2. Livewire::test(RepoSidebar)
      3. Assert: "Branches (3)" section visible
      4. Assert: "Remotes (2)" section visible
      5. Assert: "Tags (1)" section visible
      6. Assert: "Stashes (2)" section visible
    Expected Result: All sections render with correct counts
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(ui): add three-panel app layout with repository sidebar`
  - Files: `app/Livewire/AppLayout.php`, `app/Livewire/RepoSidebar.php`, views, tests
  - Pre-commit: `php artisan test`

---

- [ ] 12. Custom File Tree Component (Alpine.js)

  **What to do**:
  - Create Blade component `resources/views/components/file-tree.blade.php`:
    - Accepts `$files` (array of file paths with status)
    - Groups files by directory into a tree structure
    - Each directory: folder icon + name + expand/collapse toggle + file count
    - Each file: status icon + filename + action buttons (stage/unstage/discard)
    - Expand/collapse state managed by Alpine.js (`x-data`, NO server round-trip)
    - Initially: expand all directories (default open)
  - Create Alpine.js component `resources/js/components/file-tree.js`:
    - `x-data="fileTree(files)"` — manages tree expand/collapse state
    - Keyboard navigation: up/down arrows to move selection, space to toggle expand, enter to select file
    - Methods: `toggleDir(path)`, `selectFile(path)`, `expandAll()`, `collapseAll()`
  - Create PHP helper `app/Helpers/FileTreeBuilder.php`:
    - `buildTree(array $flatFiles): array` — converts flat file list to nested tree structure
    - Handles nested directories correctly
    - Sorts: directories first, then files, alphabetical within each
  - Integrate with StagingPanel: replace flat file list with file tree component
  - Add view toggle: flat list vs tree view (Alpine.js toggle, stored in localStorage)
  - Write tests:
    - Test: FileTreeBuilder correctly nests files
    - Test: FileTreeBuilder sorts directories first
    - Test: file tree renders all files from staging panel data

  **Must NOT do**:
  - Do NOT implement file type icons (git status icons only)
  - Do NOT implement drag-and-drop
  - Do NOT implement file search/filter within tree

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Complex interactive UI component with keyboard navigation, tree rendering, Alpine.js state management
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Tree component design matching VS Code's file tree — indentation, icons, hover states

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 5, 9, 11)
  - **Blocks**: Task 18
  - **Blocked By**: Task 3

  **References**:

  **External References**:
  - Alpine.js docs: `https://alpinejs.dev/directives/data` — x-data directive for component state
  - VS Code file tree: Reference — indented tree with folder/file icons, expand/collapse arrows

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=FileTreeBuilderTest` → PASS (3+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: File tree groups files by directory
    Tool: Pest
    Steps:
      1. Create flat file list: ['src/app/Model.php', 'src/app/Service.php', 'src/routes.php', 'README.md']
      2. Run FileTreeBuilder::buildTree()
      3. Assert: root has 'src/' directory and 'README.md' file
      4. Assert: 'src/' has 'app/' directory and 'routes.php' file
      5. Assert: 'src/app/' has 'Model.php' and 'Service.php'
    Expected Result: Flat files correctly nested into tree structure
    Evidence: Test output

  Scenario: Tree view toggle works
    Tool: Playwright (playwright skill)
    Preconditions: App running with repo containing files in multiple directories
    Steps:
      1. Navigate to app
      2. Assert: default view is tree (directories visible)
      3. Click view toggle button (flat/tree)
      4. Assert: flat list shown (no directory grouping)
      5. Click toggle again
      6. Assert: tree view restored
      7. Screenshot: .sisyphus/evidence/task-12-tree-view.png
    Expected Result: View toggles between tree and flat list
    Evidence: .sisyphus/evidence/task-12-tree-view.png
  ```

  **Commit**: YES
  - Message: `feat(ui): add custom file tree component with expand/collapse and keyboard nav`
  - Files: `resources/views/components/file-tree.blade.php`, `resources/js/components/file-tree.js`, `app/Helpers/FileTreeBuilder.php`, tests
  - Pre-commit: `php artisan test`

---

- [ ] 13. Multi-Repo Quick Switch

  **What to do**:
  - Create Livewire component `app/Livewire/RepoSwitcher.php`:
    - Properties: `$currentRepo`, `$recentRepos`, `$showOpenDialog`
    - Methods: `openRepo($path)`, `switchRepo($id)`, `removeRecentRepo($id)`, `openRepoDialog()`
    - Uses NativePHP `Dialog::folder()` to open file picker for selecting repos
    - Validates selected folder is a git repo (check .git directory)
    - Stores recent repos in SQLite `repositories` table (max 20, sorted by last_opened_at)
    - On switch: update all components with new repo path (dispatch `'repo-switched'` event)
    - All Livewire components listen for `'repo-switched'` and refresh with new path
  - Create `app/Models/Repository.php` — Eloquent model for recent repos
  - Create `app/Services/RepoManager.php`:
    - `openRepo(string $path): Repository` — validate + save to DB + return
    - `recentRepos(int $limit = 20): Collection`
    - `removeRepo(int $id): void`
    - `currentRepo(): ?Repository`
    - `setCurrentRepo(Repository $repo): void` (stores in session/cache)
  - Create view `resources/views/livewire/repo-switcher.blade.php`:
    - Current repo name + path in header area
    - "Open Repository" button → NativePHP folder dialog
    - Recent repos dropdown (Flux dropdown): repo name + path + "Remove" button
    - "No repository open" empty state with prominent "Open Repository" button
  - Integrate with NativePHP menu: File → Open Repository, File → Recent Repos submenu
  - Write Pest tests FIRST:
    - Test: open valid repo creates DB record
    - Test: open invalid folder shows error
    - Test: switch repo dispatches event
    - Test: recent repos limited to 20, sorted by last opened
    - Test: remove repo deletes from DB

  **Must NOT do**:
  - Do NOT implement multi-window (single window mode only)
  - Do NOT implement workspace files
  - Do NOT implement auto-discovery of repos

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Involves NativePHP Dialog integration, SQLite persistence, state management across components
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 6, 14, 15)
  - **Blocks**: Task 19
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - Database migration from Task 1 — `repositories` table schema
  - NativePHP Dialog: `https://nativephp.com/docs/desktop/2/the-basics/dialogs` — folder picker

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=RepoSwitcherTest` → PASS (5+ tests)
  - [ ] `php artisan test --filter=RepoManagerTest` → PASS (5+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Open repository via file dialog
    Tool: Pest (Livewire::test)
    Steps:
      1. Create test repo at /tmp/gitty-switch-test: git init
      2. Livewire::test(RepoSwitcher)->call('openRepo', '/tmp/gitty-switch-test')
      3. Assert: Repository model created in DB with correct path
      4. Assert: 'repo-switched' event dispatched
      5. Cleanup: rm -rf /tmp/gitty-switch-test
    Expected Result: Repo opened, saved to recents, components notified
    Evidence: Test output

  Scenario: Invalid folder shows error
    Tool: Pest (Livewire::test)
    Steps:
      1. Livewire::test(RepoSwitcher)->call('openRepo', '/tmp/not-a-git-repo')
      2. Assert: error message shown "Not a git repository"
      3. Assert: no DB record created
    Expected Result: Non-repo folder rejected with clear error
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(repo): add multi-repo quick switch with recent repos list`
  - Files: `app/Livewire/RepoSwitcher.php`, `app/Models/Repository.php`, `app/Services/RepoManager.php`, views, tests
  - Pre-commit: `php artisan test`

---

- [ ] 14. Auto-Fetch Background Operation

  **What to do**:
  - Create `app/Services/AutoFetchService.php`:
    - `start(string $repoPath, int $intervalSeconds = 180): void` — start auto-fetch timer
    - `stop(): void` — stop auto-fetch
    - `isRunning(): bool`
    - Uses NativePHP `ChildProcess` or Laravel scheduler with `Process::start()`
    - On fetch complete: dispatch `'remote-updated'` event
    - On fetch failure: log error, show subtle notification (not blocking), continue retrying
    - Respect GitOperationQueue (don't fetch while user operation running)
  - Integrate with `RepoSwitcher`: restart auto-fetch when repo changes
  - Integrate with `SettingsPanel` (Task 15): read interval from settings
  - Add `auto_fetch_interval` to settings table (default: 180 seconds, 0 = disabled)
  - Create Livewire component `app/Livewire/AutoFetchIndicator.php`:
    - Shows subtle icon in header when auto-fetch is active
    - Shows last fetch timestamp
    - Shows error indicator if last fetch failed (clickable to see error)
  - Write Pest tests FIRST:
    - Test: auto-fetch calls git fetch at configured interval
    - Test: auto-fetch stops during user git operations
    - Test: auto-fetch handles network failure gracefully
    - Test: disabling auto-fetch (interval = 0) stops fetching

  **Must NOT do**:
  - Do NOT implement auto-pull (only auto-fetch)
  - Do NOT show desktop notifications for every fetch (too noisy)
  - Do NOT auto-fetch more frequently than every 60 seconds

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Background process management, timer logic, error handling
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 6, 13, 15)
  - **Blocks**: Task 19
  - **Blocked By**: Task 9

  **References**:

  **Pattern References**:
  - `app/Services/Git/RemoteService.php:fetch()` — fetch operation
  - `app/Services/Git/GitOperationQueue.php` — respect operation locks
  - NativePHP ChildProcess: `https://nativephp.com/docs/desktop/2/digging-deeper/child-processes`

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=AutoFetchServiceTest` → PASS (4+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Auto-fetch runs at configured interval
    Tool: Pest
    Steps:
      1. Set auto_fetch_interval to 5 seconds (for testing)
      2. Start AutoFetchService
      3. Wait 6 seconds
      4. Assert: git fetch was called at least once
    Expected Result: Fetch triggered automatically
    Evidence: Test output

  Scenario: Auto-fetch pauses during user operations
    Tool: Pest
    Steps:
      1. Start AutoFetchService
      2. Acquire GitOperationQueue lock (simulate user push)
      3. Wait for auto-fetch interval
      4. Assert: git fetch NOT called (queue locked)
      5. Release lock
      6. Assert: git fetch called on next interval
    Expected Result: Auto-fetch respects operation queue
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(sync): add auto-fetch background operation with configurable interval`
  - Files: `app/Services/AutoFetchService.php`, `app/Livewire/AutoFetchIndicator.php`, views, tests
  - Pre-commit: `php artisan test`

---

- [ ] 15. Settings Panel

  **What to do**:
  - Create Livewire component `app/Livewire/SettingsModal.php`:
    - Properties: settings values read from DB
    - Settings to support (max 10):
      1. `auto_fetch_interval`: number (seconds), default 180, 0 = disabled
      2. `external_editor`: string, default "" (system default), options: "code", "vim", "nano", custom path
      3. `theme`: "dark" / "light" / "system", default "system"
      4. `default_branch`: string, default "main"
      5. `confirm_discard`: boolean, default true
      6. `confirm_force_push`: boolean, default true
      7. `show_untracked`: boolean, default true
      8. `diff_context_lines`: number, default 3
    - Methods: `save()`, `reset()` (reset to defaults)
    - Uses Flux modal for settings dialog
  - Create `app/Services/SettingsService.php`:
    - `get(string $key, mixed $default = null): mixed`
    - `set(string $key, mixed $value): void`
    - `all(): array`
    - `reset(): void` — reset all to defaults
    - Uses `settings` table from Task 1 migration
  - Create view `resources/views/livewire/settings-modal.blade.php`:
    - Flux modal with grouped settings
    - Each setting: label + input/dropdown/toggle
    - Save and Cancel buttons
    - Reset to Defaults link
  - Integrate with NativePHP menu: Gitty → Settings (Cmd+,)
  - Write Pest tests FIRST:
    - Test: settings save to database
    - Test: settings load from database
    - Test: default values used when no setting exists
    - Test: reset restores defaults

  **Must NOT do**:
  - Do NOT exceed 10 settings
  - Do NOT add per-repo settings
  - Do NOT add keyboard shortcut customization

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple CRUD settings panel with modal — straightforward Livewire form
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 6, 13, 14)
  - **Blocks**: Task 19
  - **Blocked By**: Task 3

  **References**:

  **External References**:
  - Flux Modal: `https://fluxui.dev/components/modal`
  - NativePHP Menu: `https://nativephp.com/docs/desktop/2/the-basics/menus` — Cmd+, shortcut for settings

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=SettingsServiceTest` → PASS (4+ tests)
  - [ ] `php artisan test --filter=SettingsModalTest` → PASS (3+ tests)

  **Commit**: YES
  - Message: `feat(settings): add settings panel with auto-fetch, editor, theme config`
  - Files: `app/Livewire/SettingsModal.php`, `app/Services/SettingsService.php`, views, tests
  - Pre-commit: `php artisan test`

---

- [ ] 16. Keyboard Shortcuts

  **What to do**:
  - Configure NativePHP global shortcuts in `NativeAppServiceProvider`:
    - `Cmd+,` — Open Settings
    - `Cmd+O` — Open Repository
    - `Cmd+Shift+G` — Focus Source Control (staging panel)
  - Configure Alpine.js in-app shortcuts in `resources/js/shortcuts.js`:
    - `Cmd+Enter` — Commit (when commit message focused)
    - `Cmd+Shift+Enter` — Commit & Push
    - `Cmd+Shift+K` — Stage all
    - `Cmd+Shift+U` — Unstage all
    - `Cmd+Shift+S` — Stash
    - `Cmd+Shift+P` — Pull
    - `Cmd+Shift+F` — Fetch
    - `Cmd+B` — Toggle sidebar
    - `Escape` — Close modal / deselect file
    - Arrow keys — Navigate file list
  - Create `resources/js/shortcuts.js` — Alpine.js keyboard handler
  - Register shortcuts as `@keydown.window` listeners
  - Add keyboard shortcut hints to Flux tooltips on buttons
  - Write Pest tests:
    - Test: Cmd+Enter triggers commit
    - Test: Cmd+Shift+K triggers stage all
    - Test: Escape closes modal

  **Must NOT do**:
  - Do NOT make shortcuts user-configurable (post-MVP)
  - Do NOT conflict with system shortcuts

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Keyboard shortcut wiring — straightforward NativePHP + Alpine.js integration
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5 (with Tasks 17, 18)
  - **Blocks**: Task 19
  - **Blocked By**: Task 7

  **References**:

  **External References**:
  - NativePHP GlobalShortcut: `https://nativephp.com/docs/desktop/2/the-basics/global-shortcuts`
  - Alpine.js @keydown: `https://alpinejs.dev/directives/on#keyboard-events`
  - VS Code git shortcuts: Reference for key binding choices

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Cmd+Enter triggers commit
    Tool: Playwright (playwright skill)
    Preconditions: App running with repo, staged files, commit message typed
    Steps:
      1. Focus commit message textarea
      2. Type "test commit"
      3. Press Cmd+Enter
      4. Assert: commit created (verify via git log)
      5. Screenshot: .sisyphus/evidence/task-16-shortcut-commit.png
    Expected Result: Keyboard shortcut triggers commit
    Evidence: .sisyphus/evidence/task-16-shortcut-commit.png
  ```

  **Commit**: YES
  - Message: `feat(ux): add VS Code-compatible keyboard shortcuts`
  - Files: `resources/js/shortcuts.js`, `app/Providers/NativeAppServiceProvider.php`, tests
  - Pre-commit: `php artisan test`

---

- [ ] 17. Error Handling + Edge Cases

  **What to do**:
  - Create `app/Services/Git/GitErrorHandler.php`:
    - Catches all Process exceptions from git commands
    - Translates git errors to user-friendly messages:
      - "fatal: not a git repository" → "This folder is not a git repository"
      - "error: pathspec 'X' did not match" → "File 'X' not found"
      - "CONFLICT" → "Merge conflict detected in: [files]"
      - "rejected" → "Push rejected. Remote has changes. Pull first."
      - Auth errors → "Authentication failed. Check your credentials."
      - "git: command not found" → "Git not found. Please install git."
    - Logs all git commands + outputs to Laravel log
  - Create `app/Livewire/ErrorBanner.php`:
    - Dismissible error banner at top of app
    - Shows error message + suggested action
    - Auto-dismiss after 10s for non-critical errors
    - Persistent for critical errors (merge conflicts, auth failures)
  - Handle edge cases in all existing components:
    - **Detached HEAD**: BranchManager shows warning, CommitPanel shows "Create branch" prompt
    - **Merge conflicts**: StagingPanel shows "Merge Changes" section with conflicted files + "Open in External Editor" button
    - **Rebase in progress**: Show "Rebase in progress" banner with Continue/Abort buttons (delegates to git rebase --continue/--abort)
    - **Corrupted repo**: Show error, prevent operations, suggest `git fsck`
    - **Empty repo** (no commits): StagingPanel works, CommitPanel shows "Initial Commit" label
    - **No git installed**: Show "Git not found" on app launch
    - **Missing git config**: Show "Configure git" prompt on first commit if user.name/email not set
    - **Binary files in diff**: Show "Binary file" placeholder
    - **Large files**: Skip diff for files > 1MB, show size warning
  - Create `app/Services/Git/GitConfigValidator.php` updates:
    - Check git version (require 2.30+)
    - Check user.name and user.email
    - Check PATH resolution for git binary
    - Run on app startup, show warnings in banner
  - For merge conflicts → "Open in Editor":
    - Use NativePHP `Shell::openFile($path)` to open conflicted file in system default editor
    - OR use configured editor from Settings (Task 15)
  - Write Pest tests:
    - Test: each error type produces correct user-friendly message
    - Test: detached HEAD detected and warning shown
    - Test: merge conflict files shown in staging panel
    - Test: empty repo (no commits) handled gracefully
    - Test: missing git binary detected

  **Must NOT do**:
  - Do NOT implement merge conflict resolution in-app
  - Do NOT implement 3-way merge editor
  - Do NOT add crash reporting / telemetry

  **Recommended Agent Profile**:
  - **Category**: `deep`
    - Reason: Requires methodical coverage of many edge cases across multiple components. Needs deep understanding of git states and error conditions.
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5 (with Tasks 16, 18)
  - **Blocks**: Task 19
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - All service classes in `app/Services/Git/` — add error handling to each
  - All Livewire components — add edge case handling to each
  - NativePHP Shell: `https://nativephp.com/docs/desktop/2/digging-deeper/shell` — openFile for external editor

  **Acceptance Criteria**:

  **TDD:**
  - [ ] `php artisan test --filter=GitErrorHandlerTest` → PASS (8+ tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Merge conflict shows conflicted files with editor button
    Tool: Bash + Pest
    Steps:
      1. Create test repo with merge conflict (using GitTestHelper::createConflict)
      2. Run app with conflicted repo
      3. Assert: StagingPanel shows "Merge Changes" section
      4. Assert: conflicted files listed with "C" status
      5. Assert: "Open in Editor" button visible
    Expected Result: Merge conflicts surfaced clearly, editor button available
    Evidence: Test output

  Scenario: Corrupted repo shows error without crash
    Tool: Pest (Livewire::test)
    Steps:
      1. Create test repo, delete .git/index
      2. Livewire::test(StagingPanel, ['repoPath' => $corruptedPath])
      3. Assert: error message shown (not exception thrown)
      4. Assert: component renders without crash
    Expected Result: App handles corrupted repo gracefully
    Evidence: Test output

  Scenario: No git installed shows error on startup
    Tool: Pest
    Steps:
      1. Fake Process for 'which git' to return empty (not found)
      2. Run GitConfigValidator::validate()
      3. Assert: result contains 'git_not_found' error
    Expected Result: Missing git detected early
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `feat(errors): add comprehensive error handling and edge case coverage`
  - Files: `app/Services/Git/GitErrorHandler.php`, `app/Livewire/ErrorBanner.php`, all modified components, tests
  - Pre-commit: `php artisan test`

---

- [ ] 18. Performance Optimization

  **What to do**:
  - Implement virtual scrolling for file lists > 50 items:
    - Use Alpine.js `x-intersect` for lazy rendering
    - Render only visible file rows + buffer (20 items above/below viewport)
    - Apply to: StagingPanel file list, commit history, branch list
  - Implement diff lazy-loading:
    - Render first 10 hunks on file select
    - "Show more" button loads next 10 hunks
    - For files > 1MB: skip diff entirely, show "File too large" message
  - Implement git data caching:
    - Cache `git log` results: 60s TTL, invalidate on commit/pull/fetch
    - Cache `git branch -a`: 30s TTL, invalidate on branch operations
    - Cache `git remote -v`: 300s TTL
    - Cache `git stash list`: 30s TTL, invalidate on stash operations
    - Use Laravel Cache facade with array driver (in-memory, no Redis needed)
  - Implement polling optimization:
    - `wire:poll.visible` on ALL polling components (pause when window unfocused)
    - Debounce: pause polling for 5s after user action (prevent flash during staging)
    - Stagger polling intervals: staging (3s), sidebar (10s), auto-fetch indicator (30s)
    - Use Livewire v4 non-blocking polling if available
  - Create performance benchmarks `tests/Performance/`:
    - Benchmark: open repo with 500 modified files → initial render < 3s
    - Benchmark: stage file → UI update < 500ms
    - Benchmark: view diff for 1000-line file → render < 1s
    - Benchmark: switch branch → full refresh < 2s
    - Benchmark: memory usage with 10 recent repos → < 500MB RSS
  - Profile memory:
    - Use `memory_get_peak_usage()` in key operations
    - Clear component state aggressively on repo switch
    - Log memory usage per operation to Laravel log

  **Must NOT do**:
  - Do NOT add Redis or external caching (array driver only)
  - Do NOT implement web workers or Service Workers
  - Do NOT over-optimize before measuring (profile first)

  **Recommended Agent Profile**:
  - **Category**: `ultrabrain`
    - Reason: Performance optimization requires careful profiling, understanding of Livewire rendering pipeline, and Alpine.js intersection observer patterns
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5 (with Tasks 16, 17)
  - **Blocks**: Task 19
  - **Blocked By**: Tasks 4, 5, 11

  **References**:

  **External References**:
  - Alpine.js x-intersect: `https://alpinejs.dev/plugins/intersect` — viewport intersection for virtual scrolling
  - Laravel Cache: `https://laravel.com/docs/12.x/cache` — array driver for in-memory caching
  - Livewire wire:poll.visible: `https://livewire.laravel.com/docs/wire-poll`

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Large file list renders without freezing
    Tool: Bash
    Steps:
      1. Create test repo with 500 modified files
      2. Open in Gitty
      3. Measure time to first render (via performance.now() in Electron DevTools)
      4. Assert: initial render < 3 seconds
      5. Assert: scrolling is smooth (no jank)
    Expected Result: Large repos handled performantly
    Evidence: Performance measurements captured

  Scenario: Memory usage within bounds
    Tool: Bash
    Steps:
      1. Launch Gitty
      2. Open 5 different repos sequentially
      3. Run: ps -o rss -p $(pgrep -f Gitty) | tail -1
      4. Assert: RSS < 512000 (500MB in KB)
    Expected Result: Memory stays under 500MB after multiple repo switches
    Evidence: Memory measurement captured
  ```

  **Commit**: YES
  - Message: `perf: add virtual scrolling, caching, and polling optimization`
  - Files: Modified components, `tests/Performance/`, cache configuration
  - Pre-commit: `php artisan test`

---

- [ ] 19. NativePHP Packaging + macOS .dmg

  **What to do**:
  - Configure NativePHP build settings:
    - App name: "Gitty"
    - App ID: "com.gitty.app" (or user's domain)
    - Version: 1.0.0
    - Icon: Create simple app icon (Git-themed)
    - Build target: macOS (darwin)
  - Configure app startup:
    - On launch: show "Open Repository" dialog if no recent repo
    - On launch with recent repo: re-open last used repo
    - Validate git installation on first launch
  - Configure NativePHP features:
    - System tray icon (optional, shows current branch)
    - Native notifications for: commit success, push/pull complete, fetch errors
    - About dialog with version info
  - Build the .dmg:
    - Run: `php artisan native:build --platform=mac`
    - Test the built .dmg on a clean macOS system (or VM)
    - Verify all features work in production build
  - Create `README.md` with:
    - Screenshots of the app
    - Installation instructions
    - Development setup instructions
    - Keyboard shortcuts reference
  - Final integration test: run ALL Pest tests against production build
  - Run full Playwright QA suite against production build

  **Must NOT do**:
  - Do NOT set up auto-updates (post-MVP)
  - Do NOT set up code signing (post-MVP)
  - Do NOT build for Windows/Linux (macOS only for now)
  - Do NOT publish to any store

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: NativePHP build configuration, production testing, packaging
  - **Skills**: [`playwright`]
    - `playwright`: Final QA testing against the production build

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 6 (sequential, final task)
  - **Blocks**: None (final task)
  - **Blocked By**: ALL previous tasks

  **References**:

  **External References**:
  - NativePHP build: `https://nativephp.com/docs/desktop/2/publishing/building`
  - NativePHP distribution: `https://nativephp.com/docs/desktop/2/publishing/distribution`

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: .dmg builds successfully
    Tool: Bash
    Steps:
      1. Run: php artisan native:build --platform=mac
      2. Assert: exit code 0
      3. Assert: .dmg file exists in dist/ directory
      4. Assert: file size > 100MB (includes Electron + PHP runtime)
    Expected Result: macOS .dmg created
    Evidence: Build output + file size

  Scenario: Production app opens and shows repo
    Tool: Playwright (playwright skill)
    Preconditions: .dmg installed, test repo at /tmp/gitty-final-test
    Steps:
      1. Launch Gitty.app
      2. Open repo via file dialog (select /tmp/gitty-final-test)
      3. Assert: staging panel visible
      4. Assert: current branch shown
      5. Assert: sidebar sections visible
      6. Stage a file, type commit message, commit
      7. Assert: commit appears in git log
      8. Screenshot: .sisyphus/evidence/task-19-production-app.png
    Expected Result: Production build fully functional
    Evidence: .sisyphus/evidence/task-19-production-app.png

  Scenario: All Pest tests pass
    Tool: Bash
    Steps:
      1. Run: php artisan test
      2. Assert: exit code 0
      3. Assert: 0 failures
    Expected Result: Full test suite green
    Evidence: Test output
  ```

  **Commit**: YES
  - Message: `release: package Gitty as macOS .dmg with NativePHP build`
  - Files: NativePHP config, README.md, build artifacts
  - Pre-commit: `php artisan test`

---

## Commit Strategy

| After Task | Message | Key Files | Verification |
|------------|---------|-----------|--------------|
| 1 | `feat(scaffold): initialize Laravel + NativePHP + Livewire + Flux project` | All scaffolded files | `php artisan test` (0 tests) |
| 2 | `test(infra): set up Pest testing framework with git fixtures and mocks` | `tests/` | `php artisan test` (1+ tests) |
| 3 | `feat(git): implement git service layer with full operation coverage` | `app/Services/Git/`, `app/DTOs/`, `tests/` | `php artisan test` (42+ tests) |
| 4 | `feat(ui): add file-level staging panel with status indicators` | `app/Livewire/StagingPanel.php`, views, tests | `php artisan test` |
| 5 | `feat(ui): add diff viewer with Shiki syntax highlighting` | `app/Livewire/DiffViewer.php`, views, tests | `php artisan test` |
| 6 | `feat(staging): add hunk-level staging/unstaging in diff viewer` | `app/Services/Git/DiffService.php`, views, tests | `php artisan test` |
| 7 | `feat(ui): add commit panel with message input, amend, and commit+push` | `app/Livewire/CommitPanel.php`, views, tests | `php artisan test` |
| 8 | `feat(branches): add branch management with switch, create, delete, merge` | `app/Livewire/BranchManager.php`, views, tests | `php artisan test` |
| 9 | `feat(sync): add push/pull/fetch with progress indicators and error handling` | `app/Livewire/SyncPanel.php`, views, tests | `php artisan test` |
| 10 | `feat(stash): add stash management with create, apply, pop, drop` | `app/Livewire/StashPanel.php`, views, tests | `php artisan test` |
| 11 | `feat(ui): add three-panel app layout with repository sidebar` | `app/Livewire/AppLayout.php`, `RepoSidebar.php`, views, tests | `php artisan test` |
| 12 | `feat(ui): add custom file tree component with expand/collapse and keyboard nav` | `resources/`, `app/Helpers/`, tests | `php artisan test` |
| 13 | `feat(repo): add multi-repo quick switch with recent repos list` | `app/Livewire/RepoSwitcher.php`, `app/Models/`, tests | `php artisan test` |
| 14 | `feat(sync): add auto-fetch background operation with configurable interval` | `app/Services/AutoFetchService.php`, tests | `php artisan test` |
| 15 | `feat(settings): add settings panel with auto-fetch, editor, theme config` | `app/Livewire/SettingsModal.php`, `app/Services/SettingsService.php`, tests | `php artisan test` |
| 16 | `feat(ux): add VS Code-compatible keyboard shortcuts` | `resources/js/`, NativeAppServiceProvider, tests | `php artisan test` |
| 17 | `feat(errors): add comprehensive error handling and edge case coverage` | `app/Services/Git/GitErrorHandler.php`, all components, tests | `php artisan test` |
| 18 | `perf: add virtual scrolling, caching, and polling optimization` | Modified components, `tests/Performance/` | `php artisan test` |
| 19 | `release: package Gitty as macOS .dmg with NativePHP build` | NativePHP config, README.md | `php artisan test` + build |

---

## Success Criteria

### Verification Commands
```bash
# All tests pass
php artisan test                          # Expected: 100+ tests, 0 failures

# App builds
php artisan native:build --platform=mac   # Expected: .dmg in dist/

# Git operations work (against real test repo)
git init /tmp/gitty-verify
echo "test" > /tmp/gitty-verify/file.txt
git -C /tmp/gitty-verify add file.txt
git -C /tmp/gitty-verify commit -m "init"
# Open in Gitty → verify staging, commit, branch operations work
```

### Final Checklist
- [ ] All "Must Have" features present and functional
- [ ] All "Must NOT Have" items absent (no scope creep)
- [ ] All Pest tests pass (100+ tests, 0 failures)
- [ ] App launches in < 3 seconds
- [ ] Memory usage < 500MB with 10 recent repos
- [ ] Diff renders in < 1 second for 1000-line files
- [ ] Keyboard shortcuts all functional
- [ ] Dark mode works
- [ ] Error handling covers: detached HEAD, merge conflicts, corrupted repo, no git, empty repo
- [ ] .dmg builds and installs on macOS
- [ ] All QA screenshots captured in `.sisyphus/evidence/`
