# Data Transfer Objects (DTOs)

Complete reference for all immutable value objects that parse git's machine-readable output into typed PHP structures.

## Table of Contents

1. [Overview](#overview)
2. [DTO Relationships](#dto-relationships)
3. [Core Status DTOs](#core-status-dtos)
   - [GitStatus](#gitstatus)
   - [ChangedFile](#changedfile)
   - [AheadBehind](#aheadbehind)
4. [Diff DTOs](#diff-dtos)
   - [DiffResult](#diffresult)
   - [DiffFile](#difffile)
   - [Hunk](#hunk)
   - [HunkLine](#hunkline)
5. [Branch & Commit DTOs](#branch--commit-dtos)
   - [Branch](#branch)
   - [Commit](#commit)
6. [Merge & Conflict DTOs](#merge--conflict-dtos)
   - [MergeResult](#mergeresult)
   - [ConflictFile](#conflictfile)
7. [Repository DTOs](#repository-dtos)
   - [Stash](#stash)
   - [Remote](#remote)
8. [History DTOs](#history-dtos)
   - [BlameLine](#blameline)
   - [GraphNode](#graphnode)

---

## Overview

All DTOs follow these patterns:

- **Immutability**: Use `readonly class` or `readonly` properties
- **Factory Methods**: Static constructors parse git output (`fromOutput()`, `fromLine()`, etc.)
- **Type Safety**: Explicit property types, no magic methods (except ChangedFile's ArrayAccess)
- **Collections**: Use `Illuminate\Support\Collection` for lists of nested DTOs

DTOs parse git's porcelain v2 format (machine-readable, stable across versions).

---

## DTO Relationships

```
GitStatus
├── branch: string
├── upstream: ?string
├── aheadBehind: AheadBehind
│   ├── ahead: int
│   └── behind: int
└── changedFiles: Collection<ChangedFile>
    ├── path: string
    ├── oldPath: ?string
    ├── indexStatus: string
    └── worktreeStatus: string

DiffResult
└── files: Collection<DiffFile>
    ├── oldPath: string
    ├── newPath: string
    ├── status: string
    ├── isBinary: bool
    ├── additions: int
    ├── deletions: int
    └── hunks: Collection<Hunk>
        ├── oldStart: int
        ├── oldCount: int
        ├── newStart: int
        ├── newCount: int
        ├── header: string
        └── lines: Collection<HunkLine>
            ├── type: string
            ├── content: string
            ├── oldLineNumber: ?int
            └── newLineNumber: ?int

MergeResult
├── success: bool
├── hasConflicts: bool
├── conflictFiles: array<string>
└── message: string
```

---

## Core Status DTOs

### GitStatus

**File**: `app/DTOs/GitStatus.php`

**Purpose**: Root DTO for repository status. Parses `git status --porcelain=v2 --branch` output.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `branch` | `string` | Current branch name (or SHA if detached HEAD) |
| `upstream` | `?string` | Upstream tracking branch (null if no upstream) |
| `aheadBehind` | `AheadBehind` | Commits ahead/behind upstream |
| `changedFiles` | `Collection<ChangedFile>` | All changed, staged, untracked, and unmerged files |

**Factory Methods**

```php
GitStatus::fromOutput(string $output): self
```

Parses porcelain v2 output. Handles:
- `# branch.head <name>` — current branch
- `# branch.upstream <name>` — tracking branch
- `# branch.ab +<ahead> -<behind>` — ahead/behind counts
- `1 <XY> ...` — ordinary changed entry
- `2 <XY> ...` — renamed/copied entry
- `u <XY> ...` — unmerged entry
- `? <path>` — untracked file
- `! <path>` — ignored file

**Produced By**
- `GitService::getStatus()`

**Consumed By**
- `StagingPanel` (file list, stage/unstage actions)
- `CommitPanel` (commit validation)
- `RepoSidebar` (branch display, ahead/behind badges)

---

### ChangedFile

**File**: `app/DTOs/ChangedFile.php`

**Purpose**: Represents a single file change. Implements `ArrayAccess` for Livewire `wire:key` compatibility.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `path` | `string` | Current file path |
| `oldPath` | `?string` | Original path (for renames, null otherwise) |
| `indexStatus` | `string` | Staging area status (`.` = unchanged, `M` = modified, `A` = added, `D` = deleted, `R` = renamed, `?` = untracked, `U` = unmerged) |
| `worktreeStatus` | `string` | Working tree status (same codes as indexStatus) |

**Key Methods**

```php
isStaged(): bool
```
Returns true if file is staged (indexStatus not `.`, `?`, or `!`).

```php
isUnstaged(): bool
```
Returns true if file has unstaged changes (worktreeStatus not `.` or `?`).

```php
isUntracked(): bool
```
Returns true if file is untracked (both statuses are `?`).

```php
isUnmerged(): bool
```
Returns true if file has merge conflicts (either status is `U`).

```php
statusLabel(): string
```
Returns human-readable status: `'untracked'`, `'unmerged'`, `'modified'`, `'added'`, `'deleted'`, `'renamed'`, `'copied'`, or `'unknown'`.

**ArrayAccess Implementation**

Implements `ArrayAccess` to allow Livewire's `wire:key="{{ $file['path'] }}"` syntax. Throws `LogicException` on write attempts (immutable).

**Produced By**
- `GitStatus::fromOutput()` (nested within GitStatus)

**Consumed By**
- `StagingPanel` (file list rendering, status badges)
- `DiffViewer` (file selection)

---

### AheadBehind

**File**: `app/DTOs/AheadBehind.php`

**Purpose**: Tracks commits ahead/behind upstream branch.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `ahead` | `int` | Commits ahead of upstream |
| `behind` | `int` | Commits behind upstream |

**Key Methods**

```php
isUpToDate(): bool
```
Returns true if both ahead and behind are 0.

```php
hasDiverged(): bool
```
Returns true if both ahead and behind are greater than 0.

**Produced By**
- `GitStatus::fromOutput()` (nested within GitStatus)

**Consumed By**
- `RepoSidebar` (sync status badges)
- `SyncPanel` (push/pull button states)

---

## Diff DTOs

### DiffResult

**File**: `app/DTOs/DiffResult.php`

**Purpose**: Root DTO for diff output. Contains collection of changed files with hunks.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `files` | `Collection<DiffFile>` | All files in the diff |

**Factory Methods**

```php
DiffResult::fromDiffOutput(string $output): self
```

Parses unified diff format. Handles:
- `diff --git a/path b/path` — file header
- `--- a/path` — old file path
- `+++ b/path` — new file path
- `Binary files ...` — binary file marker
- Hunk headers and lines (delegated to `Hunk::fromRawLines()`)

Returns empty collection if output is empty.

**Produced By**
- `GitService::getDiff()` (staged changes)
- `GitService::getUntrackedDiff()` (untracked files)
- `DiffService::getDiff()` (arbitrary diffs)

**Consumed By**
- `DiffViewer` (file list, hunk rendering)

---

### DiffFile

**File**: `app/DTOs/DiffFile.php`

**Purpose**: Represents a single file in a diff with hunks and change counts.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `oldPath` | `string` | Original file path (empty for added files) |
| `newPath` | `string` | New file path (empty for deleted files) |
| `status` | `string` | File status: `'modified'`, `'added'`, or `'deleted'` |
| `isBinary` | `bool` | True if file is binary (no hunks) |
| `hunks` | `Collection<Hunk>` | Diff hunks (empty for binary files) |
| `additions` | `int` | Total lines added |
| `deletions` | `int` | Total lines deleted |

**Factory Methods**

```php
DiffFile::fromArray(array $data): self
```

Converts intermediate array (from `DiffResult::fromDiffOutput()`) to DiffFile. Delegates hunk parsing to `Hunk::fromRawLines()`.

**Key Methods**

```php
getDisplayPath(): string
```
Returns `newPath` if present, otherwise `oldPath` (handles deleted files).

**Produced By**
- `DiffResult::fromDiffOutput()` (nested within DiffResult)

**Consumed By**
- `DiffViewer` (file header, status badge, +/- counts)

---

### Hunk

**File**: `app/DTOs/Hunk.php`

**Purpose**: Represents a contiguous block of changes in a diff.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `oldStart` | `int` | Starting line number in old file |
| `oldCount` | `int` | Number of lines in old file |
| `newStart` | `int` | Starting line number in new file |
| `newCount` | `int` | Number of lines in new file |
| `header` | `string` | Context text after `@@` (function name, etc.) |
| `lines` | `Collection<HunkLine>` | Individual diff lines |

**Factory Methods**

```php
Hunk::fromRawLines(array $rawLines): Collection<Hunk>
```

Parses raw diff lines into hunks. Handles:
- `@@ -<old>,<count> +<new>,<count> @@ <context>` — hunk header
- `+<content>` — addition
- `-<content>` — deletion
- ` <content>` — context line

Tracks line numbers for old and new files separately.

**Produced By**
- `DiffFile::fromArray()` (nested within DiffFile)

**Consumed By**
- `DiffViewer` (hunk rendering, line numbers)

---

### HunkLine

**File**: `app/DTOs/HunkLine.php`

**Purpose**: Represents a single line in a diff hunk.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `type` | `string` | Line type: `'addition'`, `'deletion'`, or `'context'` |
| `content` | `string` | Line content (without leading `+`, `-`, or space) |
| `oldLineNumber` | `?int` | Line number in old file (null for additions) |
| `newLineNumber` | `?int` | Line number in new file (null for deletions) |

**Produced By**
- `Hunk::fromRawLines()` (nested within Hunk)

**Consumed By**
- `DiffViewer` (line rendering, syntax highlighting)

---

## Branch & Commit DTOs

### Branch

**File**: `app/DTOs/Branch.php`

**Purpose**: Represents a local or remote branch.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `name` | `string` | Branch name (e.g., `main`, `remotes/origin/main`) |
| `isRemote` | `bool` | True if branch name starts with `remotes/` |
| `isCurrent` | `bool` | True if this is the current branch |
| `upstream` | `?string` | Upstream tracking branch (currently always null) |
| `aheadBehind` | `?array` | Ahead/behind counts (currently always null) |
| `lastCommitSha` | `?string` | SHA of last commit on this branch |

**Factory Methods**

```php
Branch::fromBranchLine(string $line): self
```

Parses `git branch -a -v` output. Handles:
- `* main abc123 commit message` — current branch (leading `*`)
- `  feature/new abc123 commit message` — other branch
- `  remotes/origin/main abc123 commit message` — remote branch

**Produced By**
- `BranchService::listBranches()`

**Consumed By**
- `BranchManager` (branch list, checkout, delete)
- `RepoSidebar` (current branch display)

---

### Commit

**File**: `app/DTOs/Commit.php`

**Purpose**: Represents a git commit with metadata.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `sha` | `string` | Full commit SHA |
| `shortSha` | `string` | First 7 characters of SHA |
| `message` | `string` | Commit message |
| `author` | `string` | Author name |
| `email` | `string` | Author email |
| `date` | `string` | Commit date |
| `refs` | `array<string>` | Branch/tag references (e.g., `['HEAD -> main', 'origin/main']`) |

**Factory Methods**

```php
Commit::fromLogLine(string $line): self
```

Parses simple log format: `<sha> <message>`. Sets author, email, date, and refs to empty values.

```php
Commit::fromDetailedOutput(string $output): self
```

Parses detailed `git show` or `git log` output. Handles:
- `commit <sha> (<refs>)` — commit header with optional refs
- `Author: Name <email>` — author line
- `Date: <date>` — date line
- `    <message>` — indented message lines

**Produced By**
- `GitService::getLog()` (uses `fromLogLine()`)
- `GitService::getCommitDetails()` (uses `fromDetailedOutput()`)
- `CommitService::getCommitDetails()` (manual construction)

**Consumed By**
- `HistoryPanel` (commit list)
- `CommitPanel` (commit details, cherry-pick)

---

## Merge & Conflict DTOs

### MergeResult

**File**: `app/DTOs/MergeResult.php`

**Purpose**: Result of a merge or cherry-pick operation.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `success` | `bool` | True if merge completed without errors |
| `hasConflicts` | `bool` | True if merge has conflicts |
| `conflictFiles` | `array<string>` | List of files with conflicts |
| `message` | `string` | Full git output message |

**Factory Methods**

```php
MergeResult::fromMergeOutput(string $output, int $exitCode): self
```

Parses merge output. Detects conflicts by searching for `CONFLICT` or `Automatic merge failed` in output. Extracts conflict file paths using regex: `/CONFLICT.*in (.+)$/`.

**Produced By**
- `BranchService::merge()`
- `CommitService::cherryPick()`

**Consumed By**
- `BranchManager` (merge branch action)
- `CommitPanel` (cherry-pick action)

---

### ConflictFile

**File**: `app/DTOs/ConflictFile.php`

**Purpose**: Represents a file with merge conflicts (future use).

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `path` | `string` | File path |
| `status` | `string` | Conflict status |
| `oursContent` | `string` | Content from current branch |
| `theirsContent` | `string` | Content from merging branch |
| `baseContent` | `string` | Content from common ancestor |
| `isBinary` | `bool` | True if file is binary |

**Factory Methods**

None. Currently constructed manually (not yet used in codebase).

**Produced By**

Not yet implemented.

**Consumed By**

Not yet implemented (reserved for future conflict resolution UI).

---

## Repository DTOs

### Stash

**File**: `app/DTOs/Stash.php`

**Purpose**: Represents a stashed change.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `index` | `int` | Stash index (0 = most recent) |
| `message` | `string` | Stash message |
| `branch` | `string` | Branch where stash was created |
| `sha` | `string` | Commit SHA of stash |

**Factory Methods**

```php
Stash::fromStashLine(string $line): self
```

Parses `git stash list` output. Handles:
- `stash@{0}: WIP on main: a1b2c3d feat: add feature` — WIP stash
- `stash@{1}: On feature/new-ui: Temporary changes` — manual stash

Throws `InvalidArgumentException` if line format is invalid.

**Produced By**
- `StashService::list()`

**Consumed By**
- `RepoSidebar` (stash list, apply/drop actions)
- `StagingPanel` (stash creation)
- `BranchManager` (stash before checkout)

---

### Remote

**File**: `app/DTOs/Remote.php`

**Purpose**: Represents a git remote with fetch/push URLs.

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `name` | `string` | Remote name (e.g., `origin`) |
| `fetchUrl` | `string` | URL for fetching |
| `pushUrl` | `string` | URL for pushing |

**Factory Methods**

```php
Remote::fromRemoteLines(array $lines): array<Remote>
```

Parses `git remote -v` output. Handles:
- `origin https://github.com/user/repo.git (fetch)`
- `origin https://github.com/user/repo.git (push)`

Groups fetch/push URLs by remote name, returns array of Remote objects.

**Produced By**
- `RemoteService::list()`

**Consumed By**
- `SyncPanel` (remote selection, fetch/push)
- `RepoSidebar` (remote display)

---

## History DTOs

### BlameLine

**File**: `app/DTOs/BlameLine.php`

**Purpose**: Represents a single line from `git blame` output (future use).

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `commitSha` | `string` | SHA of commit that last modified this line |
| `author` | `string` | Author name |
| `date` | `string` | Commit date |
| `lineNumber` | `int` | Line number in file |
| `content` | `string` | Line content |

**Factory Methods**

None. Currently constructed manually (not yet used in codebase).

**Produced By**

Not yet implemented.

**Consumed By**

Not yet implemented (reserved for future blame UI).

---

### GraphNode

**File**: `app/DTOs/GraphNode.php`

**Purpose**: Represents a commit node in a visual commit graph (future use).

**Properties**

| Property | Type | Description |
|----------|------|-------------|
| `sha` | `string` | Commit SHA |
| `parents` | `array<string>` | Parent commit SHAs |
| `branch` | `string` | Branch name |
| `refs` | `array<string>` | Branch/tag references |
| `message` | `string` | Commit message |
| `author` | `string` | Author name |
| `date` | `string` | Commit date |
| `lane` | `int` | Visual lane for graph rendering |

**Factory Methods**

None. Currently constructed manually (not yet used in codebase).

**Produced By**

Not yet implemented.

**Consumed By**

Not yet implemented (reserved for future visual commit graph).

---

## Common Patterns

### Parsing Strategy

All factory methods follow this pattern:

1. **Split input**: `explode("\n", $output)` or `preg_split()`
2. **Iterate lines**: `foreach ($lines as $line)`
3. **Match patterns**: `str_starts_with()`, `preg_match()`, or `match()`
4. **Extract data**: `substr()`, `explode()`, regex captures
5. **Construct DTO**: `new self(...)` or `collect()->map()`

### Error Handling

- **Invalid input**: Throw `InvalidArgumentException` (e.g., `Stash::fromStashLine()`)
- **Empty output**: Return empty collection (e.g., `DiffResult::fromDiffOutput()`)
- **Missing fields**: Use null or empty string defaults

### Collection Usage

DTOs use `Illuminate\Support\Collection` for lists:

```php
/** @var Collection<int, ChangedFile> */
public Collection $changedFiles;
```

This enables fluent operations:

```php
$status->changedFiles->filter(fn($f) => $f->isStaged())
```

### Immutability Enforcement

- **readonly class**: Entire class is immutable (most DTOs)
- **readonly properties**: Individual properties are immutable (ChangedFile)
- **ArrayAccess**: ChangedFile throws `LogicException` on write attempts

---

## See Also

- [Architecture](architecture.md) — Service layer, command execution, cache strategy
- [AGENTS.md](../AGENTS.md) — Design system, Livewire patterns, UI conventions
