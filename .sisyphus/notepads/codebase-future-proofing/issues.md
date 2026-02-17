
## Command Safety Audit (Task 3) - 2026-02-17

### Summary
Audited 128 Process::path() calls across 27 files (18 services, 9 Livewire components).

**Critical Findings:**
- **38 UNSAFE commands** with raw user input interpolation
- **63 SAFE commands** (no user input or properly escaped)
- **12 GOOD EXAMPLES** using escapeshellarg() correctly

### Highest Risk Commands (Priority 1 - Fix Immediately)

1. **CommitService::commit()** [Line 26]
   - Command: `"git commit -m \"{$message}\""`
   - Risk: Commit messages with quotes break command
   - Frequency: VERY HIGH (every commit)
   - Fix: Use `-F` flag with temp file OR escapeshellarg()

2. **CommitService::commitAmend()** [Line 31]
   - Same issue as commit()

3. **SearchService::searchCommits()** [Line 30-31]
   - Command: `"git log --grep=\"{$query}\" ..."`
   - Risk: Search queries with quotes break command
   - Fix: escapeshellarg() on $query

4. **SearchService::searchContent()** [Line 50-51]
   - Same issue as searchCommits()

5. **SearchService::searchFiles()** [Line 70-71]
   - Same issue as searchCommits()

6. **TagService::createTag()** [Line 55-63]
   - Command: `"git tag -a \"{$name}\" -m \"{$message}\""`
   - Risk: Tag names/messages with quotes break command
   - Fix: escapeshellarg() on $name and $message

7. **StashService::stash()** [Line 27-33]
   - Command: `"git stash push -u -m \"{$message}\""`
   - Risk: Stash messages with quotes break command
   - Fix: escapeshellarg() on $message

### High Risk Commands (Priority 2 - Files with Spaces)

**StagingService** (3 methods):
- `stageFile()` [Line 25]: `"git add {$file}"`
- `unstageFile()` [Line 32]: `"git reset HEAD {$file}"`
- `discardFile()` [Line 53]: `"git checkout -- {$file}"`
- Fix: escapeshellarg() on $file (see stageFiles() for correct pattern)

**GitService::diff()** [Line 106-116]:
- 3 occurrences of unescaped file paths
- Fix: escapeshellarg() on $file

**BranchService** (4 methods):
- `switchBranch()` [Line 43]: `"git checkout {$name}"`
- `createBranch()` [Line 56]: `"git checkout -b {$name} {$from}"`
- `deleteBranch()` [Line 70]: `"git branch {$flag} {$name}"`
- `mergeBranch()` [Line 82]: `"git merge {$name}"`
- Fix: escapeshellarg() on branch names

**RemoteService** (3 methods):
- `push()` [Line 44]: `"git push {$remote} {$branch}"`
- `pull()` [Line 51]: `"git pull {$remote} {$branch}"`
- `fetch()` [Line 60]: `"git fetch {$remote}"`
- Fix: escapeshellarg() on remote and branch names

**SyncPanel** (3 methods):
- `syncPush()` [Line 79]: `"git push origin {$currentBranch}"`
- `syncPull()` [Line 125]: `"git pull origin {$currentBranch}"`
- `syncForcePushWithLease()` [Line 228]: `"git push --force-with-lease origin {$currentBranch}"`
- Fix: escapeshellarg() on $currentBranch

**Other file path issues:**
- BlameService::blame() [Line 27]
- ConflictService::resolveConflict() [Line 88]
- ConflictService::getFileVersion() [Line 134]
- ConflictService::isBinaryFile() [Line 147]
- DiffViewer::getFileSize() [Line 452]
- DiffViewer::getImageData() [Line 536]

### Good Examples to Follow

**StagingService** (correct pattern):
- `stageFiles()` [Line 71-74]: Uses `escapeshellarg()` on array
- `unstageFiles()` [Line 85-88]: Uses `escapeshellarg()` on array
- `discardFiles()` [Line 99-102]: Uses `escapeshellarg()` on array

**StashService**:
- `stashFiles()` [Line 83]: Uses `escapeshellarg()` on paths array

**DiffService** (all methods):
- Uses stdin for patch input (no escaping needed)

### Recommendations

1. **Create helper function** for safe command building:
   ```php
   private function escapeGitArg(string $arg): string {
       return escapeshellarg($arg);
   }
   ```

2. **For commit messages**, use `-F` flag with temp file:
   ```php
   $tempFile = tempnam(sys_get_temp_dir(), 'gitty-commit-');
   file_put_contents($tempFile, $message);
   Process::path($this->repoPath)->run("git commit -F {$tempFile}");
   unlink($tempFile);
   ```

3. **Add SHA validation helper**:
   ```php
   private function validateSha(string $sha): bool {
       return preg_match('/^[0-9a-f]{7,40}$/i', $sha) === 1;
   }
   ```

4. **Follow the stageFiles() pattern** for all file operations:
   ```php
   $escapedFile = escapeshellarg($file);
   Process::path($this->repoPath)->run("git add {$escapedFile}");
   ```

### Statistics

- **Total commands audited**: 101 (production code only)
- **UNSAFE**: 38 (37.6%)
- **SAFE**: 63 (62.4%)
  - No user input: 51
  - Properly escaped: 12

### Full Audit Report

See: `.sisyphus/evidence/task-3-command-safety-audit.txt`

