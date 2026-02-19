# .gitignore Quick-Add

## TL;DR

> **Quick Summary**: Let users right-click an untracked file in the staging panel and add it (or its pattern) to `.gitignore` with one click.
> 
> **Deliverables**:
> - Context menu "Add to .gitignore" action on untracked files
> - Pattern options: exact file, extension wildcard, directory
> - `.gitignore` file modification service
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `StagingPanel.php` renders untracked files (items with `?` status) separately
- No existing `.gitignore` management in the codebase
- Need to handle: file doesn't exist yet, file exists and we append, pattern deduplication
- Entry point: right-click / context menu on untracked file items in staging panel

---

## Work Objectives

### Must Have
- "Add to .gitignore" action on untracked files
- Options: exact path, wildcard by extension (`*.log`), directory pattern (`dir/`)
- Creates `.gitignore` if it doesn't exist
- Appends pattern (no duplicates)
- File disappears from untracked list after adding

### Must NOT Have
- No `.gitignore` editor (just quick-add)
- No global gitignore management
- No pattern removal

---

## TODOs

- [ ] 1. Create GitignoreService

  **What to do**:
  - Create `app/Services/Git/GitignoreService.php` extending nothing (file-based, not git-command-based)
  - Method `addPattern(string $repoPath, string $pattern): void` — appends pattern to `.gitignore`, creates file if needed
  - Method `hasPattern(string $repoPath, string $pattern): bool` — checks for duplicates
  - Method `suggestPatterns(string $filePath): array` — returns options: exact path, `*.ext`, `dirname/`
  - Handle trailing newline properly

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/AbstractGitService.php` — General service pattern (but this won't extend it since it's file-based)
  - `app/Services/SettingsService.php` — File-based service pattern

  **Acceptance Criteria**:
  - [ ] `addPattern()` appends to .gitignore
  - [ ] `hasPattern()` detects duplicates
  - [ ] `suggestPatterns('src/debug.log')` returns `['src/debug.log', '*.log', 'src/']`

  **Commit**: YES
  - Message: `feat(backend): create GitignoreService for quick-add patterns`
  - Files: `app/Services/Git/GitignoreService.php`

- [ ] 2. Add gitignore context menu to staging panel

  **What to do**:
  - Add context menu (right-click or dropdown) on untracked file items in `staging-panel.blade.php`
  - Show pattern options from `suggestPatterns()`
  - On select: call service, refresh staging status
  - Add to `StagingPanel.php`: `addToGitignore(string $file, string $pattern): void`
  - Register in CommandPalette: "Add to .gitignore"

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `resources/views/livewire/staging-panel.blade.php` — Untracked file rendering
  - `app/Livewire/StagingPanel.php` — Add method
  - `app/Livewire/CommandPalette.php` — Register command

  **Acceptance Criteria**:
  - [ ] Context menu on untracked files shows gitignore pattern options
  - [ ] Selecting option adds pattern and file disappears from list

  **QA Scenarios**:

  ```
  Scenario: Add untracked file to .gitignore
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with untracked files
      2. Right-click on an untracked file
      3. Select "Add to .gitignore" option
      4. Select exact path pattern
      5. Assert file disappears from untracked list
      6. Assert .gitignore contains the pattern
    Expected Result: File ignored, pattern in .gitignore
    Evidence: .sisyphus/evidence/task-2-gitignore-add.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add .gitignore quick-add context menu`

- [ ] 3. Pest tests

  **What to do**:
  - Create `tests/Feature/Services/GitignoreServiceTest.php`
  - Test: add pattern, detect duplicates, suggest patterns, create new file
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Acceptance Criteria**:
  - [ ] All gitignore tests pass

  **Commit**: YES
  - Message: `test(backend): add tests for GitignoreService`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=Gitignore  # Expected: all pass
```
