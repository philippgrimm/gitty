# Livewire Components Reference

Complete reference for all 18 Livewire components in gitty.

## Table of Contents

- [Component Hierarchy](#component-hierarchy)
- [HandlesGitOperations Trait](#handlesgitoperations-trait)
- [Core Components](#core-components)
  - [AppLayout](#applayout)
  - [StagingPanel](#stagingpanel)
  - [CommitPanel](#commitpanel)
  - [DiffViewer](#diffviewer)
- [Header Components](#header-components)
  - [RepoSwitcher](#reposwitcher)
  - [BranchManager](#branchmanager)
  - [SyncPanel](#syncpanel)
- [Sidebar Components](#sidebar-components)
  - [RepoSidebar](#reposidebar)
- [History & Search](#history--search)
  - [HistoryPanel](#historypanel)
  - [SearchPanel](#searchpanel)
  - [BlameView](#blameview)
- [Conflict & Rebase](#conflict--rebase)
  - [ConflictResolver](#conflictresolver)
  - [RebasePanel](#rebasepanel)
- [Overlay Components](#overlay-components)
  - [CommandPalette](#commandpalette)
  - [ErrorBanner](#errorbanner)
  - [ShortcutHelp](#shortcuthelp)
  - [SettingsModal](#settingsmodal)
- [Utility Components](#utility-components)
  - [AutoFetchIndicator](#autofetchindicator)

---

## Component Hierarchy

```
AppLayout (root)
├── Header
│   ├── RepoSwitcher
│   ├── BranchManager
│   └── SyncPanel
├── Sidebar (collapsible)
│   └── RepoSidebar
├── Work Area
│   ├── StagingPanel
│   ├── CommitPanel
│   └── DiffViewer / HistoryPanel / BlameView (tabbed)
└── Overlays (global)
    ├── ErrorBanner
    ├── CommandPalette
    ├── ShortcutHelp
    ├── SettingsModal
    ├── SearchPanel
    ├── ConflictResolver
    └── RebasePanel
```

**Data Flow:**
- `AppLayout` resolves `repoPath` and passes it to all child components
- Components communicate via Livewire events (not Laravel events)
- Services are instantiated per-request: `new GitService($this->repoPath)`
- `HandlesGitOperations` trait wraps operations in try/catch, dispatches events

---

## HandlesGitOperations Trait

**File:** `app/Livewire/Concerns/HandlesGitOperations.php`

Provides standardized error handling for git operations.

### Method

```php
protected function executeGitOperation(
    callable $operation,
    bool $dispatchStatusUpdate = true
): mixed
```

**Behavior:**
1. Executes `$operation()` in try/catch block
2. On success: clears `$this->error`, optionally dispatches `status-updated`
3. On failure: translates error via `GitErrorHandler`, dispatches `show-error`
4. Returns operation result or `null` on failure

**Used By:**
- `StagingPanel` (stage/unstage/discard operations)
- `DiffViewer` (hunk/line staging)
- `SyncPanel` (push/pull/fetch operations)

**Example:**
```php
$this->executeGitOperation(function () use ($file) {
    $stagingService = new StagingService($this->repoPath);
    $stagingService->stageFile($file);
    $this->refreshStatus();
    $this->dispatchStatusUpdate();
}, dispatchStatusUpdate: false);
```

---

## Core Components

### AppLayout

**File:** `app/Livewire/AppLayout.php`  
**View:** `resources/views/livewire/app-layout.blade.php`

Root component that manages repository path resolution and sidebar state.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | `''` | Absolute path to current git repository |
| `sidebarCollapsed` | `bool` | `false` | Sidebar visibility state |
| `previousRepoPath` | `?string` | `null` | Previous repo path (private, for cache invalidation) |

#### Actions

- `mount(?string $repoPath = null)` — Resolves repo path from parameter, RepoManager, or most recent repo
- `toggleSidebar()` — Toggles sidebar visibility
- `loadMostRecentRepo(): string` — Loads most recent valid repo from RepoManager

#### Computed Properties

- `statusBarData` — Returns `['branch', 'ahead', 'behind']` from GitService

#### Events Listened

- `palette-toggle-sidebar` → `handlePaletteToggleSidebar()`
- `repo-switched` → `handleRepoSwitched(string $path)`

#### Events Dispatched

- `show-error` — When git binary not found or repo invalid

#### Services Used

- `GitConfigValidator` — Checks git binary availability
- `RepoManager` — Loads current/recent repos
- `GitService` — Fetches status for status bar
- `GitCacheService` — Invalidates cache on repo switch

#### Notes

- Invalidates all cache groups for previous repo on switch
- Falls back to empty string if no valid repos found
- Validates `.git` directory existence before setting `repoPath`

---

### StagingPanel

**File:** `app/Livewire/StagingPanel.php`  
**View:** `resources/views/livewire/staging-panel.blade.php`

Complex component managing file staging, tree/flat view, multi-select, and hash-based refresh optimization.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `unstagedFiles` | `Collection` | `collect()` | Unstaged changed files |
| `stagedFiles` | `Collection` | `collect()` | Staged files |
| `untrackedFiles` | `Collection` | `collect()` | Untracked files |
| `treeView` | `bool` | `false` | Tree view enabled |
| `error` | `string` | `''` | Last error message |
| `lastStatusHash` | `string` | `''` | MD5 hash of status (locked, prevents tampering) |
| `lastAheadBehind` | `?array` | `null` | Cached ahead/behind counts (private) |

#### Actions

- `mount()` — Initializes collections, calls `refreshStatus()`
- `refreshStatus()` — Fetches status, rebuilds file collections, skips if hash unchanged
- `stageFile(string $file)` — Stages single file
- `unstageFile(string $file)` — Unstages single file
- `stageAll()` — Stages all unstaged + untracked files
- `unstageAll()` — Unstages all staged files
- `discardFile(string $file)` — Discards unstaged changes
- `discardAll()` — Discards all unstaged changes
- `selectFile(string $file, bool $staged)` — Dispatches `file-selected` event
- `stageSelected(array $files)` — Stages multiple files (multi-select)
- `unstageSelected(array $files)` — Unstages multiple files
- `discardSelected(array $files)` — Discards multiple files
- `stashSelected(array $files)` — Stashes specific files
- `stashAll()` — Stashes all changes with message
- `toggleView()` — Switches between tree/flat view
- `dispatchStatusUpdate()` — Dispatches `status-updated` with staged count + ahead/behind

#### Events Listened

- `keyboard-stage-all` → `stageAll()`
- `keyboard-unstage-all` → `unstageAll()`
- `palette-discard-all` → `handlePaletteDiscardAll()`
- `palette-toggle-view` → `handlePaletteToggleView()`
- `refresh-staging` → `handleRefreshStaging()`

#### Events Dispatched

- `file-selected` — When file clicked (payload: `file`, `staged`)
- `status-updated` — After operations (payload: `stagedCount`, `aheadBehind`)
- `stash-created` — After stash operations
- `show-error` — On git errors

#### Services Used

- `GitService` — Fetches status, current branch
- `StagingService` — Stage/unstage/discard operations
- `StashService` — Stash operations
- `FileTreeBuilder` — Builds tree structure for tree view

#### Notes

- Hash-based refresh prevents unnecessary re-renders when status unchanged
- `lastStatusHash` is `#[Locked]` to prevent client-side tampering
- File collections store arrays (not DTOs) for Livewire serialization
- Tree view built on-demand in `render()` method
- Multi-select operations batch files into single service call

---

### CommitPanel

**File:** `app/Livewire/CommitPanel.php`  
**View:** `resources/views/livewire/commit-panel.blade.php`

Manages commit message input, templates, history cycling, amend mode, and undo last commit.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `message` | `string` | `''` | Current commit message |
| `isAmend` | `bool` | `false` | Amend mode enabled |
| `stagedCount` | `int` | `0` | Number of staged files |
| `lastCommitMessage` | `?string` | `null` | Last commit message (for undo) |
| `currentPrefill` | `string` | `''` | Current prefill template |
| `commitHistory` | `array<int, string>` | `[]` | Recent commit messages from git log |
| `historyIndex` | `int` | `-1` | Current position in history cycling |
| `draftMessage` | `string` | `''` | Saved draft when cycling history |
| `storedHistory` | `array<int, string>` | `[]` | Stored commit messages from settings |
| `error` | `?string` | `null` | Last error message |
| `showUndoConfirmation` | `bool` | `false` | Undo modal visible |
| `lastCommitPushed` | `bool` | `false` | Last commit pushed to remote |

#### Actions

- `mount()` — Loads staged count, prefill, commit history, stored history
- `loadCommitHistory()` — Loads last 10 commit messages from git log
- `loadStoredHistory()` — Loads stored messages from SettingsService
- `cycleHistory(string $direction)` — Cycles through stored history (up/down)
- `selectHistoryMessage(string $message)` — Applies message from history dropdown
- `commit()` — Creates commit (or amend), saves to history, dispatches events
- `commitAndPush()` — Commits and pushes in single operation
- `toggleAmend()` — Toggles amend mode, loads last commit message
- `getCommitPrefill(): string` — Generates prefill from branch name (e.g., `feat(JIRA-123): `)
- `getTemplates(): array` — Returns 10 commit type templates + custom template
- `applyTemplate(string $prefix)` — Applies template prefix to message
- `promptUndoLastCommit()` — Shows undo confirmation modal
- `confirmUndoLastCommit()` — Executes undo (soft reset)

#### Events Listened

- `status-updated` → `refreshStagedCount(int $stagedCount, array $aheadBehind)`
- `keyboard-commit` → `handleKeyboardCommit()`
- `keyboard-commit-push` → `handleKeyboardCommitPush()`
- `palette-toggle-amend` → `handlePaletteToggleAmend()`
- `palette-undo-last-commit` → `promptUndoLastCommit()`

#### Events Dispatched

- `committed` — After successful commit
- `prefill-updated` — After commit (clears prefill)
- `status-updated` — After undo commit
- `show-error` — On errors or success messages

#### Services Used

- `GitService` — Fetches status, current branch, config values
- `CommitService` — Commit, amend, undo operations
- `SettingsService` — Stores/retrieves commit message history

#### Notes

- Prefill auto-generated from branch name pattern: `(feature|bugfix)/(TICKET-123)`
- History cycling saves draft on first up-arrow press
- Custom template loaded from `.gitmessage` or `commit.template` config
- Undo blocked for merge commits, warns if commit pushed to remote
- Settings table may not exist in test environment (graceful fallback)

---

### DiffViewer

**File:** `app/Livewire/DiffViewer.php`  
**View:** `resources/views/livewire/diff-viewer.blade.php`

Displays file diffs with hunk/line staging, image diffs, split view, and language detection.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `file` | `?string` | `null` | Currently selected file |
| `isStaged` | `bool` | `false` | Viewing staged diff |
| `diffData` | `?array` | `null` | Diff metadata (status, additions, deletions) |
| `files` | `?array` | `null` | Parsed diff files with hunks/lines |
| `isEmpty` | `bool` | `true` | No diff to display |
| `isBinary` | `bool` | `false` | File is binary |
| `isLargeFile` | `bool` | `false` | File exceeds 1MB |
| `isImage` | `bool` | `false` | File is image |
| `imageData` | `?array` | `null` | Image data (old/new base64, sizes) |
| `error` | `string` | `''` | Last error message |
| `diffViewMode` | `string` | `'unified'` | Diff view mode (unified/split) |

#### Actions

- `mount()` — Initializes empty state
- `toggleDiffViewMode()` — Switches between unified/split view
- `openInEditor(?int $line = null)` — Opens file in configured editor
- `loadDiff(string $file, bool $staged)` — Loads diff for file
- `stageHunk(int $fileIndex, int $hunkIndex)` — Stages entire hunk
- `unstageHunk(int $fileIndex, int $hunkIndex)` — Unstages entire hunk
- `stageSelectedLines(int $fileIndex, int $hunkIndex, array $lineIndices)` — Stages specific lines
- `unstageSelectedLines(int $fileIndex, int $hunkIndex, array $lineIndices)` — Unstages specific lines
- `getSplitLines(array $hunk): array` — Converts unified hunk to split view pairs
- `hydrateDiffFileAndHunk(int $fileIndex, int $hunkIndex): array` — Rebuilds DTOs from arrays
- `refreshFileData(int $fileIndex)` — Reloads diff after staging operation
- `mapExtensionToLanguage(string $extension): string` — Maps file extension to syntax highlighting language

#### Events Listened

- `file-selected` → `onFileSelected(string $file, bool $staged)`
- `palette-toggle-diff-view` → `handlePaletteToggleDiffView()`
- `palette-open-in-editor` → `handlePaletteOpenInEditor()`

#### Events Dispatched

- `refresh-staging` — After hunk/line staging operations
- `show-error` — On errors or editor not configured

#### Services Used

- `GitService` — Fetches diff, file size, file content at HEAD
- `DiffService` — Stage/unstage hunks and lines
- `EditorService` — Opens file in external editor
- `SettingsService` — Retrieves editor configuration

#### Notes

- Image files detected before diff parsing (extensions: png, jpg, gif, svg, webp, ico, bmp)
- Large files (>1MB) show warning instead of diff
- DTOs hydrated from arrays for service calls (Livewire serialization limitation)
- Split view pairs deletions/additions side-by-side
- Language detection supports 25+ file types for syntax highlighting
- Image diffs show old (HEAD) vs new (working directory) with size comparison

---

## Header Components

### RepoSwitcher

**File:** `app/Livewire/RepoSwitcher.php`  
**View:** `resources/views/livewire/repo-switcher.blade.php`

Manages repository switching and recent repos list.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `currentRepoPath` | `string` | `''` | Current repository path |
| `currentRepoName` | `string` | `''` | Current repository name |
| `recentRepos` | `array` | `[]` | Recent repositories list |
| `error` | `string` | `''` | Last error message |

#### Actions

- `mount()` — Loads current repo and recent repos
- `openRepo(string $path)` — Opens repository at path
- `switchRepo(int $id)` — Switches to recent repo by ID
- `removeRecentRepo(int $id)` — Removes repo from recent list
- `openFolderDialog()` — Opens native folder picker dialog
- `loadCurrentRepo()` — Loads current repo from RepoManager (private)
- `loadRecentRepos()` — Loads recent repos, sorts by current + last opened (private)

#### Events Listened

- `palette-open-folder` → `handlePaletteOpenFolder()`

#### Events Dispatched

- `repo-switched` — When repo changed (payload: `path`)

#### Services Used

- `RepoManager` — Manages current repo, recent repos, validation
- `Native\Desktop\Dialog` — Native folder picker

#### Notes

- Validates `.git` directory before switching
- Updates `last_opened_at` timestamp on switch
- Recent repos sorted by current repo first, then last opened
- Native dialog integration via NativePHP

---

### BranchManager

**File:** `app/Livewire/BranchManager.php`  
**View:** `resources/views/livewire/branch-manager.blade.php`

Manages branch switching, creation, deletion, merging with auto-stash modal.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `currentBranch` | `string` | — | Current branch name |
| `branches` | `array` | `[]` | All branches (local + remote) |
| `aheadBehind` | `array` | — | Ahead/behind counts |
| `isDetachedHead` | `bool` | `false` | Detached HEAD state |
| `error` | `string` | `''` | Last error message |
| `branchQuery` | `string` | `''` | Search query for filtering |
| `showAutoStashModal` | `bool` | `false` | Auto-stash modal visible |
| `autoStashTargetBranch` | `string` | `''` | Target branch for auto-stash |

#### Actions

- `mount()` — Initializes ahead/behind, calls `refreshBranches()`
- `refreshBranches()` — Loads branches, current branch, detached HEAD state
- `switchBranch(string $name)` — Switches branch, shows auto-stash modal if dirty
- `deleteBranch(string $name)` — Deletes branch (blocks current branch)
- `mergeBranch(string $name)` — Merges branch, shows conflict warning
- `confirmAutoStash()` — Stashes changes, switches branch, tries to restore stash
- `cancelAutoStash()` — Closes auto-stash modal

#### Computed Properties

- `filteredLocalBranches` — Local branches filtered by query, sorted by current first
- `filteredRemoteBranches` — Remote branches without local counterpart, filtered by query

#### Events Listened

- `palette-create-branch` → `handlePaletteCreateBranch(string $name)`

#### Events Dispatched

- `status-updated` — After branch operations
- `show-error` — On errors, warnings, success messages

#### Services Used

- `GitService` — Fetches status, detached HEAD check
- `BranchService` — Branch operations (switch, create, delete, merge)
- `StashService` — Auto-stash operations
- `GitCacheService` — Invalidates cache after auto-stash

#### Notes

- Auto-stash triggered on dirty tree error during switch
- Auto-stash tries to restore changes after switch, preserves stash on conflict
- Remote branches filtered to exclude those with local counterparts
- Merge conflicts show warning with file list
- Detached HEAD state blocks certain operations

---

### SyncPanel

**File:** `app/Livewire/SyncPanel.php`  
**View:** `resources/views/livewire/sync-panel.blade.php`

Manages push, pull, fetch operations with native notifications.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `isOperationRunning` | `bool` | `false` | Operation in progress |
| `operationOutput` | `string` | `''` | Last operation output |
| `error` | `string` | `''` | Last error message |
| `lastOperation` | `string` | `''` | Last operation name |
| `aheadBehind` | `array` | `['ahead' => 0, 'behind' => 0]` | Ahead/behind counts |

#### Actions

- `mount()` — Initializes state, loads ahead/behind
- `syncPush()` — Pushes to origin, shows notification
- `syncPull()` — Pulls from origin, shows notification
- `syncFetch()` — Fetches from origin
- `syncFetchAll()` — Fetches from all remotes
- `syncForcePushWithLease()` — Force pushes with lease
- `executeSyncOperation(callable $operation, string $operationName)` — Wraps sync operations (private)
- `refreshAheadBehindData()` — Reloads ahead/behind counts (private)

#### Events Listened

- `status-updated` → `refreshAheadBehind(int $stagedCount, array $aheadBehind)`
- `remote-updated` → `refreshAheadBehind(int $stagedCount, array $aheadBehind)`
- `palette-push` → `handlePalettePush()`
- `palette-pull` → `handlePalettePull()`
- `palette-fetch` → `handlePaletteFetch()`
- `palette-fetch-all` → `handlePaletteFetchAll()`
- `palette-force-push` → `handlePaletteForcePush()`

#### Events Dispatched

- `status-updated` — After successful operations (payload: `stagedCount`, `aheadBehind`)

#### Services Used

- `GitService` — Fetches status, current branch, detached HEAD check
- `RemoteService` — Push, pull, fetch operations
- `NotificationService` — Native desktop notifications

#### Notes

- Blocks push/pull in detached HEAD state
- Native notifications show commit counts and branch names
- Force push uses `--force-with-lease` for safety
- Operation output captured for debugging

---

## Sidebar Components

### RepoSidebar

**File:** `app/Livewire/RepoSidebar.php`  
**View:** `resources/views/livewire/repo-sidebar.blade.php`

Displays branches, remotes, tags, stashes with hash-based refresh optimization.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `branches` | `array` | `[]` | Local branches |
| `remotes` | `array` | `[]` | Remote configurations |
| `tags` | `array` | `[]` | Git tags |
| `stashes` | `array` | `[]` | Stash list |
| `currentBranch` | `string` | `''` | Current branch name |
| `showAutoStashModal` | `bool` | `false` | Auto-stash modal visible |
| `autoStashTargetBranch` | `string` | `''` | Target branch for auto-stash |
| `showCreateTagModal` | `bool` | `false` | Create tag modal visible |
| `newTagName` | `string` | `''` | New tag name input |
| `newTagMessage` | `?string` | `null` | New tag message (annotated) |
| `newTagCommit` | `?string` | `null` | Target commit for tag |
| `lastSidebarHash` | `?string` | `null` | MD5 hash of sidebar data (private) |

#### Actions

- `mount()` — Calls `refreshSidebar()`
- `refreshSidebar()` — Loads branches, remotes, tags, stashes, skips if hash unchanged
- `switchBranch(string $name)` — Switches branch, shows auto-stash modal if dirty
- `applyStash(int $index)` — Applies stash without removing
- `popStash(int $index)` — Applies and removes stash
- `dropStash(int $index)` — Removes stash without applying
- `confirmAutoStash()` — Stashes changes, switches branch, tries to restore
- `cancelAutoStash()` — Closes auto-stash modal
- `createTag()` — Creates tag (lightweight or annotated)
- `deleteTag(string $name)` — Deletes local tag
- `pushTag(string $name)` — Pushes tag to remote

#### Events Listened

- `status-updated` → `handleStatusUpdated()`
- `stash-created` → `handleStashCreated()`
- `palette-create-tag` → `handlePaletteCreateTag()`

#### Events Dispatched

- `status-updated` — After branch/stash operations
- `refresh-staging` — After stash apply/pop
- `show-error` — On errors or success messages

#### Services Used

- `GitService` — Fetches status
- `BranchService` — Branch operations, switch
- `RemoteService` — Fetches remote configurations
- `TagService` — Tag operations (create, delete, push)
- `StashService` — Stash operations (apply, pop, drop, list)
- `GitCacheService` — Invalidates cache after auto-stash

#### Notes

- Hash-based refresh prevents unnecessary re-renders
- Auto-stash flow identical to BranchManager
- Tags support lightweight and annotated (with message)
- Stash list shows index, message, branch, SHA
- Remote branches excluded from sidebar (shown in BranchManager)

---

## History & Search

### HistoryPanel

**File:** `app/Livewire/HistoryPanel.php`  
**View:** `resources/views/livewire/history-panel.blade.php`

Displays commit history with graph, pagination, reset/revert/cherry-pick modals.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `commitsCount` | `int` | `0` | Total commits loaded |
| `page` | `int` | `1` | Current page |
| `perPage` | `int` | `100` | Commits per page |
| `hasMore` | `bool` | `false` | More commits available |
| `selectedCommitSha` | `?string` | `null` | Selected commit SHA |
| `showGraph` | `bool` | `true` | Show commit graph |
| `showResetModal` | `bool` | `false` | Reset modal visible |
| `showRevertModal` | `bool` | `false` | Revert modal visible |
| `showCherryPickModal` | `bool` | `false` | Cherry-pick modal visible |
| `resetTargetSha` | `?string` | `null` | Target commit for reset/revert |
| `resetTargetMessage` | `?string` | `null` | Target commit message |
| `cherryPickTargetSha` | `?string` | `null` | Target commit for cherry-pick |
| `cherryPickTargetMessage` | `?string` | `null` | Target commit message |
| `resetMode` | `string` | `'soft'` | Reset mode (soft/mixed/hard) |
| `hardResetConfirmText` | `string` | `''` | Confirmation text for hard reset |
| `targetCommitPushed` | `bool` | `false` | Target commit pushed to remote |
| `rebaseCommitCount` | `int` | `5` | Number of commits for rebase |
| `commits` | `?Collection` | `null` | Loaded commits (private) |

#### Actions

- `mount(string $repoPath)` — Initializes commits collection, loads commits
- `loadCommits()` — Loads commits page, checks for more (private)
- `loadMore()` — Increments page, loads next batch
- `selectCommit(string $sha)` — Dispatches `commit-selected` event
- `promptReset(string $sha, string $message)` — Shows reset modal
- `confirmReset()` — Executes reset (soft/mixed/hard)
- `promptRevert(string $sha, string $message)` — Shows revert modal
- `confirmRevert()` — Executes revert (creates new commit)
- `promptCherryPick(string $sha, string $message)` — Shows cherry-pick modal
- `confirmCherryPick()` — Executes cherry-pick
- `promptInteractiveRebase(string $sha)` — Dispatches `open-rebase-modal` event
- `isCommitPushed(string $sha): bool` — Checks if commit on remote (private)
- `refreshCommitList()` — Resets page, reloads commits (private)

#### Events Listened

- `repo-switched` → `handleRepoSwitched(string $path)`
- `status-updated` → `handleStatusUpdated()`

#### Events Dispatched

- `commit-selected` — When commit clicked (payload: `sha`)
- `status-updated` — After reset/revert/cherry-pick
- `refresh-staging` — After reset
- `show-success` — On successful operations
- `show-error` — On errors or validation failures
- `open-rebase-modal` — For interactive rebase (payload: `ontoCommit`, `count`)

#### Services Used

- `GitService` — Fetches commit log, status
- `GraphService` — Generates commit graph data
- `ResetService` — Reset (soft/mixed/hard), revert operations
- `CommitService` — Cherry-pick operation
- `BranchService` — Checks if commit on remote

#### Notes

- Hard reset requires typing "DISCARD" for confirmation
- Reset modal warns if target commit pushed to remote
- Cherry-pick shows conflict warning if conflicts detected
- Graph data loaded separately in `render()` method
- Pagination loads 100 commits per page
- Revert creates new commit (safe operation)

---

### SearchPanel

**File:** `app/Livewire/SearchPanel.php`  
**View:** `resources/views/livewire/search-panel.blade.php`

Searches commits, file content, and file names.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | `''` | Repository path |
| `query` | `string` | `''` | Search query |
| `scope` | `string` | `'commits'` | Search scope (commits/content/files) |
| `results` | `array` | `[]` | Search results |
| `isOpen` | `bool` | `false` | Panel visible |
| `selectedIndex` | `int` | `0` | Selected result index |

#### Actions

- `open()` — Opens panel, resets state
- `close()` — Closes panel, clears results
- `setScope(string $scope)` — Changes search scope, re-runs search
- `updatedQuery()` — Livewire hook, searches when query ≥3 chars
- `search()` — Executes search via SearchService
- `selectResult(string $identifier)` — Dispatches `file-selected` or `commit-selected`

#### Events Listened

- `open-search` → `open()`

#### Events Dispatched

- `file-selected` — When file result selected (payload: `path`)
- `commit-selected` — When commit result selected (payload: `sha`)

#### Services Used

- `SearchService` — Searches commits, content, files

#### Notes

- Minimum query length: 3 characters
- Scope options: commits (messages), content (file contents), files (file names)
- Results dispatched to appropriate components (DiffViewer or HistoryPanel)

---

### BlameView

**File:** `app/Livewire/BlameView.php`  
**View:** `resources/views/livewire/blame-view.blade.php`

Displays git blame for file (line-by-line authorship).

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `file` | `?string` | `null` | File path |
| `blameData` | `?array` | `null` | Blame lines (commit, author, date, content) |
| `error` | `string` | `''` | Last error message |

#### Actions

- `mount(string $repoPath)` — Initializes repo path
- `loadBlame(string $file)` — Loads blame data for file
- `selectCommit(string $sha)` — Dispatches `commit-selected`, toggles history panel

#### Events Listened

- `show-blame` → `loadBlame(string $file)`
- `repo-switched` → `handleRepoSwitched(string $path)`

#### Events Dispatched

- `commit-selected` — When commit SHA clicked (payload: `sha`)
- `toggle-history-panel` — Switches to history panel
- `show-error` — On errors

#### Services Used

- `BlameService` — Fetches blame data

#### Notes

- Blame data includes commit SHA (short + full), author, date, line number, content
- Clicking commit SHA switches to history panel and selects commit

---

## Conflict & Rebase

### ConflictResolver

**File:** `app/Livewire/ConflictResolver.php`  
**View:** `resources/views/livewire/conflict-resolver.blade.php`

Resolves merge conflicts with three-way merge view.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `conflictFiles` | `array` | `[]` | List of conflicted files |
| `selectedFile` | `?string` | `null` | Currently selected file |
| `oursContent` | `string` | `''` | Current branch version |
| `theirsContent` | `string` | `''` | Merging branch version |
| `baseContent` | `string` | `''` | Common ancestor version |
| `resultContent` | `string` | `''` | Resolved content (editable) |
| `showAbortModal` | `bool` | `false` | Abort merge modal visible |
| `isBinary` | `bool` | `false` | File is binary |
| `isInMergeState` | `bool` | `false` | Repository in merge state |
| `mergeHeadBranch` | `string` | `''` | Branch being merged |

#### Actions

- `mount(string $repoPath)` — Initializes repo path, checks merge state
- `selectFile(string $path)` — Loads conflict versions for file
- `acceptOurs()` — Sets result to ours content
- `acceptTheirs()` — Sets result to theirs content
- `acceptBoth()` — Concatenates ours + theirs
- `resolveFile()` — Writes result, stages file, refreshes list
- `abortMerge()` — Shows abort confirmation modal
- `confirmAbortMerge()` — Aborts merge, clears state
- `cancelAbortMerge()` — Closes abort modal
- `checkMergeState()` — Checks if in merge state, loads conflicts (private)
- `refreshConflictList()` — Reloads conflict files (private)

#### Events Listened

- `status-updated` → `handleStatusUpdated()`
- `repo-switched` → `handleRepoSwitched(string $path)`
- `palette-abort-merge` → `handlePaletteAbortMerge()`

#### Events Dispatched

- `refresh-staging` — After resolve/abort
- `status-updated` — After abort
- `show-error` — On errors or success messages

#### Services Used

- `ConflictService` — Checks merge state, gets conflict versions, resolves, aborts

#### Notes

- Three-way merge view: ours, theirs, base, result
- Result content editable for manual resolution
- Auto-selects first conflict file on mount
- Binary files show warning (cannot resolve in UI)
- Abort merge clears all conflict state

---

### RebasePanel

**File:** `app/Livewire/RebasePanel.php`  
**View:** `resources/views/livewire/rebase-panel.blade.php`

Interactive rebase with commit reordering and action selection.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `showRebaseModal` | `bool` | `false` | Rebase modal visible |
| `ontoCommit` | `string` | `''` | Target commit for rebase |
| `commitCount` | `int` | `5` | Number of commits to rebase |
| `rebasePlan` | `array` | `[]` | Rebase plan (commits + actions) |
| `isRebasing` | `bool` | `false` | Rebase in progress |
| `showForceWarning` | `bool` | `false` | Commits pushed to remote |

#### Actions

- `mount(string $repoPath)` — Initializes repo path, checks rebase state
- `openRebaseModal(string $ontoCommit, int $count = 5)` — Loads commits, shows modal
- `updateAction(int $index, string $action)` — Changes action for commit (pick/squash/fixup/drop)
- `reorderCommits(array $newOrder)` — Reorders commits in plan
- `startRebase()` — Executes rebase with plan
- `continueRebase()` — Continues rebase after conflict resolution
- `abortRebase()` — Aborts rebase, returns to original state
- `checkRebaseState()` — Checks if rebase in progress (private)
- `checkIfCommitsPushed(): bool` — Checks if commits on remote (private)

#### Events Listened

- `repo-switched` → `handleRepoSwitched(string $path)`
- `status-updated` → `handleStatusUpdated()`
- `open-rebase-modal` → `openRebaseModal(string $ontoCommit, int $count)`
- `palette-continue-rebase` → `continueRebase()`
- `palette-abort-rebase` → `abortRebase()`

#### Events Dispatched

- `status-updated` — After rebase operations
- `show-success` — On successful operations
- `show-warning` — If force push required
- `show-error` — On errors

#### Services Used

- `RebaseService` — Rebase operations (start, continue, abort, get commits)
- `BranchService` — Checks if commits on remote

#### Notes

- Rebase plan includes commit SHA, message, action (pick/squash/fixup/drop)
- Commits can be reordered via drag-and-drop (frontend)
- Force push warning shown if commits already pushed
- Rebase state persists across page reloads

---

## Overlay Components

### CommandPalette

**File:** `app/Livewire/CommandPalette.php`  
**View:** `resources/views/livewire/command-palette.blade.php`

Fuzzy command search with 28 commands, input mode, disabled state.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `isOpen` | `bool` | `false` | Palette visible |
| `mode` | `string` | `'search'` | Mode (search/input) |
| `query` | `string` | `''` | Search query |
| `inputValue` | `string` | `''` | Input value (for input mode) |
| `inputCommand` | `?string` | `null` | Command requiring input |
| `inputError` | `?string` | `null` | Input validation error |
| `repoPath` | `string` | `''` | Repository path |
| `stagedCount` | `int` | `0` | Staged files count |

#### Actions

- `open()` — Opens palette in search mode
- `toggle()` — Toggles palette visibility
- `close()` — Closes palette, resets state
- `getDisabledCommands(): array` — Returns disabled command IDs
- `getCommands(): array` — Returns all 28 commands (static)
- `executeCommand(string $commandId)` — Executes command or switches to input mode
- `submitInput()` — Validates and submits input (e.g., branch name)
- `cancelInput()` — Returns to search mode

#### Computed Properties

- `filteredCommands` — Commands filtered by query, marked as disabled

#### Events Listened

- `open-command-palette` → `open()`
- `toggle-command-palette` → `toggle()`
- `status-updated` → `handleStatusUpdated(int $stagedCount, array $aheadBehind)`
- `repo-switched` → `handleRepoSwitched(string $path)`
- `open-command-palette-create-branch` → `openCreateBranch()`

#### Events Dispatched

- All command events (e.g., `keyboard-stage-all`, `palette-push`, `open-settings`)

#### Services Used

None (dispatches events to other components)

#### Notes

- 28 commands total (see `getCommands()` for full list)
- Commands disabled when no repo open or no staged files (for commit)
- Input mode for commands requiring parameters (e.g., create branch)
- Fuzzy search matches label and keywords
- Keyboard shortcuts displayed in command list

---

### ErrorBanner

**File:** `app/Livewire/ErrorBanner.php`  
**View:** `resources/views/livewire/error-banner.blade.php`

Global error/success/warning banner.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `visible` | `bool` | `false` | Banner visible |
| `message` | `string` | `''` | Message text |
| `type` | `string` | `'error'` | Message type (error/success/warning) |
| `persistent` | `bool` | `false` | Requires manual dismiss |

#### Actions

- `showError(string $message, string $type = 'error', bool $persistent = false)` — Shows banner
- `dismiss()` — Hides banner

#### Events Listened

- `show-error` → `showError(string $message, string $type, bool $persistent)`

#### Events Dispatched

None

#### Services Used

None

#### Notes

- Non-persistent banners auto-dismiss after 5 seconds (frontend)
- Type determines color: error (red), success (green), warning (yellow)
- Persistent banners require manual dismiss

---

### ShortcutHelp

**File:** `app/Livewire/ShortcutHelp.php`  
**View:** `resources/views/livewire/shortcut-help.blade.php`

Keyboard shortcuts reference modal.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `showModal` | `bool` | `false` | Modal visible |

#### Actions

- `openModal()` — Shows modal
- `closeModal()` — Hides modal

#### Events Listened

- `open-shortcut-help` → `openModal()`

#### Events Dispatched

None

#### Services Used

None

#### Notes

- Shortcuts defined in view template (not component)
- Triggered by `⌘/` keyboard shortcut

---

### SettingsModal

**File:** `app/Livewire/SettingsModal.php`  
**View:** `resources/views/livewire/settings-modal.blade.php`

Application settings modal.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `autoFetchInterval` | `int` | — | Auto-fetch interval (minutes) |
| `externalEditor` | `string` | — | External editor command |
| `theme` | `string` | — | Theme (light/dark/auto) |
| `defaultBranch` | `string` | — | Default branch name |
| `confirmDiscard` | `bool` | — | Confirm before discard |
| `confirmForcePush` | `bool` | — | Confirm before force push |
| `showUntracked` | `bool` | — | Show untracked files |
| `diffContextLines` | `int` | — | Diff context lines |
| `notificationsEnabled` | `bool` | — | Enable notifications |
| `showModal` | `bool` | `false` | Modal visible |

#### Actions

- `mount()` — Loads settings
- `openModal()` — Shows modal
- `closeModal()` — Hides modal
- `save()` — Saves settings, dispatches events
- `updatedTheme(string $value)` — Livewire hook, dispatches theme update
- `resetToDefaults()` — Resets all settings to defaults
- `loadSettings()` — Loads settings from SettingsService (private)

#### Events Listened

- `open-settings` → `openModal()`

#### Events Dispatched

- `settings-updated` — After save
- `theme-updated` — After theme change (payload: `theme`)

#### Services Used

- `SettingsService` — Loads/saves settings

#### Notes

- Settings stored in database (settings table)
- Theme updates dispatched immediately on change (live preview)

---

## Utility Components

### AutoFetchIndicator

**File:** `app/Livewire/AutoFetchIndicator.php`  
**View:** `resources/views/livewire/auto-fetch-indicator.blade.php`

Auto-fetch background service indicator.

#### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `repoPath` | `string` | — | Repository path |
| `isActive` | `bool` | `false` | Auto-fetch enabled |
| `lastFetchAt` | `string` | `''` | Last fetch time (human-readable) |
| `lastError` | `string` | `''` | Last fetch error |
| `isFetching` | `bool` | `false` | Fetch in progress |
| `isQueueLocked` | `bool` | `false` | Operation queue locked |

#### Actions

- `mount()` — Calls `checkAndFetch()`
- `refreshStatus()` — Updates status from AutoFetchService
- `checkAndFetch()` — Checks if fetch needed, executes if due

#### Events Dispatched

- `remote-updated` — After successful fetch

#### Services Used

- `AutoFetchService` — Manages auto-fetch scheduling, execution
- `GitOperationQueue` — Checks queue lock status
- `NotificationService` — Shows fetch failure notifications

#### Notes

- Auto-fetch interval configured in settings
- Respects operation queue lock (prevents concurrent operations)
- Shows native notification on fetch failure
- Last fetch time displayed as relative time (e.g., "2 minutes ago")

---

## Event Reference

### Global Events

| Event | Payload | Dispatched By | Listened By |
|-------|---------|---------------|-------------|
| `repo-switched` | `path: string` | RepoSwitcher | AppLayout, HistoryPanel, ConflictResolver, RebasePanel, BlameView, CommandPalette |
| `status-updated` | `stagedCount: int`, `aheadBehind: array` | StagingPanel, BranchManager, SyncPanel, CommitPanel, HistoryPanel, ConflictResolver, RebasePanel, RepoSidebar | CommitPanel, SyncPanel, HistoryPanel, ConflictResolver, RebasePanel, RepoSidebar, CommandPalette |
| `file-selected` | `file: string`, `staged: bool` | StagingPanel, SearchPanel | DiffViewer |
| `commit-selected` | `sha: string` | HistoryPanel, SearchPanel, BlameView | (external components) |
| `show-error` | `message: string`, `type: string`, `persistent: bool` | All components | ErrorBanner |
| `refresh-staging` | — | DiffViewer, ConflictResolver, RepoSidebar, HistoryPanel | StagingPanel |
| `committed` | — | CommitPanel | (external components) |
| `stash-created` | — | StagingPanel | RepoSidebar |
| `remote-updated` | — | AutoFetchIndicator | SyncPanel |

### Command Palette Events

All `palette-*` events dispatched by CommandPalette, listened by respective components:

- `palette-toggle-sidebar` → AppLayout
- `palette-discard-all` → StagingPanel
- `palette-toggle-view` → StagingPanel
- `palette-toggle-amend` → CommitPanel
- `palette-undo-last-commit` → CommitPanel
- `palette-push` → SyncPanel
- `palette-pull` → SyncPanel
- `palette-fetch` → SyncPanel
- `palette-fetch-all` → SyncPanel
- `palette-force-push` → SyncPanel
- `palette-create-branch` → BranchManager
- `palette-open-folder` → RepoSwitcher
- `palette-toggle-diff-view` → DiffViewer
- `palette-open-in-editor` → DiffViewer
- `palette-abort-merge` → ConflictResolver
- `palette-continue-rebase` → RebasePanel
- `palette-abort-rebase` → RebasePanel
- `palette-create-tag` → RepoSidebar

### Keyboard Events

- `keyboard-stage-all` → StagingPanel
- `keyboard-unstage-all` → StagingPanel
- `keyboard-commit` → CommitPanel
- `keyboard-commit-push` → CommitPanel

### Modal Events

- `open-command-palette` → CommandPalette
- `toggle-command-palette` → CommandPalette
- `open-shortcut-help` → ShortcutHelp
- `open-settings` → SettingsModal
- `open-search` → SearchPanel
- `show-blame` → BlameView
- `open-rebase-modal` → RebasePanel

---

## Service Instantiation Pattern

All components instantiate services per-request (NOT dependency injection):

```php
$gitService = new GitService($this->repoPath);
$stagingService = new StagingService($this->repoPath);
```

Services are stateless and accept `repoPath` in constructor. This pattern allows components to work with multiple repositories without service container conflicts.

---

## Error Handling Pattern

Components using `HandlesGitOperations` trait:

```php
$this->executeGitOperation(function () use ($file) {
    $service = new StagingService($this->repoPath);
    $service->stageFile($file);
    $this->refreshStatus();
}, dispatchStatusUpdate: false);
```

Components NOT using trait:

```php
try {
    $service = new GitService($this->repoPath);
    $result = $service->operation();
} catch (\Exception $e) {
    $this->error = GitErrorHandler::translate($e->getMessage());
    $this->dispatch('show-error', message: $this->error, type: 'error');
}
```

All errors translated via `GitErrorHandler::translate()` before display.

---

## Hash-Based Refresh Optimization

Components using hash-based refresh (StagingPanel, RepoSidebar):

```php
$hash = md5(serialize($data));
if ($this->lastHash === $hash) {
    return; // Skip rebuild
}
$this->lastHash = $hash;
```

Prevents unnecessary re-renders when underlying data unchanged. Critical for performance in high-frequency refresh scenarios.

---

## Blade View Paths

All components follow convention: `resources/views/livewire/{component-name}.blade.php`

Example mappings:
- `AppLayout` → `livewire/app-layout.blade.php`
- `StagingPanel` → `livewire/staging-panel.blade.php`
- `CommandPalette` → `livewire/command-palette.blade.php`

---

## Testing Components

See `tests/Feature/Livewire/` for component tests. All components tested with Livewire's `Livewire::test()` helper.

Example:
```php
Livewire::test(StagingPanel::class, ['repoPath' => $this->repoPath])
    ->assertSet('stagedFiles', collect())
    ->call('stageFile', 'file.txt')
    ->assertDispatched('status-updated');
```

---

## Related Documentation

- [Architecture](architecture.md) — System architecture, layers, data flow
- [AGENTS.md](../AGENTS.md) — Design system, colors, icons, Flux UI patterns
- [Frontend](frontend.md) — Blade templates, Alpine.js, Flux components
