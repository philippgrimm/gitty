# Feature Documentation

Developer-focused documentation for all features in gitty. This document explains how each feature is implemented, which components and services are involved, and how to extend functionality.

## Table of Contents

- [Staging](#staging)
  - [File-Level Staging](#file-level-staging)
  - [Bulk Operations](#bulk-operations)
  - [Multi-Select](#multi-select)
  - [Tree View](#tree-view)
  - [Status Hash Optimization](#status-hash-optimization)
- [Hunk & Line Staging](#hunk--line-staging)
  - [Hunk Staging](#hunk-staging)
  - [Line Staging](#line-staging)
  - [Patch Generation](#patch-generation)
- [Committing](#committing)
  - [Message Input](#message-input)
  - [Conventional Commit Templates](#conventional-commit-templates)
  - [Branch-Based Prefill](#branch-based-prefill)
  - [Amend Mode](#amend-mode)
  - [Commit History Cycling](#commit-history-cycling)
  - [Undo Last Commit](#undo-last-commit)
- [Branch Management](#branch-management)
  - [Switch Branch](#switch-branch)
  - [Create Branch](#create-branch)
  - [Delete Branch](#delete-branch)
  - [Merge Branch](#merge-branch)
  - [Auto-Stash on Dirty Tree](#auto-stash-on-dirty-tree)
  - [Filtered Branch Properties](#filtered-branch-properties)
  - [Remote Branch Filtering](#remote-branch-filtering)
- [Diff Viewing](#diff-viewing)
  - [Unified vs Split Mode](#unified-vs-split-mode)
  - [Language Detection](#language-detection)
  - [Binary File Handling](#binary-file-handling)
  - [Image Diff](#image-diff)
  - [Large File Detection](#large-file-detection)
- [Stashing](#stashing)
  - [Create Stash](#create-stash)
  - [Apply/Pop/Drop](#applypop-drop)
  - [Stash Individual Files](#stash-individual-files)
  - [Auto-Generated Messages](#auto-generated-messages)
- [Push/Pull/Fetch](#pushpullfetch)
  - [Push](#push)
  - [Pull](#pull)
  - [Fetch](#fetch)
  - [Fetch All Remotes](#fetch-all-remotes)
  - [Force Push with Lease](#force-push-with-lease)
  - [Detached HEAD Guards](#detached-head-guards)
  - [Native Notifications](#native-notifications)
- [History](#history)
  - [Commit Log with Pagination](#commit-log-with-pagination)
  - [Commit Graph Visualization](#commit-graph-visualization)
  - [Select Commit](#select-commit)
  - [Reset (Soft/Mixed/Hard)](#reset-softmixedhard)
  - [Revert](#revert)
  - [Cherry-Pick](#cherry-pick)
- [Rebase](#rebase)
  - [Interactive Rebase Panel](#interactive-rebase-panel)
  - [Commit Reordering](#commit-reordering)
  - [Action Selection](#action-selection)
- [Search](#search)
  - [Commit Search](#commit-search)
  - [File Search](#file-search)
  - [Content Search](#content-search)
- [Blame](#blame)
  - [Git Blame View](#git-blame-view)
  - [Commit Navigation](#commit-navigation)
- [Conflict Resolution](#conflict-resolution)
  - [Conflict Detection](#conflict-detection)
  - [Resolver UI](#resolver-ui)
  - [Three-Way Merge](#three-way-merge)
- [Command Palette](#command-palette)
  - [28 Commands](#28-commands)
  - [Search/Filter](#searchfilter)
  - [Input Mode](#input-mode)
  - [Disabled State](#disabled-state)
- [Keyboard Shortcuts](#keyboard-shortcuts)
  - [15+ Shortcuts](#15-shortcuts)
  - [Alpine.js Integration](#alpinejs-integration)
  - [Event Dispatching](#event-dispatching)
- [Repository Management](#repository-management)
  - [Open Repository](#open-repository)
  - [Recent Repos](#recent-repos)
  - [Switch Repos](#switch-repos)
  - [Cache Invalidation](#cache-invalidation)
- [Settings](#settings)
  - [Editor Configuration](#editor-configuration)
  - [Auto-Fetch Interval](#auto-fetch-interval)
  - [Theme Toggle](#theme-toggle)
- [Auto-Fetch](#auto-fetch)
  - [Background Fetch](#background-fetch)
  - [Configurable Interval](#configurable-interval)
  - [Indicator](#indicator)
- [Error Handling](#error-handling)
  - [Error Banner](#error-banner)
  - [Toast Notifications](#toast-notifications)
  - [Error Translation](#error-translation)

---

## Staging

File staging is the core workflow in gitty. Users stage/unstage/discard changes before committing.

### File-Level Staging

**Components**: [StagingPanel](components.md#stagingpanel)  
**Services**: [StagingService](services.md#stagingservice), [GitService](services.md#gitservice)  
**Events**: `status-updated`, `file-selected`

**Implementation**:
- `StagingPanel` displays three file collections: unstaged, staged, untracked
- User clicks stage/unstage/discard icon next to file
- `StagingPanel->stageFile()` wraps operation in `HandlesGitOperations` trait
- `StagingService->stageFile()` executes `git add {file}`
- Cache invalidates `status` group
- `status-updated` event triggers UI refresh across all components

**File**: `app/Livewire/StagingPanel.php` (lines 150-180)

```php
public function stageFile(string $file): void
{
    $this->executeGitOperation(function () use ($file) {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->stageFile($file);
        $this->refreshStatus();
        $this->dispatchStatusUpdate();
    }, dispatchStatusUpdate: false);
}
```

**Key Pattern**: `HandlesGitOperations` trait provides automatic error handling and event dispatch. All staging operations follow this pattern.

### Bulk Operations

**Components**: [StagingPanel](components.md#stagingpanel)  
**Services**: [StagingService](services.md#stagingservice)  
**Keyboard Shortcuts**: ⌘⇧K (stage all), ⌘⇧U (unstage all)

**Implementation**:
- Stage All: `StagingService->stageAll()` executes `git add -A`
- Unstage All: `StagingService->unstageAll()` executes `git reset HEAD`
- Discard All: `StagingService->discardAll()` executes `git checkout -- .`

**File**: `app/Services/Git/StagingService.php` (lines 30-60)

**Gotcha**: Discard All is destructive. No confirmation modal (user must use command palette or keyboard shortcut intentionally).

### Multi-Select

**Components**: [StagingPanel](components.md#stagingpanel)  
**Frontend**: Alpine.js multi-select component in `staging-panel.blade.php`

**Implementation**:
- Alpine.js component tracks selected files in `selectedFiles` array
- Cmd+Click: Toggle individual file selection
- Shift+Click: Select range between last selected and clicked file
- Right-click: Context menu with stage/unstage/discard selected
- `StagingPanel->stageSelected(array $files)` batches files into single service call

**File**: `resources/views/livewire/staging-panel.blade.php` (lines 50-150)

**Key Pattern**: Multi-select operations use `StagingService->stageFiles()` which accepts array of paths. This is more efficient than individual calls.

### Tree View

**Components**: [StagingPanel](components.md#stagingpanel)  
**Helpers**: [FileTreeBuilder](../app/Helpers/FileTreeBuilder.php)

**Implementation**:
- Toggle button switches `$treeView` boolean property
- `FileTreeBuilder::buildTree()` converts flat file list to nested tree structure
- Tree built on-demand in `render()` method (not stored in component state)
- Folders collapsible via Alpine.js `x-data` component
- Indentation calculated as `($level * 16) + 16` pixels

**File**: `app/Helpers/FileTreeBuilder.php` (lines 9-75)

**Algorithm**:
1. Split file paths by `/`
2. Iterate through path segments, creating directory nodes as needed
3. Append file node to deepest directory
4. Sort tree: directories first, then alphabetically

**Visual Consistency**: Tree view matches flat view exactly (same padding, hover states, status dots). Only difference is indentation and collapse chevrons.

### Status Hash Optimization

**Components**: [StagingPanel](components.md#stagingpanel), [RepoSidebar](components.md#reposidebar)

**Implementation**:
- `StagingPanel->refreshStatus()` computes MD5 hash of git status output
- Compares hash to `$lastStatusHash` (locked property)
- If hash unchanged, skips file collection rebuild
- Prevents unnecessary re-renders when status unchanged (e.g., rapid polling)

**File**: `app/Livewire/StagingPanel.php` (lines 90-110)

```php
$hash = md5(serialize($status));
if ($this->lastStatusHash === $hash) {
    return; // Skip rebuild
}
$this->lastStatusHash = $hash;
```

**Why Locked**: `#[Locked]` attribute prevents client-side tampering with hash value, ensuring integrity of optimization.

---

## Hunk & Line Staging

Advanced staging allows users to stage individual hunks or lines within a file.

### Hunk Staging

**Components**: [DiffViewer](components.md#diffviewer)  
**Services**: [DiffService](services.md#diffservice), [GitService](services.md#gitservice)  
**Events**: `refresh-staging`

**Implementation**:
1. User clicks "Stage Hunk" button in diff viewer
2. `DiffViewer->stageHunk(int $fileIndex, int $hunkIndex)` hydrates DTOs from arrays
3. `DiffService->stageHunk(DiffFile $file, Hunk $hunk)` generates patch
4. Patch applied via `git apply --cached`
5. `refresh-staging` event triggers `StagingPanel` reload

**File**: `app/Services/Git/DiffService.php` (lines 80-100)

**Patch Format**:
```
diff --git a/file.txt b/file.txt
index abc123..def456 100644
--- a/file.txt
+++ b/file.txt
@@ -10,5 +10,6 @@ context line
 context line
-removed line
+added line
 context line
```

**Key Pattern**: DTOs must be hydrated from arrays because Livewire serializes them for wire transmission. `DiffViewer->hydrateDiffFileAndHunk()` rebuilds `DiffFile` and `Hunk` objects.

### Line Staging

**Components**: [DiffViewer](components.md#diffviewer)  
**Services**: [DiffService](services.md#diffservice)

**Implementation**:
1. User selects lines via checkboxes in diff viewer
2. `DiffViewer->stageSelectedLines(int $fileIndex, int $hunkIndex, array $lineIndices)` called
3. `DiffService->stageLines()` generates patch with only selected lines
4. Patch applied via `git apply --cached --unidiff-zero`

**File**: `app/Services/Git/DiffService.php` (lines 120-180)

**Algorithm** (see [Patch Generation](#patch-generation)):
- Context lines: Always included
- Selected additions: Included as additions
- Unselected additions: Converted to context lines
- Selected deletions: Included as deletions
- Unselected deletions: Omitted entirely

**Gotcha**: `--unidiff-zero` flag required for zero-context patches (only selected lines, no surrounding context).

### Patch Generation

**Services**: [DiffService](services.md#diffservice)

**Implementation**:
- `DiffService->generatePatch()`: Full hunk patch (used for hunk staging)
- `DiffService->generateLinePatch()`: Partial hunk patch (used for line staging)

**File**: `app/Services/Git/DiffService.php` (lines 200-280)

**Line Patch Algorithm**:
1. Iterate through hunk lines
2. For each line:
   - Context: Always include
   - Addition (selected): Include as `+`
   - Addition (unselected): Convert to context (` `)
   - Deletion (selected): Include as `-`
   - Deletion (unselected): Skip entirely
3. Recalculate hunk header counts (`@@ -old_start,old_count +new_start,new_count @@`)
4. Return patch string

**Example**:
```php
// Original hunk: 3 additions, 2 deletions
// User selects: line 0 (addition), line 3 (deletion)
// Result: 1 addition, 1 deletion, 2 context lines (unselected additions)
```

**How to Extend**: Add support for staging individual characters within a line by extending `DiffService->generateCharacterPatch()` with similar logic.

---

## Committing

Commit workflow includes message input, templates, history cycling, and amend mode.

### Message Input

**Components**: [CommitPanel](components.md#commitpanel)  
**Services**: [CommitService](services.md#commitservice)  
**Keyboard Shortcuts**: ⌘↵ (commit), ⌘⇧↵ (commit and push)

**Implementation**:
- Textarea bound to `$message` property via `wire:model.live`
- Commit button disabled when `$message` empty or `$stagedCount` is 0
- `CommitPanel->commit()` validates message, calls `CommitService->commit()`
- Success: Dispatches `committed` event, clears message, saves to history

**File**: `app/Livewire/CommitPanel.php` (lines 100-130)

**Validation**:
- Message must not be empty (trimmed)
- At least one file must be staged
- Amend mode: No validation (can amend with empty message)

### Conventional Commit Templates

**Components**: [CommitPanel](components.md#commitpanel)

**Implementation**:
- Dropdown with 10 conventional commit types: feat, fix, docs, style, refactor, perf, test, build, ci, chore
- `CommitPanel->applyTemplate(string $prefix)` prepends prefix to message
- Custom template loaded from `.gitmessage` or `commit.template` config

**File**: `app/Livewire/CommitPanel.php` (lines 200-250)

**Template Format**:
```
feat: 
fix: 
docs: 
style: 
refactor: 
perf: 
test: 
build: 
ci: 
chore: 
```

**Custom Template**: Reads from `git config commit.template` or `.gitmessage` file in repo root.

### Branch-Based Prefill

**Components**: [CommitPanel](components.md#commitpanel)  
**Services**: [GitService](services.md#gitservice)

**Implementation**:
- `CommitPanel->getCommitPrefill()` parses current branch name
- Pattern: `(feature|bugfix|hotfix)/(TICKET-123)-description`
- Extracts ticket number, generates prefill: `feat(TICKET-123): `
- Prefill auto-applied on mount, cleared after commit

**File**: `app/Livewire/CommitPanel.php` (lines 260-290)

**Regex Pattern**:
```php
preg_match('/^(feature|bugfix|hotfix)\/([A-Z]+-\d+)/', $branch, $matches)
// $matches[1] = type (feature/bugfix/hotfix)
// $matches[2] = ticket (TICKET-123)
```

**Mapping**:
- `feature/` → `feat(`
- `bugfix/` → `fix(`
- `hotfix/` → `fix(`

### Amend Mode

**Components**: [CommitPanel](components.md#commitpanel)  
**Services**: [CommitService](services.md#commitservice)

**Implementation**:
- Toggle button switches `$isAmend` boolean
- When enabled, loads last commit message into textarea
- `CommitPanel->commit()` calls `CommitService->commitAmend()` instead of `commit()`
- Amend uses `git commit --amend -m {message}`

**File**: `app/Livewire/CommitPanel.php` (lines 140-160)

**Gotcha**: Amend mode disabled if last commit is a merge commit (multiple parents).

### Commit History Cycling

**Components**: [CommitPanel](components.md#commitpanel)  
**Services**: [SettingsService](services.md#settingsservice)

**Implementation**:
- Up/Down arrow keys cycle through stored commit messages
- `$historyIndex` tracks current position in history
- First up-arrow press saves current message as `$draftMessage`
- Cycling restores draft when returning to index -1

**File**: `app/Livewire/CommitPanel.php` (lines 170-200)

**Storage**:
- Recent messages stored in `settings` table (key: `commit_history_{repoPath}`)
- Max 20 messages per repository
- Deduplicates on save (removes existing instances of message)

**Keyboard Bindings** (in Blade template):
```blade
@keydown.up="cycleHistory('up')"
@keydown.down="cycleHistory('down')"
```

### Undo Last Commit

**Components**: [CommitPanel](components.md#commitpanel)  
**Services**: [CommitService](services.md#commitservice)  
**Keyboard Shortcuts**: ⌘Z

**Implementation**:
- `CommitPanel->promptUndoLastCommit()` shows confirmation modal
- Modal warns if commit pushed to remote
- `CommitPanel->confirmUndoLastCommit()` calls `CommitService->undoLastCommit()`
- Undo uses `git reset --soft HEAD~1` (keeps changes staged)

**File**: `app/Livewire/CommitPanel.php` (lines 300-330)

**Guards**:
- Blocked if last commit is merge commit
- Warning if commit pushed to remote (requires force push to undo)
- Confirmation modal shows commit message and SHA

**How to Extend**: Add support for undoing multiple commits by accepting count parameter in `CommitService->undoLastCommit(int $count = 1)`.

---

## Branch Management

Branch operations include switching, creating, deleting, merging with auto-stash support.

### Switch Branch

**Components**: [BranchManager](components.md#branchmanager), [RepoSidebar](components.md#reposidebar)  
**Services**: [BranchService](services.md#branchservice), [StashService](services.md#stashservice)  
**Events**: `status-updated`

**Implementation**:
- User selects branch from dropdown
- `BranchManager->switchBranch(string $name)` attempts checkout
- If dirty tree error, shows auto-stash modal
- Auto-stash: Stashes changes, switches branch, tries to restore stash

**File**: `app/Livewire/BranchManager.php` (lines 80-120)

**Error Handling**:
```php
try {
    $branchService->switchBranch($name);
} catch (\RuntimeException $e) {
    if (GitErrorHandler::isDirtyTreeError($e->getMessage())) {
        $this->showAutoStashModal = true;
        $this->autoStashTargetBranch = $name;
        return;
    }
    throw $e;
}
```

### Create Branch

**Components**: [BranchManager](components.md#branchmanager), [CommandPalette](components.md#commandpalette)  
**Services**: [BranchService](services.md#branchservice)  
**Events**: `palette-create-branch`

**Implementation**:
- Command palette switches to input mode
- User enters branch name
- `CommandPalette->submitInput()` dispatches `palette-create-branch` with name
- `BranchManager->handlePaletteCreateBranch(string $name)` creates and switches

**File**: `app/Livewire/BranchManager.php` (lines 140-160)

**Validation**:
- Name must not be empty
- Name must not contain spaces or special characters (git validates)

### Delete Branch

**Components**: [BranchManager](components.md#branchmanager)  
**Services**: [BranchService](services.md#branchservice)

**Implementation**:
- User clicks delete icon next to branch
- `BranchManager->deleteBranch(string $name)` calls `BranchService->deleteBranch()`
- Uses `git branch -d {name}` (safe delete, blocks if unmerged)

**File**: `app/Livewire/BranchManager.php` (lines 180-200)

**Guards**:
- Cannot delete current branch
- Git blocks deletion if branch has unmerged commits (use `-D` for force delete)

### Merge Branch

**Components**: [BranchManager](components.md#branchmanager)  
**Services**: [BranchService](services.md#branchservice)

**Implementation**:
- User clicks merge icon next to branch
- `BranchManager->mergeBranch(string $name)` calls `BranchService->mergeBranch()`
- Returns `MergeResult` DTO with conflict detection

**File**: `app/Livewire/BranchManager.php` (lines 220-250)

**Conflict Handling**:
```php
$result = $branchService->mergeBranch($name);
if ($result->hasConflicts) {
    $this->dispatch('show-error', 
        message: "Merge conflicts in: " . implode(', ', $result->conflictedFiles),
        type: 'warning'
    );
}
```

### Auto-Stash on Dirty Tree

**Components**: [BranchManager](components.md#branchmanager), [RepoSidebar](components.md#reposidebar)  
**Services**: [StashService](services.md#stashservice), [GitCacheService](services.md#gitcacheservice)

**Implementation**:
1. User attempts branch switch with uncommitted changes
2. Git returns dirty tree error
3. `GitErrorHandler::isDirtyTreeError()` detects error pattern
4. Component shows auto-stash modal with target branch name
5. User confirms: Stashes changes, switches branch, tries to restore stash
6. If restore fails (conflicts), preserves stash for manual resolution

**File**: `app/Livewire/BranchManager.php` (lines 260-320)

**Stash Message**: `"Auto-stash before switching to {branch}"`

**Cache Invalidation**: After auto-stash, invalidates all cache groups for repo to ensure fresh data.

### Filtered Branch Properties

**Components**: [BranchManager](components.md#branchmanager)

**Implementation**:
- `BranchManager->filteredLocalBranches` computed property
- Filters branches by `$branchQuery` (search input)
- Sorts: Current branch first, then alphabetically

**File**: `app/Livewire/BranchManager.php` (lines 340-370)

```php
#[Computed]
public function filteredLocalBranches(): Collection
{
    return collect($this->branches)
        ->filter(fn($b) => !$b['isRemote'])
        ->filter(fn($b) => str_contains(strtolower($b['name']), strtolower($this->branchQuery)))
        ->sortBy(fn($b) => $b['isCurrent'] ? 0 : 1)
        ->values();
}
```

### Remote Branch Filtering

**Components**: [BranchManager](components.md#branchmanager)

**Implementation**:
- `BranchManager->filteredRemoteBranches` computed property
- Excludes remote branches that have local counterparts
- Example: `origin/main` hidden when local `main` exists

**File**: `app/Livewire/BranchManager.php` (lines 380-410)

**Logic**:
```php
$localNames = collect($this->branches)
    ->filter(fn($b) => !$b['isRemote'])
    ->pluck('name');

return collect($this->branches)
    ->filter(fn($b) => $b['isRemote'])
    ->filter(fn($b) => !$localNames->contains(str_replace('origin/', '', $b['name'])));
```

**Rationale**: Avoids redundancy in dropdown. Users switch to local branches, not remote tracking branches.

---

## Diff Viewing

Diff viewer displays file changes with syntax highlighting, split view, and image support.

### Unified vs Split Mode

**Components**: [DiffViewer](components.md#diffviewer)

**Implementation**:
- Toggle button switches `$diffViewMode` between `'unified'` and `'split'`
- Unified: Traditional diff format (deletions/additions inline)
- Split: Side-by-side view (old version left, new version right)

**File**: `app/Livewire/DiffViewer.php` (lines 100-130)

**Split View Algorithm**:
- `DiffViewer->getSplitLines(array $hunk)` pairs deletions with additions
- Deletions without matching additions: Paired with empty line
- Additions without matching deletions: Paired with empty line

**Example**:
```
Unified:          Split (Left | Right):
-old line         old line   | 
+new line                    | new line
 context          context    | context
```

### Language Detection

**Components**: [DiffViewer](components.md#diffviewer)

**Implementation**:
- `DiffViewer->mapExtensionToLanguage(string $extension)` maps file extensions to Highlight.js languages
- Supports 25+ languages: php, js, ts, py, rb, go, rs, java, c, cpp, css, html, json, yaml, xml, sql, bash, etc.
- Fallback: `'plaintext'` for unknown extensions

**File**: `app/Livewire/DiffViewer.php` (lines 400-450)

**Frontend Integration**:
- Highlight.js loaded in `app-layout.blade.php`
- Incremental highlighting via Livewire `morph.updated` hook
- Gracefully degrades if language not supported

**File**: `resources/js/app.js` (lines 20-50)

### Binary File Handling

**Components**: [DiffViewer](components.md#diffviewer)

**Implementation**:
- `DiffViewer->loadDiff()` checks if file is binary before parsing
- Binary detection: `git diff` output contains `"Binary files differ"`
- Binary files show warning message instead of diff

**File**: `app/Livewire/DiffViewer.php` (lines 150-180)

**Special Case**: Image files detected by extension before binary check (see [Image Diff](#image-diff)).

### Image Diff

**Components**: [DiffViewer](components.md#diffviewer)  
**Services**: [GitService](services.md#gitservice)

**Implementation**:
1. Detect image by extension: png, jpg, gif, svg, webp, ico, bmp
2. Load old version (HEAD) via `GitService->getFileContentAtHead()`
3. Load new version (working directory) via `file_get_contents()`
4. Base64 encode both versions
5. Display side-by-side with size comparison

**File**: `app/Livewire/DiffViewer.php` (lines 200-250)

**Data Structure**:
```php
$this->imageData = [
    'oldContent' => base64_encode($oldContent),
    'newContent' => base64_encode($newContent),
    'oldSize' => strlen($oldContent),
    'newSize' => strlen($newContent),
];
```

**Frontend**:
```blade
<img src="data:image/png;base64,{{ $imageData['oldContent'] }}" />
<img src="data:image/png;base64,{{ $imageData['newContent'] }}" />
```

### Large File Detection

**Components**: [DiffViewer](components.md#diffviewer)  
**Services**: [GitService](services.md#gitservice)

**Implementation**:
- `DiffViewer->loadDiff()` checks file size at HEAD
- Threshold: 1MB (1,048,576 bytes)
- Large files show warning instead of diff

**File**: `app/Livewire/DiffViewer.php` (lines 260-280)

**Rationale**: Prevents browser freeze when rendering massive diffs. Users can open file in external editor instead.

---

## Stashing

Stash operations allow users to save uncommitted changes temporarily.

### Create Stash

**Components**: [StagingPanel](components.md#stagingpanel)  
**Services**: [StashService](services.md#stashservice)  
**Events**: `stash-created`

**Implementation**:
- User clicks "Stash All" button
- Modal prompts for stash message
- `StagingPanel->stashAll()` calls `StashService->stash()`
- Dispatches `stash-created` event to refresh sidebar

**File**: `app/Livewire/StagingPanel.php` (lines 400-430)

**Options**:
- Include untracked files: Checkbox in modal (uses `git stash -u`)

### Apply/Pop/Drop

**Components**: [RepoSidebar](components.md#reposidebar)  
**Services**: [StashService](services.md#stashservice)

**Implementation**:
- Sidebar displays stash list with index, message, branch, SHA
- Apply: `StashService->stashApply(int $index)` (keeps stash)
- Pop: `StashService->stashPop(int $index)` (removes stash)
- Drop: `StashService->stashDrop(int $index)` (deletes stash)

**File**: `app/Livewire/RepoSidebar.php` (lines 200-250)

**Error Handling**:
- Apply/Pop may fail if conflicts detected
- Error message shows conflicted files
- Stash preserved on conflict (user must resolve manually)

### Stash Individual Files

**Components**: [StagingPanel](components.md#stagingpanel)  
**Services**: [StashService](services.md#stashservice)

**Implementation**:
- Multi-select files in staging panel
- Right-click context menu: "Stash Selected"
- `StagingPanel->stashSelected(array $files)` calls `StashService->stashFiles()`
- Uses `git stash push -m {message} -- {files...}`

**File**: `app/Livewire/StagingPanel.php` (lines 450-480)

**Gotcha**: Requires git 2.13+ for `git stash push` syntax.

### Auto-Generated Messages

**Services**: [StashService](services.md#stashservice)

**Implementation**:
- If user provides empty message, generates default: `"WIP on {branch}"`
- Auto-stash (from branch switch): `"Auto-stash before switching to {branch}"`
- File stash: `"Stash {count} files"`

**File**: `app/Services/Git/StashService.php` (lines 30-60)

---

## Push/Pull/Fetch

Sync operations communicate with remote repositories.

### Push

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [RemoteService](services.md#remoteservice), [NotificationService](services.md#notificationservice)  
**Events**: `status-updated`

**Implementation**:
- User clicks push button or uses command palette
- `SyncPanel->syncPush()` calls `RemoteService->push()`
- Success: Shows native notification with commit count
- Updates ahead/behind counts to [0, 0]

**File**: `app/Livewire/SyncPanel.php` (lines 80-110)

**Notification**:
```php
$notification->notify(
    "Pushed to origin",
    "Pushed {$aheadCount} commit(s) to {$branch}"
);
```

### Pull

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [RemoteService](services.md#remoteservice)

**Implementation**:
- `SyncPanel->syncPull()` calls `RemoteService->pull()`
- Invalidates `status`, `history`, `branches` cache groups
- Shows notification with commit count

**File**: `app/Livewire/SyncPanel.php` (lines 120-150)

**Conflict Handling**: Pull may fail if conflicts detected. Error message shows conflicted files.

### Fetch

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [RemoteService](services.md#remoteservice)

**Implementation**:
- `SyncPanel->syncFetch()` calls `RemoteService->fetch()`
- Updates remote tracking branches
- Does not modify working directory

**File**: `app/Livewire/SyncPanel.php` (lines 160-180)

### Fetch All Remotes

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [RemoteService](services.md#remoteservice)

**Implementation**:
- `SyncPanel->syncFetchAll()` calls `RemoteService->fetchAll()`
- Fetches from all configured remotes
- Uses `git fetch --all`

**File**: `app/Livewire/SyncPanel.php` (lines 190-210)

### Force Push with Lease

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [RemoteService](services.md#remoteservice)

**Implementation**:
- `SyncPanel->syncForcePushWithLease()` calls `RemoteService->forcePushWithLease()`
- Uses `git push --force-with-lease` (safer than `--force`)
- Blocks if remote has commits not in local history

**File**: `app/Livewire/SyncPanel.php` (lines 220-240)

**Safety**: `--force-with-lease` prevents overwriting commits pushed by others.

### Detached HEAD Guards

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [GitService](services.md#gitservice)

**Implementation**:
- `SyncPanel->syncPush()` checks `GitService->isDetachedHead()`
- Blocks push/pull in detached HEAD state
- Shows error: "Cannot push/pull in detached HEAD state"

**File**: `app/Livewire/SyncPanel.php` (lines 250-270)

### Native Notifications

**Components**: [SyncPanel](components.md#syncpanel)  
**Services**: [NotificationService](services.md#notificationservice)

**Implementation**:
- `NotificationService->notify()` uses NativePHP notification API
- Shows desktop notification on push/pull/fetch completion
- Respects `notifications_enabled` setting

**File**: `app/Services/NotificationService.php` (lines 20-40)

**Example**:
```php
$notification->notify(
    "Fetched from origin",
    "Fetched latest changes from origin/main"
);
```

---

## History

Commit history panel displays log with graph, pagination, and reset/revert/cherry-pick.

### Commit Log with Pagination

**Components**: [HistoryPanel](components.md#historypanel)  
**Services**: [GitService](services.md#gitservice)

**Implementation**:
- `HistoryPanel->loadCommits()` calls `GitService->log($limit, $branch, $detailed)`
- Loads 100 commits per page
- "Load More" button increments `$page` and appends next batch

**File**: `app/Livewire/HistoryPanel.php` (lines 80-120)

**Pagination Logic**:
```php
$commits = $gitService->log($this->perPage, null, true);
$this->hasMore = count($commits) === $this->perPage;
```

### Commit Graph Visualization

**Components**: [HistoryPanel](components.md#historypanel)  
**Services**: [GraphService](services.md#graphservice)

**Implementation**:
- `GraphService->getGraphData()` assigns visual lanes to commits
- Algorithm: Tracks active branches, assigns lanes left-to-right
- Graph rendered as SVG in Blade template

**File**: `app/Services/Git/GraphService.php` (lines 20-100)

**Lane Assignment**:
1. Start with lane 0 for main branch
2. When commit has multiple parents (merge), assign new lane
3. When branch ends, free lane for reuse

### Select Commit

**Components**: [HistoryPanel](components.md#historypanel)  
**Events**: `commit-selected`

**Implementation**:
- User clicks commit in history panel
- `HistoryPanel->selectCommit(string $sha)` dispatches `commit-selected` event
- Event payload: `sha`

**File**: `app/Livewire/HistoryPanel.php` (lines 140-160)

**Listeners**: No built-in listeners. External components can listen for commit selection.

### Reset (Soft/Mixed/Hard)

**Components**: [HistoryPanel](components.md#historypanel)  
**Services**: [ResetService](services.md#resetservice)

**Implementation**:
- User clicks reset icon next to commit
- Modal shows reset mode options: soft, mixed, hard
- Hard reset requires typing "DISCARD" for confirmation
- `HistoryPanel->confirmReset()` calls `ResetService->reset{Mode}()`

**File**: `app/Livewire/HistoryPanel.php` (lines 180-230)

**Reset Modes**:
- Soft: `git reset --soft {sha}` (keeps changes staged)
- Mixed: `git reset --mixed {sha}` (keeps changes unstaged)
- Hard: `git reset --hard {sha}` (discards all changes)

**Warning**: Modal warns if target commit pushed to remote (requires force push).

### Revert

**Components**: [HistoryPanel](components.md#historypanel)  
**Services**: [ResetService](services.md#resetservice)

**Implementation**:
- User clicks revert icon next to commit
- `HistoryPanel->confirmRevert()` calls `ResetService->revertCommit()`
- Creates new commit with inverse changes

**File**: `app/Livewire/HistoryPanel.php` (lines 250-280)

**Conflict Handling**: Revert may fail if conflicts detected. Error message shows conflicted files.

### Cherry-Pick

**Components**: [HistoryPanel](components.md#historypanel)  
**Services**: [CommitService](services.md#commitservice)

**Implementation**:
- User clicks cherry-pick icon next to commit
- `HistoryPanel->confirmCherryPick()` calls `CommitService->cherryPick()`
- Applies commit changes to current branch

**File**: `app/Livewire/HistoryPanel.php` (lines 300-330)

**Conflict Handling**: Cherry-pick may fail if conflicts detected. Shows conflict warning with file list.

---

## Rebase

Interactive rebase allows users to reorder, squash, fixup, or drop commits.

### Interactive Rebase Panel

**Components**: [RebasePanel](components.md#rebasepanel)  
**Services**: [RebaseService](services.md#rebaseservice)  
**Events**: `open-rebase-modal`

**Implementation**:
- User clicks "Rebase" button in history panel
- `HistoryPanel->promptInteractiveRebase(string $sha)` dispatches `open-rebase-modal`
- `RebasePanel->openRebaseModal()` loads commits and shows modal

**File**: `app/Livewire/RebasePanel.php` (lines 80-120)

**Rebase Plan**:
```php
$this->rebasePlan = [
    ['sha' => 'abc123', 'message' => 'commit 1', 'action' => 'pick'],
    ['sha' => 'def456', 'message' => 'commit 2', 'action' => 'squash'],
    ['sha' => 'ghi789', 'message' => 'commit 3', 'action' => 'drop'],
];
```

### Commit Reordering

**Components**: [RebasePanel](components.md#rebasepanel)

**Implementation**:
- Frontend: Drag-and-drop via Alpine.js Sortable
- `RebasePanel->reorderCommits(array $newOrder)` updates `$rebasePlan`

**File**: `resources/views/livewire/rebase-panel.blade.php` (lines 50-100)

**Gotcha**: Reordering may cause conflicts if commits depend on each other.

### Action Selection

**Components**: [RebasePanel](components.md#rebasepanel)

**Implementation**:
- Dropdown for each commit: pick, squash, fixup, drop
- `RebasePanel->updateAction(int $index, string $action)` updates plan
- `RebasePanel->startRebase()` executes rebase with plan

**File**: `app/Livewire/RebasePanel.php` (lines 140-180)

**Actions**:
- `pick`: Keep commit as-is
- `squash`: Combine with previous commit, keep both messages
- `fixup`: Combine with previous commit, discard this message
- `drop`: Remove commit entirely

**Execution**:
- `RebaseService->startRebase()` uses `GIT_SEQUENCE_EDITOR` to inject plan
- Git executes rebase with custom sequence

**File**: `app/Services/Git/RebaseService.php` (lines 60-120)

---

## Search

Search panel allows users to search commits, file content, and file names.

### Commit Search

**Components**: [SearchPanel](components.md#searchpanel)  
**Services**: [SearchService](services.md#searchservice)

**Implementation**:
- User enters query in search panel
- `SearchPanel->search()` calls `SearchService->searchCommits()`
- Uses `git log --grep={query}`
- Results dispatched to history panel via `commit-selected` event

**File**: `app/Livewire/SearchPanel.php` (lines 80-110)

### File Search

**Components**: [SearchPanel](components.md#searchpanel)  
**Services**: [SearchService](services.md#searchservice)

**Implementation**:
- `SearchPanel->search()` calls `SearchService->searchFiles()`
- Uses `git ls-files | grep {query}`
- Results dispatched to diff viewer via `file-selected` event

**File**: `app/Livewire/SearchPanel.php` (lines 120-150)

### Content Search

**Components**: [SearchPanel](components.md#searchpanel)  
**Services**: [SearchService](services.md#searchservice)

**Implementation**:
- `SearchPanel->search()` calls `SearchService->searchContent()`
- Uses `git log -S{query}` (pickaxe search)
- Finds commits that added/removed query string

**File**: `app/Livewire/SearchPanel.php` (lines 160-190)

**Minimum Query Length**: 3 characters (prevents slow searches).

---

## Blame

Git blame view shows line-by-line authorship for a file.

### Git Blame View

**Components**: [BlameView](components.md#blameview)  
**Services**: [BlameService](services.md#blameservice)  
**Events**: `show-blame`

**Implementation**:
- User clicks "Blame" button in diff viewer
- `DiffViewer` dispatches `show-blame` event with file path
- `BlameView->loadBlame()` calls `BlameService->blame()`
- Parses `git blame --porcelain` output

**File**: `app/Livewire/BlameView.php` (lines 40-80)

**Data Structure**:
```php
$this->blameData = [
    ['sha' => 'abc123', 'author' => 'John Doe', 'date' => '2024-01-01', 'line' => 1, 'content' => 'line content'],
    // ...
];
```

### Commit Navigation

**Components**: [BlameView](components.md#blameview)  
**Events**: `commit-selected`, `toggle-history-panel`

**Implementation**:
- User clicks commit SHA in blame view
- `BlameView->selectCommit(string $sha)` dispatches `commit-selected` and `toggle-history-panel`
- History panel opens and selects commit

**File**: `app/Livewire/BlameView.php` (lines 100-120)

---

## Conflict Resolution

Conflict resolver provides three-way merge view for resolving merge conflicts.

### Conflict Detection

**Components**: [ConflictResolver](components.md#conflictresolver)  
**Services**: [ConflictService](services.md#conflictservice)

**Implementation**:
- `ConflictService->isInMergeState()` checks for `.git/MERGE_HEAD`
- `ConflictService->getConflictedFiles()` parses `git status --porcelain=v2` for unmerged entries

**File**: `app/Services/Git/ConflictService.php` (lines 20-60)

**Unmerged Entry Format**:
```
u <XY> <sub> <m1> <m2> <m3> <mW> <h1> <h2> <h3> <path>
```

### Resolver UI

**Components**: [ConflictResolver](components.md#conflictresolver)

**Implementation**:
- Modal displays list of conflicted files
- User selects file to resolve
- Three-way merge view: ours, theirs, base, result
- Result content editable for manual resolution

**File**: `resources/views/livewire/conflict-resolver.blade.php` (lines 50-150)

### Three-Way Merge

**Components**: [ConflictResolver](components.md#conflictresolver)  
**Services**: [ConflictService](services.md#conflictservice)

**Implementation**:
- `ConflictService->getConflictVersions(string $file)` retrieves three versions:
  - `:1:` — Common ancestor (base)
  - `:2:` — Current branch (ours)
  - `:3:` — Incoming branch (theirs)
- User chooses: Accept Ours, Accept Theirs, Accept Both, or manual edit
- `ConflictResolver->resolveFile()` writes result and stages file

**File**: `app/Livewire/ConflictResolver.php` (lines 100-150)

**Accept Both**:
```php
$this->resultContent = $this->oursContent . "\n" . $this->theirsContent;
```

---

## Command Palette

Fuzzy command search with 28 commands, input mode, and disabled state.

### 28 Commands

**Components**: [CommandPalette](components.md#commandpalette)

**Implementation**:
- `CommandPalette::getCommands()` returns array of 28 commands
- Each command has: id, label, shortcut, event, keywords, requiresInput, icon

**File**: `app/Livewire/CommandPalette.php` (lines 91-356)

**Command Categories**:
- Staging: Stage All, Unstage All, Discard All, Toggle View
- Committing: Commit, Commit & Push, Toggle Amend, Undo Last Commit
- Sync: Push, Pull, Fetch, Fetch All, Force Push
- Branches: Create Branch, Switch Branch
- Repository: Open Folder, Toggle Sidebar
- Diff: Toggle Diff View, Open in Editor
- Conflict: Abort Merge
- Rebase: Continue Rebase, Abort Rebase
- Tags: Create Tag
- Settings: Open Settings
- Help: Keyboard Shortcuts

### Search/Filter

**Components**: [CommandPalette](components.md#commandpalette)

**Implementation**:
- `CommandPalette->filteredCommands` computed property
- Fuzzy search matches label and keywords
- Example: "push" matches "Push", "upload", "send"

**File**: `app/Livewire/CommandPalette.php` (lines 370-390)

**Search Algorithm**:
```php
$query = strtolower($this->query);
return collect(self::getCommands())
    ->filter(fn($cmd) => 
        str_contains(strtolower($cmd['label']), $query) ||
        collect($cmd['keywords'])->contains(fn($kw) => str_contains(strtolower($kw), $query))
    );
```

### Input Mode

**Components**: [CommandPalette](components.md#commandpalette)

**Implementation**:
- Commands with `requiresInput: true` switch to input mode
- Example: Create Branch prompts for branch name
- `CommandPalette->submitInput()` validates and dispatches event with parameter

**File**: `app/Livewire/CommandPalette.php` (lines 410-450)

**Validation**:
```php
if (empty($this->inputValue)) {
    $this->inputError = 'Branch name cannot be empty';
    return;
}
```

### Disabled State

**Components**: [CommandPalette](components.md#commandpalette)

**Implementation**:
- `CommandPalette->getDisabledCommands()` returns array of disabled command IDs
- Disabled when: No repo open, no staged files (for commit)
- Updated on `status-updated` and `repo-switched` events

**File**: `app/Livewire/CommandPalette.php` (lines 460-490)

**Disabled Logic**:
```php
$disabled = [];
if (empty($this->repoPath)) {
    $disabled = array_merge($disabled, ['stage-all', 'unstage-all', 'commit', ...]);
}
if ($this->stagedCount === 0) {
    $disabled = array_merge($disabled, ['commit', 'commit-push']);
}
return $disabled;
```

---

## Keyboard Shortcuts

15+ keyboard shortcuts for common operations.

### 15+ Shortcuts

**Components**: [AppLayout](components.md#applayout)

**Implementation**:
- Shortcuts defined in `app-layout.blade.php` via Alpine.js `@keydown.window` handlers
- All shortcuts use `.prevent` modifier to block default browser behavior

**File**: `resources/views/livewire/app-layout.blade.php` (lines 3-50)

**Complete List**:
- ⌘↵: Commit
- ⌘⇧↵: Commit & Push
- ⌘⇧K: Stage All
- ⌘⇧U: Unstage All
- ⌘⇧S: Stash All
- ⌘A: Select All Files
- ⌘K: Toggle Command Palette
- ⌘⇧P: Toggle Command Palette
- ⌘Z: Undo Last Commit
- Esc: Close Modals
- ⌘/: Keyboard Shortcuts Help
- ⌘H: Toggle History Panel
- ⌘F: Open Search
- ⌘L: Focus Commit Message
- ⌘B: Toggle Sidebar

### Alpine.js Integration

**Frontend**: `resources/views/livewire/app-layout.blade.php`

**Implementation**:
- Alpine.js captures keyboard events at window level
- Guards prevent shortcuts when no repo open

**Example**:
```blade
@keydown.window.meta.enter.prevent="
    if (!$wire.repoPath) return;
    $wire.$dispatch('keyboard-commit')
"
```

### Event Dispatching

**Pattern**: Most shortcuts dispatch Livewire events instead of calling methods directly.

**Rationale**: Decouples keyboard handling from component logic. Multiple components can listen to same event.

**Exception**: ⌘B calls `$wire.toggleSidebar()` directly (no event).

---

## Repository Management

Repository lifecycle management: open, switch, recent repos.

### Open Repository

**Components**: [RepoSwitcher](components.md#reposwitcher)  
**Services**: [RepoManager](services.md#repomanager)

**Implementation**:
- User clicks "Open Repository" button
- Native folder picker dialog opens
- `RepoSwitcher->openFolderDialog()` uses NativePHP dialog API
- `RepoManager->openRepo(string $path)` validates `.git` directory, creates `Repository` model

**File**: `app/Livewire/RepoSwitcher.php` (lines 80-120)

**Validation**:
```php
if (!is_dir($path . '/.git')) {
    throw new InvalidArgumentException('Not a valid git repository');
}
```

### Recent Repos

**Components**: [RepoSwitcher](components.md#reposwitcher)  
**Services**: [RepoManager](services.md#repomanager)

**Implementation**:
- `RepoManager->recentRepos()` returns last 20 repositories ordered by `last_opened_at`
- Dropdown shows recent repos with name and path
- Current repo highlighted at top

**File**: `app/Services/RepoManager.php` (lines 40-70)

**Storage**: `repositories` table with columns: `id`, `path`, `name`, `last_opened_at`.

### Switch Repos

**Components**: [RepoSwitcher](components.md#reposwitcher)  
**Services**: [RepoManager](services.md#repomanager)  
**Events**: `repo-switched`

**Implementation**:
- User selects repo from dropdown
- `RepoSwitcher->switchRepo(int $id)` calls `RepoManager->setCurrentRepo()`
- Dispatches `repo-switched` event with path
- All components reload for new repo

**File**: `app/Livewire/RepoSwitcher.php` (lines 140-170)

### Cache Invalidation

**Components**: [AppLayout](components.md#applayout)  
**Services**: [GitCacheService](services.md#gitcacheservice)

**Implementation**:
- `AppLayout->handleRepoSwitched(string $path)` invalidates all cache groups for previous repo
- Ensures fresh data when switching repos

**File**: `app/Livewire/AppLayout.php` (lines 80-110)

```php
if ($this->previousRepoPath && $this->previousRepoPath !== $path) {
    $cache = new GitCacheService;
    $cache->invalidateAll($this->previousRepoPath);
}
```

---

## Settings

Application settings: editor, auto-fetch, theme.

### Editor Configuration

**Components**: [SettingsModal](components.md#settingsmodal)  
**Services**: [SettingsService](services.md#settingsservice), [EditorService](services.md#editorservice)

**Implementation**:
- `EditorService->detectEditors()` scans for installed editors (VS Code, Cursor, Sublime, PhpStorm, Zed)
- User selects editor in settings modal
- `SettingsService->set('external_editor', $editorKey)` saves preference

**File**: `app/Services/EditorService.php` (lines 40-80)

**Supported Editors**:
```php
const EDITORS = [
    'code' => ['name' => 'VS Code', 'command' => 'code', 'args' => '--goto {file}:{line}'],
    'cursor' => ['name' => 'Cursor', 'command' => 'cursor', 'args' => '--goto {file}:{line}'],
    'subl' => ['name' => 'Sublime Text', 'command' => 'subl', 'args' => '{file}:{line}'],
    'phpstorm' => ['name' => 'PhpStorm', 'command' => 'phpstorm', 'args' => '--line {line} {file}'],
    'zed' => ['name' => 'Zed', 'command' => 'zed', 'args' => '{file}:{line}'],
];
```

### Auto-Fetch Interval

**Components**: [SettingsModal](components.md#settingsmodal)  
**Services**: [SettingsService](services.md#settingsservice), [AutoFetchService](services.md#autofetchservice)

**Implementation**:
- User sets interval in minutes (0 to disable)
- `SettingsService->set('auto_fetch_interval', $minutes)` saves preference
- `AutoFetchService->start($repoPath, $intervalSeconds)` starts background fetch

**File**: `app/Services/AutoFetchService.php` (lines 40-80)

**Default**: 180 seconds (3 minutes).

### Theme Toggle

**Components**: [SettingsModal](components.md#settingsmodal)  
**Services**: [SettingsService](services.md#settingsservice)  
**Events**: `theme-updated`

**Implementation**:
- User selects theme: light, dark, system
- `SettingsModal->updatedTheme()` dispatches `theme-updated` event
- Alpine.js listener in `app-layout.blade.php` applies theme class to `<html>`

**File**: `resources/views/livewire/app-layout.blade.php` (lines 60-90)

**Alpine.js Handler**:
```blade
@theme-updated.window="
    if ($event.detail.theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
"
```

---

## Auto-Fetch

Background fetch service with configurable interval.

### Background Fetch

**Components**: [AutoFetchIndicator](components.md#autofetchindicator)  
**Services**: [AutoFetchService](services.md#autofetchservice)

**Implementation**:
- `AutoFetchIndicator->checkAndFetch()` runs on mount and every 60 seconds
- `AutoFetchService->shouldFetch()` checks if interval elapsed
- `AutoFetchService->executeFetch()` runs `git fetch` in background

**File**: `app/Livewire/AutoFetchIndicator.php` (lines 40-80)

**Polling**:
```blade
<div wire:poll.60s="checkAndFetch">
```

### Configurable Interval

**Services**: [AutoFetchService](services.md#autofetchservice)

**Implementation**:
- Interval stored in cache: `auto_fetch_interval_{repoPath}`
- Minimum interval: 60 seconds
- 0 disables auto-fetch

**File**: `app/Services/AutoFetchService.php` (lines 100-140)

**Check Logic**:
```php
$lastFetch = Cache::get("auto_fetch_last_{$this->repoPath}");
$interval = Cache::get("auto_fetch_interval_{$this->repoPath}");
return $lastFetch && now()->diffInSeconds($lastFetch) >= $interval;
```

### Indicator

**Components**: [AutoFetchIndicator](components.md#autofetchindicator)

**Implementation**:
- Shows last fetch time (relative: "2 minutes ago")
- Spinner when fetch in progress
- Error message if fetch fails

**File**: `resources/views/livewire/auto-fetch-indicator.blade.php` (lines 10-40)

---

## Error Handling

Global error handling with banner, toasts, and translation.

### Error Banner

**Components**: [ErrorBanner](components.md#errorbanner)  
**Events**: `show-error`

**Implementation**:
- All components dispatch `show-error` event on errors
- `ErrorBanner->showError()` displays banner with message and type
- Auto-dismiss after 5 seconds (non-persistent)

**File**: `app/Livewire/ErrorBanner.php` (lines 20-50)

**Types**:
- `error`: Red banner
- `success`: Green banner
- `warning`: Yellow banner

### Toast Notifications

**Components**: [ErrorBanner](components.md#errorbanner)

**Implementation**:
- Non-persistent banners auto-dismiss via Alpine.js timeout
- Persistent banners require manual dismiss

**File**: `resources/views/livewire/error-banner.blade.php` (lines 20-50)

**Alpine.js**:
```blade
x-init="
    if (!persistent) {
        setTimeout(() => { visible = false }, 5000);
    }
"
```

### Error Translation

**Services**: [GitErrorHandler](services.md#giterrorhandler)

**Implementation**:
- `GitErrorHandler::translate()` maps git error messages to user-friendly text
- Pattern matching for common errors

**File**: `app/Services/Git/GitErrorHandler.php` (lines 20-80)

**Examples**:
```php
'fatal: not a git repository' → 'This folder is not a git repository'
'error: pathspec ... did not match' → 'File not found in repository'
'CONFLICT' → 'Merge conflict detected. Resolve conflicts in external editor.'
'rejected' → 'Push rejected. Pull remote changes first.'
```

**How to Extend**: Add new patterns to `GitErrorHandler::translate()` for custom error messages.

---

## How to Extend

### Adding a New Staging Operation

1. Add method to `StagingService` (e.g., `stageByPattern(string $pattern)`)
2. Add action to `StagingPanel` that calls service method
3. Wrap in `executeGitOperation()` for automatic error handling
4. Add button/shortcut to `staging-panel.blade.php`

**Example**:
```php
// app/Services/Git/StagingService.php
public function stageByPattern(string $pattern): void
{
    $this->commandRunner->run('add', [$pattern]);
    $this->cache->invalidateGroup($this->repoPath, 'status');
}

// app/Livewire/StagingPanel.php
public function stageByPattern(string $pattern): void
{
    $this->executeGitOperation(function () use ($pattern) {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->stageByPattern($pattern);
        $this->refreshStatus();
    });
}
```

### Adding a New Commit Template

1. Add template to `CommitPanel::getTemplates()` array
2. Template format: `['prefix' => 'type: ', 'label' => 'Type']`
3. Template appears in dropdown automatically

**Example**:
```php
// app/Livewire/CommitPanel.php
public function getTemplates(): array
{
    return [
        // ... existing templates
        ['prefix' => 'wip: ', 'label' => 'Work in Progress'],
    ];
}
```

### Adding a New Command Palette Command

1. Add command to `CommandPalette::getCommands()` array
2. Define: id, label, shortcut, event, keywords, requiresInput, icon
3. Add listener to target component with `#[On('event-name')]`

**Example**:
```php
// app/Livewire/CommandPalette.php
[
    'id' => 'my-command',
    'label' => 'My Command',
    'shortcut' => '⌘M',
    'event' => 'palette-my-command',
    'keywords' => ['my', 'command', 'action'],
    'requiresInput' => false,
    'icon' => 'star',
]

// app/Livewire/MyComponent.php
#[On('palette-my-command')]
public function handlePaletteMyCommand(): void
{
    // Execute command
}
```

---

## Related Documentation

- [Architecture](architecture.md) — System architecture, layers, data flow
- [Services](services.md) — Service API reference
- [Components](components.md) — Livewire component reference
- [Events](events.md) — Event system map
- [DTOs](dtos.md) — Data transfer objects
- [Frontend](frontend.md) — Blade templates, Alpine.js, Flux UI
- [AGENTS.md](../AGENTS.md) — Design system, colors, icons
