
## ReactivityListenersTest findings (2026-02-19)

### File created: `tests/Feature/Livewire/ReactivityListenersTest.php`
5 tests, all passing.

### Key patterns learned

**BranchManager + status-updated**
- `handleStatusUpdated()` calls `refreshBranches()` which runs both `git status --porcelain=v2 --branch` and `git branch -a -vv`
- Use `Process::assertRan('git branch -a -vv', 2)` to verify mount + event both triggered refresh

**DiffViewer + status-updated**
- `refreshOrClearDiff()` first calls `gitService->diff($file, $isStaged)`:
  - If non-empty → calls `loadDiff()` (calls `diff()` again internally) → `file` stays set
  - If empty → calls `clearDiff()` → `file` becomes null
- When `git diff` returns empty string, `GitService::diff()` also calls `git status --porcelain=v2 -- 'file'` internally to check for untracked files — must be faked too
- `getFileSize()` is wrapped in try/catch so unfaked git commands throw but are caught gracefully

**SearchPanel + repo-switched**
- No `Process::fake` needed — purely property resets
- `Livewire::test(SearchPanel::class)` works without `repoPath` mount arg (defaults to `''`)

**AutoFetchIndicator + settings-updated**
- `handleSettingsUpdated()` calls `refreshStatus()` — file-based checks, no git commands
- `mount()` calls `checkAndFetch()` which may invoke AutoFetchService; use `Process::fake()` (no args) to intercept all git processes safely

## ReactivityCommitSyncTest patterns (added 2026-02-19)

- CommitPanel::commit() re-fetches git status after commit to get fresh aheadBehind — fake `git status --porcelain=v2 --branch` covers both mount and post-commit calls
- CommitPanel also calls `git log --oneline -n 10` twice (mount + post-commit reload), but it's wrapped in try/catch so unfaked = graceful fallback to []
- Use wildcard `'git commit -m *' => Process::result('')` for commit command fakes
- SyncPanel mount() calls `git status --porcelain=v2 --branch` → dispatching `committed` triggers handleCommitted → refreshAheadBehindData → second git status call; `Process::assertRan(..., 2)` verifies both
- `refreshAheadBehind(aheadBehind: [])` falls back to `refreshAheadBehindData()` (empty array → git status re-fetch)
- `$testRepoPath` LSP "Undefined property" errors in Pest test files are false positives — all sibling files have them, tests still pass
