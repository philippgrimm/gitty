# Service API Reference

Complete API documentation for all services in the gitty codebase.

## Table of Contents

- [Service Overview](#service-overview)
- [Git Services](#git-services)
  - [AbstractGitService](#abstractgitservice)
  - [GitService](#gitservice)
  - [StagingService](#stagingservice)
  - [CommitService](#commitservice)
  - [BranchService](#branchservice)
  - [DiffService](#diffservice)
  - [RemoteService](#remoteservice)
  - [StashService](#stashservice)
  - [ResetService](#resetservice)
  - [RebaseService](#rebaseservice)
  - [TagService](#tagservice)
  - [ConflictService](#conflictservice)
  - [BlameService](#blameservice)
  - [SearchService](#searchservice)
  - [GraphService](#graphservice)
- [Infrastructure Services](#infrastructure-services)
  - [GitCommandRunner](#gitcommandrunner)
  - [GitCacheService](#gitcacheservice)
  - [GitOperationQueue](#gitoperationqueue)
  - [GitErrorHandler](#giterrorhandler)
  - [GitConfigValidator](#gitconfigvalidator)
- [Application Services](#application-services)
  - [RepoManager](#repomanager)
  - [SettingsService](#settingsservice)
  - [EditorService](#editorservice)
  - [NotificationService](#notificationservice)
  - [AutoFetchService](#autofetchservice)

## Service Overview

Summary of all services, their responsibilities, cache groups, and key methods.

| Service | Responsibility | Cache Group | Key Methods |
|---------|---------------|-------------|-------------|
| **GitService** | Core git operations (status, log, diff) | status, history | `status()`, `log()`, `diff()` |
| **StagingService** | Stage/unstage/discard files | status | `stageFile()`, `unstageFile()`, `discardFile()` |
| **CommitService** | Create commits, amend, cherry-pick | status, history | `commit()`, `commitAmend()`, `cherryPick()` |
| **BranchService** | Branch management, merging | branches, status | `branches()`, `switchBranch()`, `mergeBranch()` |
| **DiffService** | Parse diffs, stage hunks/lines | — | `parseDiff()`, `stageHunk()`, `stageLines()` |
| **RemoteService** | Push, pull, fetch operations | branches, status, history | `push()`, `pull()`, `fetch()` |
| **StashService** | Stash management | stashes, status | `stash()`, `stashList()`, `stashApply()` |
| **ResetService** | Reset commits, revert | status, history | `resetSoft()`, `resetHard()`, `revertCommit()` |
| **RebaseService** | Interactive rebase | status, history, branches | `startRebase()`, `continueRebase()`, `abortRebase()` |
| **TagService** | Tag management | tags | `tags()`, `createTag()`, `deleteTag()` |
| **ConflictService** | Merge conflict resolution | status, branches | `getConflictedFiles()`, `resolveConflict()` |
| **BlameService** | File blame annotations | — | `blame()` |
| **SearchService** | Search commits, content, files | — | `searchCommits()`, `searchContent()`, `searchFiles()` |
| **GraphService** | Commit graph visualization | — | `getGraphData()` |
| **GitCommandRunner** | Execute git commands with escaping | — | `run()`, `runOrFail()`, `runWithInput()` |
| **GitCacheService** | Group-based cache invalidation | — | `get()`, `invalidateGroup()` |
| **GitOperationQueue** | Prevent concurrent git operations | — | `execute()`, `isLocked()` |
| **GitErrorHandler** | Translate git errors to user messages | — | `translate()`, `isDirtyTreeError()` |
| **GitConfigValidator** | Validate git configuration | — | `validate()`, `checkGitBinary()` |
| **RepoManager** | Repository lifecycle management | — | `openRepo()`, `recentRepos()`, `currentRepo()` |
| **SettingsService** | Application settings persistence | — | `get()`, `set()`, `all()` |
| **EditorService** | External editor integration | — | `detectEditors()`, `openFile()` |
| **NotificationService** | Native desktop notifications | — | `notify()` |
| **AutoFetchService** | Background fetch scheduling | — | `start()`, `shouldFetch()`, `executeFetch()` |

---

## Git Services

All git services extend `AbstractGitService` and operate on a specific repository path.

### AbstractGitService

**File:** `app/Services/Git/AbstractGitService.php`

Base class for all git services. Validates repository path and provides shared dependencies.

#### Constructor

```php
public function __construct(protected string $repoPath)
```

Validates that `$repoPath/.git` exists. Throws `InvalidRepositoryException` if not a valid git repository.

Initializes:
- `$this->cache` — `GitCacheService` instance
- `$this->commandRunner` — `GitCommandRunner` instance

#### Protected Properties

```php
protected GitCacheService $cache;
protected GitCommandRunner $commandRunner;
protected string $repoPath;
```

---

### GitService

**File:** `app/Services/Git/GitService.php`

Core git operations: status, log, diff, config.

#### Methods

##### `status(): GitStatus`

Get repository status (branch, ahead/behind, changed files).

- **Cache:** `status` group, 5 seconds
- **Returns:** `GitStatus` DTO parsed from `git status --porcelain=v2 --branch`

##### `log(int $limit = 100, ?string $branch = null, bool $detailed = false): Collection`

Get commit history.

- **Cache:** `log:{$limit}:{$branch}:{detailed}`, 60 seconds
- **Parameters:**
  - `$limit` — Maximum number of commits
  - `$branch` — Branch name (defaults to HEAD)
  - `$detailed` — Include author, email, refs
- **Returns:** Collection of `Commit` DTOs

##### `diff(?string $file = null, bool $staged = false): DiffResult`

Get diff for file or entire repository.

- **Cache:** None (real-time)
- **Parameters:**
  - `$file` — File path (null for all files)
  - `$staged` — Show staged changes (--cached)
- **Returns:** `DiffResult` DTO
- **Special handling:** Untracked files use `git diff --no-index /dev/null {file}`

##### `currentBranch(): string`

Get current branch name.

- **Cache:** Uses cached `status()`
- **Returns:** Branch name or `"(detached)"` for detached HEAD

##### `isDetachedHead(): bool`

Check if HEAD is detached.

- **Cache:** Uses cached `status()`
- **Returns:** True if detached HEAD

##### `aheadBehind(): AheadBehind`

Get ahead/behind counts for current branch.

- **Cache:** Uses cached `status()`
- **Returns:** `AheadBehind` DTO

##### `getTrackedFileSize(string $file): int`

Get file size at HEAD.

- **Cache:** None
- **Returns:** File size in bytes, 0 if not found

##### `getFileContentAtHead(string $file): ?string`

Get file content at HEAD.

- **Cache:** None
- **Returns:** File content or null if not found

##### `getConfigValue(string $key): ?string`

Get git config value.

- **Cache:** None
- **Parameters:** `$key` — Config key (e.g., `user.name`)
- **Returns:** Config value or null

---

### StagingService

**File:** `app/Services/Git/StagingService.php`

Stage, unstage, and discard file changes.

#### Methods

##### `stageFile(string $file): void`

Stage a single file.

- **Invalidates:** `status` group
- **Throws:** None (git errors propagate from `run()`)

##### `unstageFile(string $file): void`

Unstage a single file.

- **Invalidates:** `status` group

##### `stageAll(): void`

Stage all changes.

- **Invalidates:** `status` group

##### `unstageAll(): void`

Unstage all changes.

- **Invalidates:** `status` group

##### `discardFile(string $file): void`

Discard changes to a file (restore from HEAD).

- **Invalidates:** `status` group

##### `discardAll(): void`

Discard all changes.

- **Invalidates:** `status` group

##### `stageFiles(array $files): void`

Stage multiple files.

- **Invalidates:** `status` group
- **Throws:** `InvalidArgumentException` if `$files` is empty

##### `unstageFiles(array $files): void`

Unstage multiple files.

- **Invalidates:** `status` group
- **Throws:** `InvalidArgumentException` if `$files` is empty

##### `discardFiles(array $files): void`

Discard changes to multiple files.

- **Invalidates:** `status` group
- **Throws:** `InvalidArgumentException` if `$files` is empty

---

### CommitService

**File:** `app/Services/Git/CommitService.php`

Create commits, amend, undo, cherry-pick.

#### Methods

##### `commit(string $message): void`

Create a commit.

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitCommandFailedException` if commit fails

##### `commitAmend(string $message): void`

Amend the last commit.

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitCommandFailedException` if amend fails

##### `commitAndPush(string $message): void`

Create a commit and push to remote.

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitCommandFailedException` if commit or push fails

##### `lastCommitMessage(): string`

Get the last commit message.

- **Cache:** None
- **Returns:** Commit message (trimmed)

##### `undoLastCommit(): void`

Undo the last commit (soft reset to HEAD~1).

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitCommandFailedException` if reset fails

##### `isLastCommitPushed(): bool`

Check if the last commit has been pushed.

- **Cache:** None
- **Returns:** True if ahead count is 0 or no upstream

##### `isLastCommitMerge(): bool`

Check if the last commit is a merge commit.

- **Cache:** None
- **Returns:** True if HEAD has multiple parents

##### `cherryPick(string $sha): MergeResult`

Cherry-pick a commit.

- **Invalidates:** `status`, `history` groups
- **Returns:** `MergeResult` DTO (may indicate conflicts)

##### `cherryPickAbort(): void`

Abort an in-progress cherry-pick.

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitCommandFailedException` if abort fails

##### `cherryPickContinue(): void`

Continue a cherry-pick after resolving conflicts.

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitCommandFailedException` if continue fails

---

### BranchService

**File:** `app/Services/Git/BranchService.php`

Branch management, switching, merging.

#### Methods

##### `branches(): Collection`

Get all branches (local and remote).

- **Cache:** `branches` group, 30 seconds
- **Returns:** Collection of `Branch` DTOs

##### `switchBranch(string $name): void`

Switch to a branch.

- **Invalidates:** `branches`, `status` groups
- **Throws:** `RuntimeException` if checkout fails

##### `createBranch(string $name, string $from): void`

Create and switch to a new branch.

- **Invalidates:** `branches`, `status` groups
- **Parameters:**
  - `$name` — New branch name
  - `$from` — Starting point (branch name or commit SHA)
- **Throws:** `RuntimeException` if creation fails

##### `deleteBranch(string $name, bool $force): void`

Delete a branch.

- **Invalidates:** `branches` group
- **Parameters:**
  - `$name` — Branch name
  - `$force` — Use `-D` instead of `-d`
- **Throws:** `RuntimeException` if deletion fails

##### `isCommitOnRemote(string $sha): bool`

Check if a commit exists on any remote branch.

- **Cache:** None
- **Returns:** True if commit is on a remote branch

##### `mergeBranch(string $name): MergeResult`

Merge a branch into the current branch.

- **Invalidates:** `status`, `history` groups
- **Returns:** `MergeResult` DTO (may indicate conflicts)

---

### DiffService

**File:** `app/Services/Git/DiffService.php`

Parse diffs, stage/unstage hunks and individual lines.

#### Methods

##### `parseDiff(string $rawDiff): DiffResult`

Parse raw diff output into structured data.

- **Cache:** None
- **Returns:** `DiffResult` DTO

##### `extractHunks(DiffFile $file): Collection`

Extract hunks from a diff file.

- **Cache:** None
- **Returns:** Collection of `Hunk` DTOs

##### `stageHunk(DiffFile $file, Hunk $hunk): void`

Stage a single hunk.

- **Invalidates:** None (caller should invalidate `status`)
- **Uses:** `git apply --cached` with generated patch

##### `unstageHunk(DiffFile $file, Hunk $hunk): void`

Unstage a single hunk.

- **Invalidates:** None
- **Uses:** `git apply --cached --reverse` with generated patch

##### `stageLines(DiffFile $file, Hunk $hunk, array $selectedLineIndices): void`

Stage specific lines from a hunk.

- **Invalidates:** None
- **Parameters:** `$selectedLineIndices` — Array of line indices to stage
- **Uses:** `git apply --cached --unidiff-zero` with recalculated patch
- **Throws:** `RuntimeException` if apply fails

##### `unstageLines(DiffFile $file, Hunk $hunk, array $selectedLineIndices): void`

Unstage specific lines from a hunk.

- **Invalidates:** None
- **Uses:** `git apply --cached --unidiff-zero --reverse`
- **Throws:** `RuntimeException` if apply fails

#### Protected Methods (Internal Patch Generation)

##### `generatePatch(DiffFile $file, Hunk $hunk): string`

Generate a complete patch for a hunk.

- **Returns:** Patch string with diff headers and hunk content

##### `generateLinePatch(DiffFile $file, Hunk $hunk, array $selectedLineIndices): string`

Generate a patch for selected lines within a hunk.

- **Logic:**
  - Context lines: Always included
  - Selected additions: Included as additions
  - Unselected additions: Converted to context lines
  - Selected deletions: Included as deletions
  - Unselected deletions: Omitted entirely
- **Returns:** Patch string with recalculated hunk header counts

---

### RemoteService

**File:** `app/Services/Git/RemoteService.php`

Push, pull, fetch operations.

#### Methods

##### `remotes(): Collection`

Get all configured remotes.

- **Cache:** `remotes` group, 300 seconds
- **Returns:** Collection of `Remote` DTOs

##### `push(string $remote, string $branch): string`

Push a branch to a remote.

- **Invalidates:** `branches` group
- **Throws:** `GitCommandFailedException` if push fails
- **Returns:** Command output

##### `pull(string $remote, string $branch): string`

Pull a branch from a remote.

- **Invalidates:** `status`, `history`, `branches` groups
- **Throws:** `GitCommandFailedException` if pull fails
- **Returns:** Command output

##### `fetch(string $remote): string`

Fetch from a specific remote.

- **Invalidates:** `branches`, `history` groups
- **Throws:** `GitCommandFailedException` if fetch fails
- **Returns:** Command output

##### `fetchAll(): string`

Fetch from all remotes.

- **Invalidates:** `branches`, `history` groups
- **Throws:** `GitCommandFailedException` if fetch fails
- **Returns:** Command output

##### `forcePushWithLease(string $remote, string $branch): string`

Force push with lease (safer than force push).

- **Invalidates:** `branches` group
- **Throws:** `GitCommandFailedException` if push fails
- **Returns:** Command output

---

### StashService

**File:** `app/Services/Git/StashService.php`

Stash management.

#### Methods

##### `stash(string $message, bool $includeUntracked): void`

Create a stash.

- **Invalidates:** `stashes`, `status` groups
- **Parameters:**
  - `$message` — Stash message
  - `$includeUntracked` — Include untracked files (`-u` flag)
- **Throws:** `GitCommandFailedException` if stash fails

##### `stashList(): Collection`

Get all stashes.

- **Cache:** `stashes` group, 30 seconds
- **Returns:** Collection of `Stash` DTOs

##### `stashApply(int $index): void`

Apply a stash without removing it.

- **Invalidates:** `status` group
- **Throws:** `GitCommandFailedException` if apply fails

##### `tryStashApply(int $index): bool`

Try to apply a stash, return success status.

- **Invalidates:** `status` group
- **Returns:** True if successful, false otherwise

##### `stashPop(int $index): void`

Apply a stash and remove it.

- **Invalidates:** `stashes`, `status` groups
- **Throws:** `GitCommandFailedException` if pop fails

##### `stashDrop(int $index): void`

Delete a stash.

- **Invalidates:** `stashes` group
- **Throws:** `GitCommandFailedException` if drop fails

##### `stashFiles(array $paths): void`

Stash specific files.

- **Invalidates:** `stashes`, `status` groups
- **Parameters:** `$paths` — Array of file paths
- **Throws:** `InvalidArgumentException` if `$paths` is empty
- **Throws:** `GitCommandFailedException` if stash fails

---

### ResetService

**File:** `app/Services/Git/ResetService.php`

Reset commits, revert changes.

#### Methods

##### `resetSoft(string $commitSha): void`

Soft reset to a commit (keep changes staged).

- **Invalidates:** `status`, `history` groups
- **Throws:** `RuntimeException` if reset fails

##### `resetMixed(string $commitSha): void`

Mixed reset to a commit (keep changes unstaged).

- **Invalidates:** `status`, `history` groups
- **Throws:** `RuntimeException` if reset fails

##### `resetHard(string $commitSha): void`

Hard reset to a commit (discard all changes).

- **Invalidates:** `status`, `history` groups
- **Throws:** `RuntimeException` if reset fails

##### `revertCommit(string $commitSha): void`

Revert a commit (create inverse commit).

- **Invalidates:** `status`, `history` groups
- **Throws:** `GitConflictException` if revert causes conflicts
- **Throws:** `RuntimeException` for other failures

---

### RebaseService

**File:** `app/Services/Git/RebaseService.php`

Interactive rebase operations.

#### Methods

##### `isRebasing(): bool`

Check if a rebase is in progress.

- **Cache:** None
- **Returns:** True if `.git/rebase-merge` or `.git/rebase-apply` exists

##### `getRebaseCommits(string $onto, int $count): Collection`

Get commits for rebase planning.

- **Cache:** None
- **Parameters:**
  - `$onto` — Target commit/branch
  - `$count` — Number of commits to include
- **Returns:** Collection of commit arrays with `sha`, `shortSha`, `message`, `action`
- **Throws:** `RuntimeException` if log fails

##### `startRebase(string $onto, array $plan): void`

Start an interactive rebase.

- **Invalidates:** `status`, `history`, `branches` groups
- **Parameters:**
  - `$onto` — Target commit/branch
  - `$plan` — Array of commits with `action` and `sha` keys
- **Uses:** `GIT_SEQUENCE_EDITOR` environment variable to inject rebase plan
- **Throws:** `GitConflictException` if rebase causes conflicts
- **Throws:** `RuntimeException` for other failures

##### `continueRebase(): void`

Continue a rebase after resolving conflicts.

- **Invalidates:** `status`, `history`, `branches` groups
- **Throws:** `GitConflictException` if conflicts remain
- **Throws:** `RuntimeException` for other failures

##### `abortRebase(): void`

Abort an in-progress rebase.

- **Invalidates:** `status`, `history`, `branches` groups
- **Throws:** `RuntimeException` if abort fails

---

### TagService

**File:** `app/Services/Git/TagService.php`

Tag management.

#### Methods

##### `tags(): Collection`

Get all tags.

- **Cache:** `tags` group, 60 seconds
- **Returns:** Collection of tag arrays with `name`, `sha`, `date`, `message`

##### `createTag(string $name, ?string $message = null, ?string $commit = null): void`

Create a tag.

- **Invalidates:** `tags` group
- **Parameters:**
  - `$name` — Tag name
  - `$message` — Annotated tag message (null for lightweight tag)
  - `$commit` — Commit SHA (null for HEAD)
- **Throws:** `RuntimeException` if tag creation fails

##### `deleteTag(string $name): void`

Delete a tag.

- **Invalidates:** `tags` group
- **Throws:** `RuntimeException` if deletion fails

##### `pushTag(string $name, string $remote = 'origin'): void`

Push a tag to a remote.

- **Invalidates:** None
- **Throws:** `RuntimeException` if push fails

---

### ConflictService

**File:** `app/Services/Git/ConflictService.php`

Merge conflict detection and resolution.

#### Methods

##### `isInMergeState(): bool`

Check if repository is in a merge state.

- **Cache:** None
- **Returns:** True if `.git/MERGE_HEAD` exists

##### `getConflictedFiles(): Collection`

Get all conflicted files.

- **Cache:** None
- **Returns:** Collection of arrays with `path` and `status` keys
- **Parses:** `git status --porcelain=v2` for unmerged entries (`u` prefix)

##### `getConflictVersions(string $file): ConflictFile`

Get all three versions of a conflicted file.

- **Cache:** None
- **Returns:** `ConflictFile` DTO with `oursContent`, `theirsContent`, `baseContent`, `isBinary`
- **Versions:**
  - `:1:` — Common ancestor (base)
  - `:2:` — Current branch (ours)
  - `:3:` — Incoming branch (theirs)

##### `resolveConflict(string $file, string $resolvedContent): void`

Resolve a conflict by writing resolved content and staging.

- **Invalidates:** `status` group
- **Throws:** `RuntimeException` if staging fails

##### `abortMerge(): void`

Abort an in-progress merge.

- **Invalidates:** `status`, `branches` groups
- **Throws:** `RuntimeException` if abort fails

##### `getMergeHeadBranch(): string`

Get the name of the branch being merged.

- **Cache:** None
- **Returns:** Branch name or `"unknown"`
- **Parses:** `.git/MERGE_MSG` for branch name

---

### BlameService

**File:** `app/Services/Git/BlameService.php`

File blame annotations.

#### Methods

##### `blame(string $file): Collection`

Get blame information for a file.

- **Cache:** None
- **Returns:** Collection of `BlameLine` DTOs
- **Throws:** `RuntimeException` if blame fails
- **Parses:** `git blame --porcelain` output

---

### SearchService

**File:** `app/Services/Git/SearchService.php`

Search commits, content, and files.

#### Methods

##### `searchCommits(string $query, int $limit = 50): Collection`

Search commit messages.

- **Cache:** None
- **Uses:** `git log --grep`
- **Returns:** Collection of commit arrays with `sha`, `shortSha`, `author`, `date`, `message`
- **Throws:** `RuntimeException` if search fails

##### `searchContent(string $query, int $limit = 50): Collection`

Search file content changes (pickaxe search).

- **Cache:** None
- **Uses:** `git log -S`
- **Returns:** Collection of commit arrays
- **Throws:** `RuntimeException` if search fails

##### `searchFiles(string $query): Collection`

Search filenames.

- **Cache:** None
- **Uses:** `git ls-files`
- **Returns:** Collection of arrays with `path` key
- **Throws:** `RuntimeException` if search fails

---

### GraphService

**File:** `app/Services/Git/GraphService.php`

Commit graph visualization data.

#### Methods

##### `getGraphData(int $limit = 200): array`

Get commit graph data with lane assignments.

- **Cache:** None
- **Parameters:** `$limit` — Maximum number of commits
- **Returns:** Array of `GraphNode` DTOs with `sha`, `parents`, `branch`, `refs`, `message`, `author`, `date`, `lane`
- **Algorithm:** Assigns visual lanes for parallel branch rendering

---

## Infrastructure Services

Low-level services for command execution, caching, concurrency, and error handling.

### GitCommandRunner

**File:** `app/Services/Git/GitCommandRunner.php`

Execute git commands with argument escaping.

#### Constructor

```php
public function __construct(protected string $repoPath)
```

#### Methods

##### `run(string $subcommand, array $args = []): ProcessResult`

Run a git command.

- **Parameters:**
  - `$subcommand` — Git subcommand (e.g., `status`, `add`, `commit -m`)
  - `$args` — Arguments to escape with `escapeshellarg()`
- **Returns:** Laravel `ProcessResult`
- **Example:** `run('add', ['file.txt'])` → `git add 'file.txt'`

##### `runOrFail(string $subcommand, array $args = [], string $errorPrefix = ''): ProcessResult`

Run a git command and throw on failure.

- **Throws:** `GitCommandFailedException` if exit code is non-zero
- **Parameters:** Same as `run()`, plus `$errorPrefix` for exception message

##### `runWithInput(string $subcommand, string $input): ProcessResult`

Run a git command with stdin input.

- **Parameters:**
  - `$subcommand` — Git subcommand (may include flags)
  - `$input` — Stdin content
- **Returns:** Laravel `ProcessResult`
- **Example:** `runWithInput('apply --cached', $patchContent)`

---

### GitCacheService

**File:** `app/Services/Git/GitCacheService.php`

Group-based cache invalidation for git data.

#### Cache Groups

```php
const GROUPS = [
    'status' => ['status', 'diff'],
    'history' => ['log'],
    'branches' => ['branches'],
    'remotes' => ['remotes'],
    'stashes' => ['stashes'],
    'tags' => ['tags'],
];
```

#### Methods

##### `get(string $repoPath, string $key, callable $callback, int $ttl): mixed`

Get cached value or execute callback.

- **Parameters:**
  - `$repoPath` — Repository path (used for cache key hashing)
  - `$key` — Cache key
  - `$callback` — Function to execute if cache miss
  - `$ttl` — Time-to-live in seconds
- **Returns:** Cached or computed value

##### `invalidate(string $repoPath, string $key): void`

Invalidate a single cache key.

##### `invalidateAll(string $repoPath): void`

Invalidate all cache keys for a repository.

##### `invalidateGroup(string $repoPath, string $group): void`

Invalidate all keys in a cache group.

- **Example:** `invalidateGroup($repoPath, 'status')` invalidates `status` and `diff` keys

---

### GitOperationQueue

**File:** `app/Services/Git/GitOperationQueue.php`

Prevent concurrent git operations on the same repository.

#### Constructor

```php
public function __construct(protected string $repoPath)
```

#### Methods

##### `execute(callable $operation): mixed`

Execute an operation with a lock.

- **Lock duration:** 30 seconds
- **Throws:** `GitOperationInProgressException` if lock cannot be acquired
- **Returns:** Result of `$operation()`
- **Ensures:** Lock is released even if operation throws

##### `isLocked(): bool`

Check if a lock is currently held.

- **Returns:** True if locked, false otherwise

---

### GitErrorHandler

**File:** `app/Services/Git/GitErrorHandler.php`

Translate git error messages to user-friendly text.

#### Static Methods

##### `translate(string $gitError): string`

Translate a git error message.

- **Returns:** User-friendly message or original error if no pattern matches
- **Patterns:**
  - `fatal: not a git repository` → "This folder is not a git repository"
  - `error: pathspec ... did not match` → "File not found in repository"
  - `CONFLICT` → "Merge conflict detected. Resolve conflicts in external editor."
  - `rejected` → "Push rejected. Pull remote changes first."
  - `Authentication failed` → "Authentication failed. Check your credentials."
  - `git: command not found` → "Git is not installed. Please install git."
  - `fatal: bad object` → "Repository may be corrupted. Try running 'git fsck'."
  - Uncommitted changes → "Cannot switch branches: You have uncommitted changes. Commit or stash them first."

##### `isDirtyTreeError(string $errorMessage): bool`

Check if error is due to uncommitted changes blocking checkout.

- **Returns:** True if error matches dirty tree patterns

---

### GitConfigValidator

**File:** `app/Services/Git/GitConfigValidator.php`

Validate git installation and configuration.

#### Methods

##### `validate(): array`

Validate git configuration for the repository.

- **Checks:**
  - `user.name` is set
  - `user.email` is set
  - Git version >= 2.30.0
- **Returns:** Array of issue strings (empty if valid)

##### `checkGitBinary(): bool` (static)

Check if git binary is available.

- **Returns:** True if `which git` succeeds

##### `validateAll(): array`

Validate git binary and configuration.

- **Returns:** Array of issue strings (empty if valid)

---

## Application Services

High-level services for repository management, settings, editor integration, and notifications.

### RepoManager

**File:** `app/Services/RepoManager.php`

Repository lifecycle management.

#### Methods

##### `openRepo(string $path): Repository`

Open a repository and mark it as current.

- **Validates:** `$path/.git` exists
- **Creates:** `Repository` model if not exists
- **Updates:** `last_opened_at` timestamp
- **Sets:** Current repository in cache
- **Throws:** `InvalidArgumentException` if not a valid git repository
- **Returns:** `Repository` model

##### `recentRepos(int $limit = 20): Collection`

Get recently opened repositories.

- **Returns:** Collection of `Repository` models ordered by `last_opened_at`

##### `removeRepo(int $id): void`

Remove a repository from the list.

- **Note:** Does not delete files, only removes from database

##### `currentRepo(): ?Repository`

Get the currently active repository.

- **Returns:** `Repository` model or null

##### `setCurrentRepo(Repository $repo): void`

Set the current repository.

- **Stores:** Repository ID in cache

---

### SettingsService

**File:** `app/Services/SettingsService.php`

Application settings persistence.

#### Default Settings

```php
const DEFAULTS = [
    'auto_fetch_interval' => 180,
    'external_editor' => '',
    'theme' => 'dark',
    'default_branch' => 'main',
    'confirm_discard' => true,
    'confirm_force_push' => true,
    'show_untracked' => true,
    'diff_context_lines' => 3,
    'notifications_enabled' => true,
];
```

#### Methods

##### `defaults(): array`

Get default settings.

- **Returns:** Array of default key-value pairs

##### `get(string $key, mixed $default = null): mixed`

Get a setting value.

- **Returns:** Stored value, default value, or `$default` parameter
- **Type casting:** Booleans, integers, floats are cast automatically

##### `set(string $key, mixed $value): void`

Set a setting value.

- **Stores:** Value as string in database

##### `all(): array`

Get all settings (defaults merged with stored values).

- **Returns:** Array of all settings with type casting applied

##### `reset(): void`

Reset all settings to defaults.

- **Deletes:** All stored settings

##### `getCommitHistory(string $repoPath): array`

Get commit message history for a repository.

- **Returns:** Array of up to 20 recent commit messages

##### `addCommitMessage(string $repoPath, string $message): void`

Add a commit message to history.

- **Deduplicates:** Removes existing instances of the message
- **Limits:** Keeps max 20 messages

---

### EditorService

**File:** `app/Services/EditorService.php`

External editor integration.

#### Supported Editors

```php
const EDITORS = [
    'code' => ['name' => 'VS Code', 'command' => 'code', 'args' => '--goto {file}:{line}'],
    'cursor' => ['name' => 'Cursor', 'command' => 'cursor', 'args' => '--goto {file}:{line}'],
    'subl' => ['name' => 'Sublime Text', 'command' => 'subl', 'args' => '{file}:{line}'],
    'phpstorm' => ['name' => 'PhpStorm', 'command' => 'phpstorm', 'args' => '--line {line} {file}'],
    'zed' => ['name' => 'Zed', 'command' => 'zed', 'args' => '{file}:{line}'],
];
```

#### Constructor

```php
public function __construct(private SettingsService $settings)
```

#### Methods

##### `detectEditors(): array`

Detect installed editors.

- **Returns:** Array of `editorKey => editorName` for installed editors
- **Uses:** `which` command to check binary availability

##### `getDefaultEditor(): ?string`

Get the default editor.

- **Priority:**
  1. Saved setting (`external_editor`)
  2. First detected editor
- **Returns:** Editor key or null

##### `openFile(string $repoPath, string $file, int $line = 1, ?string $editorKey = null): void`

Open a file in an external editor.

- **Parameters:**
  - `$repoPath` — Repository path
  - `$file` — Relative file path
  - `$line` — Line number
  - `$editorKey` — Editor key (null for default)
- **Throws:** `RuntimeException` if no editor configured

---

### NotificationService

**File:** `app/Services/NotificationService.php`

Native desktop notifications.

#### Constructor

```php
public function __construct(private readonly SettingsService $settingsService)
```

#### Methods

##### `notify(string $title, string $body): void`

Show a desktop notification.

- **Checks:** `notifications_enabled` setting
- **Graceful degradation:** Silently fails if NativePHP unavailable

---

### AutoFetchService

**File:** `app/Services/AutoFetchService.php`

Background fetch scheduling.

#### Constructor

```php
public function __construct(?string $repoPath = null)
```

#### Methods

##### `start(string $repoPath, int $intervalSeconds = 180): void`

Start auto-fetch for a repository.

- **Parameters:**
  - `$repoPath` — Repository path
  - `$intervalSeconds` — Fetch interval (minimum 60 seconds, 0 to disable)
- **Stores:** Configuration in cache
- **Throws:** `InvalidArgumentException` if not a valid git repository

##### `stop(): void`

Stop auto-fetch.

- **Clears:** All auto-fetch cache keys

##### `isRunning(): bool`

Check if auto-fetch is running.

- **Returns:** True if interval is configured and > 0

##### `shouldFetch(): bool`

Check if a fetch should be executed now.

- **Checks:**
  1. Auto-fetch is running
  2. No git operation lock
  3. Interval has elapsed since last fetch
- **Returns:** True if fetch should run

##### `executeFetch(): array`

Execute a fetch operation.

- **Returns:** Array with `success`, `output`, `error` keys
- **Updates:** Last fetch timestamp on success

##### `getLastFetchTime(): ?Carbon`

Get the last fetch timestamp.

- **Returns:** Carbon instance or null

##### `getNextFetchTime(): ?Carbon`

Get the next scheduled fetch time.

- **Returns:** Carbon instance or null

---

## Usage Patterns

### Service Instantiation

All git services are instantiated per-request with a repository path:

```php
$gitService = new GitService($repoPath);
$status = $gitService->status();
```

### Cache Invalidation

Mutating methods invalidate relevant cache groups:

```php
$stagingService = new StagingService($repoPath);
$stagingService->stageFile('file.txt'); // Invalidates 'status' group
```

### Error Handling

Use `runOrFail()` for operations that must succeed:

```php
$commitService = new CommitService($repoPath);
try {
    $commitService->commit('Initial commit');
} catch (GitCommandFailedException $e) {
    $userMessage = GitErrorHandler::translate($e->getMessage());
    // Show $userMessage to user
}
```

### Concurrency Control

Wrap critical operations in `GitOperationQueue`:

```php
$queue = new GitOperationQueue($repoPath);
try {
    $queue->execute(function () use ($commitService, $message) {
        $commitService->commit($message);
    });
} catch (GitOperationInProgressException $e) {
    // Another operation is in progress
}
```

### Hunk Staging

DiffService requires a `DiffFile` and `Hunk` from parsed diff:

```php
$gitService = new GitService($repoPath);
$diffResult = $gitService->diff('file.txt');
$file = $diffResult->files->first();
$hunk = $file->hunks->first();

$diffService = new DiffService($repoPath);
$diffService->stageHunk($file, $hunk);
```

### Line Staging

Select specific lines within a hunk:

```php
$selectedLineIndices = [0, 2, 5]; // Stage lines 0, 2, and 5
$diffService->stageLines($file, $hunk, $selectedLineIndices);
```

---

## See Also

- [Architecture Documentation](architecture.md) — System architecture and data flow
- [AGENTS.md](../AGENTS.md) — Design system and conventions
