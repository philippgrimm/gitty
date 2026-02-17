# Livewire Event System

Complete map of all Livewire events in gitty, including dispatchers, listeners, payloads, and event flow diagrams.

## Table of Contents

- [Event Categories](#event-categories)
  - [Core Events](#core-events)
  - [Keyboard Events](#keyboard-events)
  - [Command Palette Events](#command-palette-events)
  - [UI Toggle Events](#ui-toggle-events)
  - [Git Operation Events](#git-operation-events)
  - [Notification Events](#notification-events)
  - [Theme Events](#theme-events)
- [Event Flow Diagrams](#event-flow-diagrams)
  - [Staging Flow](#staging-flow)
  - [Commit Flow](#commit-flow)
  - [Branch Switch Flow](#branch-switch-flow)
  - [Sync Flow](#sync-flow)
- [Keyboard Shortcut Pipeline](#keyboard-shortcut-pipeline)
- [Command Palette Event Dispatch](#command-palette-event-dispatch)
- [Master Event Reference Table](#master-event-reference-table)

## Event Categories

### Core Events

Events that drive the primary git workflow and component synchronization.

| Event | Payload | Purpose |
|-------|---------|---------|
| `status-updated` | `stagedCount: int`, `aheadBehind: array` | Fired after every git mutation. Triggers UI refresh across 8+ components. |
| `file-selected` | `file: string`, `staged: bool` | User selects a file in staging panel. DiffViewer loads the diff. |
| `repo-switched` | `path: string` | User switches repository. All components reload for new repo. |
| `committed` | none | Commit succeeded. Triggers status refresh and commit panel reset. |
| `stash-created` | none | Stash operation completed. RepoSidebar refreshes stash list. |
| `refresh-staging` | none | Manual staging panel refresh (after discard, conflict resolution). |
| `commit-selected` | `sha: string` | User selects commit in history or blame view. |
| `remote-updated` | none | Auto-fetch detected remote changes. SyncPanel updates ahead/behind counts. |

**Key Pattern**: `status-updated` is the backbone event. Fired by `HandlesGitOperations` trait after every successful git operation. Listened by:
- `CommitPanel` (update staged count, enable/disable commit button)
- `StagingPanel` (reload file list)
- `RepoSidebar` (refresh branches, stashes, tags)
- `SyncPanel` (update ahead/behind counts)
- `HistoryPanel` (reload commit log)
- `CommandPalette` (update disabled commands)
- `ConflictResolver` (check for conflicts)
- `RebasePanel` (check rebase state)

### Keyboard Events

Events dispatched from Alpine.js keyboard shortcuts in `app-layout.blade.php`.

| Event | Shortcut | Purpose |
|-------|----------|---------|
| `keyboard-commit` | ⌘↵ | Trigger commit action |
| `keyboard-commit-push` | ⌘⇧↵ | Trigger commit and push action |
| `keyboard-stage-all` | ⌘⇧K | Stage all unstaged files |
| `keyboard-unstage-all` | ⌘⇧U | Unstage all staged files |
| `keyboard-stash` | ⌘⇧S | Stash all changes |
| `keyboard-select-all` | ⌘A | Select all files in staging panel |
| `keyboard-escape` | Esc | Close modals, clear selection |

**Dispatched by**: `resources/views/livewire/app-layout.blade.php` (Alpine.js `@keydown.window` handlers)

**Listened by**:
- `CommitPanel`: `keyboard-commit`, `keyboard-commit-push`
- `StagingPanel`: `keyboard-stage-all`, `keyboard-unstage-all`

### Command Palette Events

Events dispatched when user executes commands via command palette (⌘K).

| Event | Command | Purpose |
|-------|---------|---------|
| `palette-discard-all` | Discard All | Discard all unstaged changes |
| `palette-toggle-view` | Toggle File View | Switch between flat/tree view |
| `palette-toggle-amend` | Toggle Amend | Toggle amend mode in commit panel |
| `palette-undo-last-commit` | Undo Last Commit | Undo last commit (⌘Z) |
| `palette-push` | Push | Push to remote |
| `palette-pull` | Pull | Pull from remote |
| `palette-fetch` | Fetch | Fetch from remote |
| `palette-fetch-all` | Fetch All Remotes | Fetch from all remotes |
| `palette-force-push` | Force Push (with Lease) | Force push with lease |
| `palette-create-branch` | Create Branch | Create new branch (with name input) |
| `palette-toggle-sidebar` | Toggle Sidebar | Toggle sidebar visibility |
| `palette-open-folder` | Open Repository… | Open folder picker |
| `palette-toggle-diff-view` | Toggle Diff View Mode | Switch diff view mode |
| `palette-abort-merge` | Abort Merge | Abort merge in progress |
| `palette-create-tag` | Create Tag | Create git tag |
| `palette-open-in-editor` | Open in Editor | Open file in external editor |
| `palette-continue-rebase` | Continue Rebase | Continue rebase after conflict resolution |
| `palette-abort-rebase` | Abort Rebase | Abort rebase in progress |

**Dispatched by**: `app/Livewire/CommandPalette.php` (line 413: `$this->dispatch($command['event'])`)

**Listened by**:
- `StagingPanel`: `palette-discard-all`, `palette-toggle-view`
- `CommitPanel`: `palette-toggle-amend`, `palette-undo-last-commit`
- `SyncPanel`: `palette-push`, `palette-pull`, `palette-fetch`, `palette-fetch-all`, `palette-force-push`
- `BranchManager`: `palette-create-branch`
- `AppLayout`: `palette-toggle-sidebar`
- `DiffViewer`: `palette-toggle-diff-view`, `palette-open-in-editor`
- `ConflictResolver`: `palette-abort-merge`
- `RepoSwitcher`: `palette-open-folder`
- `RepoSidebar`: `palette-create-tag`
- `RebasePanel`: `palette-continue-rebase`, `palette-abort-rebase`

### UI Toggle Events

Events that control modal and panel visibility.

| Event | Shortcut | Purpose |
|-------|----------|---------|
| `toggle-command-palette` | ⌘K, ⌘⇧P | Toggle command palette visibility |
| `open-command-palette` | none | Open command palette (always opens, never closes) |
| `open-command-palette-create-branch` | none | Open command palette in branch creation mode |
| `open-shortcut-help` | ⌘/ | Open keyboard shortcuts help modal |
| `toggle-history-panel` | ⌘H | Toggle history panel visibility |
| `open-search` | ⌘F | Open search panel |
| `focus-commit-message` | ⌘L | Focus commit message textarea |
| `open-settings` | none | Open settings modal |
| `show-blame` | none | Show blame view for file |
| `open-rebase-modal` | none | Open rebase modal with commit context |

**Dispatched by**:
- `app-layout.blade.php`: `toggle-command-palette`, `open-shortcut-help`, `toggle-history-panel`, `open-search`, `focus-commit-message`
- `branch-manager.blade.php`: `open-command-palette-create-branch`
- `history-panel.blade.php`: `toggle-history-panel`
- `diff-viewer.blade.php`: `show-blame`
- `HistoryPanel.php`: `open-rebase-modal`

**Listened by**:
- `CommandPalette`: `toggle-command-palette`, `open-command-palette`, `open-command-palette-create-branch`
- `ShortcutHelp`: `open-shortcut-help`
- `SearchPanel`: `open-search`
- `SettingsModal`: `open-settings`
- `BlameView`: `show-blame`
- `RebasePanel`: `open-rebase-modal`

### Git Operation Events

Events dispatched after git operations complete.

| Event | Payload | Purpose |
|-------|---------|---------|
| `committed` | none | Commit succeeded |
| `stash-created` | none | Stash created |
| `prefill-updated` | none | Commit message prefill changed |
| `settings-updated` | none | Settings saved |

**Dispatched by**:
- `CommitPanel`: `committed`, `prefill-updated`
- `StagingPanel`: `stash-created`
- `RepoSidebar`: `stash-created`
- `SettingsModal`: `settings-updated`

### Notification Events

Events that display user feedback (success, error, warning).

| Event | Payload | Purpose |
|-------|---------|---------|
| `show-error` | `message: string`, `type: string`, `persistent: bool` | Display error/success/warning banner |
| `show-success` | `message: string` | Display success message |
| `show-warning` | `message: string` | Display warning message |

**Dispatched by**:
- `HandlesGitOperations` trait (auto-dispatches on error)
- `StagingPanel`, `CommitPanel`, `BranchManager`, `RepoSidebar`, `HistoryPanel`, `BlameView`, `ConflictResolver`, `RebasePanel`, `AppLayout`

**Listened by**:
- `ErrorBanner`: `show-error`

**Note**: `show-success` and `show-warning` are aliases that dispatch `show-error` with `type: 'success'` or `type: 'warning'`.

### Theme Events

Events related to theme switching.

| Event | Payload | Purpose |
|-------|---------|---------|
| `theme-updated` | `theme: string` | Theme changed (light/dark/system) |
| `theme-changed` | `theme: string` | Theme toggled via header button |

**Dispatched by**:
- `SettingsModal`: `theme-updated`
- `app-layout.blade.php`: `theme-changed` (Alpine.js)

**Listened by**:
- `app-layout.blade.php` (Alpine.js `@theme-updated.window` handler)

## Event Flow Diagrams

### Staging Flow

User stages a file → cache invalidation → status-updated → multiple components refresh.

```
┌─────────────────────────────────────────────────────────────────────────┐
│ USER INTERACTION                                                        │
│ User clicks "Stage" button next to file in StagingPanel                │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ LIVEWIRE COMPONENT: StagingPanel                                       │
│ File: app/Livewire/StagingPanel.php                                    │
│                                                                         │
│ stageFile(string $file)                                                │
│   └─> executeGitOperation(fn => $service->stageFile($file))           │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ TRAIT: HandlesGitOperations                                            │
│ File: app/Livewire/Concerns/HandlesGitOperations.php                   │
│                                                                         │
│ executeGitOperation(callable $operation)                               │
│   ├─> try { $result = $operation() }                                  │
│   ├─> catch { dispatch('show-error') }                                │
│   └─> success: dispatch('status-updated')                             │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ SERVICE: StagingService                                                │
│ File: app/Services/Git/StagingService.php                              │
│                                                                         │
│ stageFile(string $file)                                                │
│   ├─> commandRunner->run(['git', 'add', $file])                       │
│   └─> cache->invalidate(['status', 'diff'])                           │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ EVENT DISPATCH: status-updated                                         │
│ Payload: none (components fetch fresh data)                            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┬───────────────┐
                    ▼               ▼               ▼               ▼
        ┌───────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
        │ StagingPanel  │ │ CommitPanel │ │ RepoSidebar │ │  SyncPanel  │
        │ #[On('status- │ │ #[On('status│ │ #[On('status│ │ #[On('status│
        │   updated')]  │ │   -updated')]│ │   -updated')]│ │   -updated')]│
        │               │ │             │ │             │ │             │
        │ Reload file   │ │ Update      │ │ Refresh     │ │ Update      │
        │ list with     │ │ staged      │ │ branches,   │ │ ahead/behind│
        │ new staged    │ │ count       │ │ stashes     │ │ counts      │
        │ status        │ │             │ │             │ │             │
        └───────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
```

### Commit Flow

User commits → CommitService → committed event → status-updated → UI refresh.

```
┌─────────────────────────────────────────────────────────────────────────┐
│ USER INTERACTION                                                        │
│ User clicks "Commit" button or presses ⌘↵                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                               ▼
        ┌───────────────────────┐       ┌───────────────────────┐
        │ Alpine.js Keyboard    │       │ Flux Button Click     │
        │ @keydown.window       │       │ wire:click="commit"   │
        │ .meta.enter.prevent   │       │                       │
        │                       │       │                       │
        │ $dispatch('keyboard-  │       │                       │
        │   commit')            │       │                       │
        └───────────────────────┘       └───────────────────────┘
                    │                               │
                    └───────────────┬───────────────┘
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ LIVEWIRE COMPONENT: CommitPanel                                        │
│ File: app/Livewire/CommitPanel.php                                     │
│                                                                         │
│ #[On('keyboard-commit')]                                               │
│ commit()                                                                │
│   ├─> Validate message not empty                                      │
│   ├─> CommitService->commit($message, $amend)                         │
│   ├─> dispatch('committed')                                            │
│   ├─> dispatch('prefill-updated')                                      │
│   └─> Clear message, reset amend flag                                 │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ SERVICE: CommitService                                                 │
│ File: app/Services/Git/CommitService.php                               │
│                                                                         │
│ commit(string $message, bool $amend)                                   │
│   ├─> commandRunner->run(['git', 'commit', '-m', $message])           │
│   └─> cache->invalidate(['status', 'history', 'branches'])            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ EVENT DISPATCH: committed                                              │
│ Payload: none                                                           │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ TRAIT: HandlesGitOperations (auto-dispatch)                            │
│ dispatch('status-updated')                                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┬───────────────┐
                    ▼               ▼               ▼               ▼
        ┌───────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐
        │ StagingPanel  │ │ HistoryPanel│ │ RepoSidebar │ │ CommitPanel │
        │               │ │             │ │             │ │             │
        │ Reload file   │ │ Reload      │ │ Refresh     │ │ Reset       │
        │ list (now     │ │ commit log  │ │ branches    │ │ staged      │
        │ empty)        │ │ with new    │ │ (HEAD moved)│ │ count to 0  │
        │               │ │ commit      │ │             │ │             │
        └───────────────┘ └─────────────┘ └─────────────┘ └─────────────┘
```

### Branch Switch Flow

User switches branch → BranchService → status-updated → all components refresh.

```
┌─────────────────────────────────────────────────────────────────────────┐
│ USER INTERACTION                                                        │
│ User clicks branch name in BranchManager dropdown                      │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ LIVEWIRE COMPONENT: BranchManager                                      │
│ File: app/Livewire/BranchManager.php                                   │
│                                                                         │
│ switchBranch(string $name)                                             │
│   ├─> Check for uncommitted changes                                   │
│   ├─> Auto-stash if needed                                            │
│   ├─> BranchService->switchBranch($name)                              │
│   └─> Auto-unstash if stashed                                         │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ SERVICE: BranchService                                                 │
│ File: app/Services/Git/BranchService.php                               │
│                                                                         │
│ switchBranch(string $name)                                             │
│   ├─> commandRunner->run(['git', 'checkout', $name])                  │
│   └─> cache->invalidate(['status', 'branches', 'history', 'diff'])    │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ TRAIT: HandlesGitOperations (auto-dispatch)                            │
│ dispatch('status-updated')                                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
        ┌───────────────┬───────────┼───────────┬───────────┬───────────┐
        ▼               ▼           ▼           ▼           ▼           ▼
┌─────────────┐ ┌─────────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│StagingPanel │ │ CommitPanel │ │RepoSidebar│ │SyncPanel │ │HistoryPanel│ │DiffViewer│
│             │ │             │ │          │ │          │ │          │ │          │
│ Reload file │ │ Update      │ │ Refresh  │ │ Update   │ │ Reload   │ │ Clear    │
│ list for    │ │ staged      │ │ current  │ │ ahead/   │ │ commit   │ │ current  │
│ new branch  │ │ count       │ │ branch   │ │ behind   │ │ log for  │ │ diff     │
│ HEAD        │ │             │ │ name     │ │ counts   │ │ new HEAD │ │          │
└─────────────┘ └─────────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘
```

### Sync Flow

User pushes/pulls/fetches → RemoteService → status-updated → notification.

```
┌─────────────────────────────────────────────────────────────────────────┐
│ USER INTERACTION                                                        │
│ User clicks Push/Pull/Fetch button in header or via command palette    │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                               ▼
        ┌───────────────────────┐       ┌───────────────────────┐
        │ Header Button Click   │       │ Command Palette       │
        │ wire:click="push"     │       │ dispatch('palette-    │
        │                       │       │   push')              │
        └───────────────────────┘       └───────────────────────┘
                    │                               │
                    └───────────────┬───────────────┘
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ LIVEWIRE COMPONENT: SyncPanel                                          │
│ File: app/Livewire/SyncPanel.php                                       │
│                                                                         │
│ #[On('palette-push')]                                                  │
│ push()                                                                  │
│   ├─> RemoteService->push()                                            │
│   ├─> dispatch('status-updated', aheadBehind: [0, 0])                 │
│   └─> Show success notification                                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ SERVICE: RemoteService                                                 │
│ File: app/Services/Git/RemoteService.php                               │
│                                                                         │
│ push()                                                                  │
│   ├─> commandRunner->run(['git', 'push'])                             │
│   └─> cache->invalidate(['status', 'branches'])                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ EVENT DISPATCH: status-updated                                         │
│ Payload: stagedCount: 0, aheadBehind: [0, 0]                          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
        ┌───────────────┐ ┌─────────────┐ ┌─────────────┐
        │  SyncPanel    │ │ RepoSidebar │ │ ErrorBanner │
        │               │ │             │ │             │
        │ Update ahead/ │ │ Refresh     │ │ Show        │
        │ behind counts │ │ branches    │ │ "Pushed     │
        │ to [0, 0]     │ │ (tracking   │ │ successfully│
        │               │ │ updated)    │ │ " message   │
        └───────────────┘ └─────────────┘ └─────────────┘
```

## Keyboard Shortcut Pipeline

Keyboard shortcuts flow from Alpine.js event handlers → Livewire event dispatch → component listeners.

**File**: `resources/views/livewire/app-layout.blade.php`

```
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 1: User presses keyboard shortcut                                 │
│ Example: ⌘↵ (Command + Enter)                                          │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 2: Alpine.js captures keydown event                               │
│ File: resources/views/livewire/app-layout.blade.php (line 3)           │
│                                                                         │
│ @keydown.window.meta.enter.prevent="                                   │
│   if (!$wire.repoPath) return;                                         │
│   $wire.$dispatch('keyboard-commit')                                   │
│ "                                                                       │
│                                                                         │
│ Guards:                                                                 │
│ - .prevent: Prevents default browser behavior                          │
│ - if (!$wire.repoPath): Only works when repo is open                  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 3: Livewire event dispatched globally                             │
│ Event: 'keyboard-commit'                                                │
│ Scope: window (all components can listen)                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 4: Component listener receives event                              │
│ File: app/Livewire/CommitPanel.php (line 134)                          │
│                                                                         │
│ #[On('keyboard-commit')]                                               │
│ public function handleKeyboardCommit(): void                            │
│ {                                                                       │
│     $this->commit();                                                    │
│ }                                                                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 5: Component executes action                                      │
│ CommitPanel->commit() runs commit logic                                │
└─────────────────────────────────────────────────────────────────────────┘
```

**All Keyboard Shortcuts** (from `app-layout.blade.php`):

| Shortcut | Alpine.js Handler | Event Dispatched | Listener |
|----------|-------------------|------------------|----------|
| ⌘↵ | `@keydown.window.meta.enter.prevent` | `keyboard-commit` | `CommitPanel` |
| ⌘⇧↵ | `@keydown.window.meta.shift.enter.prevent` | `keyboard-commit-push` | `CommitPanel` |
| ⌘⇧K | `@keydown.window.meta.shift.k.prevent` | `keyboard-stage-all` | `StagingPanel` |
| ⌘⇧U | `@keydown.window.meta.shift.u.prevent` | `keyboard-unstage-all` | `StagingPanel` |
| ⌘⇧S | `@keydown.window.meta.shift.s.prevent` | `keyboard-stash` | `StagingPanel` |
| ⌘A | `@keydown.window.meta.a.prevent` | `keyboard-select-all` | `StagingPanel` |
| ⌘K | `@keydown.window.meta.k.prevent` | `toggle-command-palette` | `CommandPalette` |
| ⌘⇧P | `@keydown.window.meta.shift.p.prevent` | `toggle-command-palette` | `CommandPalette` |
| ⌘Z | `@keydown.window.meta.z.prevent` | `palette-undo-last-commit` | `CommitPanel` |
| Esc | `@keydown.window.escape.prevent` | `keyboard-escape` | Multiple |
| ⌘/ | `@keydown.window.meta.slash.prevent` | `open-shortcut-help` | `ShortcutHelp` |
| ⌘H | `@keydown.window.meta.h.prevent` | `toggle-history-panel` | Alpine.js (app-layout) |
| ⌘F | `@keydown.window.meta.f.prevent` | `open-search` | `SearchPanel` |
| ⌘L | `@keydown.window.meta.l.prevent` | `focus-commit-message` | `CommitPanel` |
| ⌘B | `@keydown.window.meta.b.prevent` | (direct method call) | `AppLayout->toggleSidebar()` |

**Note**: ⌘B is the only shortcut that calls a Livewire method directly (`$wire.toggleSidebar()`) instead of dispatching an event.

## Command Palette Event Dispatch

Command palette dispatches events when user executes commands.

**File**: `app/Livewire/CommandPalette.php`

```
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 1: User opens command palette (⌘K)                                │
│ CommandPalette->open() sets isOpen = true                              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 2: User types search query                                        │
│ CommandPalette->filteredCommands() filters 28 commands by query        │
│ Searches: label + keywords (e.g., "push", "upload", "send")            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 3: User selects command (Enter or click)                          │
│ CommandPalette->executeCommand(string $commandId)                      │
│                                                                         │
│ File: app/Livewire/CommandPalette.php (line 390)                       │
│                                                                         │
│ public function executeCommand(string $commandId): void                │
│ {                                                                       │
│     $command = collect(self::getCommands())                            │
│         ->firstWhere('id', $commandId);                                │
│                                                                         │
│     if ($command['requiresInput']) {                                   │
│         // Switch to input mode (e.g., branch name)                   │
│         $this->mode = 'input';                                         │
│         return;                                                         │
│     }                                                                   │
│                                                                         │
│     if ($command['event']) {                                           │
│         $this->dispatch($command['event']); // LINE 413                │
│     }                                                                   │
│                                                                         │
│     $this->close();                                                     │
│ }                                                                       │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 4: Event dispatched to listening component                        │
│ Example: $this->dispatch('palette-push')                               │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│ STEP 5: Component listener executes action                             │
│ File: app/Livewire/SyncPanel.php (line 170)                            │
│                                                                         │
│ #[On('palette-push')]                                                  │
│ public function handlePalettePush(): void                              │
│ {                                                                       │
│     $this->push();                                                      │
│ }                                                                       │
└─────────────────────────────────────────────────────────────────────────┘
```

**Command Registry** (from `CommandPalette::getCommands()`):

28 commands defined in `app/Livewire/CommandPalette.php` (lines 91-356). Each command has:
- `id`: Unique identifier
- `label`: Display name
- `shortcut`: Keyboard shortcut (or null)
- `event`: Livewire event to dispatch
- `keywords`: Search terms
- `requiresInput`: Whether command needs user input
- `icon`: Phosphor icon name

**Commands Requiring Input**:
- `create-branch`: Switches to input mode, prompts for branch name, then dispatches `palette-create-branch` with `name` parameter.

**Disabled Commands**:
Commands are disabled when:
- No repo is open (most commands)
- No staged files (`commit`, `commit-push`)

Disabled state is computed in `CommandPalette->getDisabledCommands()` and updated on `status-updated` and `repo-switched` events.

## Master Event Reference Table

Complete list of all Livewire events in gitty.

| Event | Payload | Dispatched By | Listened By | Purpose |
|-------|---------|---------------|-------------|---------|
| `status-updated` | `stagedCount: int`, `aheadBehind: array` | `HandlesGitOperations` trait, `SyncPanel`, `StagingPanel`, `BranchManager`, `RepoSidebar`, `CommitPanel`, `HistoryPanel`, `ConflictResolver`, `RebasePanel` | `CommitPanel`, `StagingPanel`, `RepoSidebar`, `SyncPanel`, `HistoryPanel`, `CommandPalette`, `ConflictResolver`, `RebasePanel` | Fired after every git mutation. Triggers UI refresh across all components. |
| `file-selected` | `file: string`, `staged: bool` | `StagingPanel`, `SearchPanel`, `blame-view.blade.php` | `DiffViewer` | User selects file. DiffViewer loads diff. |
| `repo-switched` | `path: string` | `RepoSwitcher` | `AppLayout`, `CommandPalette`, `HistoryPanel`, `BlameView`, `ConflictResolver`, `RebasePanel` | User switches repository. All components reload. |
| `committed` | none | `CommitPanel` | none | Commit succeeded. Triggers status refresh. |
| `stash-created` | none | `StagingPanel`, `RepoSidebar` | `RepoSidebar` | Stash created. RepoSidebar refreshes stash list. |
| `refresh-staging` | none | `DiffViewer`, `RepoSidebar`, `HistoryPanel`, `ConflictResolver` | `StagingPanel` | Manual staging panel refresh. |
| `commit-selected` | `sha: string` | `HistoryPanel`, `BlameView`, `SearchPanel` | none | User selects commit in history or blame view. |
| `remote-updated` | none | `AutoFetchIndicator` | `SyncPanel` | Auto-fetch detected remote changes. |
| `keyboard-commit` | none | `app-layout.blade.php` (Alpine.js) | `CommitPanel` | ⌘↵ pressed. Trigger commit. |
| `keyboard-commit-push` | none | `app-layout.blade.php` (Alpine.js) | `CommitPanel` | ⌘⇧↵ pressed. Trigger commit and push. |
| `keyboard-stage-all` | none | `app-layout.blade.php` (Alpine.js) | `StagingPanel` | ⌘⇧K pressed. Stage all files. |
| `keyboard-unstage-all` | none | `app-layout.blade.php` (Alpine.js) | `StagingPanel` | ⌘⇧U pressed. Unstage all files. |
| `keyboard-stash` | none | `app-layout.blade.php` (Alpine.js) | `StagingPanel` | ⌘⇧S pressed. Stash all changes. |
| `keyboard-select-all` | none | `app-layout.blade.php` (Alpine.js) | `StagingPanel` | ⌘A pressed. Select all files. |
| `keyboard-escape` | none | `app-layout.blade.php` (Alpine.js) | Multiple | Esc pressed. Close modals, clear selection. |
| `palette-discard-all` | none | `CommandPalette` | `StagingPanel` | Discard all unstaged changes. |
| `palette-toggle-view` | none | `CommandPalette` | `StagingPanel` | Toggle flat/tree view. |
| `palette-toggle-amend` | none | `CommandPalette` | `CommitPanel` | Toggle amend mode. |
| `palette-undo-last-commit` | none | `CommandPalette`, `app-layout.blade.php` (⌘Z) | `CommitPanel` | Undo last commit. |
| `palette-push` | none | `CommandPalette` | `SyncPanel` | Push to remote. |
| `palette-pull` | none | `CommandPalette` | `SyncPanel` | Pull from remote. |
| `palette-fetch` | none | `CommandPalette` | `SyncPanel` | Fetch from remote. |
| `palette-fetch-all` | none | `CommandPalette` | `SyncPanel` | Fetch from all remotes. |
| `palette-force-push` | none | `CommandPalette` | `SyncPanel` | Force push with lease. |
| `palette-create-branch` | `name: string` | `CommandPalette` | `BranchManager` | Create new branch. |
| `palette-toggle-sidebar` | none | `CommandPalette` | `AppLayout` | Toggle sidebar visibility. |
| `palette-open-folder` | none | `CommandPalette` | `RepoSwitcher` | Open folder picker. |
| `palette-toggle-diff-view` | none | `CommandPalette` | `DiffViewer` | Toggle diff view mode. |
| `palette-abort-merge` | none | `CommandPalette` | `ConflictResolver` | Abort merge in progress. |
| `palette-create-tag` | none | `CommandPalette` | `RepoSidebar` | Create git tag. |
| `palette-open-in-editor` | none | `CommandPalette` | `DiffViewer` | Open file in external editor. |
| `palette-continue-rebase` | none | `CommandPalette` | `RebasePanel` | Continue rebase after conflict resolution. |
| `palette-abort-rebase` | none | `CommandPalette` | `RebasePanel` | Abort rebase in progress. |
| `toggle-command-palette` | none | `app-layout.blade.php` (⌘K, ⌘⇧P) | `CommandPalette` | Toggle command palette visibility. |
| `open-command-palette` | none | `CommandPalette` | `CommandPalette` | Open command palette (always opens). |
| `open-command-palette-create-branch` | none | `branch-manager.blade.php` | `CommandPalette` | Open command palette in branch creation mode. |
| `open-shortcut-help` | none | `app-layout.blade.php` (⌘/) | `ShortcutHelp` | Open keyboard shortcuts help modal. |
| `toggle-history-panel` | none | `app-layout.blade.php` (⌘H), `history-panel.blade.php`, `BlameView` | Alpine.js (app-layout) | Toggle history panel visibility. |
| `open-search` | none | `app-layout.blade.php` (⌘F) | `SearchPanel` | Open search panel. |
| `focus-commit-message` | none | `app-layout.blade.php` (⌘L) | `CommitPanel` | Focus commit message textarea. |
| `open-settings` | none | `CommandPalette` | `SettingsModal` | Open settings modal. |
| `show-blame` | `file: string` | `diff-viewer.blade.php` | `BlameView` | Show blame view for file. |
| `open-rebase-modal` | `ontoCommit: string`, `count: int` | `HistoryPanel` | `RebasePanel` | Open rebase modal with commit context. |
| `show-error` | `message: string`, `type: string`, `persistent: bool` | `HandlesGitOperations` trait, `StagingPanel`, `CommitPanel`, `BranchManager`, `RepoSidebar`, `HistoryPanel`, `BlameView`, `ConflictResolver`, `RebasePanel`, `AppLayout`, `DiffViewer` | `ErrorBanner` | Display error/success/warning banner. |
| `show-success` | `message: string` | `HistoryPanel`, `RebasePanel` | `ErrorBanner` | Display success message (alias for show-error with type='success'). |
| `show-warning` | `message: string` | `RebasePanel` | `ErrorBanner` | Display warning message (alias for show-error with type='warning'). |
| `prefill-updated` | none | `CommitPanel` | none | Commit message prefill changed. |
| `settings-updated` | none | `SettingsModal` | none | Settings saved. |
| `theme-updated` | `theme: string` | `SettingsModal` | `app-layout.blade.php` (Alpine.js) | Theme changed (light/dark/system). |
| `theme-changed` | `theme: string` | `app-layout.blade.php` (Alpine.js) | none | Theme toggled via header button. |

**Total Events**: 50

**Most Important Events**:
1. `status-updated` (8+ listeners, fired after every git operation)
2. `file-selected` (drives diff viewer)
3. `repo-switched` (reloads entire app state)
4. `show-error` (universal notification system)

**Event Naming Conventions**:
- `keyboard-*`: Dispatched from Alpine.js keyboard shortcuts
- `palette-*`: Dispatched from command palette
- `open-*`: Opens modal/panel
- `toggle-*`: Toggles visibility
- `show-*`: Displays content
- `*-updated`: State change notification
- `*-selected`: User selection
- `*-created`: Resource created

**HandlesGitOperations Pattern**:
The `HandlesGitOperations` trait (used by most components) automatically dispatches:
- `status-updated` on success
- `show-error` on failure

This eliminates boilerplate and ensures consistent event dispatch across all git operations.
