# Developer Documentation for Gitty

## TL;DR

> **Quick Summary**: Create comprehensive developer documentation for gitty covering architecture, patterns, service APIs, and all features — structured as a `docs/` directory with focused Markdown files and ASCII diagrams.
> 
> **Deliverables**:
> - `docs/README.md` — master index with navigation
> - `docs/architecture.md` — system architecture, layers, data flow, key patterns
> - `docs/services.md` — all 20 services with public API reference
> - `docs/dtos.md` — all 15 DTOs with property tables and factory methods
> - `docs/components.md` — all 18 Livewire components, state, events, views
> - `docs/events.md` — complete event system map (30+ events)
> - `docs/frontend.md` — CSS architecture, Blade layouts, Alpine.js, Flux UI usage
> - `docs/features.md` — all features explained from a developer perspective
> - `docs/testing.md` — test infrastructure, patterns, helpers, how to write tests
> - `docs/common-tasks.md` — cookbook for common development tasks
> 
> **Estimated Effort**: Large (10 doc files, deep technical content)
> **Parallel Execution**: YES — 5 waves
> **Critical Path**: T1 (architecture) → T2-T6 (reference docs) → T7-T9 (feature/testing/tasks) → T10 (index) → FINAL

---

## Context

### Original Request
User wants "highclass developer docs" explaining:
1. How the app works in general — concepts, abstractions, patterns
2. What the app can do and how — feature documentation

### Interview Summary
**Key Discussions**:
- Location: `docs/` directory at project root
- Audience: Developers working on the codebase
- Depth: Deep dive — full service APIs, every DTO, event flows, caching, error handling
- Diagrams: ASCII art (text-only, universally visible)

**Research Findings**:
- 70 PHP source files, 22 Blade views, 83 test files
- Layered architecture: Git CLI → GitCommandRunner → Services → DTOs → Livewire → Blade
- Service base pattern: AbstractGitService provides repoPath, cache, commandRunner
- Cache with group-based invalidation (6 groups: status, history, branches, remotes, stashes, tags)
- Operation queue with cache locks for concurrency
- Error handler translates raw git errors → user-friendly messages
- HandlesGitOperations trait for standardized component error handling
- Event-driven inter-component communication (30+ events)
- NativePHP Electron wrapper with native menus
- Catppuccin Latte palette, Flux UI components, Phosphor Icons

### Metis Review
**Identified Gaps** (addressed):
- Documentation depth: Resolved → public methods with signatures, descriptions, cache/concurrency notes
- Code example strategy: Resolved → real code excerpts with file path references, not full files
- Audience expertise: Resolved → assumes expert Laravel/Livewire developers
- Diagram complexity: Resolved → per-feature event diagrams, max 80 chars wide / 30 lines tall
- Feature docs scope: Resolved → how features work internally (implementation perspective)
- Maintenance: Resolved → docs reference source files as truth; no CI validation
- Scope creep guard: No code changes, no auto-generation, no third-party library docs

---

## Work Objectives

### Core Objective
Create a complete set of developer documentation that enables any experienced Laravel developer to understand gitty's architecture, navigate the codebase, and extend any feature — without needing to read every source file.

### Concrete Deliverables
- 10 Markdown files in `docs/`
- ASCII architecture diagrams
- Per-feature event flow diagrams
- Cross-referenced navigation between all docs

### Definition of Done
- [x] All 10 doc files exist in `docs/` with content
- [x] Master index links to every doc file without broken links
- [x] All 20 services documented with public method signatures
- [x] All 15 DTOs documented with property tables
- [x] All 18 Livewire components documented with state/events/actions
- [x] All 30+ events mapped with dispatchers and listeners
- [x] At least 8 ASCII diagrams across all docs
- [x] All code examples include source file path references
- [x] Each doc has a table of contents for navigation

### Must Have
- Complete coverage of the architecture layer model
- Every service's public API documented
- Every DTO's structure documented
- Complete event system map
- Feature docs covering all 14+ features from developer perspective
- Cross-references between related docs
- Master index with organized navigation

### Must NOT Have (Guardrails)
- **No code changes** — documentation files only (Markdown in `docs/`)
- **No third-party library docs** — don't explain Laravel, Livewire, or Flux UI basics
- **No git tutorials** — assume reader knows git, document how gitty USES git
- **No full file copies** — use excerpts with file path references
- **No auto-generated docs** — hand-written Markdown only
- **No duplicate of AGENTS.md** — reference it for design system; don't rewrite
- **No marketing copy** — technical writing only, imperative mood
- **No directory nesting deeper than 1 level** — all docs flat in `docs/`
- **No Mermaid diagrams** — ASCII art only (user decision)
- **No subjective opinions** — document what IS, not what SHOULD BE

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES (Pest framework)
- **Automated tests**: NO — documentation task, no code changes to test
- **Framework**: N/A

### QA Policy
Every task MUST verify documentation quality through automated checks.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| Markdown files | Bash (grep/find) | Verify files exist, check structure, validate links |
| File references | Bash (grep + test) | Extract all `app/` paths from docs, verify each exists |
| Event names | Bash (grep + diff) | Cross-reference events in docs vs dispatched in code |
| Internal links | Bash (grep + test) | Check all `docs/*.md` links resolve |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately — foundation document):
└── Task 1: Architecture overview (docs/architecture.md) [writing]
    The foundation doc that all others reference.

Wave 2 (After Wave 1 — reference docs, MAX PARALLEL):
├── Task 2: Services reference (docs/services.md) [writing]
├── Task 3: DTOs reference (docs/dtos.md) [writing]
├── Task 4: Components reference (docs/components.md) [writing]
├── Task 5: Event system map (docs/events.md) [writing]
└── Task 6: Frontend architecture (docs/frontend.md) [writing]

Wave 3 (After Wave 2 — feature & guide docs):
├── Task 7: Feature docs (docs/features.md) [writing]
├── Task 8: Testing guide (docs/testing.md) [writing]
└── Task 9: Common tasks cookbook (docs/common-tasks.md) [writing]

Wave 4 (After Wave 3 — master index):
└── Task 10: Master index (docs/README.md) [writing]

Wave FINAL (After ALL tasks — verification):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Documentation quality review (unspecified-high)
├── Task F3: Cross-reference validation (unspecified-high)
└── Task F4: Scope fidelity check (deep)

Critical Path: T1 → T2-T6 → T7-T9 → T10 → FINAL
Parallel Speedup: ~60% faster than sequential
Max Concurrent: 5 (Wave 2)
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | 2, 3, 4, 5, 6 | 1 |
| 2 | 1 | 7, 9 | 2 |
| 3 | 1 | 7 | 2 |
| 4 | 1 | 7, 9 | 2 |
| 5 | 1 | 7 | 2 |
| 6 | 1 | 7 | 2 |
| 7 | 2, 3, 4, 5, 6 | 10 | 3 |
| 8 | 1 | 10 | 3 |
| 9 | 2, 4 | 10 | 3 |
| 10 | 7, 8, 9 | F1-F4 | 4 |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 1 | **1** | T1 → `writing` |
| 2 | **5** | T2-T6 → `writing` |
| 3 | **3** | T7-T9 → `writing` |
| 4 | **1** | T10 → `quick` |
| FINAL | **4** | F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep` |

---

## TODOs

- [x] 1. Architecture Overview (`docs/architecture.md`)

  **What to do**:
  - Create `docs/` directory if it doesn't exist
  - Write comprehensive architecture document covering:
    - **System Overview**: What gitty is, tech stack (NativePHP + Laravel + Livewire + Flux UI + Tailwind)
    - **Architecture Layers Diagram** (ASCII): Git CLI → GitCommandRunner → Services → DTOs → Livewire → Blade
    - **Boot Process**: How the app starts — NativeAppServiceProvider → Window creation → routes/web.php → AppLayout component
    - **Core Patterns**:
      - AbstractGitService base class pattern (repoPath, cache, commandRunner)
      - GitCommandRunner: how all git commands are executed via Laravel Process facade
      - Service instantiation pattern (per-request `new GitService($repoPath)`, not DI container)
      - DTO parsing pattern: git porcelain output → `fromOutput()`/`fromLine()` factory methods
      - Cache strategy: GitCacheService with group-based invalidation, TTL per operation
      - Concurrency: GitOperationQueue with cache-based locks
      - Error handling pipeline: git stderr → GitCommandFailedException → GitErrorHandler::translate() → user-friendly message
      - HandlesGitOperations trait: standardized try/catch + event dispatch in components
    - **Component Layout Diagram** (ASCII): How AppLayout orchestrates the UI panels
    - **Data Flow**: Step-by-step trace of a typical operation (e.g., staging a file) through all layers
    - **Repository Management**: RepoManager + Repository model + current repo tracking via Cache
    - **NativePHP Integration**: Electron window, custom menus, traffic light spacer, drag regions
  - Include ASCII diagrams for: layer model, component layout, data flow for a staging operation
  - Reference AGENTS.md for design system details (don't duplicate)

  **Must NOT do**:
  - Don't explain Laravel/Livewire basics
  - Don't duplicate AGENTS.md design system content
  - Don't write code — only documentation

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Technical documentation requiring clear prose, structured sections, and ASCII diagrams
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Needed to understand Livewire component lifecycle patterns being documented

  **Parallelization**:
  - **Can Run In Parallel**: NO (foundation document)
  - **Parallel Group**: Wave 1 (solo)
  - **Blocks**: Tasks 2, 3, 4, 5, 6
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL):

  **Pattern References**:
  - `app/Services/Git/AbstractGitService.php` — Base class pattern all services extend; shows repoPath validation, cache/commandRunner setup
  - `app/Services/Git/GitCommandRunner.php` — How git commands are built and executed via Process facade
  - `app/Services/Git/GitCacheService.php` — Cache group definitions, TTL strategy, invalidation methods
  - `app/Services/Git/GitOperationQueue.php` — Lock-based concurrency control pattern
  - `app/Services/Git/GitErrorHandler.php` — Error translation patterns (git messages → user messages)
  - `app/Livewire/Concerns/HandlesGitOperations.php` — Trait providing standardized error handling for components
  - `app/Livewire/AppLayout.php` — Root component mounting, repo path resolution, sidebar toggle
  - `resources/views/livewire/app-layout.blade.php` — Full layout structure showing all child components
  - `app/Providers/NativeAppServiceProvider.php` — NativePHP boot: window config, menu system
  - `app/Services/RepoManager.php` — Repository persistence, current repo tracking
  - `bootstrap/app.php` — Laravel boot configuration
  - `routes/web.php` — Route definitions

  **Acceptance Criteria**:
  - [x] File `docs/architecture.md` exists with all sections listed above
  - [x] At least 3 ASCII diagrams: layer model, component layout, data flow trace
  - [x] All file path references point to existing files
  - [x] Table of contents at top of document
  - [x] References AGENTS.md for design system (link, not duplication)

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Architecture doc exists with required sections
    Tool: Bash (grep)
    Preconditions: Task implementation complete
    Steps:
      1. Verify file exists: test -f docs/architecture.md
      2. Check for required sections: grep -c "## " docs/architecture.md (expect >= 8)
      3. Check for ASCII diagrams: grep -c "^\s*[|+\-]" docs/architecture.md (expect >= 10 lines of diagram content)
      4. Check for file references: grep -c "app/" docs/architecture.md (expect >= 10)
      5. Check table of contents: head -30 docs/architecture.md | grep -c "\[.*\](#" (expect >= 5)
    Expected Result: All checks pass — file exists, 8+ sections, diagrams present, 10+ file refs, TOC present
    Failure Indicators: File missing, sections < 8, no diagrams, no file references
    Evidence: .sisyphus/evidence/task-1-architecture-validation.txt

  Scenario: File references in architecture doc are valid
    Tool: Bash (grep + test)
    Preconditions: docs/architecture.md exists
    Steps:
      1. Extract all app/ file references: grep -oP 'app/[^\s`\)]+\.php' docs/architecture.md | sort -u
      2. For each reference, verify file exists: while read f; do test -f "$f" || echo "MISSING: $f"; done
    Expected Result: Zero "MISSING" lines — all referenced files exist
    Failure Indicators: Any "MISSING" output indicates a broken reference
    Evidence: .sisyphus/evidence/task-1-fileref-validation.txt
  ```

  **Commit**: YES
  - Message: `docs(architecture): add comprehensive architecture overview`
  - Files: `docs/architecture.md`

---

- [x] 2. Services Reference (`docs/services.md`)

  **What to do**:
  - Document ALL services in `app/Services/Git/` and `app/Services/`:
    - **Git Services** (inherit from AbstractGitService):
      - GitService: status(), log(), diff(), currentBranch(), aheadBehind(), getConfigValue()
      - StagingService: stageFile(), unstageFile(), stageAll(), unstageAll(), discardFile(), discardAll(), stageFiles(), unstageFiles(), discardFiles()
      - CommitService: commit(), commitAmend(), commitAndPush(), undoLastCommit(), lastCommitMessage(), cherryPick(), cherryPickAbort(), cherryPickContinue(), isLastCommitPushed(), isLastCommitMerge()
      - BranchService: branches(), switchBranch(), createBranch(), deleteBranch(), mergeBranch(), isCommitOnRemote()
      - DiffService: parseDiff(), extractHunks(), stageHunk(), unstageHunk(), stageLines(), unstageLines()
      - RemoteService: push(), pull(), fetch(), fetchAll(), forcePushWithLease(), remotes()
      - StashService: stash(), stashList(), stashApply(), stashPop(), stashDrop(), stashFiles()
      - ResetService: resetSoft(), resetMixed(), resetHard(), revertCommit()
      - RebaseService: (document all public methods)
      - TagService: (document all public methods)
      - ConflictService: (document all public methods)
      - BlameService: (document all public methods)
      - SearchService: (document all public methods)
      - GraphService: (document all public methods)
    - **Infrastructure Services** (not AbstractGitService):
      - GitCommandRunner: run(), runOrFail(), runWithInput() — the execution layer
      - GitCacheService: get(), invalidate(), invalidateAll(), invalidateGroup() — cache groups table
      - GitOperationQueue: execute(), isLocked() — concurrency model
      - GitErrorHandler: translate(), isDirtyTreeError() — error pattern matching
      - GitConfigValidator: checkGitBinary() — startup validation
    - **App Services**:
      - RepoManager: openRepo(), recentRepos(), removeRepo(), currentRepo(), setCurrentRepo()
      - SettingsService: (document all public methods including getCommitHistory, addCommitMessage)
      - EditorService: openFile() — external editor integration
      - NotificationService: notify() — native notifications
      - AutoFetchService: (document all public methods)
  - For each service document: purpose, constructor params, each public method (signature, what it does, cache behavior, events dispatched if any, throws)
  - Include a summary table: Service → Responsibility → Cache Group → Key Methods

  **Must NOT do**:
  - Don't copy entire service files — use method signatures with descriptions
  - Don't document private/protected methods (except generatePatch/generateLinePatch in DiffService as they explain hunk staging internals)
  - Don't explain git commands themselves — focus on how the service wraps them

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Technical reference documentation requiring precise method documentation
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 3, 4, 5, 6)
  - **Blocks**: Tasks 7, 9
  - **Blocked By**: Task 1

  **References** (CRITICAL):

  **Pattern References**:
  - `app/Services/Git/AbstractGitService.php` — Base class; constructor pattern
  - `app/Services/Git/GitService.php` — Core service with status, log, diff
  - `app/Services/Git/StagingService.php` — File staging operations
  - `app/Services/Git/CommitService.php` — Commit operations including amend, undo, cherry-pick
  - `app/Services/Git/BranchService.php` — Branch CRUD and merge
  - `app/Services/Git/DiffService.php` — Diff parsing, hunk/line staging with patch generation
  - `app/Services/Git/RemoteService.php` — Push, pull, fetch operations
  - `app/Services/Git/StashService.php` — Stash operations
  - `app/Services/Git/ResetService.php` — Reset (soft/mixed/hard) and revert
  - `app/Services/Git/RebaseService.php` — Rebase operations
  - `app/Services/Git/TagService.php` — Tag management
  - `app/Services/Git/ConflictService.php` — Conflict detection and resolution
  - `app/Services/Git/BlameService.php` — Git blame
  - `app/Services/Git/SearchService.php` — Search (commits, files, content)
  - `app/Services/Git/GraphService.php` — Commit graph generation
  - `app/Services/Git/GitCommandRunner.php` — Command execution layer
  - `app/Services/Git/GitCacheService.php` — Caching with groups
  - `app/Services/Git/GitOperationQueue.php` — Concurrency control
  - `app/Services/Git/GitErrorHandler.php` — Error translation
  - `app/Services/Git/GitConfigValidator.php` — Git binary validation
  - `app/Services/RepoManager.php` — Repository management
  - `app/Services/SettingsService.php` — Settings persistence
  - `app/Services/EditorService.php` — External editor
  - `app/Services/NotificationService.php` — Notifications
  - `app/Services/AutoFetchService.php` — Background fetch

  **Acceptance Criteria**:
  - [x] File `docs/services.md` exists
  - [x] All 20 services have dedicated sections
  - [x] Each service section includes: purpose, constructor, public method signatures with descriptions
  - [x] Summary table at top listing Service → Responsibility → Cache Group → Key Methods
  - [x] Cache invalidation behavior documented for each mutating method
  - [x] Table of contents at top

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All services documented
    Tool: Bash (grep)
    Preconditions: docs/services.md exists
    Steps:
      1. Count service sections: grep -c "### " docs/services.md (expect >= 20)
      2. Check key services present: grep "GitService\|StagingService\|CommitService\|BranchService\|DiffService\|RemoteService\|StashService\|ResetService" docs/services.md | wc -l (expect >= 8)
      3. Check summary table: grep -c "|" docs/services.md (expect >= 22, header + 20 services + separator)
    Expected Result: 20+ service sections, all key services present, summary table complete
    Failure Indicators: Missing services, no summary table
    Evidence: .sisyphus/evidence/task-2-services-validation.txt

  Scenario: Method signatures are accurate
    Tool: Bash (grep cross-check)
    Preconditions: docs/services.md exists
    Steps:
      1. Extract documented method names from services.md
      2. Spot-check 5 methods against actual PHP files using grep
      3. Verify parameter names match between docs and code
    Expected Result: All spot-checked methods match code signatures
    Failure Indicators: Method name or parameter mismatch
    Evidence: .sisyphus/evidence/task-2-method-accuracy.txt
  ```

  **Commit**: YES
  - Message: `docs(services): add complete service API reference`
  - Files: `docs/services.md`

---

- [x] 3. DTOs Reference (`docs/dtos.md`)

  **What to do**:
  - Document ALL 15 DTOs in `app/DTOs/`:
    - **GitStatus**: branch, upstream, aheadBehind, changedFiles; `fromOutput()` parsing porcelain v2
    - **ChangedFile**: path, oldPath, indexStatus, worktreeStatus; `isStaged()`, `isUnstaged()`, `isUntracked()`, `isUnmerged()`, `statusLabel()`; implements ArrayAccess
    - **AheadBehind**: ahead, behind (readonly)
    - **Branch**: name, isRemote, isCurrent, lastCommitSha; `fromBranchLine()`
    - **Commit**: sha, shortSha, message, author, email, date, refs; `fromLogLine()`
    - **DiffResult**: files (Collection of DiffFile); `fromDiffOutput()`
    - **DiffFile**: oldPath, newPath, status, isBinary, hunks, additions, deletions; `getDisplayPath()`
    - **Hunk**: oldStart, oldCount, newStart, newCount, header, lines (Collection of HunkLine)
    - **HunkLine**: type (context/addition/deletion), content, oldLineNumber, newLineNumber
    - **ConflictFile**: path, ourContent, theirContent, baseContent, mergeType
    - **MergeResult**: hasConflicts, conflictFiles, output; `fromMergeOutput()`
    - **Stash**: index, message, branch, sha; `fromStashLine()`
    - **Remote**: name, url, type; `fromRemoteLine()`
    - **BlameLine**: sha, author, date, lineNumber, content; `fromBlameLine()`
    - **GraphNode**: (document properties)
  - For each DTO: property table (name, type, description), factory methods, key methods, which services produce it, which components consume it
  - Include a "DTO Relationships" diagram showing which DTOs are nested in others (e.g., GitStatus contains Collection<ChangedFile> and AheadBehind; DiffResult contains Collection<DiffFile> which contains Collection<Hunk> which contains Collection<HunkLine>)
  - Document the `readonly` class pattern used across DTOs
  - Note the ArrayAccess implementation on ChangedFile (for Livewire wire:key compatibility)

  **Must NOT do**:
  - Don't copy entire DTO files
  - Don't document constructor implementation details beyond signatures

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Structured reference documentation with tables and relationship mapping
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 2, 4, 5, 6)
  - **Blocks**: Task 7
  - **Blocked By**: Task 1

  **References** (CRITICAL):

  **Pattern References**:
  - `app/DTOs/GitStatus.php` — Core DTO with `fromOutput()` parsing git status --porcelain=v2
  - `app/DTOs/ChangedFile.php` — File status DTO with ArrayAccess, status helpers
  - `app/DTOs/AheadBehind.php` — Simple readonly value object
  - `app/DTOs/Branch.php` — Branch DTO with `fromBranchLine()` parser
  - `app/DTOs/Commit.php` — Commit DTO with `fromLogLine()` parser
  - `app/DTOs/DiffResult.php` — Nested DTO containing DiffFile collection
  - `app/DTOs/DiffFile.php` — File-level diff with hunks collection
  - `app/DTOs/Hunk.php` — Hunk with lines collection
  - `app/DTOs/HunkLine.php` — Individual diff line
  - `app/DTOs/ConflictFile.php` — Merge conflict representation
  - `app/DTOs/MergeResult.php` — Merge/cherry-pick result
  - `app/DTOs/Stash.php` — Stash entry
  - `app/DTOs/Remote.php` — Remote repository
  - `app/DTOs/BlameLine.php` — Blame annotation
  - `app/DTOs/GraphNode.php` — Commit graph node

  **Acceptance Criteria**:
  - [x] File `docs/dtos.md` exists
  - [x] All 15 DTOs documented with property tables
  - [x] Factory methods documented for all DTOs that have them
  - [x] DTO relationship diagram showing nesting (GitStatus → ChangedFile, DiffResult → DiffFile → Hunk → HunkLine)
  - [x] Table of contents at top

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All DTOs documented
    Tool: Bash (grep)
    Preconditions: docs/dtos.md exists
    Steps:
      1. Count DTO sections: grep -c "### " docs/dtos.md (expect >= 15)
      2. Check all DTOs present: grep "GitStatus\|ChangedFile\|AheadBehind\|Branch\|Commit\|DiffResult\|DiffFile\|Hunk\b\|HunkLine\|ConflictFile\|MergeResult\|Stash\b\|Remote\b\|BlameLine\|GraphNode" docs/dtos.md | wc -l (expect >= 15)
      3. Check property tables: grep -c "|" docs/dtos.md (expect >= 60 — ~4 properties per DTO)
    Expected Result: 15 DTO sections, all names present, property tables for each
    Failure Indicators: Missing DTOs, no property tables
    Evidence: .sisyphus/evidence/task-3-dtos-validation.txt

  Scenario: DTO properties match code
    Tool: Bash (grep cross-check)
    Preconditions: docs/dtos.md exists
    Steps:
      1. Check GitStatus properties documented: grep "branch\|upstream\|aheadBehind\|changedFiles" docs/dtos.md (expect all 4)
      2. Check ChangedFile properties documented: grep "path\|oldPath\|indexStatus\|worktreeStatus" docs/dtos.md (expect all 4)
      3. Verify against actual code: grep "public.*\$" app/DTOs/GitStatus.php
    Expected Result: All documented properties match actual class properties
    Failure Indicators: Missing or incorrect property documentation
    Evidence: .sisyphus/evidence/task-3-property-accuracy.txt
  ```

  **Commit**: YES
  - Message: `docs(dtos): add complete DTO reference with property tables`
  - Files: `docs/dtos.md`

---

- [x] 4. Livewire Components Reference (`docs/components.md`)

  **What to do**:
  - Document ALL 18 Livewire components in `app/Livewire/`:
    - **AppLayout** (root): repoPath, sidebarCollapsed; mount logic (repo resolution), toggleSidebar, handleRepoSwitched; Layout('layouts.app')
    - **StagingPanel**: unstagedFiles, stagedFiles, untrackedFiles, treeView; stageFile/unstageFile/stageAll/unstageAll/discardFile/discardAll/stageSelected/unstageSelected/discardSelected/stashSelected/stashAll; refreshStatus with hash-based change detection
    - **CommitPanel**: message, isAmend, stagedCount, commitHistory; commit/commitAndPush/toggleAmend/undoLastCommit; branch-based prefill, template system, history cycling
    - **DiffViewer**: file, isStaged, diffData, files, diffViewMode; loadDiff/stageHunk/unstageHunk/stageSelectedLines/unstageSelectedLines/openInEditor/toggleDiffViewMode/getSplitLines; image diff support, large file detection, language detection
    - **BranchManager**: currentBranch, branches, branchQuery; switchBranch/createBranch/deleteBranch/mergeBranch; auto-stash modal, filtered local/remote branch properties
    - **SyncPanel**: isOperationRunning, aheadBehind; syncPush/syncPull/syncFetch/syncFetchAll/syncForcePushWithLease; detached HEAD guard, native notifications
    - **HistoryPanel**: commits, selectedCommitSha, showGraph; loadCommits/selectCommit/promptReset/confirmReset/promptRevert/confirmRevert/promptCherryPick/confirmCherryPick/promptInteractiveRebase; pagination, graph data
    - **RepoSwitcher**: (document state, actions)
    - **RepoSidebar**: (document state, actions — stash list, tag management)
    - **CommandPalette**: isOpen, mode, query; 28 commands, filtering, input mode for create-branch; disabled commands based on repo state
    - **SearchPanel**: (document state, actions)
    - **ConflictResolver**: (document state, actions)
    - **RebasePanel**: (document state, actions)
    - **BlameView**: (document state, actions)
    - **SettingsModal**: (document state, actions — editor, auto-fetch, theme)
    - **ErrorBanner**: (document state, actions)
    - **AutoFetchIndicator**: (document state, actions)
    - **ShortcutHelp**: (document state, actions)
  - For each component: purpose, public properties (state), actions (methods), events listened (#[On]), events dispatched ($this->dispatch()), services injected, corresponding Blade view
  - Include component hierarchy diagram (ASCII) showing parent-child relationships
  - Document the HandlesGitOperations trait usage pattern

  **Must NOT do**:
  - Don't copy entire component files
  - Don't document Blade view HTML structure (that's frontend.md territory)
  - Don't explain basic Livewire concepts

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Comprehensive component reference requiring understanding of Livewire patterns
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Critical for understanding Livewire component lifecycle, events, computed properties

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 2, 3, 5, 6)
  - **Blocks**: Tasks 7, 9
  - **Blocked By**: Task 1

  **References** (CRITICAL):

  **Pattern References**:
  - `app/Livewire/AppLayout.php` — Root component, repo path resolution
  - `app/Livewire/StagingPanel.php` — Complex component with many actions, hash-based refresh
  - `app/Livewire/CommitPanel.php` — Template system, history cycling, branch prefill
  - `app/Livewire/DiffViewer.php` — Hunk/line staging, image diffs, split view
  - `app/Livewire/BranchManager.php` — Auto-stash, filtered computed properties
  - `app/Livewire/SyncPanel.php` — Sync operations with notifications
  - `app/Livewire/HistoryPanel.php` — Reset/revert/cherry-pick modals, graph
  - `app/Livewire/CommandPalette.php` — Command registry, input mode, disabled state
  - `app/Livewire/RepoSwitcher.php` — Repository switching
  - `app/Livewire/RepoSidebar.php` — Stash list, tags
  - `app/Livewire/SearchPanel.php` — Search functionality
  - `app/Livewire/ConflictResolver.php` — Conflict resolution UI
  - `app/Livewire/RebasePanel.php` — Rebase operations
  - `app/Livewire/BlameView.php` — Blame display
  - `app/Livewire/SettingsModal.php` — Settings management
  - `app/Livewire/ErrorBanner.php` — Error display
  - `app/Livewire/AutoFetchIndicator.php` — Auto-fetch status
  - `app/Livewire/ShortcutHelp.php` — Shortcut overlay
  - `app/Livewire/Concerns/HandlesGitOperations.php` — Shared error handling trait

  **Acceptance Criteria**:
  - [x] File `docs/components.md` exists
  - [x] All 18 components documented
  - [x] Each component has: purpose, public properties table, actions list, events listened, events dispatched
  - [x] Component hierarchy diagram present
  - [x] HandlesGitOperations trait documented

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All components documented
    Tool: Bash (grep)
    Preconditions: docs/components.md exists
    Steps:
      1. Count component sections: grep -c "### " docs/components.md (expect >= 18)
      2. Check key components: grep "AppLayout\|StagingPanel\|CommitPanel\|DiffViewer\|BranchManager\|SyncPanel\|CommandPalette\|HistoryPanel" docs/components.md | wc -l (expect >= 8)
    Expected Result: 18+ component sections, all key components present
    Failure Indicators: Missing component sections
    Evidence: .sisyphus/evidence/task-4-components-validation.txt

  Scenario: Events accurately documented
    Tool: Bash (grep cross-check)
    Preconditions: docs/components.md exists
    Steps:
      1. Extract event names from docs: grep -oP "(?<=dispatch\(')[^']+|(?<=#\[On\(')[^']+" docs/components.md | sort -u
      2. Extract event names from code: grep -rh "dispatch(" app/Livewire/ | grep -oP "(?<=')[^']+(?=')" | sort -u
      3. Compare the two lists
    Expected Result: All dispatched events from code appear in docs
    Failure Indicators: Events in code not mentioned in docs
    Evidence: .sisyphus/evidence/task-4-event-accuracy.txt
  ```

  **Commit**: YES
  - Message: `docs(components): add complete Livewire component reference`
  - Files: `docs/components.md`

---

- [x] 5. Event System Map (`docs/events.md`)

  **What to do**:
  - Create a comprehensive map of ALL Livewire events in the app:
    - **Core Events**:
      - `status-updated` — dispatched by: StagingPanel, BranchManager, SyncPanel, HistoryPanel, CommitPanel; listened by: CommitPanel, SyncPanel, HistoryPanel, CommandPalette, BranchManager
      - `file-selected` — dispatched by: StagingPanel; listened by: DiffViewer
      - `repo-switched` — dispatched by: RepoSwitcher; listened by: AppLayout, HistoryPanel, CommandPalette
      - `show-error` — dispatched by: many components; listened by: ErrorBanner
      - `committed` — dispatched by: CommitPanel; listened by: (various for refresh)
      - `refresh-staging` — dispatched by: DiffViewer; listened by: StagingPanel
      - `stash-created` — dispatched by: StagingPanel; listened by: RepoSidebar
    - **Keyboard Events** (dispatched from AppLayout view via Alpine.js `@keydown`):
      - `keyboard-commit`, `keyboard-commit-push`, `keyboard-stage-all`, `keyboard-unstage-all`, `keyboard-stash`, `keyboard-select-all`, `keyboard-escape`
    - **Command Palette Events** (dispatched by CommandPalette):
      - `palette-discard-all`, `palette-toggle-view`, `palette-toggle-amend`, `palette-undo-last-commit`, `palette-push`, `palette-pull`, `palette-fetch`, `palette-fetch-all`, `palette-force-push`, `palette-create-branch`, `palette-toggle-sidebar`, `palette-toggle-diff-view`, `palette-open-in-editor`, `palette-abort-merge`, `palette-create-tag`, `palette-continue-rebase`, `palette-abort-rebase`, `palette-open-folder`
    - **UI Toggle Events**:
      - `toggle-command-palette`, `open-shortcut-help`, `toggle-history-panel`, `open-search`, `focus-commit-message`, `open-settings`, `show-blame`
  - For each event: name, payload (parameters), who dispatches it, who listens, what it triggers
  - Per-feature event flow diagrams (ASCII): staging flow, commit flow, branch switch flow, sync flow
  - Document the keyboard shortcut → Alpine.js → Livewire dispatch → component handler pipeline

  **Must NOT do**:
  - Don't try to make one massive diagram with all events (unreadable)
  - Don't document Laravel framework events (only gitty custom events)

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Technical mapping document with precise event tracing
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Understanding Livewire event dispatch/listen patterns

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 2, 3, 4, 6)
  - **Blocks**: Task 7
  - **Blocked By**: Task 1

  **References** (CRITICAL):

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php` — All keyboard shortcut bindings (lines 3-17)
  - `app/Livewire/CommandPalette.php:getCommands()` — Complete command registry with events
  - `app/Livewire/StagingPanel.php` — Dispatches status-updated, file-selected, stash-created
  - `app/Livewire/CommitPanel.php` — Listens status-updated, keyboard-commit; dispatches committed
  - `app/Livewire/DiffViewer.php` — Listens file-selected; dispatches refresh-staging
  - `app/Livewire/SyncPanel.php` — Listens status-updated, palette-push/pull/fetch
  - `app/Livewire/BranchManager.php` — Dispatches status-updated on branch operations
  - `app/Livewire/AppLayout.php` — Listens repo-switched, palette-toggle-sidebar
  - `app/Livewire/ErrorBanner.php` — Listens show-error

  **Acceptance Criteria**:
  - [x] File `docs/events.md` exists
  - [x] All 30+ events listed with dispatcher/listener mapping
  - [x] At least 4 per-feature event flow diagrams (ASCII)
  - [x] Keyboard shortcut pipeline documented
  - [x] Command palette event dispatch documented
  - [x] Summary table: Event → Payload → Dispatchers → Listeners

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All events documented
    Tool: Bash (grep)
    Preconditions: docs/events.md exists
    Steps:
      1. Count documented events: grep -c "status-updated\|file-selected\|repo-switched\|show-error\|committed\|refresh-staging\|keyboard-commit\|palette-" docs/events.md (expect >= 15)
      2. Check for flow diagrams: grep -c "Flow\|flow" docs/events.md (expect >= 4)
    Expected Result: 15+ events documented, 4+ flow diagrams
    Failure Indicators: Events missing, no flow diagrams
    Evidence: .sisyphus/evidence/task-5-events-validation.txt

  Scenario: Event names match codebase
    Tool: Bash (grep cross-check)
    Preconditions: docs/events.md exists
    Steps:
      1. Extract all $this->dispatch event names from codebase: grep -rh "dispatch(" app/Livewire/ | grep -oP "(?<=')[^']+(?=')" | sort -u > /tmp/code-events.txt
      2. Extract event names from docs: grep -oP "'[a-z\-]+'" docs/events.md | tr -d "'" | sort -u > /tmp/doc-events.txt
      3. Check code events present in docs: comm -23 /tmp/code-events.txt /tmp/doc-events.txt
    Expected Result: All code events appear in docs (empty diff)
    Failure Indicators: Events in code not documented
    Evidence: .sisyphus/evidence/task-5-event-accuracy.txt
  ```

  **Commit**: YES
  - Message: `docs(events): add complete event system map with flow diagrams`
  - Files: `docs/events.md`

---

- [x] 6. Frontend Architecture (`docs/frontend.md`)

  **What to do**:
  - Document the frontend architecture covering:
    - **Layout Structure**: `layouts/app.blade.php` → `livewire/app-layout.blade.php` hierarchy
    - **Panel Architecture**: How the three-panel layout works (sidebar, staging+commit, diff/history/blame) with resizable divider
    - **CSS Architecture**:
      - Two systems: `@theme {}` (Tailwind/Flux tokens) vs `:root {}` (CSS custom properties)
      - Catppuccin Latte color palette integration (reference AGENTS.md)
      - Custom CSS classes: diff-line-addition, diff-line-deletion, diff-line-context, animations
      - Hardcoded hex values pattern (intentional for grep-ability)
    - **Alpine.js Components**:
      - Panel resize handler (panelWidth, isDragging, startDrag, onDrag, stopDrag)
      - Theme toggle (dark/light/system with localStorage persistence)
      - Active right panel switcher (diff/history/blame)
      - Keyboard shortcut bindings (how Alpine dispatches Livewire events)
    - **Flux UI Usage**:
      - Button variants and sizes used
      - Split buttons with `flux:button.group`
      - Dropdown menus for branch manager, repo switcher
      - Tooltip wrapping pattern for icon buttons
      - Modal patterns
    - **Phosphor Icons**: Light variant for headers, regular for actions
    - **Custom Blade Components**:
      - `file-tree.blade.php` — recursive tree view
      - `command-palette.blade.php` — command palette overlay
    - **NativePHP Integration**:
      - Electron window with hidden titlebar
      - Traffic light spacer (64px)
      - `-webkit-app-region: drag/no-drag` pattern
      - Native menu bar from NativeAppServiceProvider

  **Must NOT do**:
  - Don't duplicate AGENTS.md color palette tables — link to it
  - Don't explain Tailwind CSS or Flux UI basics
  - Don't document every Blade view line-by-line

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Frontend documentation requiring CSS/JS/Blade understanding
  - **Skills**: [`tailwindcss-development`, `fluxui-development`]
    - `tailwindcss-development`: CSS architecture with Tailwind v4
    - `fluxui-development`: Flux UI component usage patterns

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 2, 3, 4, 5)
  - **Blocks**: Task 7
  - **Blocked By**: Task 1

  **References** (CRITICAL):

  **Pattern References**:
  - `resources/views/layouts/app.blade.php` — Base HTML layout
  - `resources/views/livewire/app-layout.blade.php` — Full app layout with Alpine.js components
  - `resources/css/app.css` — CSS architecture: @theme, :root, custom classes, animations
  - `resources/js/app.js` — JavaScript entry point
  - `resources/views/components/file-tree.blade.php` — Recursive tree component
  - `app/Providers/NativeAppServiceProvider.php` — Electron window config, native menus
  - `AGENTS.md` — Design system reference (link, don't duplicate)

  **Acceptance Criteria**:
  - [x] File `docs/frontend.md` exists
  - [x] Layout structure documented with panel architecture
  - [x] CSS two-system architecture explained (@theme vs :root)
  - [x] Alpine.js components documented
  - [x] Flux UI usage patterns documented
  - [x] NativePHP integration explained
  - [x] References AGENTS.md for color palette details

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Frontend doc covers all major areas
    Tool: Bash (grep)
    Preconditions: docs/frontend.md exists
    Steps:
      1. Check for CSS architecture section: grep -c "@theme\|:root\|Catppuccin" docs/frontend.md (expect >= 3)
      2. Check for Alpine.js section: grep -c "Alpine\|x-data\|x-show" docs/frontend.md (expect >= 3)
      3. Check for Flux UI section: grep -c "flux:\|Flux" docs/frontend.md (expect >= 3)
      4. Check for NativePHP section: grep -c "NativePHP\|Electron\|traffic light" docs/frontend.md (expect >= 2)
      5. Check AGENTS.md reference: grep -c "AGENTS.md" docs/frontend.md (expect >= 1)
    Expected Result: All sections present with sufficient content
    Failure Indicators: Missing sections, no AGENTS.md reference
    Evidence: .sisyphus/evidence/task-6-frontend-validation.txt
  ```

  **Commit**: YES
  - Message: `docs(frontend): add frontend architecture documentation`
  - Files: `docs/frontend.md`

---

- [x] 7. Feature Documentation (`docs/features.md`)

  **What to do**:
  - Document ALL features from a developer perspective (how each is implemented, which components/services are involved, how to extend):
    - **Staging**: File-level (stage/unstage/discard), bulk operations, multi-select, tree view, status hash optimization
    - **Hunk & Line Staging**: How DiffService generates patches, the hunk hydration pattern in DiffViewer
    - **Committing**: Message input, conventional commit templates, branch-based prefill, amend mode, commit history cycling, undo last commit
    - **Branch Management**: Switch, create, delete, merge; auto-stash on dirty tree; filtered branch properties; remote branch filtering
    - **Diff Viewing**: Unified vs split mode, language detection, binary file handling, image diff (base64 old/new), large file detection (>1MB threshold)
    - **Stashing**: Create stash (with untracked), apply/pop/drop, stash individual files, auto-generated stash messages
    - **Push/Pull/Fetch**: Push, pull, fetch, fetch all remotes, force push with lease; detached HEAD guards; native notifications on completion
    - **History**: Commit log with pagination, commit graph visualization, select commit, reset (soft/mixed/hard with DISCARD confirmation), revert, cherry-pick
    - **Rebase**: Interactive rebase panel
    - **Search**: Commit search, file search, content search
    - **Blame**: Git blame view with annotations
    - **Conflict Resolution**: Conflict detection, resolver UI
    - **Command Palette**: 28 commands, search/filter, input mode for branch creation, disabled state based on repo context
    - **Keyboard Shortcuts**: 15+ shortcuts via Alpine.js, dispatching to Livewire events
    - **Repository Management**: Open repo (native dialog), recent repos, switch repos with cache invalidation
    - **Settings**: Editor configuration, auto-fetch interval, theme toggle (dark/light/system)
    - **Auto-Fetch**: Background fetch with configurable interval, indicator
    - **Error Handling**: Error banner, toast notifications, error translation
  - For each feature: what it does, which components are involved, which services it uses, key implementation details, how to extend it

  **Must NOT do**:
  - Don't write user guides ("click this button")
  - Don't duplicate service API details from services.md — reference it
  - Don't duplicate component details from components.md — reference it

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Feature documentation connecting components, services, and DTOs together
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Understanding feature implementation across Livewire components

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 8, 9)
  - **Blocks**: Task 10
  - **Blocked By**: Tasks 2, 3, 4, 5, 6

  **References** (CRITICAL):

  **Pattern References**:
  - All Livewire components in `app/Livewire/`
  - All services in `app/Services/`
  - All DTOs in `app/DTOs/`
  - `app/Helpers/FileTreeBuilder.php` — Tree view logic
  - `resources/views/livewire/` — All Blade views for UI structure
  - `docs/services.md` (from Task 2) — Cross-reference service APIs
  - `docs/components.md` (from Task 4) — Cross-reference component details
  - `docs/events.md` (from Task 5) — Cross-reference event flows

  **Acceptance Criteria**:
  - [x] File `docs/features.md` exists
  - [x] All 18+ features documented
  - [x] Each feature lists: involved components, services used, key implementation details
  - [x] Cross-references to services.md, components.md, events.md
  - [x] At least 3 "how to extend" sections for core features

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All features documented
    Tool: Bash (grep)
    Preconditions: docs/features.md exists
    Steps:
      1. Count feature sections: grep -c "### " docs/features.md (expect >= 15)
      2. Check key features: grep "Staging\|Commit\|Branch\|Diff\|Stash\|Push\|Pull\|History\|Search\|Blame\|Command Palette\|Keyboard" docs/features.md | wc -l (expect >= 10)
      3. Check cross-references: grep -c "services.md\|components.md\|events.md" docs/features.md (expect >= 5)
    Expected Result: 15+ features, all key features present, cross-references included
    Failure Indicators: Missing features, no cross-references
    Evidence: .sisyphus/evidence/task-7-features-validation.txt
  ```

  **Commit**: YES
  - Message: `docs(features): add comprehensive feature documentation`
  - Files: `docs/features.md`

---

- [x] 8. Testing Guide (`docs/testing.md`)

  **What to do**:
  - Document the testing infrastructure and patterns:
    - **Test Overview**: 83 tests across Feature/, Unit/, Browser/ directories; Pest framework
    - **Running Tests**:
      - `php artisan test --compact` — all tests
      - `php artisan test --compact --filter=TestName` — specific test
      - `php artisan test --compact tests/Feature/Services/` — directory
    - **Test Organization**:
      - `tests/Feature/Services/` — Service-level tests (GitServiceTest, BranchServiceTest, etc.)
      - `tests/Feature/Livewire/` — Component tests (StagingPanelTest, CommitPanelTest, etc.)
      - `tests/Unit/DTOs/` — DTO unit tests
      - `tests/Unit/Exceptions/` — Exception tests
      - `tests/Browser/` — Pest browser tests
    - **Test Helpers**:
      - `tests/Helpers/GitTestHelper.php` — Git repo scaffolding for tests
      - `tests/Mocks/GitOutputFixtures.php` — Fixed git output for deterministic parsing tests
      - `tests/TestCase.php` — Base test case
    - **Testing Patterns**:
      - How to test git services (create temp repo, run operations, assert state)
      - How to test Livewire components (Livewire::test(), asserting events, wire:model)
      - How to test DTOs (provide raw git output, assert parsed properties)
      - How to mock git operations
      - Browser testing with Pest
    - **Writing New Tests**: Step-by-step guide for adding tests for new features
    - **Code Formatting**: `vendor/bin/pint --dirty --format agent` before committing

  **Must NOT do**:
  - Don't explain Pest basics — link to Pest docs
  - Don't list every individual test case

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Testing documentation for developer reference
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Understanding Pest 4 testing patterns and assertions

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 7, 9)
  - **Blocks**: Task 10
  - **Blocked By**: Task 1

  **References** (CRITICAL):

  **Pattern References**:
  - `tests/Pest.php` — Pest configuration
  - `tests/TestCase.php` — Base test case
  - `tests/Helpers/GitTestHelper.php` — Git repo scaffolding
  - `tests/Mocks/GitOutputFixtures.php` — Output fixtures
  - `tests/Feature/Services/GitServiceTest.php` — Example service test
  - `tests/Feature/Livewire/StagingPanelTest.php` — Example component test
  - `tests/Unit/DTOs/ChangedFileTest.php` — Example DTO test
  - `tests/Browser/SmokeTest.php` — Example browser test
  - `tests/Browser/Helpers/BrowserTestHelper.php` — Browser test helper

  **Acceptance Criteria**:
  - [x] File `docs/testing.md` exists
  - [x] Test organization documented (directories, naming)
  - [x] Running tests section with commands
  - [x] Test helpers documented (GitTestHelper, GitOutputFixtures)
  - [x] Testing patterns section (services, components, DTOs)
  - [x] "Writing new tests" section with step-by-step guide

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Testing guide covers all areas
    Tool: Bash (grep)
    Preconditions: docs/testing.md exists
    Steps:
      1. Check running tests: grep -c "php artisan test" docs/testing.md (expect >= 2)
      2. Check test helpers documented: grep -c "GitTestHelper\|GitOutputFixtures" docs/testing.md (expect >= 2)
      3. Check patterns: grep -c "Livewire::test\|Feature\|Unit\|Browser" docs/testing.md (expect >= 4)
    Expected Result: All areas covered
    Failure Indicators: Missing sections
    Evidence: .sisyphus/evidence/task-8-testing-validation.txt
  ```

  **Commit**: YES
  - Message: `docs(testing): add testing guide with patterns and helpers`
  - Files: `docs/testing.md`

---

- [x] 9. Common Tasks Cookbook (`docs/common-tasks.md`)

  **What to do**:
  - Create a practical cookbook for common development tasks:
    - **Adding a New Git Operation**:
      1. Create service method in appropriate service (extends AbstractGitService)
      2. Use commandRunner.run() or runOrFail()
      3. Invalidate relevant cache groups
      4. Create/update DTO if new data shape
      5. Add Livewire component method using executeGitOperation()
      6. Add Blade view button/UI element
      7. Write tests
    - **Adding a New Livewire Component**:
      1. Create component with `php artisan make:livewire`
      2. Accept repoPath prop
      3. Use HandlesGitOperations trait for git operations
      4. Dispatch/listen events for cross-component communication
      5. Register in app-layout.blade.php
    - **Adding a Command Palette Command**:
      1. Add entry to CommandPalette::getCommands() array
      2. Add #[On('event-name')] handler in target component
      3. Add disabled logic in getDisabledCommands()
    - **Adding a Keyboard Shortcut**:
      1. Add @keydown handler in app-layout.blade.php
      2. Add event handler in target component
      3. Add to ShortcutHelp display
    - **Adding a New DTO**:
      1. Create readonly class in app/DTOs/
      2. Add `fromOutput()` or `fromLine()` factory method
      3. Document parsing logic
    - **Modifying the Cache Strategy**:
      1. Understand cache groups in GitCacheService
      2. Add new group or modify existing TTLs
      3. Ensure proper invalidation after mutations
    - **Working with the Diff Viewer**:
      1. How diffData flows from git → DiffResult → DiffFile → Hunk → HunkLine
      2. How hunk staging generates patches
      3. How split view computes paired lines
    - **Debugging Tips**:
      - Clearing view cache (NativePHP path)
      - Port conflicts (8321)
      - Checking git command output (add logging to GitCommandRunner)

  **Must NOT do**:
  - Don't duplicate detailed API info from services.md
  - Don't write full implementations — show patterns and steps

  **Recommended Agent Profile**:
  - **Category**: `writing`
    - Reason: Practical cookbook requiring understanding of development workflow
  - **Skills**: [`livewire-development`]
    - `livewire-development`: Understanding component creation patterns

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 7, 8)
  - **Blocks**: Task 10
  - **Blocked By**: Tasks 2, 4

  **References** (CRITICAL):

  **Pattern References**:
  - `app/Services/Git/StagingService.php` — Example of a simple service (good template)
  - `app/Livewire/StagingPanel.php` — Example of component using services
  - `app/Livewire/CommandPalette.php:getCommands()` — Command registry
  - `resources/views/livewire/app-layout.blade.php:3-17` — Keyboard shortcut bindings
  - `app/DTOs/ChangedFile.php` — Example DTO
  - `app/Services/Git/GitCacheService.php` — Cache groups
  - `app/Services/Git/DiffService.php` — Patch generation pattern
  - `AGENTS.md` — Gotchas section for common issues

  **Acceptance Criteria**:
  - [x] File `docs/common-tasks.md` exists
  - [x] At least 8 cookbook recipes
  - [x] Each recipe has numbered steps
  - [x] File path references for each step
  - [x] Debugging tips section

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Cookbook has practical recipes
    Tool: Bash (grep)
    Preconditions: docs/common-tasks.md exists
    Steps:
      1. Count recipes: grep -c "### " docs/common-tasks.md (expect >= 8)
      2. Check numbered steps: grep -c "^[0-9]\." docs/common-tasks.md (expect >= 20)
      3. Check file references: grep -c "app/" docs/common-tasks.md (expect >= 10)
    Expected Result: 8+ recipes, 20+ steps, 10+ file references
    Failure Indicators: Too few recipes or steps
    Evidence: .sisyphus/evidence/task-9-cookbook-validation.txt
  ```

  **Commit**: YES
  - Message: `docs(common-tasks): add development cookbook with practical recipes`
  - Files: `docs/common-tasks.md`

---

- [x] 10. Master Index (`docs/README.md`)

  **What to do**:
  - Create the master navigation document:
    - **Title and Description**: "Gitty Developer Documentation"
    - **Quick Start**: 3-sentence summary of what gitty is and how it's built
    - **Documentation Map**: Organized links to all 9 doc files with one-line descriptions:
      - Architecture & Patterns
        - [Architecture Overview](architecture.md) — system layers, boot process, core patterns
      - API Reference
        - [Services](services.md) — all 20 services with public method signatures
        - [DTOs](dtos.md) — all 15 data transfer objects with property tables
        - [Components](components.md) — all 18 Livewire components
        - [Event System](events.md) — complete event map with flow diagrams
      - Frontend
        - [Frontend Architecture](frontend.md) — CSS, Alpine.js, Flux UI, NativePHP
      - Guides
        - [Features](features.md) — all features from a developer perspective
        - [Testing](testing.md) — test infrastructure, patterns, how to write tests
        - [Common Tasks](common-tasks.md) — cookbook for common development tasks
    - **Related Resources**: Link to AGENTS.md (design system), NativePHP docs, Flux UI docs
    - **Tech Stack Summary**: Table of technologies with versions

  **Must NOT do**:
  - Don't add content beyond navigation — keep it focused as an index
  - Don't add badges, shields, or marketing elements

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple index document linking to existing files
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (needs all other docs to exist)
  - **Parallel Group**: Wave 4 (solo)
  - **Blocks**: F1-F4
  - **Blocked By**: Tasks 7, 8, 9

  **References** (CRITICAL):

  **Pattern References**:
  - All docs created in Tasks 1-9
  - `AGENTS.md` — Link to it from Related Resources

  **Acceptance Criteria**:
  - [x] File `docs/README.md` exists
  - [x] Links to all 9 doc files
  - [x] All links resolve (no broken links)
  - [x] Quick start section present
  - [x] Tech stack summary table

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All links in index are valid
    Tool: Bash (grep + test)
    Preconditions: docs/README.md exists
    Steps:
      1. Extract all .md links: grep -oP '\(([a-z\-]+\.md)\)' docs/README.md | tr -d '()' | sort -u
      2. For each link, verify file exists in docs/: while read f; do test -f "docs/$f" || echo "BROKEN: $f"; done
    Expected Result: Zero "BROKEN" lines — all links resolve
    Failure Indicators: Any broken link
    Evidence: .sisyphus/evidence/task-10-index-validation.txt

  Scenario: Index links to all doc files
    Tool: Bash (find + diff)
    Preconditions: docs/README.md exists
    Steps:
      1. Find all .md files in docs/ except README: find docs/ -name "*.md" ! -name "README.md" -exec basename {} \; | sort
      2. Extract linked files from README: grep -oP '\(([a-z\-]+\.md)\)' docs/README.md | tr -d '()' | sort -u
      3. Compare: diff <(step1) <(step2)
    Expected Result: All doc files are linked from README
    Failure Indicators: Files exist but aren't linked
    Evidence: .sisyphus/evidence/task-10-completeness.txt
  ```

  **Commit**: YES
  - Message: `docs: add master index with navigation to all documentation`
  - Files: `docs/README.md`

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 4 review agents run in PARALLEL. ALL must APPROVE. Rejection → fix → re-run.

- [x] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each doc file listed in deliverables: verify it exists (test -f docs/X.md). For each "Must Have": verify implementation exists (grep for key content). For each "Must NOT Have": search docs for forbidden patterns (marketing copy, tutorial-style, duplicated AGENTS.md content). Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [x] F2. **Documentation Quality Review** — `unspecified-high`
  Read every doc file. Check for: consistent heading structure, table of contents presence, code examples with file paths, ASCII diagram readability (max 80 wide), no broken internal links, no placeholder text, no TODO markers left in docs, consistent tone (imperative mood, no first-person), grammar and spelling.
  Output: `Files [N clean/N issues] | Structure [consistent/inconsistent] | Links [N valid/N broken] | VERDICT`

- [x] F3. **Cross-Reference Validation** — `unspecified-high`
  Verify all cross-references between docs: every `[link](other.md)` resolves, every file path `app/X.php` exists in codebase, every event name matches actual dispatched events in code, every DTO property matches actual class definition. Run the validation scripts defined in QA scenarios.
  Output: `File Refs [N/N valid] | Event Names [N/N valid] | DTO Properties [N/N valid] | Links [N/N valid] | VERDICT`

- [x] F4. **Scope Fidelity Check** — `deep`
  For each task: read acceptance criteria, verify all met. Check: no code files were modified (only docs/*.md), no files created outside docs/, no duplicate content from AGENTS.md, all 10 doc files present, no marketing or opinion content, developer audience maintained throughout. Verify total doc count matches plan.
  Output: `Tasks [N/N compliant] | Scope [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `docs(architecture): add comprehensive architecture overview` | `docs/architecture.md` | File exists, 3+ diagrams |
| 2 | `docs(services): add complete service API reference` | `docs/services.md` | 20 services documented |
| 3 | `docs(dtos): add complete DTO reference with property tables` | `docs/dtos.md` | 15 DTOs documented |
| 4 | `docs(components): add complete Livewire component reference` | `docs/components.md` | 18 components documented |
| 5 | `docs(events): add complete event system map with flow diagrams` | `docs/events.md` | 30+ events mapped |
| 6 | `docs(frontend): add frontend architecture documentation` | `docs/frontend.md` | CSS/Alpine/Flux sections |
| 7 | `docs(features): add comprehensive feature documentation` | `docs/features.md` | 15+ features documented |
| 8 | `docs(testing): add testing guide with patterns and helpers` | `docs/testing.md` | Test patterns section |
| 9 | `docs(common-tasks): add development cookbook with practical recipes` | `docs/common-tasks.md` | 8+ recipes |
| 10 | `docs: add master index with navigation to all documentation` | `docs/README.md` | All links valid |

---

## Success Criteria

### Verification Commands
```bash
# All 10 doc files exist
ls docs/*.md | wc -l  # Expected: 10

# All doc files have content (not empty)
find docs/ -name "*.md" -empty  # Expected: no output

# No broken internal links
grep -rh '](.*\.md)' docs/ | grep -oP '\(([^)]+\.md)\)' | tr -d '()' | sort -u | while read f; do test -f "docs/$f" || echo "BROKEN: $f"; done  # Expected: no output

# All app/ file references exist
grep -roh 'app/[^ `)]*\.php' docs/ | sort -u | while read f; do test -f "$f" || echo "MISSING: $f"; done  # Expected: no output

# No code files modified
git diff --name-only | grep -v "^docs/"  # Expected: no output (only docs/ changes)
```

### Final Checklist
- [x] All 10 doc files exist in `docs/`
- [x] Master index links to all docs without broken links
- [x] All file path references point to existing files
- [x] All event names match actual events in code
- [x] No code files were modified
- [x] No content duplicated from AGENTS.md
- [x] Developer-focused tone maintained throughout
- [x] ASCII diagrams present and readable (max 80 chars wide)
