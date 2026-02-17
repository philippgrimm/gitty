# Codebase Future-Proofing & Maintainability Overhaul

## TL;DR

> **Quick Summary**: Systematic refactoring of gitty's service layer and Livewire components to eliminate massive code duplication (700+ lines of boilerplate), centralize git command execution for safety and testability, and introduce proper type safety — all without breaking existing functionality.
> 
> **Deliverables**:
> - `AbstractGitService` base class eliminating 11x duplicated constructor blocks
> - `GitCommandRunner` centralizing all Process calls with shell escaping and error checking
> - `HandlesGitOperations` Livewire trait reducing 20+ identical try/catch blocks to one-liners
> - `ChangedFile` DTO replacing untyped arrays in `GitStatus`
> - `AheadBehind` value object replacing raw arrays everywhere
> - DTO hydration helper eliminating 4x copy-pasted reconstruction blocks in DiffViewer
> - Shell injection fixes across 6+ vulnerable command locations
> - Fixed broken tests + new coverage for all new abstractions
> 
> **Estimated Effort**: Large (25+ tasks across 6 waves + verification)
> **Parallel Execution**: YES — 6 waves, 3-7 tasks per wave
> **Critical Path**: Wave 0 → Wave 1 → Waves 2+3 (parallel) → Wave 4 → Wave 5 → FINAL

---

## Context

### Original Request
User requested a "VERY VERY detailed analysis" of what can be improved to make the codebase "future proof and maintainable" without breaking anything.

### Interview Summary
**Key Discussions**:
- **Priority**: DRY/readability first, then architecture, then safety
- **Test Strategy**: TDD approach — write tests first, then implement
- **Future Features**: Just maintenance, no major features planned

**Research Findings** (from 5 parallel analysis agents):
- 11+ services duplicate identical 5-line constructor validation block
- 18 services have zero interfaces
- 20+ identical try/catch/error patterns in Livewire components
- 6+ shell injection vulnerabilities (low risk in desktop app but robustness concern)
- GitOperationQueue exists but is never used
- DiffViewer has 4x copy-pasted DTO reconstruction blocks (~120 lines)
- RemoteService::push() doesn't check exit code
- Test files have LSP errors ($testRepoPath undefined)
- All DTOs already use modern PHP (readonly, constructor promotion) ✅

### Metis Review
**Identified Gaps** (addressed):
- **Wave 0 needed**: Must establish test baseline before any refactoring → Added Wave 0
- **Shell injection severity**: In a desktop app, user IS the attacker. This is a robustness issue (commit message with `"` breaks command), not a security exploit → Keeping in Wave 4 (code quality)
- **DI nuance**: Services take `$repoPath` in constructor — not traditional singletons. Full DI not appropriate; base class + GitCommandRunner pattern is right approach → Reflected in Wave 1 design
- **GitOperationQueue**: Already inspected — it's a working mutex using Cache::lock. Usable but optional → Included as optional in Wave 4
- **NativePHP constraints**: Service providers work normally in NativePHP. No known issues → Confirmed safe
- **Scope boundaries per wave**: Added explicit MUST/MUST NOT guardrails per wave

---

## Work Objectives

### Core Objective
Eliminate systematic code duplication, centralize git process execution for safety and testability, and strengthen type safety — using TDD methodology to ensure zero regressions.

### Concrete Deliverables
1. `app/Services/Git/AbstractGitService.php` — base class
2. `app/Services/Git/GitCommandRunner.php` — process execution abstraction
3. `app/Livewire/Concerns/HandlesGitOperations.php` — Livewire trait
4. `app/DTOs/ChangedFile.php` — typed DTO for file status
5. `app/DTOs/AheadBehind.php` — value object
6. Refactored services extending `AbstractGitService`
7. Refactored Livewire components using trait
8. Shell-safe git commands across all services
9. Fixed test suite + new coverage

### Definition of Done
- [ ] `php artisan test --compact` → 0 failures (≥ baseline count)
- [ ] `vendor/bin/pint --test` → 0 style violations
- [ ] Zero `$testRepoPath` LSP errors in test files
- [ ] Zero duplicated constructor blocks across services
- [ ] All git commands use escapeshellarg() or equivalent
- [ ] DiffViewer has ≤1 DTO reconstruction pattern (down from 4)
- [ ] StagingPanel has ≤3 try/catch blocks (down from 11)

### Must Have
- All existing tests continue to pass
- Same error messages shown to user (behavior preservation)
- Same git command output parsing (exact same DTO structures)
- TDD workflow: test first → implement → verify

### Must NOT Have (Guardrails)
- ❌ Do NOT refactor User model (unused, out of scope)
- ❌ Do NOT touch Blade templates or CSS (only Livewire PHP logic)
- ❌ Do NOT change database schema or migrations
- ❌ Do NOT add new features or functionality
- ❌ Do NOT add logging, monitoring, or observability
- ❌ Do NOT optimize performance unless broken
- ❌ Do NOT refactor routes, middleware, or config files
- ❌ Do NOT redesign DTOs (only fix typing and add missing ones)
- ❌ Do NOT refactor test infrastructure (GitTestHelper works, leave it)
- ❌ Do NOT touch NativeAppServiceProvider
- ❌ Do NOT create documentation files unless explicitly asked

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: TDD (tests first, then implement)
- **Framework**: Pest 4
- **Each task**: RED (write failing test) → GREEN (implement) → REFACTOR

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| PHP Services | Bash (pest) | `php artisan test --compact --filter=TestName` |
| Livewire Components | Bash (pest) | `php artisan test --compact --filter=ComponentTest` |
| Code Style | Bash (pint) | `vendor/bin/pint --dirty --format agent` |
| Type Safety | Bash (phpstan) | Check with LSP diagnostics |
| Shell Safety | Bash (pest) | Test with malicious input fixtures |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 0 (Start Immediately — baseline):
├── Task 1: Establish test baseline [quick]
├── Task 2: Run Pint baseline [quick]
└── Task 3: Audit git command safety [quick]

Wave 1 (After Wave 0 — foundation abstractions):
├── Task 4: Create ChangedFile DTO + AheadBehind value object [quick]
├── Task 5: Create AbstractGitService base class [deep]
├── Task 6: Create GitCommandRunner [deep]
└── Task 7: Create Livewire HandlesGitOperations trait [deep]

Wave 2 (After Wave 1 — service layer DRY, ALL PARALLEL):
├── Task 8: Migrate GitService + StagingService [unspecified-high]
├── Task 9: Migrate BranchService + CommitService [unspecified-high]
├── Task 10: Migrate DiffService + SearchService [unspecified-high]
├── Task 11: Migrate RemoteService + StashService [unspecified-high]
├── Task 12: Migrate remaining 7 services [unspecified-high]
└── Task 13: Update GitStatus to use ChangedFile DTO [unspecified-high]

Wave 3 (After Wave 1 — Livewire DRY, PARALLEL with Wave 2):
├── Task 14: Refactor StagingPanel boilerplate [unspecified-high]
├── Task 15: Refactor DiffViewer DTO reconstruction [deep]
├── Task 16: Refactor SyncPanel boilerplate [unspecified-high]
└── Task 17: Move direct Process calls from Livewire into services [unspecified-high]

Wave 4 (After Waves 2+3 — safety + architecture):
├── Task 18: Fix shell injection across all services [deep]
├── Task 19: Add domain-specific exceptions [unspecified-high]
├── Task 20: Fix unchecked exit codes (RemoteService etc.) [unspecified-high]
└── Task 21: Register service factory in AppServiceProvider [unspecified-high]

Wave 5 (After Wave 4 — test completion):
├── Task 22: Fix broken test files ($testRepoPath etc.) [quick]
├── Task 23: Add edge case tests (empty repo, detached HEAD, unicode) [unspecified-high]
├── Task 24: Add shell injection test fixtures [unspecified-high]
└── Task 25: Clean up Unit test dir + verify coverage [quick]

Wave FINAL (After ALL — independent review, 4 parallel):
├── Task F1: Plan compliance audit [oracle]
├── Task F2: Code quality review [unspecified-high]
├── Task F3: Full test suite + manual QA [unspecified-high]
└── Task F4: Scope fidelity check [deep]

Critical Path: T1 → T5/T6 → T8-T12 → T18 → T22 → F1-F4
Parallel Speedup: ~60% faster than sequential
Max Concurrent: 6 (Waves 2 & 3 combined)
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1-3 | — | 4-7 | 0 |
| 4 | 1 | 13 | 1 |
| 5 | 1 | 8-12 | 1 |
| 6 | 1 | 8-12, 17, 18 | 1 |
| 7 | 1 | 14-16 | 1 |
| 8 | 5, 6 | 18 | 2 |
| 9 | 5, 6 | 18, 20 | 2 |
| 10 | 5, 6 | 18 | 2 |
| 11 | 5, 6 | 18, 20 | 2 |
| 12 | 5, 6 | 18 | 2 |
| 13 | 4 | 14 | 2 |
| 14 | 7, 13 | — | 3 |
| 15 | 7 | — | 3 |
| 16 | 7 | — | 3 |
| 17 | 6 | — | 3 |
| 18 | 6, 8-12 | 24 | 4 |
| 19 | 8-12 | — | 4 |
| 20 | 9, 11 | — | 4 |
| 21 | 5, 6 | — | 4 |
| 22 | — | 23 | 5 |
| 23 | 22 | — | 5 |
| 24 | 18 | — | 5 |
| 25 | 22-24 | — | 5 |
| F1-F4 | ALL | — | FINAL |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 0 | **3** | T1-T3 → `quick` |
| 1 | **4** | T4 → `quick`, T5-T7 → `deep` |
| 2 | **6** | T8-T12 → `unspecified-high`, T13 → `unspecified-high` |
| 3 | **4** | T14,T16,T17 → `unspecified-high`, T15 → `deep` |
| 4 | **4** | T18 → `deep`, T19-T21 → `unspecified-high` |
| 5 | **4** | T22,T25 → `quick`, T23-T24 → `unspecified-high` |
| FINAL | **4** | F1 → `oracle`, F2-F3 → `unspecified-high`, F4 → `deep` |

---

## TODOs

---

### WAVE 0: BASELINE VERIFICATION

---

- [x] 1. Establish Test Baseline

  **What to do**:
  - Run `php artisan test --compact` and capture the full output
  - Document: total tests, passed, failed, skipped
  - Run `vendor/bin/pint --test --format agent` and capture output
  - Save both outputs as evidence — this is the baseline all future waves must match or exceed
  - If any tests fail, document them but do NOT fix them (that's a separate concern)

  **Must NOT do**:
  - Do NOT fix any failing tests (just document them)
  - Do NOT modify any code

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Need to run Pest tests correctly

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 0 (with Tasks 2, 3)
  - **Blocks**: All subsequent waves
  - **Blocked By**: None

  **References**:
  - `tests/Pest.php` — Pest configuration, extends TestCase in Feature and Browser dirs
  - `tests/TestCase.php` — Base test class (empty, just extends Laravel's)
  - `phpunit.xml` — PHPUnit/Pest configuration

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Run full test suite and capture baseline
    Tool: Bash
    Preconditions: Application is set up with dependencies installed
    Steps:
      1. Run `php artisan test --compact` — capture output
      2. Run `vendor/bin/pint --test --format agent` — capture output
      3. Save both outputs to evidence files
    Expected Result: Both commands complete (tests may have some failures — that's OK, we're documenting baseline)
    Evidence: .sisyphus/evidence/task-1-test-baseline.txt

  Scenario: Parse baseline metrics
    Tool: Bash
    Preconditions: Test output captured
    Steps:
      1. Extract total test count from output
      2. Extract pass/fail/skip counts
      3. Document any pre-existing failures
    Expected Result: Clear numeric baseline documented
    Evidence: .sisyphus/evidence/task-1-baseline-metrics.txt
  ```

  **Commit**: YES
  - Message: `chore(tests): document test baseline before refactoring`
  - Files: `.sisyphus/evidence/task-1-*`

---

- [x] 2. Run Pint Code Style Baseline

  **What to do**:
  - Run `vendor/bin/pint --format agent` (NOT --test, actually fix any existing issues)
  - This ensures we start from a clean style baseline
  - Commit any Pint fixes separately so they don't mix with refactoring

  **Must NOT do**:
  - Do NOT manually change any files
  - Do NOT change Pint configuration

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 0 (with Tasks 1, 3)
  - **Blocks**: All subsequent waves
  - **Blocked By**: None

  **References**:
  - `pint.json` or default Laravel Pint config — code style rules
  - `vendor/bin/pint --format agent` — auto-fix with agent-friendly output

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Run Pint and fix style issues
    Tool: Bash
    Preconditions: None
    Steps:
      1. Run `vendor/bin/pint --format agent`
      2. Run `vendor/bin/pint --test --format agent` to verify clean
    Expected Result: `vendor/bin/pint --test --format agent` shows 0 issues
    Evidence: .sisyphus/evidence/task-2-pint-baseline.txt
  ```

  **Commit**: YES (only if Pint made changes)
  - Message: `style(backend): apply Pint formatting baseline`
  - Files: Any files Pint modified
  - Pre-commit: `vendor/bin/pint --test --format agent`

---

- [x] 3. Audit Git Command Safety

  **What to do**:
  - Grep all `Process::path` calls across `app/Services/Git/*.php` and `app/Livewire/*.php`
  - For each call, document: (a) the command string, (b) whether user input is interpolated, (c) whether `escapeshellarg()` is used
  - Create a prioritized list of unsafe commands
  - Note: This is a desktop app — the "attacker" is the user themselves. Shell injection is a **robustness issue** (commit messages with quotes break commands), not a remote exploitation vector.

  **Must NOT do**:
  - Do NOT fix any commands yet (that's Wave 4)
  - Do NOT modify any code

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 0 (with Tasks 1, 2)
  - **Blocks**: Task 18 (shell injection fixes)
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/StagingService.php:25` — `"git add {$file}"` (no escape)
  - `app/Services/Git/StagingService.php:65-77` — `stageFiles()` uses `escapeshellarg()` (good example)
  - `app/Services/Git/CommitService.php:26` — `"git commit -m \"{$message}\""` (quotes in message break this)
  - `app/Services/Git/BranchService.php:43` — `"git checkout {$name}"` (no escape)
  - `app/Services/Git/SearchService.php:31` — `"git log --grep=\"{$query}\""` (no escape)
  - `app/Livewire/SyncPanel.php:79` — Direct Process call bypassing service layer

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Find all Process calls and classify safety
    Tool: Bash (grep)
    Preconditions: None
    Steps:
      1. Grep for `Process::path` in app/Services/Git/*.php — list all occurrences
      2. Grep for `Process::path` in app/Livewire/*.php — list all occurrences
      3. For each, note if user-provided input is interpolated without escaping
      4. Save audit to evidence file
    Expected Result: Complete inventory of all Process calls with safety classification
    Evidence: .sisyphus/evidence/task-3-command-safety-audit.txt
  ```

  **Commit**: NO (documentation only, saved as evidence)

---

### WAVE 1: FOUNDATION ABSTRACTIONS

---

- [x] 4. Create ChangedFile DTO and AheadBehind Value Object

  **What to do**:
  - **TDD: Write tests first** for `ChangedFile` and `AheadBehind`:
    - Test `ChangedFile` construction with all status types (modified, added, deleted, renamed, untracked, unmerged)
    - Test `AheadBehind` construction and accessor methods
  - **Then implement**:
    - Create `app/DTOs/ChangedFile.php` — readonly class with typed properties: `string $path`, `?string $oldPath`, `string $indexStatus`, `string $worktreeStatus`
    - Add helper methods: `isStaged(): bool`, `isUnstaged(): bool`, `isUntracked(): bool`, `isUnmerged(): bool`, `statusLabel(): string`
    - Create `app/DTOs/AheadBehind.php` — readonly class with `int $ahead`, `int $behind`, `bool $isUpToDate()`, `bool $hasDiverged()`
  - Do NOT update `GitStatus` yet (that's Task 13) — just create the DTOs

  **Must NOT do**:
  - Do NOT modify existing DTOs
  - Do NOT change GitStatus.php yet
  - Do NOT add any behavior beyond simple accessors

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Writing Pest tests for DTOs

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 5, 6, 7)
  - **Blocks**: Task 13
  - **Blocked By**: Wave 0

  **References**:

  **Pattern References**:
  - `app/DTOs/Commit.php` — Example of readonly DTO with constructor promotion and factory method. Follow this pattern exactly.
  - `app/DTOs/Branch.php` — Another clean readonly DTO example
  - `app/DTOs/GitStatus.php:40-45` — Current untyped array structure that `ChangedFile` will replace: `['indexStatus' => ..., 'worktreeStatus' => ..., 'path' => ..., 'oldPath' => ...]`

  **Test References**:
  - `tests/Mocks/GitOutputFixtures.php:20-58` — Git status output fixtures showing all status types (modified, staged, untracked, deleted, renamed, conflicted)

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=ChangedFileTest` → PASS
  - [ ] `php artisan test --compact --filter=AheadBehindTest` → PASS
  - [ ] Both classes use `readonly class` with constructor promotion
  - [ ] Both have `declare(strict_types=1)`
  - [ ] No use of `mixed` type

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: ChangedFile DTO works for all status types
    Tool: Bash (pest)
    Preconditions: Test and DTO files created
    Steps:
      1. Run `php artisan test --compact --filter=ChangedFileTest`
      2. Verify tests cover: modified, added, deleted, renamed, untracked, unmerged
    Expected Result: All tests pass, covering all status types
    Evidence: .sisyphus/evidence/task-4-changed-file-tests.txt

  Scenario: AheadBehind value object
    Tool: Bash (pest)
    Preconditions: Test and DTO files created
    Steps:
      1. Run `php artisan test --compact --filter=AheadBehindTest`
      2. Verify isUpToDate() returns true when ahead=0, behind=0
    Expected Result: All tests pass
    Evidence: .sisyphus/evidence/task-4-ahead-behind-tests.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): add ChangedFile DTO and AheadBehind value object`
  - Files: `app/DTOs/ChangedFile.php`, `app/DTOs/AheadBehind.php`, `tests/Unit/DTOs/ChangedFileTest.php`, `tests/Unit/DTOs/AheadBehindTest.php`
  - Pre-commit: `php artisan test --compact --filter=ChangedFile && php artisan test --compact --filter=AheadBehind`

---

- [x] 5. Create AbstractGitService Base Class

  **What to do**:
  - **TDD: Write tests first**:
    - Test that AbstractGitService validates repo path correctly
    - Test that it throws `InvalidArgumentException` for non-git directories
    - Test that it provides access to `GitCacheService`
    - Test that it provides access to `GitCommandRunner` (from Task 6)
  - **Then implement**:
    - Create `app/Services/Git/AbstractGitService.php`
    - Move the duplicated constructor logic here:
      ```php
      abstract class AbstractGitService {
          protected GitCacheService $cache;
          protected GitCommandRunner $commandRunner;
          
          public function __construct(protected string $repoPath) {
              $gitDir = rtrim($this->repoPath, '/').'/.git';
              if (! is_dir($gitDir)) {
                  throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
              }
              $this->cache = new GitCacheService;
              $this->commandRunner = new GitCommandRunner($this->repoPath);
          }
      }
      ```
    - Do NOT migrate existing services yet (that's Wave 2)

  **Must NOT do**:
  - Do NOT modify any existing service files
  - Do NOT add business logic to base class
  - Do NOT add abstract methods (keep it simple)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Writing thorough tests for base class

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 4, 6, 7)
  - **Blocks**: Tasks 8-12 (all service migrations)
  - **Blocked By**: Wave 0

  **References**:

  **Pattern References**:
  - `app/Services/Git/GitService.php:17-25` — Constructor pattern to extract (validation + cache instantiation)
  - `app/Services/Git/StagingService.php:13-21` — Same pattern duplicated
  - `app/Services/Git/BranchService.php:16-24` — Same pattern duplicated
  - `app/Services/Git/CommitService.php:14-22` — Same pattern duplicated

  **WHY Each Reference Matters**:
  - These 4 files show the exact same constructor code that will be extracted. The base class must reproduce this behavior exactly to ensure backward compatibility.

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=AbstractGitServiceTest` → PASS
  - [ ] Class validates .git directory existence
  - [ ] Class provides `$this->cache` (GitCacheService instance)
  - [ ] Class provides `$this->commandRunner` (GitCommandRunner instance)
  - [ ] Existing tests still pass: `php artisan test --compact`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: AbstractGitService validates repo path
    Tool: Bash (pest)
    Preconditions: Test file and base class created
    Steps:
      1. Run `php artisan test --compact --filter=AbstractGitServiceTest`
      2. Verify test covers: valid repo path, invalid path throws exception
    Expected Result: All tests pass
    Evidence: .sisyphus/evidence/task-5-abstract-service-tests.txt

  Scenario: No regressions in existing tests
    Tool: Bash (pest)
    Preconditions: AbstractGitService created (no services migrated yet)
    Steps:
      1. Run `php artisan test --compact`
    Expected Result: Same pass count as baseline (Task 1)
    Evidence: .sisyphus/evidence/task-5-regression-check.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): create AbstractGitService base class`
  - Files: `app/Services/Git/AbstractGitService.php`, `tests/Unit/Services/AbstractGitServiceTest.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 6. Create GitCommandRunner

  **What to do**:
  - **TDD: Write tests first**:
    - Test `run()` method executes git command via Process facade
    - Test that arguments are properly escaped with `escapeshellarg()`
    - Test that `runOrFail()` throws exception on non-zero exit code
    - Test that `runWithInput()` handles stdin piping (for `git apply`)
    - Test error output is captured and included in exceptions
    - Test Process facade can be mocked (this is the key testability win)
  - **Then implement**:
    - Create `app/Services/Git/GitCommandRunner.php`
    - Methods:
      - `run(string $command, array $args = []): ProcessResult` — run with escaped args
      - `runOrFail(string $command, array $args = [], string $errorPrefix = ''): ProcessResult` — run + throw on failure
      - `runWithInput(string $command, string $input): ProcessResult` — for `git apply`
    - All methods use `Process::path($this->repoPath)` internally
    - Arguments array items are each passed through `escapeshellarg()`
    - Build command string safely: `"git {$subcommand} " . implode(' ', array_map('escapeshellarg', $args))`

  **Must NOT do**:
  - Do NOT modify existing services to use this yet (Wave 2)
  - Do NOT add caching logic (that stays in services)
  - Do NOT add logging (out of scope)
  - Do NOT overcomplicate — keep it a thin wrapper

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Writing Process facade mock tests

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 4, 5, 7)
  - **Blocks**: Tasks 8-12, 17, 18
  - **Blocked By**: Wave 0

  **References**:

  **Pattern References**:
  - `app/Services/Git/StagingService.php:65-77` — `stageFiles()` method showing the CORRECT pattern with `escapeshellarg()` — this is what GitCommandRunner should generalize
  - `app/Services/Git/CommitService.php:26` — `"git commit -m \"{$message}\""` — UNSAFE pattern that GitCommandRunner must prevent
  - `app/Services/Git/DiffService.php:37-38` — `Process::path()->input($patch)->run()` — pattern for runWithInput

  **External References**:
  - Laravel Process facade docs: `Process::path()->run()` returns `ProcessResult` with `output()`, `errorOutput()`, `exitCode()`, `successful()`

  **WHY Each Reference Matters**:
  - StagingService:65-77 shows the safe pattern to generalize
  - CommitService:26 shows the unsafe pattern to prevent
  - DiffService:37-38 shows stdin piping needed for `git apply` (stageHunk/unstageHunk)

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=GitCommandRunnerTest` → PASS
  - [ ] All arguments escaped with `escapeshellarg()`
  - [ ] `runOrFail()` throws exception with error output on failure
  - [ ] `runWithInput()` pipes stdin correctly
  - [ ] Process facade is mockable in tests
  - [ ] Existing tests still pass: `php artisan test --compact`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: GitCommandRunner escapes arguments safely
    Tool: Bash (pest)
    Preconditions: GitCommandRunner and tests created
    Steps:
      1. Run `php artisan test --compact --filter=GitCommandRunnerTest`
      2. Verify tests cover: basic run, escaped args, failed command throws, stdin input
    Expected Result: All tests pass, arguments are escaped
    Evidence: .sisyphus/evidence/task-6-command-runner-tests.txt

  Scenario: Process facade is mockable
    Tool: Bash (pest)
    Preconditions: Tests use Process::fake()
    Steps:
      1. Verify test file uses Process::fake() to mock git commands
      2. Run tests to confirm mock works
    Expected Result: Tests pass without executing real git commands
    Evidence: .sisyphus/evidence/task-6-mock-verification.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): add GitCommandRunner with shell escaping`
  - Files: `app/Services/Git/GitCommandRunner.php`, `tests/Unit/Services/GitCommandRunnerTest.php`
  - Pre-commit: `php artisan test --compact`

---

- [x] 7. Create Livewire HandlesGitOperations Trait

  **What to do**:
  - **TDD: Write tests first**:
    - Test that `executeGitOperation()` calls the callback and dispatches refresh events on success
    - Test that it catches exceptions and dispatches `show-error` with translated message
    - Test that it sets `$this->error` property on failure
    - Test that it optionally dispatches `status-updated` event
  - **Then implement**:
    - Create `app/Livewire/Concerns/HandlesGitOperations.php` — a Livewire-compatible trait
    - Core method:
      ```php
      protected function executeGitOperation(callable $operation, bool $dispatchStatusUpdate = true): mixed {
          try {
              $result = $operation();
              $this->error = '';
              if ($dispatchStatusUpdate) {
                  $this->dispatch('status-updated', ...);
              }
              return $result;
          } catch (\Exception $e) {
              $this->error = GitErrorHandler::translate($e->getMessage());
              $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
              return null;
          }
      }
      ```
    - This replaces the 15-line try/catch block repeated 20+ times across StagingPanel, DiffViewer, SyncPanel, BranchManager
    - Do NOT modify existing Livewire components yet (Wave 3)

  **Must NOT do**:
  - Do NOT modify any existing Livewire component
  - Do NOT add business logic to the trait
  - Do NOT change the error message format (must produce identical user-facing messages)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`livewire-development`, `pest-testing`]
    - `livewire-development`: Understanding Livewire traits and dispatch
    - `pest-testing`: Writing Livewire component tests

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 4, 5, 6)
  - **Blocks**: Tasks 14-16 (Livewire refactoring)
  - **Blocked By**: Wave 0

  **References**:

  **Pattern References**:
  - `app/Livewire/StagingPanel.php:86-101` — `stageFile()` method showing the EXACT try/catch/dispatch pattern this trait must replicate
  - `app/Livewire/StagingPanel.php:121-136` — `stageAll()` — same pattern, proves it's duplicated
  - `app/Livewire/SyncPanel.php:63-107` — `syncPush()` — slightly different variant (sets `isOperationRunning`)
  - `app/Livewire/BranchManager.php:70-89` — `switchBranch()` — includes special handling for dirty tree errors
  - `app/Services/Git/GitErrorHandler.php:12-61` — `translate()` method the trait must call

  **WHY Each Reference Matters**:
  - StagingPanel:86-101 is the canonical pattern to extract — it shows the exact error handling + dispatch sequence
  - SyncPanel:63-107 shows a variant with `isOperationRunning` flag — trait must accommodate this
  - BranchManager:70-89 shows a variant with special error handling — trait must allow post-catch hooks
  - GitErrorHandler::translate() must be called for all errors (behavior preservation)

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=HandlesGitOperationsTest` → PASS
  - [ ] Trait method produces identical error dispatch as existing pattern
  - [ ] Trait handles both simple operations and operations needing status-updated dispatch
  - [ ] Existing tests still pass: `php artisan test --compact`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Trait executes operation successfully
    Tool: Bash (pest)
    Preconditions: Trait and test component created
    Steps:
      1. Run `php artisan test --compact --filter=HandlesGitOperationsTest`
      2. Verify success path: callback executed, error cleared, events dispatched
    Expected Result: All success-path tests pass
    Evidence: .sisyphus/evidence/task-7-trait-success-tests.txt

  Scenario: Trait handles operation failure
    Tool: Bash (pest)
    Preconditions: Tests include failure scenarios
    Steps:
      1. Run tests with callback that throws RuntimeException
      2. Verify: error translated, show-error dispatched, error property set
    Expected Result: All failure-path tests pass with correct error messages
    Evidence: .sisyphus/evidence/task-7-trait-failure-tests.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): add HandlesGitOperations Livewire trait`
  - Files: `app/Livewire/Concerns/HandlesGitOperations.php`, `tests/Feature/Livewire/Concerns/HandlesGitOperationsTest.php`
  - Pre-commit: `php artisan test --compact`

---

### WAVE 2: SERVICE LAYER DRY (ALL PARALLEL)

---

- [x] 8. Migrate GitService + StagingService to AbstractGitService

  **What to do**:
  - **TDD**: Run existing tests first to confirm they pass. Then refactor.
  - Modify `GitService` to extend `AbstractGitService`:
    - Remove duplicated constructor (validation + cache instantiation)
    - Replace `Process::path($this->repoPath)->run(...)` calls with `$this->commandRunner->run(...)` or `$this->commandRunner->runOrFail(...)`
    - Keep all public method signatures identical
  - Modify `StagingService` identically
  - Run existing tests after each file change

  **Must NOT do**:
  - Do NOT change any public method signatures
  - Do NOT change return types
  - Do NOT change behavior (same git commands, same caching logic)
  - Do NOT rename classes

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 9-13)
  - **Blocks**: Task 18
  - **Blocked By**: Tasks 5, 6

  **References**:
  - `app/Services/Git/GitService.php` — 144 lines, 6 public methods, current constructor at lines 17-25
  - `app/Services/Git/StagingService.php` — 107 lines, 9 public methods, constructor at lines 13-21
  - `tests/Feature/Services/GitServiceTest.php` — Existing tests to verify no regressions
  - `tests/Feature/Services/StagingServiceTest.php` — Existing tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=GitServiceTest` → PASS (same count as baseline)
  - [ ] `php artisan test --compact --filter=StagingServiceTest` → PASS (same count)
  - [ ] Both classes `extends AbstractGitService`
  - [ ] Zero duplicated constructor code
  - [ ] `Process::path()` calls replaced with `$this->commandRunner`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: GitService works identically after migration
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=GitServiceTest`
    Expected Result: All existing tests pass with zero changes
    Evidence: .sisyphus/evidence/task-8-git-service-tests.txt

  Scenario: StagingService works identically after migration
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=StagingServiceTest`
    Expected Result: All existing tests pass
    Evidence: .sisyphus/evidence/task-8-staging-service-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): migrate GitService + StagingService to AbstractGitService`
  - Files: `app/Services/Git/GitService.php`, `app/Services/Git/StagingService.php`
  - Pre-commit: `php artisan test --compact --filter=GitServiceTest && php artisan test --compact --filter=StagingServiceTest`

---

- [x] 9. Migrate BranchService + CommitService to AbstractGitService

  **What to do**:
  - Same approach as Task 8:
    - Extend `AbstractGitService`, remove constructor, use `$this->commandRunner`
    - Fix `CommitService::isLastCommitPushed()` (line 78) which does `new GitService($this->repoPath)` — refactor to accept GitService as parameter or use a different approach
  - Run existing tests after each change

  **Must NOT do**:
  - Do NOT change public method signatures
  - Do NOT change behavior

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 8, 10-13)
  - **Blocks**: Tasks 18, 20
  - **Blocked By**: Tasks 5, 6

  **References**:
  - `app/Services/Git/BranchService.php` — 90 lines, 5 public methods
  - `app/Services/Git/CommitService.php` — 133 lines, 9 public methods
  - `app/Services/Git/CommitService.php:78` — `new GitService($this->repoPath)` — internal service creation to address
  - `tests/Feature/Services/BranchServiceTest.php` — Existing tests
  - `tests/Feature/Services/CommitServiceTest.php` — Existing tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=BranchServiceTest` → PASS
  - [ ] `php artisan test --compact --filter=CommitServiceTest` → PASS
  - [ ] No `new GitService()` inside CommitService
  - [ ] Both extend `AbstractGitService`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: BranchService + CommitService work after migration
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=BranchServiceTest`
      2. Run `php artisan test --compact --filter=CommitServiceTest`
    Expected Result: All existing tests pass
    Evidence: .sisyphus/evidence/task-9-branch-commit-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): migrate BranchService + CommitService to AbstractGitService`
  - Files: `app/Services/Git/BranchService.php`, `app/Services/Git/CommitService.php`
  - Pre-commit: `php artisan test --compact --filter=BranchServiceTest && php artisan test --compact --filter=CommitServiceTest`

---

- [ ] 10. Migrate DiffService + SearchService to AbstractGitService

  **What to do**:
  - Same approach. Note: DiffService doesn't use cache (no `$this->cache`), but AbstractGitService provides it optionally — just don't use it.
  - DiffService uses `Process::path()->input()` for stdin — use `$this->commandRunner->runWithInput()`

  **Must NOT do**:
  - Do NOT change DiffResult or DiffFile parsing
  - Do NOT change public method signatures

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2

  **References**:
  - `app/Services/Git/DiffService.php` — 141 lines, uses `Process::input()` for `git apply`
  - `app/Services/Git/SearchService.php` — 109 lines, simple Process calls
  - `tests/Feature/Services/DiffServiceTest.php`, `tests/Feature/Services/SearchServiceTest.php`

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=DiffServiceTest` → PASS
  - [ ] `php artisan test --compact --filter=SearchServiceTest` → PASS

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: DiffService + SearchService work after migration
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=DiffServiceTest`
      2. Run `php artisan test --compact --filter=SearchServiceTest`
    Expected Result: All tests pass
    Evidence: .sisyphus/evidence/task-10-diff-search-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): migrate DiffService + SearchService to AbstractGitService`
  - Files: `app/Services/Git/DiffService.php`, `app/Services/Git/SearchService.php`

---

- [ ] 11. Migrate RemoteService + StashService to AbstractGitService

  **What to do**:
  - Same approach. Note: Fix `RemoteService::push()` (line 44) which does NOT check exit code — use `$this->commandRunner->runOrFail()` instead.

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2

  **References**:
  - `app/Services/Git/RemoteService.php:44` — `Process::path($this->repoPath)->run("git push {$remote} {$branch}")` — NO exit code check!
  - `app/Services/Git/StashService.php` — 108 lines, standard pattern
  - `tests/Feature/Services/RemoteServiceTest.php`, `tests/Feature/Services/StashServiceTest.php`

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=RemoteServiceTest` → PASS
  - [ ] `php artisan test --compact --filter=StashServiceTest` → PASS
  - [ ] `RemoteService::push()` now checks exit code via `runOrFail()`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: RemoteService + StashService work after migration
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=RemoteServiceTest`
      2. Run `php artisan test --compact --filter=StashServiceTest`
    Expected Result: All tests pass, push() now checks exit code
    Evidence: .sisyphus/evidence/task-11-remote-stash-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): migrate RemoteService + StashService to AbstractGitService`

---

- [ ] 12. Migrate Remaining 7 Services to AbstractGitService

  **What to do**:
  - Migrate: `BlameService`, `GraphService`, `RebaseService`, `ResetService`, `TagService`, `GitConfigValidator`, `GitCacheService` (if applicable)
  - Note: `GitCacheService` and `GitErrorHandler` are utility classes, not repo-bound — they should NOT extend AbstractGitService. Leave them as-is.
  - `GitConfigValidator` has a static method `checkGitBinary()` — keep it static, but migrate the instance methods to use base class
  - `GitOperationQueue` takes `$repoPath` in constructor but is NOT a git command service — leave as-is

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2

  **References**:
  - `app/Services/Git/BlameService.php`, `app/Services/Git/GraphService.php`, `app/Services/Git/RebaseService.php`, `app/Services/Git/ResetService.php`, `app/Services/Git/TagService.php`
  - `app/Services/Git/GitConfigValidator.php` — has static method, special handling needed
  - `tests/Feature/Services/BlameServiceTest.php` through `tests/Feature/Services/TagServiceTest.php`

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → All tests pass (full suite)
  - [ ] All 5 services extend `AbstractGitService`
  - [ ] `GitConfigValidator` migrated with static method preserved
  - [ ] `GitCacheService`, `GitErrorHandler`, `GitOperationQueue` left untouched

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All remaining services work after migration
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact` (full suite)
    Expected Result: All tests pass, same count as baseline
    Evidence: .sisyphus/evidence/task-12-remaining-services-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): migrate remaining services to AbstractGitService`

---

- [ ] 13. Update GitStatus to Use ChangedFile DTO

  **What to do**:
  - **TDD**: Update existing `GitStatus` tests to expect `ChangedFile` objects instead of arrays
  - Modify `GitStatus::fromOutput()` to return `Collection<ChangedFile>` instead of `Collection<array>`
  - Change `public Collection $changedFiles` type hint to be clear it contains `ChangedFile` objects
  - Replace `array $aheadBehind` with `AheadBehind $aheadBehind`
  - Update ALL consumers of `$status->changedFiles` and `$status->aheadBehind`:
    - `StagingPanel::refreshStatus()` — update file iteration
    - `CommitPanel::mount()` — update staged count filter
    - Any other consumers

  **Must NOT do**:
  - Do NOT change the git output parsing logic (only the output types)
  - Do NOT change any blade templates

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2

  **References**:
  - `app/DTOs/GitStatus.php:18,40-45` — Current constructor + fromOutput parsing
  - `app/Livewire/StagingPanel.php:61-76` — Consumer: iterates changedFiles, accesses `$file['indexStatus']`, `$file['worktreeStatus']`, `$file['path']`
  - `app/Livewire/CommitPanel.php:49-51` — Consumer: filters on `$file['indexStatus']`
  - `tests/Feature/Services/GitServiceTest.php` — Tests that parse git status output

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=GitServiceTest` → PASS
  - [ ] `php artisan test --compact --filter=StagingPanelTest` → PASS
  - [ ] `php artisan test --compact --filter=CommitPanelTest` → PASS
  - [ ] `GitStatus::$changedFiles` contains `ChangedFile` objects, not arrays
  - [ ] `GitStatus::$aheadBehind` is `AheadBehind` type, not `array`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: GitStatus now uses typed DTOs
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact`
      2. Verify all consumers of GitStatus work with new types
    Expected Result: Full test suite passes
    Evidence: .sisyphus/evidence/task-13-gitstatus-typed.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): type-safe ChangedFile and AheadBehind in GitStatus`
  - Files: `app/DTOs/GitStatus.php`, `app/Livewire/StagingPanel.php`, `app/Livewire/CommitPanel.php`, related tests

---

### WAVE 3: LIVEWIRE DRY (PARALLEL WITH WAVE 2)

---

- [ ] 14. Refactor StagingPanel Boilerplate

  **What to do**:
  - Add `use HandlesGitOperations` trait to `StagingPanel`
  - Replace all 11 identical try/catch blocks with `$this->executeGitOperation(fn () => ...)`:
    - `stageFile()`, `unstageFile()`, `stageAll()`, `unstageAll()`, `discardFile()`, `discardAll()`, `stageSelected()`, `unstageSelected()`, `discardSelected()`, `stashSelected()`, `stashAll()`
  - Each method should go from ~15 lines to ~5 lines
  - Run existing StagingPanel tests after each method change

  **Must NOT do**:
  - Do NOT change the behavior of any method
  - Do NOT remove `refreshStatus()` calls (ensure trait handles this)
  - Do NOT modify blade template
  - Do NOT change event dispatch parameters

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 15-17)
  - **Blocked By**: Tasks 7, 13

  **References**:
  - `app/Livewire/StagingPanel.php:86-296` — All 11 methods with duplicated try/catch
  - `app/Livewire/Concerns/HandlesGitOperations.php` — Trait from Task 7
  - `tests/Feature/Livewire/StagingPanelTest.php` — 24 tests covering all staging operations

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=StagingPanelTest` → PASS (all 24 tests)
  - [ ] StagingPanel has `use HandlesGitOperations` 
  - [ ] ≤3 try/catch blocks remain (down from 11)
  - [ ] File line count reduced by 100+ lines

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: StagingPanel works identically after trait refactor
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=StagingPanelTest`
    Expected Result: All existing tests pass with zero behavior changes
    Evidence: .sisyphus/evidence/task-14-staging-panel-tests.txt

  Scenario: Error handling preserved
    Tool: Bash (pest)
    Steps:
      1. Verify tests that trigger errors still get `show-error` dispatch
      2. Verify error messages are unchanged
    Expected Result: Error behavior identical to before
    Evidence: .sisyphus/evidence/task-14-error-handling.txt
  ```

  **Commit**: YES
  - Message: `refactor(staging): eliminate 11x duplicated try/catch with HandlesGitOperations trait`
  - Files: `app/Livewire/StagingPanel.php`
  - Pre-commit: `php artisan test --compact --filter=StagingPanelTest`

---

- [ ] 15. Refactor DiffViewer DTO Reconstruction

  **What to do**:
  - Extract a helper method `hydrateDiffFileAndHunk(int $fileIndex, int $hunkIndex): array` that reconstructs `DiffFile` and `Hunk` objects from the stored array data
  - This code is copy-pasted in `stageHunk()`, `unstageHunk()`, `stageSelectedLines()`, `unstageSelectedLines()` — each has ~30 identical lines
  - Replace 4x copy-pasted blocks with calls to the helper
  - Also add `use HandlesGitOperations` for the try/catch pattern
  - Run existing DiffViewer tests after each change

  **Must NOT do**:
  - Do NOT change the DTO classes themselves
  - Do NOT change the serialization format (how hunks/lines are stored as arrays)
  - Do NOT modify the blade template

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`livewire-development`, `pest-testing`]
    - Using `deep` because the DTO hydration logic is complex and must be extracted precisely

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 14, 16, 17)
  - **Blocked By**: Task 7

  **References**:
  - `app/Livewire/DiffViewer.php:191-238` — `stageHunk()` — first instance of DTO reconstruction
  - `app/Livewire/DiffViewer.php:240-287` — `unstageHunk()` — identical copy
  - `app/Livewire/DiffViewer.php:289-336` — `stageSelectedLines()` — identical copy
  - `app/Livewire/DiffViewer.php:338-385` — `unstageSelectedLines()` — identical copy
  - `app/DTOs/DiffFile.php`, `app/DTOs/Hunk.php`, `app/DTOs/HunkLine.php` — DTOs being reconstructed
  - `tests/Feature/Livewire/DiffViewerTest.php` — Existing tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=DiffViewerTest` → PASS
  - [ ] Only 1 DTO reconstruction method (down from 4 copies)
  - [ ] File line count reduced by ~90 lines
  - [ ] stageHunk, unstageHunk, stageSelectedLines, unstageSelectedLines all use shared helper

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: DiffViewer hunk staging still works
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=DiffViewerTest`
    Expected Result: All existing tests pass
    Evidence: .sisyphus/evidence/task-15-diff-viewer-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(panels): extract DiffViewer DTO hydration helper, eliminate 4x duplication`
  - Files: `app/Livewire/DiffViewer.php`

---

- [ ] 16. Refactor SyncPanel Boilerplate

  **What to do**:
  - Add `use HandlesGitOperations` to SyncPanel
  - Refactor 5 methods: `syncPush()`, `syncPull()`, `syncFetch()`, `syncFetchAll()`, `syncForcePushWithLease()`
  - These share: set running flag → try/catch → error handling → refresh ahead/behind → dispatch
  - Consider a shared `executeSyncOperation(string $command, string $operationName)` helper within the component
  - Note: SyncPanel has an `isOperationRunning` flag — trait should accommodate this

  **Must NOT do**:
  - Do NOT change notification dispatch (push/pull send native notifications via NotificationService)
  - Do NOT change the git commands themselves

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3

  **References**:
  - `app/Livewire/SyncPanel.php:63-249` — 5 methods with identical structure
  - `app/Services/NotificationService.php` — Used by push/pull for native notifications
  - `tests/Feature/Livewire/SyncPanelTest.php` — Existing tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=SyncPanelTest` → PASS
  - [ ] ≤2 try/catch blocks remain (down from 5)
  - [ ] `isOperationRunning` flag still works correctly

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: SyncPanel works after refactor
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=SyncPanelTest`
    Expected Result: All tests pass
    Evidence: .sisyphus/evidence/task-16-sync-panel-tests.txt
  ```

  **Commit**: YES
  - Message: `refactor(panels): eliminate SyncPanel boilerplate with shared operation pattern`

---

- [ ] 17. Move Direct Process Calls from Livewire into Services

  **What to do**:
  - Find ALL direct `Process::path()` calls in Livewire components and move them into appropriate services
  - Known locations:
    - `StagingPanel.php:300` — `Process::path()->run('git rev-parse --abbrev-ref HEAD')` → Should use `GitService::currentBranch()`
    - `SyncPanel.php:79,125,160,189,228` — Direct `git push/pull/fetch` calls → Should use `RemoteService`
    - `BranchManager.php:167` — `Process::path()->run('git stash apply stash@{0}')` → Should use `StashService::stashApply()`
    - `HistoryPanel.php:203-204` — `Process::path()->run("git branch -r --contains {$sha}")` → Should be in a service method
    - `DiffViewer.php:452` — `Process::path()->run("git cat-file -s HEAD:\"{$file}\"")` → Should be in GitService
  - Add necessary methods to services if they don't exist

  **Must NOT do**:
  - Do NOT change the behavior of any command
  - Do NOT refactor the Livewire method logic (only extract the Process call)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3
  - **Blocked By**: Task 6

  **References**:
  - `app/Livewire/StagingPanel.php:300` — Direct Process call for branch name
  - `app/Livewire/SyncPanel.php:79` — Direct `git push` bypassing RemoteService
  - `app/Livewire/BranchManager.php:167` — Direct `git stash apply` bypassing StashService
  - `app/Livewire/HistoryPanel.php:203-204` — Direct `git branch -r --contains`
  - `app/Livewire/DiffViewer.php:452` — Direct `git cat-file`
  - `app/Services/Git/RemoteService.php` — Where push/pull should live
  - `app/Services/Git/StashService.php` — Where stash apply should live
  - `app/Services/Git/GitService.php` — Where cat-file should live

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → All tests pass
  - [ ] Zero `Process::path()` calls in any Livewire component
  - [ ] All git commands go through service layer

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: No more Process calls in Livewire
    Tool: Bash (grep)
    Steps:
      1. Run `grep -r "Process::path" app/Livewire/` — should return 0 results
      2. Run `php artisan test --compact` — all tests pass
    Expected Result: Zero Process calls in Livewire, all tests pass
    Evidence: .sisyphus/evidence/task-17-no-process-in-livewire.txt
  ```

  **Commit**: YES
  - Message: `refactor(backend): move all Process calls from Livewire into service layer`

---

### WAVE 4: SAFETY & ARCHITECTURE

---

- [ ] 18. Fix Shell Injection Across All Services

  **What to do**:
  - **TDD**: Write tests with malicious input FIRST (commit messages with quotes, branch names with spaces/special chars, search queries with shell metacharacters)
  - Then verify `GitCommandRunner` properly escapes all arguments
  - Since all services now use `GitCommandRunner` (from Wave 2), this should be mostly verified by the runner's own tests
  - Review each service to ensure arguments are passed as array items (escaped) not concatenated into command string
  - Test specifically:
    - Commit message: `Hello "world"; echo pwned` → should commit successfully
    - Branch name: `feature/my branch` → should handle spaces
    - Search query: `"; rm -rf /; echo "` → should not execute

  **Must NOT do**:
  - Do NOT change git command semantics
  - Do NOT add validation beyond escaping (let git reject invalid input)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 19-21)
  - **Blocked By**: Tasks 6, 8-12

  **References**:
  - `app/Services/Git/GitCommandRunner.php` — Escaping implementation from Task 6
  - Task 3 evidence — Complete audit of unsafe commands
  - `app/Services/Git/CommitService.php` — `commit()` method with message interpolation
  - `app/Services/Git/BranchService.php` — `switchBranch()` with name interpolation
  - `app/Services/Git/SearchService.php` — `searchCommits()` with query interpolation

  **Acceptance Criteria**:
  - [ ] Tests with malicious input pass (commands execute safely or fail gracefully)
  - [ ] Zero string interpolation of user input in git commands
  - [ ] `php artisan test --compact` → All tests pass

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Commit message with special characters
    Tool: Bash (pest)
    Steps:
      1. Create test with commit message containing double quotes, backticks, semicolons
      2. Verify commit succeeds or fails gracefully (no command injection)
    Expected Result: Safe execution, no shell injection
    Evidence: .sisyphus/evidence/task-18-shell-injection-tests.txt

  Scenario: Branch name with special characters
    Tool: Bash (pest)
    Steps:
      1. Test switchBranch() with spaces, quotes in branch name
      2. Verify no command injection
    Expected Result: Fails gracefully with proper error message
    Evidence: .sisyphus/evidence/task-18-branch-name-safety.txt
  ```

  **Commit**: YES
  - Message: `fix(backend): secure all git commands against shell injection`

---

- [ ] 19. Add Domain-Specific Exceptions

  **What to do**:
  - **TDD**: Write tests that expect specific exception types
  - Create:
    - `app/Exceptions/GitCommandFailedException.php` — for failed git commands (replaces generic RuntimeException)
    - `app/Exceptions/InvalidRepositoryException.php` — for invalid repo paths (replaces generic InvalidArgumentException)
    - `app/Exceptions/GitConflictException.php` — for merge/rebase conflicts
  - Update `AbstractGitService` constructor to throw `InvalidRepositoryException`
  - Update `GitCommandRunner::runOrFail()` to throw `GitCommandFailedException`
  - Update catch blocks in Livewire trait to catch the right exceptions
  - Existing `GitOperationInProgressException` stays as-is

  **Must NOT do**:
  - Do NOT create exceptions for every possible error (keep it to 3-4 domain exceptions)
  - Do NOT change user-facing error messages

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4

  **References**:
  - `app/Exceptions/GitOperationInProgressException.php` — Existing exception, follow this pattern
  - `app/Services/Git/GitErrorHandler.php` — Error translation (must still work with new exception types)
  - `app/Services/Git/AbstractGitService.php` — Constructor throws for invalid repo

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → All tests pass
  - [ ] 3 new exception classes created
  - [ ] `AbstractGitService` throws `InvalidRepositoryException`
  - [ ] `GitCommandRunner::runOrFail()` throws `GitCommandFailedException`

  **Commit**: YES
  - Message: `feat(backend): add domain-specific Git exceptions`

---

- [ ] 20. Fix Unchecked Exit Codes

  **What to do**:
  - Review audit from Task 3 for commands that don't check exit codes
  - Known issues:
    - `RemoteService::push()` — doesn't check exit code (already partially fixed in Task 11 with `runOrFail`)
    - `RemoteService::pull()` — same issue
    - `RemoteService::fetch()` — same issue
    - `StashService::stash()` — doesn't check result
  - Verify all `$this->commandRunner->run()` calls that should be `runOrFail()` are updated
  - Add tests for failure scenarios

  **Must NOT do**:
  - Do NOT change commands that intentionally ignore failures (e.g., `git stash apply` in auto-stash flow where failure is handled)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4

  **References**:
  - `app/Services/Git/RemoteService.php:42-47` — push without exit code check
  - `app/Services/Git/RemoteService.php:49-56` — pull without exit code check
  - `app/Services/Git/StashService.php:33` — stash without exit code check
  - Task 3 evidence — Full audit

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → All tests pass
  - [ ] All write operations use `runOrFail()` or explicitly check exit codes
  - [ ] Read-only operations can use `run()` (graceful failure is acceptable)

  **Commit**: YES
  - Message: `fix(backend): check exit codes for all git write operations`

---

- [ ] 21. Register Service Factory in AppServiceProvider

  **What to do**:
  - Register `GitCacheService` as a singleton in `AppServiceProvider` (it's stateless, one instance is fine)
  - Register `SettingsService` as a singleton
  - Register `NotificationService` as a singleton
  - Register `RepoManager` as a singleton
  - Do NOT register Git services as singletons (they take `$repoPath` in constructor)
  - Instead, update `AbstractGitService` to accept `GitCacheService` via DI (optional constructor parameter with fallback to `new`)
  - This allows tests to inject mock cache while production code still works

  **Must NOT do**:
  - Do NOT create a complex factory pattern (KISS)
  - Do NOT change how Livewire components create services (they can still use `new`)
  - Do NOT break any existing functionality

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4

  **References**:
  - `app/Providers/AppServiceProvider.php` — Currently empty, will add bindings
  - `app/Services/Git/GitCacheService.php` — Stateless, good singleton candidate
  - `app/Services/SettingsService.php` — Stateless
  - `app/Services/NotificationService.php` — Stateless

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → All tests pass
  - [ ] `GitCacheService`, `SettingsService`, `NotificationService`, `RepoManager` registered as singletons
  - [ ] `app(GitCacheService::class)` returns a singleton instance

  **Commit**: YES
  - Message: `refactor(backend): register stateless services in AppServiceProvider`

---

### WAVE 5: TEST COMPLETION

---

- [ ] 22. Fix Broken Test Files

  **What to do**:
  - Fix `$testRepoPath` undefined property in:
    - `tests/Feature/Livewire/StashPanelTest.php` (12 errors)
    - `tests/Feature/Livewire/AutoFetchIndicatorTest.php` (27 errors)
    - `tests/Feature/Livewire/SyncPanelTest.php` (16 errors)
  - These tests likely need a `beforeEach` or `setUp` that creates a test repo
  - Use `GitTestHelper::createTestRepo()` pattern from `tests/Helpers/GitTestHelper.php`
  - Run all fixed tests to verify they pass

  **Must NOT do**:
  - Do NOT refactor test infrastructure (GitTestHelper is fine)
  - Do NOT delete any tests

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5 (with Tasks 23-25)
  - **Blocked By**: None (independent of refactoring waves)

  **References**:
  - `tests/Helpers/GitTestHelper.php:12-27` — `createTestRepo()` method for test setup
  - `tests/Feature/Livewire/StagingPanelTest.php` — Working test that probably uses `$testRepoPath` correctly — use as pattern
  - `tests/Feature/Livewire/StashPanelTest.php` — 12 LSP errors for undefined `$testRepoPath`

  **Acceptance Criteria**:
  - [ ] Zero LSP errors for `$testRepoPath` across all test files
  - [ ] `php artisan test --compact --filter=StashPanelTest` → PASS
  - [ ] `php artisan test --compact --filter=AutoFetchIndicatorTest` → PASS
  - [ ] `php artisan test --compact --filter=SyncPanelTest` → PASS

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All previously broken tests now pass
    Tool: Bash (pest)
    Steps:
      1. Run `php artisan test --compact --filter=StashPanelTest`
      2. Run `php artisan test --compact --filter=AutoFetchIndicatorTest`
      3. Run `php artisan test --compact --filter=SyncPanelTest`
    Expected Result: All tests pass, zero LSP errors
    Evidence: .sisyphus/evidence/task-22-fixed-tests.txt
  ```

  **Commit**: YES
  - Message: `fix(tests): resolve $testRepoPath undefined in test files`

---

- [ ] 23. Add Edge Case Tests

  **What to do**:
  - Add tests for edge cases identified by Metis:
    - Empty repository (0 commits) — `GitService::log()` and `GitService::status()`
    - Detached HEAD state — `BranchService::branches()`, `SyncPanel` operations
    - Unicode/emoji file names — `StagingService::stageFile()`
    - Binary files — `DiffViewer::loadDiff()`
    - Very large diff output — `DiffResult::fromDiffOutput()`
  - Use `GitTestHelper` to set up appropriate test repos

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5

  **References**:
  - `tests/Helpers/GitTestHelper.php:77-83` — `createDetachedHead()` helper already exists!
  - `tests/Helpers/GitTestHelper.php:58-75` — `createConflict()` helper exists
  - `tests/Mocks/GitOutputFixtures.php:120-127` — Detached HEAD fixture exists

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → All tests pass including new ones
  - [ ] Edge cases covered: empty repo, detached HEAD, unicode filenames, binary files

  **Commit**: YES
  - Message: `test(backend): add edge case tests for empty repo, detached HEAD, unicode, binary files`

---

- [ ] 24. Add Shell Injection Test Fixtures

  **What to do**:
  - Add test fixtures to `GitOutputFixtures` for malicious input scenarios
  - Add specific tests verifying:
    - Commit with message `"Hello'; DROP TABLE users; --"` → safe execution
    - Branch checkout with name containing backticks → safe execution
    - Search with query containing `$(command)` → safe execution
  - These tests validate the `GitCommandRunner` escaping from Task 6

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5
  - **Blocked By**: Task 18

  **References**:
  - `tests/Mocks/GitOutputFixtures.php` — Add new fixtures here
  - `app/Services/Git/GitCommandRunner.php` — Escaping logic to test
  - Task 18 — Shell injection fixes to verify

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=ShellInjection` → PASS
  - [ ] Tests cover: quotes in messages, backticks in names, $() in queries

  **Commit**: YES
  - Message: `test(backend): add shell injection test fixtures and verification`

---

- [ ] 25. Clean Up Unit Tests + Verify Coverage

  **What to do**:
  - Remove `tests/Unit/ExampleTest.php` (boilerplate)
  - Verify unit tests from Tasks 4-6 are in `tests/Unit/` (DTOs, AbstractGitService, GitCommandRunner)
  - Run full test suite one final time
  - Run `vendor/bin/pint --dirty --format agent` to ensure all new code matches style
  - Document final test count and compare to baseline from Task 1

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocked By**: Tasks 22-24

  **References**:
  - `tests/Unit/ExampleTest.php` — Delete this
  - Task 1 evidence — Baseline test count to compare against

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact` → PASS, test count ≥ baseline + new tests
  - [ ] `vendor/bin/pint --test --format agent` → 0 issues
  - [ ] `tests/Unit/ExampleTest.php` removed
  - [ ] Final test count documented

  **Commit**: YES
  - Message: `chore(tests): clean up unit tests and verify coverage`

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 4 review agents run in PARALLEL. ALL must APPROVE. Rejection → fix → re-run.

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists (read file, run command). For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Code Quality Review** — `unspecified-high`
  Run `vendor/bin/pint --test --format agent`. Review all changed files for: `as any`/@ts-ignore equivalents, empty catches, leftover debug code, unused imports. Check for remaining code duplication. Verify AbstractGitService is used by all services. Verify HandlesGitOperations trait is used by refactored components.
  Output: `Pint [PASS/FAIL] | Tests [N pass/N fail] | Files [N clean/N issues] | VERDICT`

- [ ] F3. **Full Test Suite + Manual QA** — `unspecified-high` (+ `pest-testing` skill)
  Start from clean state. Run `php artisan test --compact`. Execute EVERY QA scenario from EVERY task — follow exact steps, capture evidence. Verify zero `Process::path` calls in Livewire (`grep -r "Process::path" app/Livewire/`). Verify all services extend AbstractGitService. Save to `.sisyphus/evidence/final-qa/`.
  Output: `Tests [N/N pass] | QA Scenarios [N/N] | Grep Checks [N/N] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff (git log/diff). Verify 1:1 — everything in spec was built (no missing), nothing beyond spec was built (no creep). Check "Must NOT do" compliance. Detect cross-task contamination. Flag unaccounted changes. Specifically verify: no Blade template changes, no database changes, no route changes, no config changes.
  Output: `Tasks [N/N compliant] | Scope Violations [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

| After Task(s) | Message | Files | Verification |
|---------------|---------|-------|--------------|
| 1 | `chore(tests): document test baseline` | evidence only | `php artisan test --compact` |
| 2 | `style(backend): apply Pint formatting baseline` | auto-fixed files | `vendor/bin/pint --test` |
| 4 | `feat(backend): add ChangedFile DTO and AheadBehind value object` | DTOs + tests | unit tests |
| 5 | `refactor(backend): create AbstractGitService base class` | base class + tests | full suite |
| 6 | `feat(backend): add GitCommandRunner with shell escaping` | runner + tests | unit tests |
| 7 | `feat(backend): add HandlesGitOperations Livewire trait` | trait + tests | full suite |
| 8 | `refactor(backend): migrate GitService + StagingService` | 2 services | service tests |
| 9 | `refactor(backend): migrate BranchService + CommitService` | 2 services | service tests |
| 10 | `refactor(backend): migrate DiffService + SearchService` | 2 services | service tests |
| 11 | `refactor(backend): migrate RemoteService + StashService` | 2 services | service tests |
| 12 | `refactor(backend): migrate remaining services` | 5 services | full suite |
| 13 | `refactor(backend): type-safe GitStatus with ChangedFile` | DTOs + consumers | full suite |
| 14 | `refactor(staging): eliminate try/catch duplication` | StagingPanel | component tests |
| 15 | `refactor(panels): extract DiffViewer DTO hydration` | DiffViewer | component tests |
| 16 | `refactor(panels): eliminate SyncPanel boilerplate` | SyncPanel | component tests |
| 17 | `refactor(backend): move Process calls to service layer` | Livewire + services | full suite + grep |
| 18 | `fix(backend): secure git commands against shell injection` | services + tests | injection tests |
| 19 | `feat(backend): add domain-specific Git exceptions` | exceptions + updates | full suite |
| 20 | `fix(backend): check exit codes for git write operations` | services | service tests |
| 21 | `refactor(backend): register services in AppServiceProvider` | provider | full suite |
| 22 | `fix(tests): resolve $testRepoPath undefined` | test files | fixed tests |
| 23 | `test(backend): add edge case tests` | test files | new tests |
| 24 | `test(backend): add shell injection test fixtures` | test files | new tests |
| 25 | `chore(tests): clean up and verify coverage` | test cleanup | full suite |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact                    # Expected: All tests pass (≥ baseline count)
vendor/bin/pint --test --format agent         # Expected: 0 style violations
grep -r "Process::path" app/Livewire/        # Expected: 0 results (no direct Process in Livewire)
grep -rc "extends AbstractGitService" app/Services/Git/  # Expected: 11+ files
grep -r "new GitCacheService" app/Services/Git/ | grep -v AbstractGitService  # Expected: 0 results
```

### Final Checklist
- [ ] All "Must Have" present (AbstractGitService, GitCommandRunner, trait, DTOs)
- [ ] All "Must NOT Have" absent (no Blade changes, no DB changes, no new features)
- [ ] All tests pass (≥ baseline from Task 1)
- [ ] Zero Process::path() calls in Livewire components
- [ ] All services extend AbstractGitService (except utilities)
- [ ] Shell injection tests pass with malicious input
- [ ] Code style clean (Pint passes)
