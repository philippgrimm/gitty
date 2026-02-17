# QoL: Branch Prefill, Commit Prefill & Commit History

## TL;DR

> **Quick Summary**: Add three quality-of-life features — prefill `feature/` in branch creation, auto-generate `feat(TICKET):` / `fix(TICKET):` commit prefixes from the current branch name, and shell-style arrow-up/down to recall recent commit messages.
>
> **Deliverables**:
> - Branch creation modal prefills `feature/` with cursor at end
> - Commit message auto-prefills `feat(JIRA-123): ` or `fix(JIRA-456): ` based on current branch
> - Arrow-up/down in commit textarea cycles through last 10 commit messages (shell-style)
> - Pest tests for all three features
>
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 (independent) | Task 2 → Task 3 (sequential on CommitPanel)

---

## Context

### Original Request
"When I create a new branch the field should be prefilled with `feature/`. When I commit, the field is prefilled with `feat(<ticket_nr>):` for `feature/` branches and `fix(<ticket_nr>):` for `bugfix/` branches. When I hit the up arrow key in the commit field it goes to the history of my last commit messages so that I can re-use them."

### Interview Summary
**Key Discussions**:
- Branch naming convention: `feature/JIRA-123-description` format (uppercase prefix + number)
- History navigation: shell-style (arrow-up = older, arrow-down = newer, ESC = restore draft)
- Post-commit behavior: auto re-prefill `feat(TICKET): ` after committing (not empty)

**Research Findings**:
- `CommitPanel` already demonstrates programmatic `$message` setting via `toggleAmend()`
- `GitService::log(limit)` returns `Collection<Commit>` — ready to use for history
- `GitService::currentBranch()` returns branch name string
- Alpine.js `@keydown` patterns already established in `app-layout.blade.php`
- `CommitPanel` already listens to `status-updated` event (fires on branch switch)

### Metis Review
**Identified Gaps** (addressed):
- Cursor position after prefill: needs Alpine.js `$nextTick` with `setSelectionRange` — included in plan
- Multiline commit messages in history: use first line only — included in plan
- Amend mode interaction: toggling amend OFF restores auto-prefill — included in plan
- Edge cases (detached HEAD, empty repo, no ticket match): graceful fallback to empty message — included in plan
- Draft save/restore on history navigation: save current text on first arrow-up, ESC restores — included in plan

---

## Work Objectives

### Core Objective
Add three conveniences that reduce repetitive typing during the branch→commit workflow.

### Concrete Deliverables
- Modified `BranchManager` component: prefills `feature/` on modal open
- Modified `CommitPanel` component: auto-prefill from branch name + history navigation
- Modified `commit-panel.blade.php`: Alpine.js keyboard handlers for arrow-up/down/ESC
- Pest tests covering all features and edge cases

### Definition of Done
- [ ] Creating a branch shows `feature/` prefilled in the name input
- [ ] On a `feature/JIRA-123-desc` branch, commit textarea shows `feat(JIRA-123): `
- [ ] On a `bugfix/PROJ-456-desc` branch, commit textarea shows `fix(PROJ-456): `
- [ ] On `main`, `develop`, or other branches, commit textarea is empty
- [ ] After committing, textarea re-prefills with the correct prefix
- [ ] Arrow-up in commit textarea shows the previous commit message
- [ ] Arrow-down navigates back to newer messages
- [ ] ESC restores the message the user was typing before browsing history
- [ ] `php artisan test --compact` passes all new tests

### Must Have
- `feature/` prefill when branch creation modal opens
- Ticket number extraction from `feature/TICKET-NR-*` and `bugfix/TICKET-NR-*` branches
- Commit prefix mapping: `feature/` → `feat()`, `bugfix/` → `fix()`
- Re-prefill after successful commit
- Shell-style history navigation (up/down/ESC)
- Draft preservation when browsing history

### Must NOT Have (Guardrails)
- No settings UI or config file for the ticket regex — hardcode for now
- No support for branch types beyond `feature/` and `bugfix/` (no `hotfix/`, `chore/`, etc.)
- No commit message format validation or enforcement — prefill is a convenience, not a rule
- No persistent history storage (database, localStorage, session) — use git log only
- No fuzzy search (Ctrl+R) for commit history
- No visual indicator when browsing history — ESC-to-restore is sufficient
- No dropdown for branch prefix selection (`feature/`, `bugfix/`) in the create modal
- Do NOT modify `StagingPanel`, `GitService`, or `BranchService` — read-only usage only

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.

### Test Decision
- **Infrastructure exists**: YES (Pest)
- **Automated tests**: YES (tests-after)
- **Framework**: Pest via `php artisan test --compact`

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

Verification uses `Livewire::test()` assertions in Pest tests plus Playwright for end-to-end UI verification where keyboard interaction is involved.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
├── Task 1: Branch creation prefill (BranchManager — independent component)
└── Task 2: Commit message auto-prefill (CommitPanel — base feature)

Wave 2 (After Wave 1):
└── Task 3: Commit history navigation (CommitPanel — builds on Task 2's changes)

Critical Path: Task 2 → Task 3
Parallel Speedup: ~30% faster than sequential
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | None | 2 |
| 2 | None | 3 | 1 |
| 3 | 2 | None | None |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Agents |
|------|-------|-------------------|
| 1 | 1, 2 | task(category="quick") for Task 1, task(category="unspecified-low") for Task 2 |
| 2 | 3 | task(category="unspecified-high") for Task 3 (Alpine.js complexity) |

---

## TODOs

- [x] 1. Branch Creation Prefill

  **What to do**:
  - Add an `openCreateModal()` method to `BranchManager` that sets `$this->newBranchName = 'feature/'` and `$this->showCreateModal = true`
  - Update the Blade template to call this method instead of directly setting `$showCreateModal`
  - Find every trigger that opens the create modal (button click in the Blade template + `handlePaletteCreateBranch` event) and route them through `openCreateModal()`
  - **Important**: `handlePaletteCreateBranch()` currently receives a `$name` and immediately calls `createBranch()` — it should NOT go through `openCreateModal()`. Only the modal-opening triggers should prefill.
  - After branch creation succeeds, `$newBranchName` is already reset to `''` (line 100) — this is correct
  - Write Pest test: assert `$newBranchName === 'feature/'` after opening modal

  **Must NOT do**:
  - Do NOT add a prefix dropdown or selector
  - Do NOT change `handlePaletteCreateBranch()` — it creates branches without showing the modal
  - Do NOT modify `createBranch()` logic

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single-component change, ~10 lines of code + simple test
  - **Skills**: [`livewire-development`, `pest-testing`]
    - `livewire-development`: Livewire component method addition and Blade template wiring
    - `pest-testing`: Writing Livewire::test() assertions
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: Modal already exists, no new Flux components needed
    - `tailwindcss-development`: No styling changes

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Task 2)
  - **Blocks**: None
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Livewire/BranchManager.php:91-107` — `createBranch()` method showing how `$showCreateModal` and `$newBranchName` are managed. Follow this pattern for the new `openCreateModal()` method.
  - `app/Livewire/BranchManager.php:109-115` — `handlePaletteCreateBranch()` — do NOT modify this. It bypasses the modal entirely.

  **Template References** (UI to update):
  - `resources/views/livewire/branch-manager.blade.php:194-231` — The create modal markup. The trigger button that sets `$showCreateModal = true` needs to call `openCreateModal()` instead. Find the button/link in the template that opens this modal (look for `wire:click` that sets `showCreateModal`).

  **Acceptance Criteria**:

  **Pest Tests:**
  - [ ] Test: `Livewire::test(BranchManager::class)->call('openCreateModal')` → `assertSet('newBranchName', 'feature/')`
  - [ ] Test: `Livewire::test(BranchManager::class)->call('openCreateModal')` → `assertSet('showCreateModal', true)`
  - [ ] `php artisan test --compact --filter=BranchManager` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Branch creation modal opens with feature/ prefilled
    Tool: Playwright (playwright skill)
    Preconditions: App running via `php artisan native:serve`, repo loaded
    Steps:
      1. Navigate to app URL (use get-absolute-url tool)
      2. Click the branch dropdown trigger in the header (the element showing current branch name)
      3. Wait for dropdown menu visible (timeout: 5s)
      4. Click the "New Branch" or "Create Branch" menu item
      5. Wait for modal visible (flux:modal with heading "Create New Branch")
      6. Assert: input[wire\:model="newBranchName"] value equals "feature/"
      7. Assert: input is focused with cursor at position 8 (after "feature/")
      8. Screenshot: .sisyphus/evidence/task-1-branch-prefill.png
    Expected Result: Modal shows with "feature/" prefilled in branch name input
    Evidence: .sisyphus/evidence/task-1-branch-prefill.png
  ```

  **Commit**: YES (groups with Task 2)
  - Message: `feat(staging): add branch creation prefill and commit message auto-prefill`
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`, `tests/Feature/Livewire/BranchManagerTest.php`

---

- [x] 2. Commit Message Auto-Prefill from Branch Name

  **What to do**:
  - Add a `getCommitPrefill(): string` method to `CommitPanel` that:
    1. Gets the current branch name via `(new GitService($this->repoPath))->currentBranch()`
    2. Matches against regex `/^(feature|bugfix)\/([A-Z]+-\d+)/`
    3. Maps: `feature` → `feat`, `bugfix` → `fix`
    4. Returns `"feat(JIRA-123): "` or `"fix(PROJ-456): "` (note the trailing space after colon)
    5. Returns `''` if no match (e.g., `main`, `develop`, detached HEAD)
  - Call `getCommitPrefill()` in `mount()` to set initial `$this->message`
  - After successful `commit()` (line 71 where `$this->message = ''`), replace with `$this->message = $this->getCommitPrefill()`
  - After successful `commitAndPush()` (line 92), same replacement
  - In the `refreshStagedCount()` method (the `status-updated` listener), add a call to re-prefill ONLY if `$this->message` is empty or equals the current prefill (don't overwrite user-typed text)
  - When `toggleAmend()` turns amend OFF (line 109 where `$this->message = ''`), replace with `$this->message = $this->getCommitPrefill()`
  - Store the current prefill as a property `public string $currentPrefill = ''` so you can check whether the user has modified the message beyond the prefill
  - In the Blade template, use Alpine.js `$nextTick` to position the cursor at the end of the prefilled text after Livewire updates the message

  **Must NOT do**:
  - Do NOT validate or enforce the commit message format
  - Do NOT support `hotfix/`, `chore/`, or other branch types
  - Do NOT make the regex configurable
  - Do NOT overwrite user-typed text — only prefill when message is empty or matches current prefill exactly

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Single-component changes with regex logic, moderate complexity
  - **Skills**: [`livewire-development`, `pest-testing`]
    - `livewire-development`: Livewire component methods, event listeners, property management
    - `pest-testing`: Writing Livewire::test() with mocked services
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No styling changes
    - `fluxui-development`: No new Flux components

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Task 1)
  - **Blocks**: Task 3
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Livewire/CommitPanel.php:101-111` — `toggleAmend()` demonstrates the exact pattern of programmatically setting `$this->message`. Follow this pattern for auto-prefill.
  - `app/Livewire/CommitPanel.php:54-78` — `commit()` method, specifically line 71 where `$this->message = ''`. Replace this with `$this->message = $this->getCommitPrefill()`.
  - `app/Livewire/CommitPanel.php:36-40` — `refreshStagedCount()` method (the `status-updated` listener). This is where you add re-prefill logic on branch switch.

  **Service References** (APIs to call):
  - `app/Services/Git/GitService.php:78-83` — `currentBranch()` method. Returns the branch name as a string. Use this to parse the ticket number.

  **Template References** (cursor positioning):
  - `resources/views/livewire/commit-panel.blade.php:14-21` — The `<flux:textarea>` element. Add Alpine.js cursor positioning here via a Livewire hook or `x-effect`.

  **Acceptance Criteria**:

  **Pest Tests:**
  - [ ] Test: On `feature/JIRA-123-add-login` branch → `$message === 'feat(JIRA-123): '`
  - [ ] Test: On `bugfix/PROJ-456-fix-crash` branch → `$message === 'fix(PROJ-456): '`
  - [ ] Test: On `main` branch → `$message === ''`
  - [ ] Test: On detached HEAD → `$message === ''`
  - [ ] Test: On `feature/no-ticket-here` branch → `$message === ''`
  - [ ] Test: After `commit()` on feature branch → `$message === 'feat(JIRA-123): '` (re-prefill)
  - [ ] Test: After `toggleAmend()` OFF → `$message === 'feat(JIRA-123): '` (restore prefill)
  - [ ] `php artisan test --compact --filter=CommitPanel` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Commit textarea prefills feat(TICKET) on feature branch
    Tool: Playwright (playwright skill)
    Preconditions: App running, repo on branch `feature/TEST-99-something`
    Steps:
      1. Navigate to app URL
      2. Wait for commit panel visible (timeout: 5s)
      3. Read textarea[wire\:model.live.debounce\.300ms="message"] value
      4. Assert: value equals "feat(TEST-99): "
      5. Assert: cursor position is at end of text (position 15)
      6. Screenshot: .sisyphus/evidence/task-2-prefill-feature.png
    Expected Result: Textarea shows "feat(TEST-99): " with cursor at end
    Evidence: .sisyphus/evidence/task-2-prefill-feature.png

  Scenario: Commit textarea is empty on main branch
    Tool: Playwright (playwright skill)
    Preconditions: App running, repo on branch `main`
    Steps:
      1. Navigate to app URL
      2. Wait for commit panel visible (timeout: 5s)
      3. Read textarea value
      4. Assert: value is empty string
      5. Screenshot: .sisyphus/evidence/task-2-no-prefill-main.png
    Expected Result: Textarea is empty on main branch
    Evidence: .sisyphus/evidence/task-2-no-prefill-main.png

  Scenario: After commit, textarea re-prefills
    Tool: Playwright (playwright skill)
    Preconditions: App running, repo on `feature/TEST-99-something`, files staged
    Steps:
      1. Navigate to app URL
      2. Fill commit textarea with "feat(TEST-99): add login page"
      3. Click commit button (or press ⌘↵)
      4. Wait for commit flash animation to finish (200ms)
      5. Read textarea value
      6. Assert: value equals "feat(TEST-99): " (re-prefilled, not empty)
      7. Screenshot: .sisyphus/evidence/task-2-reprefill-after-commit.png
    Expected Result: Textarea re-prefills after successful commit
    Evidence: .sisyphus/evidence/task-2-reprefill-after-commit.png
  ```

  **Commit**: YES (groups with Task 1)
  - Message: `feat(staging): add branch creation prefill and commit message auto-prefill`
  - Files: `app/Livewire/CommitPanel.php`, `resources/views/livewire/commit-panel.blade.php`, `tests/Feature/Livewire/CommitPanelTest.php`

---

- [x] 3. Commit Message History Navigation (Arrow-Up/Down)

  **What to do**:

  **Livewire Side (CommitPanel.php):**
  - Add property `public array $commitHistory = []` — stores recent commit messages (strings)
  - Add method `loadCommitHistory(): void` that calls `(new GitService($this->repoPath))->log(10)`, extracts first line of each message (`Str::before($message, "\n")`), and stores in `$this->commitHistory`
  - Call `loadCommitHistory()` in `mount()`
  - After successful `commit()` and `commitAndPush()`, call `loadCommitHistory()` to refresh (the new commit should now appear in history)
  - In `refreshStagedCount()` (status-updated listener), call `loadCommitHistory()` to refresh on branch switch

  **Alpine.js Side (commit-panel.blade.php):**
  - Expand the `x-data` object to include: `historyIndex: -1, draft: '', browsingHistory: false`
  - `historyIndex: -1` means "not browsing history, showing draft/current message"
  - `historyIndex: 0` = most recent commit, `1` = second most recent, etc.
  - Add `@keydown.arrow-up.prevent` handler on the `<flux:textarea>`:
    1. If `!browsingHistory`: save current textarea value to `draft`, set `browsingHistory = true`
    2. If `historyIndex < history.length - 1`: increment `historyIndex`
    3. Set `$wire.message` to `history[historyIndex]` via `$wire.set('message', ...)`
    4. Update `charCount`
  - Add `@keydown.arrow-down.prevent` handler:
    1. If `historyIndex > 0`: decrement `historyIndex`, set message to `history[historyIndex]`
    2. If `historyIndex === 0`: decrement to `-1`, restore `draft`, set `browsingHistory = false`
    3. Update `charCount`
  - Add `@keydown.escape` handler (extend existing if any):
    1. If `browsingHistory`: restore `draft`, reset `historyIndex = -1`, set `browsingHistory = false`
    2. Update `charCount`
  - On any regular `@input` event (user typing): reset `historyIndex = -1`, set `browsingHistory = false` (user has taken over)
  - Wire `commitHistory` from Livewire to Alpine via `$wire.commitHistory` or `@entangle`

  **Must NOT do**:
  - Do NOT persist history to database, session, or localStorage
  - Do NOT add fuzzy search (Ctrl+R)
  - Do NOT add a visual indicator (background color change, counter label)
  - Do NOT filter out merge commits or any other commit types
  - Do NOT add a dropdown/popover to show the history list

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Complex Alpine.js state management with keyboard handlers, Livewire↔Alpine interplay
  - **Skills**: [`livewire-development`, `pest-testing`]
    - `livewire-development`: Livewire component methods, properties, Alpine.js integration patterns
    - `pest-testing`: Testing Livewire components and verifying state changes
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No styling changes
    - `fluxui-development`: No new Flux components

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (sequential)
  - **Blocks**: None
  - **Blocked By**: Task 2 (both modify CommitPanel.php)

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Livewire/CommitPanel.php:101-111` — `toggleAmend()` demonstrates programmatic `$message` setting. History navigation follows the same pattern.
  - `app/Livewire/CommitPanel.php:27-34` — `mount()` method. Add `loadCommitHistory()` call here.
  - `resources/views/livewire/commit-panel.blade.php:2-4` — Existing Alpine.js `x-data` block. Extend this with history state variables.
  - `resources/views/livewire/commit-panel.blade.php:16` — `x-on:input` handler for charCount. This is where to reset history index when user types.

  **Service References** (APIs to call):
  - `app/Services/Git/GitService.php:41-61` — `log(int $limit)` method. Call with `limit: 10` to get recent commits.
  - `app/DTOs/Commit.php` — Commit DTO with `->message` property. Use `Str::before($commit->message, "\n")` for first line.

  **Alpine.js Pattern References** (keyboard handling):
  - `resources/views/livewire/app-layout.blade.php:3-12` — Global keyboard shortcut pattern using `@keydown.window`. The history handlers should be scoped to the textarea (NOT window), using `@keydown.arrow-up.prevent` directly on the element.

  **Acceptance Criteria**:

  **Pest Tests:**
  - [ ] Test: `loadCommitHistory()` populates `$commitHistory` with recent messages
  - [ ] Test: `$commitHistory` contains max 10 entries
  - [ ] Test: After `commit()`, `$commitHistory` is refreshed (new commit appears)
  - [ ] Test: `$commitHistory` uses first line only (no multiline messages)
  - [ ] `php artisan test --compact --filter=CommitPanel` → PASS

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Arrow-up recalls previous commit message
    Tool: Playwright (playwright skill)
    Preconditions: App running, repo with at least 3 previous commits
    Steps:
      1. Navigate to app URL
      2. Wait for commit panel visible (timeout: 5s)
      3. Click on commit textarea to focus it
      4. Press ArrowUp key
      5. Wait 300ms for Livewire debounce
      6. Read textarea value
      7. Assert: value equals the most recent commit message from git log
      8. Press ArrowUp again
      9. Wait 300ms
      10. Read textarea value
      11. Assert: value equals the second most recent commit message
      12. Screenshot: .sisyphus/evidence/task-3-history-arrow-up.png
    Expected Result: Each arrow-up shows an older commit message
    Evidence: .sisyphus/evidence/task-3-history-arrow-up.png

  Scenario: Arrow-down navigates back to newer messages
    Tool: Playwright (playwright skill)
    Preconditions: App running, user has pressed arrow-up twice (showing 2nd oldest message)
    Steps:
      1. (Continuing from arrow-up scenario)
      2. Press ArrowDown key
      3. Wait 300ms
      4. Read textarea value
      5. Assert: value equals the most recent commit message
      6. Press ArrowDown again
      7. Wait 300ms
      8. Read textarea value
      9. Assert: value equals the original draft (what user was typing before)
      10. Screenshot: .sisyphus/evidence/task-3-history-arrow-down.png
    Expected Result: Arrow-down navigates from older to newer, ending at draft
    Evidence: .sisyphus/evidence/task-3-history-arrow-down.png

  Scenario: ESC restores draft message
    Tool: Playwright (playwright skill)
    Preconditions: App running, textarea has user-typed text
    Steps:
      1. Navigate to app URL
      2. Click textarea, type "my work in progress"
      3. Press ArrowUp (should save draft and show history)
      4. Wait 300ms
      5. Assert: textarea value is NOT "my work in progress"
      6. Press Escape
      7. Wait 300ms
      8. Read textarea value
      9. Assert: value equals "my work in progress" (draft restored)
      10. Screenshot: .sisyphus/evidence/task-3-history-esc-restore.png
    Expected Result: ESC restores the message user was typing before browsing history
    Evidence: .sisyphus/evidence/task-3-history-esc-restore.png

  Scenario: Typing resets history browsing
    Tool: Playwright (playwright skill)
    Preconditions: App running, user browsing history (arrow-up pressed)
    Steps:
      1. Press ArrowUp to enter history mode
      2. Wait 300ms
      3. Clear textarea and type "new message"
      4. Press ArrowUp
      5. Wait 300ms
      6. Read textarea value
      7. Assert: value equals the most recent commit (history index reset to 0, not continuing from previous position)
      8. Screenshot: .sisyphus/evidence/task-3-history-typing-reset.png
    Expected Result: Typing resets history index so next arrow-up starts from most recent
    Evidence: .sisyphus/evidence/task-3-history-typing-reset.png

  Scenario: Arrow-up at end of history stays on oldest message
    Tool: Playwright (playwright skill)
    Preconditions: App running, repo with exactly 3 commits
    Steps:
      1. Press ArrowUp 3 times (reach oldest)
      2. Read textarea value → oldest commit message
      3. Press ArrowUp again (4th time)
      4. Read textarea value
      5. Assert: value still equals oldest commit message (no wrap, no error)
      6. Screenshot: .sisyphus/evidence/task-3-history-bounds.png
    Expected Result: Stays at oldest message, does not wrap or error
    Evidence: .sisyphus/evidence/task-3-history-bounds.png
  ```

  **Commit**: YES
  - Message: `feat(staging): add commit message history navigation with arrow keys`
  - Files: `app/Livewire/CommitPanel.php`, `resources/views/livewire/commit-panel.blade.php`, `tests/Feature/Livewire/CommitPanelTest.php`

---

## Commit Strategy

| After Task(s) | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1, 2 | `feat(staging): add branch creation prefill and commit message auto-prefill` | BranchManager.php, CommitPanel.php, blade templates, tests | `php artisan test --compact --filter=BranchManager\|CommitPanel` |
| 3 | `feat(staging): add commit message history navigation with arrow keys` | CommitPanel.php, commit-panel.blade.php, tests | `php artisan test --compact --filter=CommitPanel` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact --filter=BranchManager  # Expected: all tests pass
php artisan test --compact --filter=CommitPanel     # Expected: all tests pass
vendor/bin/pint --dirty --format agent              # Expected: no formatting issues
```

### Final Checklist
- [ ] Branch creation modal shows `feature/` prefilled
- [ ] Commit prefill works for `feature/` and `bugfix/` branches with ticket numbers
- [ ] Commit prefill is empty for branches without tickets
- [ ] After commit, message re-prefills automatically
- [ ] Arrow-up/down cycles through commit history
- [ ] ESC restores draft message
- [ ] All existing tests still pass
- [ ] No changes to `StagingPanel`, `GitService`, or `BranchService`
- [ ] Pint formatting passes
