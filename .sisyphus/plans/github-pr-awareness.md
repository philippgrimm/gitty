# GitHub PR Awareness

## TL;DR

> **Quick Summary**: Show lightweight GitHub pull request status indicators on branches — whether a PR exists for a branch, its status (open/merged/closed), and a quick link to open it in the browser.
> 
> **Deliverables**:
> - GitHub API service using `gh` CLI (no OAuth tokens needed)
> - PR status indicator on branch items in branch manager
> - Click-to-open PR in browser
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- No GitHub integration exists in the codebase
- `gh` CLI is the most reliable approach — it handles auth via `gh auth login` (already done by most developers)
- `gh pr list --head {branch} --json number,title,state,url` returns PR info for a branch
- NativePHP can open URLs in browser via `shell_exec('open {url}')` on macOS
- This should be opt-in (not all repos are on GitHub)
- Must handle: `gh` not installed, not authenticated, non-GitHub repos gracefully

---

## Work Objectives

### Must Have
- PR status badge on branches (green=open, purple=merged, red=closed, none=no PR)
- PR number and title on hover/tooltip
- Click to open PR URL in browser
- Graceful degradation when `gh` not available
- Cache PR data (don't hit API on every render)

### Must NOT Have
- No PR creation from gitty
- No PR review/merge from gitty
- No OAuth token management
- No GitLab/Bitbucket support (GitHub only for now)

---

## TODOs

- [ ] 1. Create GitHubService using gh CLI

  **What to do**:
  - Create `app/Services/GitHubService.php`
  - Method `isAvailable(): bool` — checks if `gh` CLI exists and is authenticated
  - Method `getPullRequestForBranch(string $repoPath, string $branch): ?array` — runs `gh pr list --head {branch} --json number,title,state,url --limit 1`
  - Method `getPullRequests(string $repoPath): Collection` — batch fetch for all branches
  - Method `openUrl(string $url): void` — opens URL in macOS browser
  - Cache results for 5 minutes
  - Handle errors gracefully (return null, not exceptions)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/AbstractGitService.php` — Service pattern (but this uses `gh`, not `git`)
  - `app/Services/Git/GitCommandRunner.php` — Command execution pattern
  - `app/Services/Git/GitCacheService.php` — Caching pattern

  **Acceptance Criteria**:
  - [ ] `isAvailable()` returns true when gh is installed and authed
  - [ ] `getPullRequestForBranch()` returns PR data or null
  - [ ] Results cached for 5 minutes
  - [ ] No exceptions when gh is not available

  **Commit**: YES
  - Message: `feat(backend): create GitHubService for PR awareness via gh CLI`
  - Files: `app/Services/GitHubService.php`

- [ ] 2. Add PR status indicators to branch manager

  **What to do**:
  - In `BranchManager.php`, load PR data for branches (if GitHub available)
  - Add PR status to branch item data
  - In `branch-manager.blade.php`, show colored PR badge: `#40a02b` (open), `#8839ef` (merged), `#d20f39` (closed)
  - Show PR number: `#123`
  - Add tooltip with PR title
  - Click PR badge opens PR URL in browser
  - Settings toggle to enable/disable GitHub integration

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/BranchManager.php:53-60` — Branch data mapping
  - `resources/views/livewire/branch-manager.blade.php` — Branch item rendering
  - `AGENTS.md` — Color system (green, mauve, red)

  **Acceptance Criteria**:
  - [ ] PR badge visible on branches with open/merged PRs
  - [ ] Badge color matches PR state
  - [ ] Clicking badge opens PR in browser
  - [ ] No badge shown when GitHub unavailable

  **QA Scenarios**:

  ```
  Scenario: PR badge shown on branch with open PR
    Tool: Playwright (playwright skill)
    Preconditions: Repo has branches with open GitHub PRs, gh CLI authenticated
    Steps:
      1. Navigate to app
      2. Open branch manager
      3. Find a branch with a known open PR
      4. Assert PR badge is visible with green color and PR number
      5. Hover over badge
      6. Assert tooltip shows PR title
    Expected Result: Green PR badge with number visible
    Evidence: .sisyphus/evidence/task-2-pr-badge.png
  ```

  **Commit**: YES
  - Message: `feat(header): add GitHub PR status indicators to branch manager`

- [ ] 3. Pest tests for GitHub service

  **What to do**:
  - Create `tests/Feature/Services/GitHubServiceTest.php`
  - Test: isAvailable, getPullRequestForBranch (with Process::fake), graceful failure
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Acceptance Criteria**:
  - [ ] All GitHub service tests pass

  **Commit**: YES
  - Message: `test(backend): add tests for GitHubService`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=GitHub  # Expected: all pass
```
