
## Task 2: Pint Baseline (2026-02-17)
- **Finding:** Codebase already 100% Pint-compliant
- **Evidence:** Both `--format agent` and `--test` modes returned `{"result":"pass"}`
- **Implication:** Code style discipline is already strong; refactoring won't introduce style debt
- **Tooling:** Pint's `--format agent` mode provides clean JSON output for automation

## GitService + StagingService → AbstractGitService Migration

### Successfully Completed
Migrated both GitService and StagingService to extend AbstractGitService, eliminating ~40 lines of duplicate code.

### Migration Pattern
1. Change class: `class Service extends AbstractGitService`
2. Remove constructor (inherited)
3. Remove `private GitCacheService $cache;` property (inherited as `protected`)
4. Remove `use Illuminate\Support\Facades\Process;` import
5. Replace `Process::path($this->repoPath)->run('git ...')` with `$this->commandRunner->run('...')`
   - Remove "git" prefix from command
   - Pass file paths as array args for automatic escaping
   - Remove manual `escapeshellarg()` calls

### Command Conversion Examples
```php
// BEFORE
Process::path($this->repoPath)->run('git status --porcelain=v2 --branch')
// AFTER
$this->commandRunner->run('status --porcelain=v2 --branch')

// BEFORE (unsafe - no escaping)
Process::path($this->repoPath)->run("git add {$file}")
// AFTER (safe - automatic escaping)
$this->commandRunner->run('add', [$file])

// BEFORE (manual escaping)
$escapedFiles = array_map(fn($file) => escapeshellarg($file), $files);
$filesString = implode(' ', $escapedFiles);
Process::path($this->repoPath)->run("git add {$filesString}");
// AFTER (automatic escaping)
$this->commandRunner->run('add', $files)
```

### Test Updates - CRITICAL
**escapeshellarg adds quotes**, so Process::fake() and Process::assertRan() with exact strings need updating:

```php
// OLD TEST
Process::assertRan('git add README.md');
// NEW TEST
Process::assertRan("git add 'README.md'");  // Note the quotes!
```

Tests using callbacks with `str_contains()` work without changes:
```php
Process::assertRan(fn($p) => str_contains($p->command, 'git add') && str_contains($p->command, 'README.md'))
```

### Results
- GitService: 16/16 tests ✓
- StagingService: 16/16 tests ✓  
- Commit: da9b992
- Lines saved: ~40 (constructors + manual escaping)
- Security: Improved (automatic arg escaping)

### Next Services to Migrate
Still using old pattern (check with `grep "public function __construct" app/Services/Git/*.php`):
- CommitService
- BranchService
- DiffService
- RemoteService
- TagService
- RebaseService
- etc.


## Test Fixes for GitCommandRunner Migration (Task 9)

**Problem**: 3 tests in `StagingPanelTest.php` failed after migrating `StagingService` to use `GitCommandRunner` because `escapeshellarg()` wraps file arguments in single quotes.

**Solution**: Updated command string expectations in both `Process::fake()` keys and `Process::assertRan()` assertions:
- `'git add README.md'` → `"git add 'README.md'"`
- `'git reset HEAD README.md'` → `"git reset HEAD 'README.md'"`
- `'git checkout -- README.md'` → `"git checkout -- 'README.md'"`

**Key Insight**: When using exact string matching in `Process::fake()` and `Process::assertRan()`, the command string must match EXACTLY what `GitCommandRunner` produces, including the single quotes from `escapeshellarg()`.

**Tests using closure-based assertions** (e.g., `fn($p) => str_contains($p->command, 'git add')`) were unaffected because they match partial strings, not exact commands.

**Result**: All 18 StagingPanelTest tests now pass.

## BranchService + CommitService → AbstractGitService Migration (Task 10)

**Completed**: Successfully migrated both BranchService and CommitService to extend AbstractGitService, following the established pattern from GitService/StagingService.

### Changes to BranchService
1. Extended `AbstractGitService` (removed constructor + cache property)
2. Removed `use Illuminate\Support\Facades\Process;` import
3. Converted all Process calls to commandRunner:
   - `branches()`: `Process::path(...)->run('git branch -a -vv')` → `$this->commandRunner->run('branch -a -vv')`
   - `switchBranch()`: `run("git checkout {$name}")` → `run('checkout', [$name])`
   - `createBranch()`: `run("git checkout -b {$name} {$from}")` → `run('checkout -b', [$name, $from])`
   - `deleteBranch()`: `run("git branch {$flag} {$name}")` → `run("branch {$flag}", [$name])` (flag is internal, not user input)
   - `mergeBranch()`: `run("git merge {$name}")` → `run('merge', [$name])`
4. Updated test assertions for escapeshellarg quotes (e.g., `"git checkout 'feature/new-ui'"`)

### Changes to CommitService
1. Extended `AbstractGitService` (removed constructor + cache property)
2. Removed `use Illuminate\Support\Facades\Process;` import
3. **Removed `runCommit()` helper method** — replaced with direct `runOrFail()` calls
4. Converted all Process calls to commandRunner:
   - `commit()`: `runCommit("git commit -m \"{$message}\"", ...)` → `$this->commandRunner->runOrFail('commit -m', [$message], 'Git commit failed')`
   - `commitAmend()`: Similar pattern
   - `commitAndPush()`: Split into commit + push, both using `runOrFail()`
   - `lastCommitMessage()`: `Process::path(...)->run('git log -1 --pretty=%B')` → `$this->commandRunner->run('log -1 --pretty=%B')`
   - `undoLastCommit()`: `Process::path(...)->run('git reset --soft HEAD~1')` → `$this->commandRunner->runOrFail('reset --soft HEAD~1', [], 'Git reset failed')`
   - `cherryPick()`: `run("git cherry-pick {$sha}")` → `run('cherry-pick', [$sha])`
   - `cherryPickAbort()`: `run('git cherry-pick --abort')` → `runOrFail('cherry-pick --abort', [], 'Git cherry-pick abort failed')`
   - `cherryPickContinue()`: `run('git cherry-pick --continue')` → `runOrFail('cherry-pick --continue', [], 'Git cherry-pick continue failed')`
5. **Fixed `isLastCommitPushed()`**: Previously created `new GitService($this->repoPath)` internally. Refactored to use `$this->commandRunner->run('status --porcelain=v2 --branch')` directly, parsing the output inline with regex.
6. Updated test assertions for escapeshellarg quotes (commit messages: double quotes → single quotes)

### Test Updates for escapeshellarg
**BranchServiceTest:**
- `'git checkout feature/new-ui'` → `"git checkout 'feature/new-ui'"`
- `'git checkout -b feature/test main'` → `"git checkout -b 'feature/test' 'main'"`
- `'git branch -d feature/old'` → `"git branch -d 'feature/old'"`
- `'git branch -D feature/old'` → `"git branch -D 'feature/old'"`
- `'git merge feature/new-ui'` → `"git merge 'feature/new-ui'"`

**CommitServiceTest:**
- `'git commit -m "feat: add new feature"'` → `"git commit -m 'feat: add new feature'"`
- `'git commit --amend -m "feat: updated feature"'` → `"git commit --amend -m 'feat: updated feature'"`
- `'git commit -m "feat: add feature"'` → `"git commit -m 'feat: add feature'"`
- `'git push'` → unchanged (no args)
- `'git log -1 --pretty=%B'` → unchanged (no args)

### Results
- BranchService: 7/7 tests ✓
- CommitService: 5/5 tests ✓
- Lines removed: ~60 (2 constructors, 2 cache properties, 1 runCommit helper, Process imports)
- Security: Improved (automatic arg escaping for branch names, commit messages, SHAs)
- Dependency elimination: No more `new GitService()` calls inside CommitService

### Key Learning
**isLastCommitPushed() refactoring**: The original implementation created a new GitService instance to call `status()` and `aheadBehind()`. This was:
1. A hidden dependency (not visible in constructor)
2. Inefficient (creates cache, commandRunner, validates repo path again)
3. Duplicates the "status --porcelain=v2 --branch" parsing logic

**Better approach**: Call `$this->commandRunner->run('status --porcelain=v2 --branch')` directly and parse inline. This:
1. Uses existing commandRunner instance
2. Avoids duplicate object creation
3. Makes the dependency explicit
4. Eliminates tight coupling between services

### Remaining Services to Migrate
Still using old pattern (check with `grep "public function __construct" app/Services/Git/*.php`):
- DiffService
- RemoteService
- TagService
- RebaseService
- etc.
