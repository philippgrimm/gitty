# gitty Architecture

Comprehensive architecture overview for gitty, a macOS-native git client built with NativePHP, Laravel, Livewire, and Flux UI.

## Table of Contents

1. [System Overview](#system-overview)
2. [Architecture Layers](#architecture-layers)
3. [Boot Process](#boot-process)
4. [Core Patterns](#core-patterns)
   - [AbstractGitService](#abstractgitservice)
   - [GitCommandRunner](#gitcommandrunner)
   - [Service Instantiation](#service-instantiation)
   - [DTO Parsing](#dto-parsing)
   - [Cache Strategy](#cache-strategy)
   - [Concurrency Control](#concurrency-control)
   - [Error Handling Pipeline](#error-handling-pipeline)
5. [Component Layout](#component-layout)
6. [Data Flow Trace](#data-flow-trace)
7. [Repository Management](#repository-management)
8. [NativePHP Integration](#nativephp-integration)
9. [Design System Reference](#design-system-reference)

---

## System Overview

gitty is a macOS-native git client that wraps git CLI commands in a reactive, desktop-native interface. The application runs as an Electron app via NativePHP, with all UI logic handled server-side through Laravel and Livewire.

### Tech Stack

- **NativePHP** — Laravel + Electron wrapper for macOS desktop apps
- **Laravel 12** — PHP web framework with streamlined structure
- **Livewire 4** — Reactive server-side components (no JavaScript required)
- **Flux UI Free v2** — Official Livewire component library
- **Tailwind CSS v4** — JIT utility-first CSS framework
- **Catppuccin Latte** — Color palette (light theme)
- **Phosphor Icons** — Icon set (light variant for headers)
- **SQLite** — Local persistence for repository list and settings

### Key Characteristics

- **Server-side rendering**: All state lives in PHP. Livewire handles reactivity.
- **Git CLI wrapper**: No libgit2 or native bindings. Pure CLI execution via Laravel Process.
- **Per-request services**: Services are instantiated per-operation, not dependency-injected.
- **Stateless services**: Each service receives `repoPath` and operates independently.
- **Cache-based locking**: Prevents concurrent git operations on the same repository.

---

## Architecture Layers

gitty follows a strict 6-layer architecture from git CLI to Blade views:

```
┌─────────────────────────────────────────────────────────────────┐
│ Layer 6: Blade/Flux Views                                       │
│ resources/views/livewire/*.blade.php                            │
│ - Flux UI components, Alpine.js directives                     │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 5: Livewire Components                                    │
│ app/Livewire/*.php                                              │
│ - Reactive state, event handling, user interactions            │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 4: DTOs (Data Transfer Objects)                          │
│ app/DTOs/*.php                                                  │
│ - Immutable value objects, factory methods, type safety        │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 3: Git Services                                           │
│ app/Services/Git/*.php                                          │
│ - Business logic, cache invalidation, operation orchestration  │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 2: GitCommandRunner                                       │
│ app/Services/Git/GitCommandRunner.php                           │
│ - Command building, argument escaping, Process facade wrapper  │
└─────────────────────────────────────────────────────────────────┘
                              ▲
                              │
┌─────────────────────────────────────────────────────────────────┐
│ Layer 1: Git CLI                                                │
│ /usr/bin/git (system binary)                                    │
│ - Actual git operations, porcelain v2 output                   │
└─────────────────────────────────────────────────────────────────┘
```

### Layer Responsibilities

1. **Git CLI**: System git binary. Executes commands, returns porcelain v2 output.
2. **GitCommandRunner**: Builds command strings, escapes arguments, wraps Laravel Process.
3. **Git Services**: Orchestrate operations, invalidate caches, handle business logic.
4. **DTOs**: Parse git output into typed PHP objects. Immutable, factory-based.
5. **Livewire Components**: Manage UI state, dispatch events, handle user interactions.
6. **Blade/Flux Views**: Render HTML, bind to Livewire properties, Alpine.js for client-side interactivity.

---

## Boot Process

The application boots in this sequence:

### 1. NativePHP Window Creation

`app/Providers/NativeAppServiceProvider.php` creates the Electron window:

```php
$window = Window::open()
    ->title('Gitty')
    ->width(1200)
    ->height(800)
    ->minWidth(900)
    ->minHeight(600);

if (method_exists($window, 'titleBarStyle')) {
    $window->titleBarStyle('hiddenInset');
}
```

- **Window dimensions**: 1200x800 (min 900x600)
- **Title bar style**: `hiddenInset` (macOS traffic lights inset into content area)

### 2. Native Menu Bar

`NativeAppServiceProvider` registers macOS menu bar with hotkeys:

- **File**: Open Repository (⌘O), Settings (⌘,), Quit (⌘Q)
- **Git**: Commit (⌘↵), Push (⌘P), Pull (⌘⇧P), Fetch (⌘T), Stash
- **Branch**: Switch (⌘B), Create (⌘⇧B), Delete, Merge
- **Help**: About Gitty

Menu events dispatch to Livewire components via `event('menu:git:commit')`.

### 3. Route Resolution

`routes/web.php` serves the single-page app:

```php
Route::get('/', AppLayout::class);
```

All requests route to `AppLayout` Livewire component.

### 4. AppLayout Mount

`app/Livewire/AppLayout.php` mounts as the root component:

```php
public function mount(?string $repoPath = null): void
{
    // Check git binary exists
    if (! \App\Services\Git\GitConfigValidator::checkGitBinary()) {
        $this->dispatch('show-error', message: 'Git is not installed', ...);
    }

    // Resolve repo path from RepoManager or most recent
    if ($repoPath !== null) {
        $this->repoPath = $repoPath;
    } else {
        $repoManager = app(\App\Services\RepoManager::class);
        $currentRepo = $repoManager->currentRepo();
        
        if ($currentRepo && is_dir($currentRepo->path.'/.git')) {
            $this->repoPath = $currentRepo->path;
        } else {
            $this->repoPath = $this->loadMostRecentRepo();
        }
    }
}
```

### 5. Child Component Initialization

`AppLayout` passes `repoPath` as a prop to all child components:

```blade
@livewire('staging-panel', ['repoPath' => $repoPath], key('staging-panel-' . $repoPath))
@livewire('commit-panel', ['repoPath' => $repoPath], key('commit-panel-' . $repoPath))
@livewire('diff-viewer', ['repoPath' => $repoPath], key('diff-viewer-' . $repoPath))
```

Each component receives `repoPath` and creates service instances as needed.

---

## Core Patterns

### AbstractGitService

All git services extend `AbstractGitService`, which validates the repository and provides shared dependencies.

**File**: `app/Services/Git/AbstractGitService.php`

```php
abstract class AbstractGitService
{
    protected GitCacheService $cache;
    protected GitCommandRunner $commandRunner;

    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new InvalidRepositoryException($this->repoPath);
        }
        $this->cache = new GitCacheService;
        $this->commandRunner = new GitCommandRunner($this->repoPath);
    }
}
```

**Responsibilities**:

1. Validate `.git` directory exists (throws `InvalidRepositoryException` if not)
2. Create `GitCacheService` instance
3. Create `GitCommandRunner` instance with `repoPath`
4. Provide `$cache` and `$commandRunner` to all child services

**Example child service**: `app/Services/Git/StagingService.php`

```php
class StagingService extends AbstractGitService
{
    public function stageFile(string $file): void
    {
        $this->commandRunner->run('add', [$file]);
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }
}
```

### GitCommandRunner

Builds git command strings, escapes arguments, and wraps Laravel's Process facade.

**File**: `app/Services/Git/GitCommandRunner.php`

```php
class GitCommandRunner
{
    public function __construct(
        protected string $repoPath,
    ) {}

    public function run(string $subcommand, array $args = []): ProcessResult
    {
        $command = $this->buildCommand($subcommand, $args);
        return Process::path($this->repoPath)->run($command);
    }

    public function runOrFail(string $subcommand, array $args = [], 
                              string $errorPrefix = ''): ProcessResult
    {
        $result = $this->run($subcommand, $args);
        
        if (! $result->successful()) {
            $command = $errorPrefix !== '' ? $errorPrefix : "git {$subcommand}";
            throw new GitCommandFailedException($command, 
                                                $result->errorOutput(), 
                                                $result->exitCode());
        }
        
        return $result;
    }

    public function runWithInput(string $subcommand, string $input): ProcessResult
    {
        $command = "git {$subcommand}";
        return Process::path($this->repoPath)->input($input)->run($command);
    }

    private function buildCommand(string $subcommand, array $args): string
    {
        $command = "git {$subcommand}";
        
        if (! empty($args)) {
            $escapedArgs = array_map('escapeshellarg', $args);
            $command .= ' '.implode(' ', $escapedArgs);
        }
        
        return $command;
    }
}
```

**Methods**:

- `run()`: Execute command, return `ProcessResult` (success or failure)
- `runOrFail()`: Execute command, throw `GitCommandFailedException` on failure
- `runWithInput()`: Execute command with stdin (for `git apply`, etc.)
- `buildCommand()`: Build command string with escaped arguments via `escapeshellarg()`

**Example usage**:

```php
// Simple command
$result = $commandRunner->run('status', ['--porcelain=v2', '--branch']);

// Throw on failure
$result = $commandRunner->runOrFail('commit', ['-m', 'Initial commit']);

// With stdin
$result = $commandRunner->runWithInput('apply --cached', $patchContent);
```

### Service Instantiation

Services are **not** dependency-injected. They are instantiated per-request with `repoPath`.

**Pattern**:

```php
// In Livewire component
public function stageFile(string $path): void
{
    $service = new StagingService($this->repoPath);
    $service->stageFile($path);
}
```

**Why not DI?**

- Services are stateless except for `repoPath`
- Each operation may target a different repository
- Per-request instantiation keeps services simple and testable

### DTO Parsing

DTOs (Data Transfer Objects) parse git's machine-readable output into typed PHP objects.

**File**: `app/DTOs/GitStatus.php`

```php
readonly class GitStatus
{
    public function __construct(
        public string $branch,
        public ?string $upstream,
        public AheadBehind $aheadBehind,
        /** @var Collection<int, ChangedFile> */
        public Collection $changedFiles,
    ) {}

    public static function fromOutput(string $output): self
    {
        $lines = explode("\n", trim($output));
        $branch = '';
        $upstream = null;
        $ahead = 0;
        $behind = 0;
        $changedFiles = collect();

        foreach ($lines as $line) {
            if (str_starts_with($line, '# branch.head ')) {
                $branch = trim(substr($line, 14));
            } elseif (str_starts_with($line, '# branch.upstream ')) {
                $upstream = trim(substr($line, 18));
            } elseif (str_starts_with($line, '# branch.ab ')) {
                $parts = explode(' ', trim(substr($line, 12)));
                $ahead = (int) ltrim($parts[0], '+');
                $behind = (int) ltrim($parts[1], '-');
            } elseif (str_starts_with($line, '1 ')) {
                // Ordinary changed entry
                $parts = preg_split('/\s+/', $line, 9);
                $changedFiles->push(new ChangedFile(
                    path: $parts[8] ?? '',
                    oldPath: null,
                    indexStatus: $parts[1][0] ?? '.',
                    worktreeStatus: $parts[1][1] ?? '.',
                ));
            }
            // ... more parsing logic
        }

        return new self($branch, $upstream, 
                       new AheadBehind($ahead, $behind), 
                       $changedFiles);
    }
}
```

**Characteristics**:

- **Immutable**: `readonly` classes or `readonly` properties
- **Factory methods**: `fromOutput()`, `fromLine()`, `fromBranchLine()`
- **Type-safe**: Strict types, no nullable properties unless necessary
- **Porcelain v2**: Parse git's machine-readable formats (`--porcelain=v2`, `--branch`)

**Example DTO**: `app/DTOs/ChangedFile.php`

```php
class ChangedFile implements \ArrayAccess
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $oldPath,
        public readonly string $indexStatus,
        public readonly string $worktreeStatus,
    ) {}

    public function isStaged(): bool
    {
        return $this->indexStatus !== '.' && 
               $this->indexStatus !== '?' && 
               $this->indexStatus !== '!';
    }

    public function statusLabel(): string
    {
        if ($this->isUntracked()) return 'untracked';
        if ($this->isUnmerged()) return 'unmerged';
        
        $status = $this->indexStatus !== '.' 
            ? $this->indexStatus 
            : $this->worktreeStatus;
        
        return match ($status) {
            'M' => 'modified',
            'A' => 'added',
            'D' => 'deleted',
            'R' => 'renamed',
            default => 'unknown',
        };
    }
}
```

### Cache Strategy

`GitCacheService` provides group-based cache invalidation with per-operation TTLs.

**File**: `app/Services/Git/GitCacheService.php`

```php
class GitCacheService
{
    private const CACHE_PREFIX = 'gitty';

    private const GROUPS = [
        'status' => ['status', 'diff'],
        'history' => ['log'],
        'branches' => ['branches'],
        'remotes' => ['remotes'],
        'stashes' => ['stashes'],
        'tags' => ['tags'],
    ];

    public function get(string $repoPath, string $key, 
                       callable $callback, int $ttl): mixed
    {
        $cacheKey = $this->buildCacheKey($repoPath, $key);
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    public function invalidateGroup(string $repoPath, string $group): void
    {
        if (! isset(self::GROUPS[$group])) return;
        
        $keys = self::GROUPS[$group];
        foreach ($keys as $key) {
            $this->invalidate($repoPath, $key);
        }
    }

    private function buildCacheKey(string $repoPath, string $key): string
    {
        $hash = md5($repoPath);
        return self::CACHE_PREFIX.":{$hash}:{$key}";
    }
}
```

**Cache key format**: `gitty:{md5(repoPath)}:{key}`

**Groups**:

- `status`: status, diff (invalidated on stage/unstage/commit)
- `history`: log (invalidated on commit/rebase/merge)
- `branches`: branches (invalidated on branch create/delete/checkout)
- `remotes`: remotes (invalidated on remote add/remove)
- `stashes`: stashes (invalidated on stash save/pop/drop)
- `tags`: tags (invalidated on tag create/delete)

**TTL examples** (defined in individual services):

- Status: 5 seconds
- Log: 60 seconds
- Branches: 30 seconds

**Usage**:

```php
// In a service method
public function status(): GitStatus
{
    return $this->cache->get(
        $this->repoPath,
        'status',
        fn() => $this->fetchStatus(),
        5 // TTL in seconds
    );
}

// After mutating operation
public function commit(string $message): void
{
    $this->commandRunner->runOrFail('commit', ['-m', $message]);
    $this->cache->invalidateGroup($this->repoPath, 'status');
    $this->cache->invalidateGroup($this->repoPath, 'history');
}
```

### Concurrency Control

`GitOperationQueue` prevents parallel git operations on the same repository using cache-based locks.

**File**: `app/Services/Git/GitOperationQueue.php`

```php
class GitOperationQueue
{
    protected string $lockKey;

    public function __construct(
        protected string $repoPath,
    ) {
        $this->lockKey = 'git-op-'.md5($this->repoPath);
    }

    public function execute(callable $operation): mixed
    {
        $lock = Cache::lock($this->lockKey, 30);
        
        if (! $lock->get()) {
            throw new GitOperationInProgressException($this->repoPath);
        }
        
        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }

    public function isLocked(): bool
    {
        $lock = Cache::lock($this->lockKey, 0);
        
        if ($lock->get()) {
            $lock->release();
            return false;
        }
        
        return true;
    }
}
```

**Lock key format**: `git-op-{md5(repoPath)}`

**Lock timeout**: 30 seconds (prevents deadlocks if operation crashes)

**Usage**:

```php
$queue = new GitOperationQueue($this->repoPath);

$queue->execute(function() {
    // Long-running operation (e.g., fetch, pull, rebase)
    $this->commandRunner->runOrFail('pull', ['--rebase']);
});
```

### Error Handling Pipeline

Git errors flow through a standardized pipeline: command failure → exception → translation → user-friendly message → Livewire event.

**File**: `app/Services/Git/GitErrorHandler.php`

```php
class GitErrorHandler
{
    public static function translate(string $gitError): string
    {
        if (empty($gitError)) return '';
        
        // Not a git repository
        if (str_contains($gitError, 'fatal: not a git repository')) {
            return 'This folder is not a git repository';
        }
        
        // Push rejected
        if (str_contains($gitError, 'rejected')) {
            return 'Push rejected. Pull remote changes first.';
        }
        
        // Authentication failed
        if (str_contains($gitError, 'Authentication failed')) {
            return 'Authentication failed. Check your credentials.';
        }
        
        // Uncommitted changes blocking checkout
        if (str_contains($gitError, 'Your local changes to the following files')) {
            return 'Cannot switch branches: You have uncommitted changes.';
        }
        
        // Return original error if no pattern matches
        return $gitError;
    }
}
```

**File**: `app/Livewire/Concerns/HandlesGitOperations.php`

```php
trait HandlesGitOperations
{
    protected function executeGitOperation(callable $operation, 
                                          bool $dispatchStatusUpdate = true): mixed
    {
        try {
            $result = $operation();
            $this->error = '';
            
            if ($dispatchStatusUpdate) {
                $this->dispatch('status-updated');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', 
                          message: $this->error, 
                          type: 'error', 
                          persistent: false);
            
            return null;
        }
    }
}
```

**Flow**:

1. Git command fails → `GitCommandFailedException` thrown
2. `HandlesGitOperations::executeGitOperation()` catches exception
3. `GitErrorHandler::translate()` maps error to user-friendly message
4. Livewire dispatches `show-error` event to `ErrorBanner` component
5. On success: dispatches `status-updated` event to refresh UI

**Usage in components**:

```php
use App\Livewire\Concerns\HandlesGitOperations;

class StagingPanel extends Component
{
    use HandlesGitOperations;

    public function stageFile(string $path): void
    {
        $this->executeGitOperation(function() use ($path) {
            $service = new StagingService($this->repoPath);
            $service->stageFile($path);
        });
    }
}
```

---

## Component Layout

The UI is structured as a tree of Livewire components, with `AppLayout` as the root.

```
AppLayout (root component)
├── ErrorBanner (overlay, listens for 'show-error')
├── CommandPalette (overlay, ⌘K to open)
├── ShortcutHelp (overlay, ⌘/ to open)
├── SearchPanel (overlay, ⌘F to open)
│
├── Header Bar (36px fixed height, bg-[#e6e9ef])
│   ├── Traffic Light Spacer (64px, -webkit-app-region: drag)
│   ├── Sidebar Toggle (⌘B)
│   ├── RepoSwitcher (folder icon + dropdown)
│   ├── BranchManager (git-branch icon + dropdown)
│   ├── [flex-1 spacer]
│   ├── SyncPanel (push/pull/fetch buttons)
│   └── Theme Toggle (sun/moon icon)
│
└── Main Content (flex-1, overflow-hidden)
    ├── Sidebar (250px, collapsible, bg-[#eff1f5])
    │   └── RepoSidebar
    │       ├── Stashes section
    │       └── Tags section
    │
    └── Work Area (flex-1, resizable panels)
        ├── Left Panel (resizable, default 33%)
        │   ├── StagingPanel (flex-1)
        │   │   ├── Staged Files section
        │   │   ├── Changes section
        │   │   └── Untracked Files section
        │   └── CommitPanel (border-t)
        │       ├── Commit message textarea
        │       └── Commit button (split: Commit / Commit & Push)
        │
        ├── Resize Handle (5px, cursor-col-resize)
        │
        └── Right Panel (flex-1, switchable)
            ├── DiffViewer (default, shown on file-selected)
            ├── HistoryPanel (⌘H to toggle)
            └── BlameView (shown on show-blame event)

Conditional Overlays (shown when needed):
├── ConflictResolver (merge conflicts detected)
└── RebasePanel (rebase in progress)
```

### Layout Dimensions

- **Header**: 36px (`h-9`)
- **Sidebar**: 250px (collapsible to 0px)
- **Left panel**: Default 33% of work area (resizable, min 200px, max 50%)
- **Resize handle**: 5px
- **Right panel**: Remaining space

### Component Communication

Components communicate via Livewire events:

- `status-updated`: Fired after git operations (commit, stage, pull, etc.)
- `show-error`: Display error banner with message
- `file-selected`: Switch right panel to DiffViewer
- `toggle-history-panel`: Switch right panel to HistoryPanel
- `show-blame`: Switch right panel to BlameView
- `repo-switched`: Invalidate caches, update repoPath
- `keyboard-commit`: Trigger commit (⌘↵)
- `keyboard-stage-all`: Stage all files (⌘⇧K)

---

## Data Flow Trace

Step-by-step trace of **staging a file** from user click to UI update:

### 1. User Interaction

User clicks the stage button (plus icon) next to a file in the Changes section.

**File**: `resources/views/livewire/staging-panel.blade.php`

```blade
<flux:button wire:click="stageFile('{{ $file->path }}')" ...>
    <x-phosphor-plus class="w-4 h-4" />
</flux:button>
```

### 2. Livewire Method Call

`StagingPanel::stageFile()` is invoked with the file path.

**File**: `app/Livewire/StagingPanel.php`

```php
public function stageFile(string $path): void
{
    $this->executeGitOperation(function() use ($path) {
        $service = new StagingService($this->repoPath);
        $service->stageFile($path);
    });
}
```

### 3. Error Handling Wrapper

`HandlesGitOperations::executeGitOperation()` wraps the call in try/catch.

**File**: `app/Livewire/Concerns/HandlesGitOperations.php`

```php
protected function executeGitOperation(callable $operation, 
                                      bool $dispatchStatusUpdate = true): mixed
{
    try {
        $result = $operation();
        $this->error = '';
        
        if ($dispatchStatusUpdate) {
            $this->dispatch('status-updated');
        }
        
        return $result;
    } catch (\Exception $e) {
        $this->error = GitErrorHandler::translate($e->getMessage());
        $this->dispatch('show-error', message: $this->error, ...);
        return null;
    }
}
```

### 4. Service Instantiation

`StagingService` is created with `repoPath`.

**File**: `app/Services/Git/StagingService.php`

```php
class StagingService extends AbstractGitService
{
    public function stageFile(string $file): void
    {
        $this->commandRunner->run('add', [$file]);
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }
}
```

### 5. Command Execution

`GitCommandRunner::run()` builds and executes `git add <file>`.

**File**: `app/Services/Git/GitCommandRunner.php`

```php
public function run(string $subcommand, array $args = []): ProcessResult
{
    $command = $this->buildCommand($subcommand, $args);
    return Process::path($this->repoPath)->run($command);
}

private function buildCommand(string $subcommand, array $args): string
{
    $command = "git {$subcommand}";
    
    if (! empty($args)) {
        $escapedArgs = array_map('escapeshellarg', $args);
        $command .= ' '.implode(' ', $escapedArgs);
    }
    
    return $command;
}
```

**Executed command**: `git add 'path/to/file.php'`

### 6. Cache Invalidation

`GitCacheService::invalidateGroup()` clears the `status` group.

**File**: `app/Services/Git/GitCacheService.php`

```php
public function invalidateGroup(string $repoPath, string $group): void
{
    if (! isset(self::GROUPS[$group])) return;
    
    $keys = self::GROUPS[$group];
    foreach ($keys as $key) {
        $this->invalidate($repoPath, $key);
    }
}
```

**Invalidated keys**: `gitty:{md5(repoPath)}:status`, `gitty:{md5(repoPath)}:diff`

### 7. Status Update Event

`executeGitOperation()` dispatches `status-updated` event.

```php
$this->dispatch('status-updated');
```

### 8. Component Refresh

`StagingPanel::refreshStatus()` re-fetches git status.

```php
#[On('status-updated')]
public function refreshStatus(): void
{
    $gitService = new GitService($this->repoPath);
    $status = $gitService->status();
    
    $this->stagedFiles = $status->changedFiles->filter->isStaged();
    $this->unstagedFiles = $status->changedFiles->filter->isUnstaged();
    $this->untrackedFiles = $status->changedFiles->filter->isUntracked();
}
```

### 9. DTO Parsing

`GitStatus::fromOutput()` parses `git status --porcelain=v2 --branch`.

**File**: `app/DTOs/GitStatus.php`

```php
public static function fromOutput(string $output): self
{
    $lines = explode("\n", trim($output));
    $changedFiles = collect();
    
    foreach ($lines as $line) {
        if (str_starts_with($line, '1 ')) {
            // Ordinary changed entry
            $parts = preg_split('/\s+/', $line, 9);
            $changedFiles->push(new ChangedFile(
                path: $parts[8] ?? '',
                oldPath: null,
                indexStatus: $parts[1][0] ?? '.',
                worktreeStatus: $parts[1][1] ?? '.',
            ));
        }
    }
    
    return new self($branch, $upstream, $aheadBehind, $changedFiles);
}
```

### 10. UI Re-render

Livewire detects property changes (`$stagedFiles`, `$unstagedFiles`) and re-renders the view. The file moves from "Changes" to "Staged Files" section.

### 11. Other Components React

`CommitPanel`, `SyncPanel`, and `DiffViewer` also listen for `status-updated` and refresh their state.

---

## Repository Management

gitty tracks opened repositories in a SQLite database and manages the current repository via cache.

### Repository Model

**File**: `app/Models/Repository.php`

```php
class Repository extends Model
{
    protected $fillable = [
        'path',
        'name',
        'last_opened_at',
    ];

    protected function casts(): array
    {
        return [
            'last_opened_at' => 'datetime',
        ];
    }
}
```

**Schema**:

- `id`: Primary key
- `path`: Absolute path to repository (unique)
- `name`: Display name (defaults to `basename($path)`)
- `last_opened_at`: Timestamp of last access

### RepoManager Service

**File**: `app/Services/RepoManager.php`

```php
class RepoManager
{
    public function openRepo(string $path): Repository
    {
        $gitDir = rtrim($path, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository");
        }
        
        $name = basename($path);
        
        $repo = Repository::firstOrCreate(
            ['path' => $path],
            ['name' => $name]
        );
        
        $repo->forceFill(['last_opened_at' => now()])->save();
        
        $this->setCurrentRepo($repo);
        
        return $repo;
    }

    public function recentRepos(int $limit = 20): Collection
    {
        return Repository::whereNotNull('last_opened_at')
            ->orderBy('last_opened_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function currentRepo(): ?Repository
    {
        return Repository::find(Cache::get('current_repo_id'));
    }

    public function setCurrentRepo(Repository $repo): void
    {
        Cache::put('current_repo_id', $repo->id);
    }
}
```

### Current Repository Tracking

The current repository is tracked via `Cache::get('current_repo_id')`, not session or database.

**Why cache?**

- Fast access (no database query)
- Persists across requests
- Cleared on app restart (intentional: fresh state on launch)

### Repository Switching

When the user switches repositories via `RepoSwitcher`:

1. `RepoSwitcher` dispatches `repo-switched` event with new path
2. `AppLayout::handleRepoSwitched()` invalidates all caches for previous repo
3. `AppLayout` updates `$repoPath` property
4. All child components receive new `repoPath` and re-render

**File**: `app/Livewire/AppLayout.php`

```php
#[On('repo-switched')]
public function handleRepoSwitched(string $path): void
{
    if ($this->previousRepoPath && $this->previousRepoPath !== $path) {
        $cache = new GitCacheService;
        $cache->invalidateAll($this->previousRepoPath);
    }
    
    $this->previousRepoPath = $path;
    $this->repoPath = $path;
}
```

---

## NativePHP Integration

gitty uses NativePHP to wrap the Laravel app in an Electron window with native macOS features.

### Window Configuration

**File**: `app/Providers/NativeAppServiceProvider.php`

```php
$window = Window::open()
    ->title('Gitty')
    ->width(1200)
    ->height(800)
    ->minWidth(900)
    ->minHeight(600);

if (method_exists($window, 'titleBarStyle')) {
    $window->titleBarStyle('hiddenInset');
}
```

- **Title bar style**: `hiddenInset` (traffic lights inset into content area)
- **Default size**: 1200x800
- **Minimum size**: 900x600

### Native Menu Bar

NativePHP creates a macOS menu bar with keyboard shortcuts:

```php
Menu::create(
    Menu::app(),
    
    Menu::make(
        Menu::label('Open Repository...')->hotkey('CmdOrCtrl+O')
            ->event('menu:file:open-repo'),
        Menu::label('Settings')->hotkey('CmdOrCtrl+,')
            ->event('menu:file:settings'),
        Menu::separator(),
        Menu::label('Quit')->hotkey('CmdOrCtrl+Q')
            ->event('menu:file:quit'),
    )->label('File'),
    
    Menu::make(
        Menu::label('Commit')->hotkey('CmdOrCtrl+Return')
            ->event('menu:git:commit'),
        Menu::separator(),
        Menu::label('Push')->hotkey('CmdOrCtrl+P')
            ->event('menu:git:push'),
        Menu::label('Pull')->hotkey('CmdOrCtrl+Shift+P')
            ->event('menu:git:pull'),
        Menu::label('Fetch')->hotkey('CmdOrCtrl+T')
            ->event('menu:git:fetch'),
    )->label('Git'),
    
    // ... Branch, Help menus
);
```

Menu events are dispatched to Livewire components via `event('menu:git:commit')`.

### Traffic Light Spacer

macOS window controls (red/yellow/green buttons) occupy ~64px on the left. The header includes a spacer to prevent content overlap.

**File**: `resources/views/livewire/app-layout.blade.php`

```blade
<div class="border-b border-[var(--border-default)] bg-[var(--surface-1)] 
            px-3 flex items-center gap-2 h-9" 
     style="-webkit-app-region: drag;">
    
    {{-- Traffic light drag spacer --}}
    <div class="w-16" style="-webkit-app-region: drag;"></div>
    
    {{-- Buttons opt out of drag region --}}
    <div style="-webkit-app-region: no-drag;">
        <flux:button wire:click="toggleSidebar" ...>
            <x-phosphor-sidebar-simple class="w-4 h-4" />
        </flux:button>
    </div>
</div>
```

- **Drag region**: Header is draggable (`-webkit-app-region: drag`)
- **No-drag zones**: Buttons opt out (`-webkit-app-region: no-drag`)
- **Spacer width**: 64px (`w-16`)

---

## Design System Reference

All design system details (colors, icons, Flux UI patterns, CSS architecture) are documented in `AGENTS.md`.

**Key topics in AGENTS.md**:

- **Color System**: Catppuccin Latte palette (exact hex values)
- **Flux UI Integration**: Accent color configuration, button variants, split buttons
- **Icons**: Phosphor Light for headers, regular for actions
- **Status Indicators**: File status dots, diff header badges
- **Hover States**: Background colors for file items
- **Tooltips**: Required labels for icon-only buttons
- **Tree View**: Folder icons, indentation, collapse chevrons
- **Dropdown Backgrounds**: Sticky areas need explicit `bg-white`
- **Header Layout**: Traffic light spacer, drag regions
- **CSS Architecture**: `@theme {}` vs `:root {}`, hardcoded hex values
- **Diff Viewer Styles**: Catppuccin-tinted backgrounds for additions/deletions
- **Typography**: Instrument Sans (UI), JetBrains Mono (code)
- **Animations**: Slide-in, commit flash, sync pulse, fade-in
- **Keyboard Shortcuts**: ⌘↵ commit, ⌘B toggle sidebar, ⌘K command palette

**Reference**: See `AGENTS.md` for complete design system documentation.

---

## Summary

gitty's architecture follows a strict layered approach:

1. **Git CLI** executes commands and returns porcelain v2 output
2. **GitCommandRunner** wraps Laravel Process with argument escaping
3. **Git Services** orchestrate operations, invalidate caches, handle business logic
4. **DTOs** parse git output into immutable, typed PHP objects
5. **Livewire Components** manage UI state and dispatch events
6. **Blade/Flux Views** render HTML with Alpine.js for client-side interactivity

Key patterns:

- **Per-request services**: Instantiated with `repoPath`, not dependency-injected
- **Group-based caching**: Cache invalidation by operation type (status, history, branches)
- **Cache-based locking**: Prevents concurrent git operations on the same repository
- **Error translation pipeline**: Git errors → user-friendly messages → Livewire events
- **Event-driven updates**: Components communicate via Livewire events (`status-updated`, `file-selected`)

The result is a reactive, desktop-native git client that feels fast and responsive while keeping all logic server-side in PHP.
