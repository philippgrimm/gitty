# Branch Merge via Right-Click Context Menu

## TL;DR

> **Quick Summary**: Surface the existing merge backend through a right-click context menu on branches in the dropdown, and add a success toast type so users get feedback when merge completes.
> 
> **Deliverables**:
> - Right-click context menu on local, non-current branches (Switch, Merge, Delete)
> - Success toast type added to ErrorBanner (green, check-circle icon)
> - Merge action dispatches success toast on successful merge
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES - 2 waves
> **Critical Path**: Task 1 → Task 3 (Task 2 runs in parallel with Task 1)

---

## Context

### Original Request
User wants to merge one branch into another using the app. After discussion, the workflow is: "Merge into current branch" — user is on branch A, picks branch B from the dropdown to merge INTO A.

### Interview Summary
**Key Discussions**:
- **Trigger**: Right-click context menu on branches (not hover icons, not a separate button)
- **Confirmation**: None — merge executes immediately on click
- **Merge strategy**: Default `git merge` only — no --no-ff, --squash, --ff-only
- **Conflict handling**: Show warning toast with conflicted file list (already implemented)
- **Existing interactions**: Left-click to switch + hover trash icon STAY. Context menu is additive.
- **Success feedback**: Add a success toast for successful merges (new toast type needed)
- **Testing**: TDD with Pest (Red-Green-Refactor)

**Research Findings**:
- **Merge backend already exists**: `BranchService::mergeBranch()`, `MergeResult` DTO, `BranchManager::mergeBranch()` Livewire action — all functional
- **Merge tests already exist**: Both `BranchServiceTest.php` and `BranchManagerTest.php` cover merge success and conflict scenarios
- **ErrorBanner supports**: `error`, `warning`, `info` types — but NOT `success`
- **Context menu approach**: Alpine.js `@contextmenu.prevent` with positioned div. Flux `<flux:menu>` is tied to popover triggers and can't be cursor-positioned, so use a custom Alpine context menu styled to match Flux menus visually.

### Metis Review
**Identified Gaps** (addressed):
- Context menu positioning → At cursor position (standard OS behavior)
- Context menu dismissal → Click outside, Esc, action click
- Success toast duration → Same 5s as other toasts (consistent)
- Context menu on current branch → Don't show
- Electron compatibility → Use `@contextmenu.prevent` which suppresses native menu; works in Electron
- Multiple toasts → ErrorBanner replaces previous toast (existing single-instance behavior)

---

## Work Objectives

### Core Objective
Add a right-click context menu to local branches in the branch dropdown, with "Merge into [current branch]" as the key new action, and provide success feedback via a new toast type.

### Concrete Deliverables
- Modified `app/Livewire/ErrorBanner.php` — support `success` type
- Modified `resources/views/livewire/error-banner.blade.php` — green styling + check icon for success
- Modified `app/Livewire/BranchManager.php` — dispatch success toast after merge
- Modified `resources/views/livewire/branch-manager.blade.php` — right-click context menu
- New/updated tests in `tests/Feature/Livewire/ErrorBannerTest.php`
- New/updated tests in `tests/Feature/Livewire/BranchManagerTest.php`

### Definition of Done
- [ ] Right-clicking a local, non-current branch shows context menu with Switch/Merge/Delete
- [ ] Right-clicking current branch does NOT show context menu
- [ ] Right-clicking remote branches does NOT show context menu
- [ ] Clicking "Merge into [current]" calls existing `mergeBranch()` and shows success toast
- [ ] Success toast renders with green color and check icon
- [ ] All existing tests still pass
- [ ] New tests pass: `php artisan test --compact --filter=ErrorBanner`
- [ ] New tests pass: `php artisan test --compact --filter=BranchManager`

### Must Have
- Right-click context menu on local, non-current branches
- Context menu items: "Switch to Branch", "Merge into {currentBranch}", "Delete Branch"
- Success toast type in ErrorBanner (green `#40a02b`, check-circle icon)
- Success dispatch from `mergeBranch()` on non-conflict merge
- Context menu closes on: click outside, Esc key, action click
- Context menu positioned at cursor coordinates

### Must NOT Have (Guardrails)
- NO merge strategy options (--no-ff, --squash, --ff-only)
- NO confirmation modal before merge
- NO conflict resolution UI or abort merge button
- NO context menu on remote branches or current branch
- NO keyboard shortcuts for context menu actions
- NO new Livewire components (extend existing ErrorBanner, not a new SuccessBanner)
- NO custom CSS classes or animations beyond existing patterns
- NO Flux badge components for context menu (use plain styled div)
- NO merge preview or merge commit message customization
- NO toast notification center or toast history
- NO context menu on commit list, file list, or other non-branch elements

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks are verifiable WITHOUT any human action.
> ALL verification is executed by the agent using tools.

### Test Decision
- **Infrastructure exists**: YES (Pest 4 + Livewire::test + Process::fake)
- **Automated tests**: YES (TDD — Red-Green-Refactor)
- **Framework**: Pest 4
- **Existing test patterns**: `tests/Feature/Livewire/ErrorBannerTest.php`, `tests/Feature/Livewire/BranchManagerTest.php`
- **Test fixtures**: `tests/Mocks/GitOutputFixtures.php`

### TDD Structure

Each TODO follows RED-GREEN-REFACTOR:

**Task Structure:**
1. **RED**: Write failing test first
   - Test command: `php artisan test --compact --filter=TestName`
   - Expected: FAIL (test exists, implementation doesn't)
2. **GREEN**: Implement minimum code to pass
   - Command: `php artisan test --compact --filter=TestName`
   - Expected: PASS
3. **REFACTOR**: Clean up while keeping green
   - Command: `php artisan test --compact --filter=TestName`
   - Expected: PASS (still)

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

**Verification Tool by Deliverable Type:**

| Type | Tool | How Agent Verifies |
|------|------|-------------------|
| **Livewire component** | Bash (Pest) | `php artisan test --compact --filter=TestName` |
| **Blade view rendering** | Bash (Pest + assertSee) | Livewire::test assertions |
| **Full integration** | Playwright (playwright skill) | Open app in browser, right-click branch, verify menu |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
├── Task 1: Add success toast type to ErrorBanner
└── Task 2: Add right-click context menu to branch dropdown

Wave 2 (After Wave 1):
└── Task 3: Wire merge success feedback into BranchManager

Critical Path: Task 1 → Task 3
Parallel Speedup: ~33% faster than sequential
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | 3 | 2 |
| 2 | None | None | 1 |
| 3 | 1 | None | None (final) |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Agents |
|------|-------|-------------------|
| 1 | 1, 2 | task(category="quick", ...) for Task 1; task(category="visual-engineering", ...) for Task 2 |
| 2 | 3 | task(category="quick", ...) |

---

## TODOs

- [x] 1. Add success toast type to ErrorBanner

  **What to do**:
  - **RED**: Add test `'component shows success message'` to `ErrorBannerTest.php` — dispatch `show-error` with `type: 'success'`, assert component renders with success styling
  - **RED**: Add test `'component renders success icon'` — assert success type renders the check-circle icon
  - **GREEN**: In `ErrorBanner.php`: no changes needed (the component already accepts any `type` string)
  - **GREEN**: In `error-banner.blade.php`:
    - Add `success` case to the Alpine `:class` binding for border colors: `'border-[#40a02b]/30 border-l-[#40a02b]': '{{ $type }}' === 'success'`
    - Add `@if($type === 'success')` block with green circle icon (`bg-[#40a02b]`) containing a checkmark SVG
    - Add `'text-[#40a02b]': '{{ $type }}' === 'success'` to the title color binding
    - Add `@elseif($type === 'success') Success` to the title text
  - **REFACTOR**: Verify all existing tests still pass

  **Must NOT do**:
  - Do NOT create a new SuccessBanner or ToastManager component
  - Do NOT change the existing error/warning/info behavior
  - Do NOT add new CSS classes or animation keyframes
  - Do NOT change the auto-dismiss timer (keep 5s)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small, focused change to one component — extend existing type mapping with one new type
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Writing Pest tests for the ErrorBanner Livewire component
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not needed — the PHP component doesn't change, only the Blade view
    - `tailwindcss-development`: Not needed — colors are inline hex values matching Catppuccin palette, not Tailwind utilities

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Task 2)
  - **Blocks**: Task 3
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References** (existing code to follow):
  - `resources/views/livewire/error-banner.blade.php:21-24` — Alpine `:class` binding for border colors per type (add `success` alongside `error`/`warning`/`info`)
  - `resources/views/livewire/error-banner.blade.php:30-48` — Icon rendering per type with colored circle + SVG (follow exact same pattern for success)
  - `resources/views/livewire/error-banner.blade.php:53-58` — Title text color per type (add success case)
  - `resources/views/livewire/error-banner.blade.php:60-66` — Title label per type (add `@elseif($type === 'success') Success`)
  - `app/Livewire/ErrorBanner.php:21-27` — `showError()` method accepts any type string, no PHP changes needed

  **Test References** (testing patterns to follow):
  - `tests/Feature/Livewire/ErrorBannerTest.php:26-31` — Test pattern for `'component shows warning message'`: dispatch event, assert type and assertSee. Follow exact same structure for success.
  - `tests/Feature/Livewire/ErrorBannerTest.php:33-38` — Test pattern for `'component shows info message'`: identical structure, swap type string

  **Documentation References**:
  - `AGENTS.md:Semantic Colors` — Catppuccin green is `#40a02b` (use this for success background/text)

  **Acceptance Criteria**:

  **TDD Tests:**
  - [ ] Test file updated: `tests/Feature/Livewire/ErrorBannerTest.php`
  - [ ] Test covers: success type renders with correct message
  - [ ] Test covers: success type renders with correct title text "Success"
  - [ ] `php artisan test --compact --filter=ErrorBanner` → PASS (all tests including new ones)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Success toast renders with green styling
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component shows success message"
      2. Assert: Test passes (exit code 0)
    Expected Result: Test passes confirming success type renders correctly
    Evidence: Test output captured

  Scenario: All existing ErrorBanner tests still pass
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter=ErrorBanner
      2. Assert: All tests pass (0 failures)
      3. Assert: Test count is >= 9 (7 existing + 2 new)
    Expected Result: Zero regressions
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `feat(panels): add success toast type to ErrorBanner`
  - Files: `app/Livewire/ErrorBanner.php` (if changed), `resources/views/livewire/error-banner.blade.php`, `tests/Feature/Livewire/ErrorBannerTest.php`
  - Pre-commit: `php artisan test --compact --filter=ErrorBanner`

---

- [x] 2. Add right-click context menu to branch dropdown

  **What to do**:
  - **RED**: Add test `'component renders context menu trigger on non-current local branches'` to `BranchManagerTest.php` — assert Blade output contains `@contextmenu` or `x-on:contextmenu` attribute on local branch items that are NOT current
  - **RED**: Add test `'component does not render context menu on current branch'` — assert current branch item does NOT have context menu trigger
  - **RED**: Add test `'component does not render context menu on remote branches'` — assert remote branch items do NOT have context menu trigger
  - **GREEN**: In `branch-manager.blade.php`:
    - Add Alpine.js `x-data` state to the branch list container (or extend existing `x-data`): `contextMenu: { show: false, branch: '', x: 0, y: 0 }`
    - On each local, non-current branch `<div>`: add `@contextmenu.prevent="contextMenu = { show: true, branch: '{{ $branch['name'] }}', x: $event.clientX, y: $event.clientY }"`
    - Add a positioned context menu div (rendered once, outside the branch loop):
      ```
      <div x-show="contextMenu.show"
           x-transition
           @click.away="contextMenu.show = false"
           @keydown.escape.window="contextMenu.show = false"
           :style="`position: fixed; left: ${contextMenu.x}px; top: ${contextMenu.y}px; z-index: 50;`"
           class="bg-white border border-[#ccd0da] rounded-lg shadow-lg py-1 min-w-[200px]"
      >
      ```
    - Context menu items (styled to match Flux menu items):
      - "Switch to Branch" — calls `$wire.switchBranch(contextMenu.branch)` + closes dropdown + closes context menu
      - "Merge into {currentBranch}" — calls `$wire.mergeBranch(contextMenu.branch)` + closes dropdown + closes context menu
      - A visual separator (thin border line)
      - "Delete Branch" — calls `$wire.deleteBranch(contextMenu.branch)` + closes dropdown + closes context menu (uses red text like existing trash icon color: `text-[var(--color-red)]` on hover)
    - Each menu item: `px-3 py-1.5 text-sm cursor-pointer hover:bg-[#eff1f5] transition-colors text-[var(--text-secondary)]`
    - Close context menu after any action: `contextMenu.show = false`
    - Close context menu when clicking a branch (left-click switch): add `@click="contextMenu.show = false"` alongside existing `wire:click`
  - **REFACTOR**: Ensure visual consistency with existing Flux menus

  **Must NOT do**:
  - Do NOT use `<flux:menu>` or `<flux:dropdown>` for the context menu (they are popover-based and can't be cursor-positioned)
  - Do NOT add context menu to remote branches
  - Do NOT add context menu to current branch
  - Do NOT add keyboard shortcuts (⌘M, etc.)
  - Do NOT remove the existing hover trash icon or left-click switch behavior
  - Do NOT add ARIA attributes beyond what's naturally present
  - Do NOT add merge strategy options in the menu
  - Do NOT add a confirmation step before any action

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: This is primarily a frontend/UI task — building a context menu with proper positioning, styling, and Alpine.js interactivity
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `pest-testing`]
    - `livewire-development`: The context menu interacts with Livewire component methods via `$wire` calls
    - `tailwindcss-development`: Styling the context menu to match existing Flux menu visual language using Tailwind utilities + Catppuccin colors
    - `pest-testing`: Writing TDD Pest tests for the Livewire component behavior
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: Evaluated but omitted because we're NOT using Flux menu components for the context menu (can't cursor-position them)

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Task 1)
  - **Blocks**: None
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References** (existing code to follow):
  - `resources/views/livewire/branch-manager.blade.php:81-111` — Local branch item rendering: each branch is a `<div>` with `wire:click="switchBranch"`, hover trash icon. Add `@contextmenu.prevent` alongside existing handlers.
  - `resources/views/livewire/branch-manager.blade.php:32-56` — Existing Alpine.js `x-data` in the branch list: manages `activeIndex`, `items[]`, keyboard navigation. Extend this `x-data` with `contextMenu` state (don't create a separate `x-data`).
  - `resources/views/livewire/branch-manager.blade.php:83-84` — Branch item styling: `group flex items-center justify-between px-3 py-1.5 transition-colors cursor-pointer` with `:class` for active/hover. Context menu items should use similar padding and text styling.
  - `resources/views/livewire/branch-manager.blade.php:100-107` — Hover action button pattern (trash icon with `wire:click.stop`): opacity-0 → group-hover:opacity-100 transition. Context menu actions use `$wire` calls the same way.
  - `resources/views/livewire/branch-manager.blade.php:87` — Dropdown close pattern: `x-on:click="$el.closest('[popover]')?.hidePopover()"` — reuse this to close the parent dropdown after context menu actions.

  **API/Type References**:
  - `app/Livewire/BranchManager.php:71-85` — `switchBranch(string $name)` method signature and behavior
  - `app/Livewire/BranchManager.php:128-148` — `mergeBranch(string $name)` method signature and behavior
  - `app/Livewire/BranchManager.php:105-126` — `deleteBranch(string $name)` method signature and behavior

  **Test References** (testing patterns to follow):
  - `tests/Feature/Livewire/BranchManagerTest.php:17-30` — Component mount test pattern: Process::fake with fixtures, Livewire::test, assertSee. Use same structure for asserting context menu markup.
  - `tests/Feature/Livewire/BranchManagerTest.php:44-56` — Action test pattern: Process::fake, call method, assertDispatched. Follow for merge action through context menu.
  - `tests/Mocks/GitOutputFixtures.php:229-241` — `branchListVerbose()` fixture: provides branches `main` (current), `feature/new-ui`, `feature/api-improvement`, `bugfix/parser-issue`, plus remotes. Use this fixture in tests.

  **Documentation References**:
  - `AGENTS.md:Hover & Interaction States` — Hover on white backgrounds uses `hover:bg-[#eff1f5]` (Base). Apply to context menu items.
  - `AGENTS.md:Color System` — Text colors: primary `#4c4f69`, secondary `#6c6f85`, tertiary `#8c8fa1`. Border: `#ccd0da`. Red for delete: `#d20f39`.
  - `AGENTS.md:Dropdown Backgrounds` — White backgrounds for menus (`bg-white`), shadows, rounded corners.

  **Acceptance Criteria**:

  **TDD Tests:**
  - [ ] Test file updated: `tests/Feature/Livewire/BranchManagerTest.php`
  - [ ] Test covers: context menu trigger attribute exists on local non-current branches
  - [ ] Test covers: context menu trigger does NOT exist on current branch
  - [ ] Test covers: context menu trigger does NOT exist on remote branches
  - [ ] `php artisan test --compact --filter=BranchManager` → PASS (all tests including new ones)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Context menu renders on non-current local branches
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component renders context menu trigger on non-current local branches"
      2. Assert: Test passes (exit code 0)
    Expected Result: Blade output includes contextmenu handler on non-current branch items
    Evidence: Test output captured

  Scenario: Context menu does not render on current branch
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component does not render context menu on current branch"
      2. Assert: Test passes (exit code 0)
    Expected Result: Current branch item lacks contextmenu handler
    Evidence: Test output captured

  Scenario: Context menu does not render on remote branches
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component does not render context menu on remote branches"
      2. Assert: Test passes (exit code 0)
    Expected Result: Remote branch items lack contextmenu handler
    Evidence: Test output captured

  Scenario: All existing BranchManager tests still pass
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter=BranchManager
      2. Assert: All tests pass (0 failures)
    Expected Result: Zero regressions
    Evidence: Test output captured

  Scenario: Visual verification of context menu in browser
    Tool: Playwright (playwright skill)
    Preconditions: App running via `php artisan native:serve` or dev server on localhost:8321
    Steps:
      1. Navigate to the app URL
      2. Wait for branch dropdown trigger button to be visible (timeout: 10s)
      3. Click the branch dropdown trigger button (flux:button with git-branch icon)
      4. Wait for dropdown menu to appear (timeout: 5s)
      5. Right-click on a non-current branch item (e.g., "feature/new-ui")
      6. Wait for context menu to appear (timeout: 3s)
      7. Assert: Context menu is visible with 3 items
      8. Assert: Menu contains text "Switch to Branch"
      9. Assert: Menu contains text "Merge into" (dynamic current branch name)
      10. Assert: Menu contains text "Delete Branch"
      11. Screenshot: .sisyphus/evidence/task-2-context-menu-visible.png
      12. Click "Merge into [current]" in context menu
      13. Wait for context menu to close (timeout: 3s)
      14. Screenshot: .sisyphus/evidence/task-2-merge-executed.png
    Expected Result: Context menu appears at cursor with correct items, merge executes
    Evidence: .sisyphus/evidence/task-2-context-menu-visible.png, .sisyphus/evidence/task-2-merge-executed.png
  ```

  **Commit**: YES
  - Message: `feat(header): add right-click context menu to branch dropdown`
  - Files: `resources/views/livewire/branch-manager.blade.php`, `tests/Feature/Livewire/BranchManagerTest.php`
  - Pre-commit: `php artisan test --compact --filter=BranchManager`

---

- [x] 3. Wire merge success feedback into BranchManager

  **What to do**:
  - **RED**: Add/update test `'component dispatches success toast on successful merge'` in `BranchManagerTest.php` — call `mergeBranch`, assert `show-error` event dispatched with `type: 'success'` and message containing the branch name
  - **RED**: Add test `'component does not dispatch success toast when merge has conflicts'` — mock conflicting merge, assert NO success dispatch (only warning dispatch)
  - **GREEN**: In `BranchManager.php` `mergeBranch()` method:
    - After the existing conflict check (`if ($mergeResult->hasConflicts)`), add an `else` block:
    - `$this->dispatch('show-error', message: "Merged {$name} into {$this->currentBranch}", type: 'success', persistent: false);`
  - **REFACTOR**: Verify all merge tests pass, including existing conflict test

  **Must NOT do**:
  - Do NOT change the existing conflict handling behavior
  - Do NOT add merge strategy parameters
  - Do NOT add confirmation before merge
  - Do NOT change the error dispatch for merge failures
  - Do NOT modify `BranchService::mergeBranch()` — only the Livewire component

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Tiny change — add one `else` block with a dispatch call and two tests
  - **Skills**: [`pest-testing`, `livewire-development`]
    - `pest-testing`: Writing TDD Pest tests for the success dispatch
    - `livewire-development`: Dispatching Livewire events correctly
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No styling changes in this task

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (sequential after Wave 1)
  - **Blocks**: None (final task)
  - **Blocked By**: Task 1 (success toast type must exist for the dispatch to render properly)

  **References**:

  **Pattern References** (existing code to follow):
  - `app/Livewire/BranchManager.php:128-148` — Existing `mergeBranch()` method: calls service, checks conflicts, dispatches warning. Add success dispatch in the `else` path after the conflict check on line 139.
  - `app/Livewire/BranchManager.php:67` — Error dispatch pattern: `$this->dispatch('show-error', message: $this->error, type: 'error', persistent: false)` — follow same signature with `type: 'success'`
  - `app/Livewire/BranchManager.php:142` — Conflict dispatch pattern: `$this->dispatch('show-error', message: $this->error, type: 'warning', persistent: true)` — success should use `persistent: false` so it auto-dismisses

  **Test References** (testing patterns to follow):
  - `tests/Feature/Livewire/BranchManagerTest.php:103-116` — Existing test `'component merges branch successfully'`: Process::fake with merge output, call mergeBranch, assertDispatched status-updated. Extend this test (or add new test) to also assertDispatched `show-error` with type `success`.
  - `tests/Feature/Livewire/BranchManagerTest.php:118-131` — Existing test `'component shows conflict warning when merge has conflicts'`: uses Process::result with CONFLICT output. Add parallel test asserting success is NOT dispatched when conflicts occur.

  **Acceptance Criteria**:

  **TDD Tests:**
  - [ ] Test file updated: `tests/Feature/Livewire/BranchManagerTest.php`
  - [ ] Test covers: successful merge dispatches `show-error` with `type: 'success'`
  - [ ] Test covers: merge with conflicts does NOT dispatch success (only warning)
  - [ ] `php artisan test --compact --filter=BranchManager` → PASS (all tests)

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Successful merge dispatches success toast
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component dispatches success toast on successful merge"
      2. Assert: Test passes (exit code 0)
    Expected Result: mergeBranch dispatches show-error with type='success' and message containing branch name
    Evidence: Test output captured

  Scenario: Conflicting merge does not dispatch success toast
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact --filter="component does not dispatch success toast when merge has conflicts"
      2. Assert: Test passes (exit code 0)
    Expected Result: Only warning toast dispatched, no success toast
    Evidence: Test output captured

  Scenario: Full test suite passes
    Tool: Bash (Pest)
    Preconditions: None
    Steps:
      1. Run: php artisan test --compact
      2. Assert: All tests pass (exit code 0, 0 failures)
    Expected Result: Zero regressions across entire test suite
    Evidence: Test output captured

  Scenario: End-to-end merge with success toast in browser
    Tool: Playwright (playwright skill)
    Preconditions: App running, repository with at least 2 branches
    Steps:
      1. Navigate to app URL
      2. Wait for branch dropdown trigger (timeout: 10s)
      3. Click branch dropdown trigger
      4. Wait for dropdown to appear (timeout: 5s)
      5. Right-click on a non-current branch
      6. Wait for context menu (timeout: 3s)
      7. Click "Merge into [current branch]"
      8. Wait for success toast to appear in bottom-right corner (timeout: 5s)
      9. Assert: Toast contains text "Merged" and the branch name
      10. Assert: Toast has green left border (border-l-[#40a02b])
      11. Screenshot: .sisyphus/evidence/task-3-success-toast.png
      12. Wait 6 seconds for auto-dismiss
      13. Assert: Toast is no longer visible
      14. Screenshot: .sisyphus/evidence/task-3-toast-dismissed.png
    Expected Result: Success toast appears with green styling, auto-dismisses after 5s
    Evidence: .sisyphus/evidence/task-3-success-toast.png, .sisyphus/evidence/task-3-toast-dismissed.png
  ```

  **Commit**: YES
  - Message: `feat(header): dispatch success toast on merge completion`
  - Files: `app/Livewire/BranchManager.php`, `tests/Feature/Livewire/BranchManagerTest.php`
  - Pre-commit: `php artisan test --compact --filter=BranchManager`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `feat(panels): add success toast type to ErrorBanner` | error-banner.blade.php, ErrorBannerTest.php | `php artisan test --compact --filter=ErrorBanner` |
| 2 | `feat(header): add right-click context menu to branch dropdown` | branch-manager.blade.php, BranchManagerTest.php | `php artisan test --compact --filter=BranchManager` |
| 3 | `feat(header): dispatch success toast on merge completion` | BranchManager.php, BranchManagerTest.php | `php artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact --filter=ErrorBanner  # Expected: All pass (9+ tests)
php artisan test --compact --filter=BranchManager  # Expected: All pass (12+ tests)
php artisan test --compact  # Expected: All pass (full suite, 0 failures)
```

### Final Checklist
- [ ] Right-click context menu appears on local non-current branches
- [ ] Context menu does NOT appear on current branch or remote branches
- [ ] "Merge into [current]" calls existing mergeBranch method
- [ ] Success toast shows green with check icon after successful merge
- [ ] Conflict warning toast still works (existing behavior preserved)
- [ ] Left-click switch and hover trash icon still work (existing behavior preserved)
- [ ] All tests pass: `php artisan test --compact`
- [ ] Code formatted: `vendor/bin/pint --dirty --format agent`
