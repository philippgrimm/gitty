# Gitty — Learnings

## Conventions
- Laravel project with NativePHP Desktop v2 (Electron)
- TDD with Pest (test-first, RED-GREEN-REFACTOR)
- Git operations via shell out to git CLI (Laravel Process facade)
- gitonomy/gitlib for structured data (log, diff, blame parsing)
- Shiki-php for syntax highlighting in diffs
- Flux UI (free) components + custom Livewire/Alpine.js components
- Single-window mode, SQLite for app settings + recent repos
- macOS only for MVP

## Architecture Patterns
- Service layer: GitService, StagingService, CommitService, BranchService, RemoteService, StashService, DiffService
- DTOs: GitStatus, Commit, Branch, Remote, Stash, DiffResult, DiffFile, Hunk, HunkLine, MergeResult
- GitOperationQueue with mutex (one git op at a time per repo)
- Livewire components: StagingPanel, CommitPanel, DiffViewer, BranchManager, SyncPanel, StashPanel, RepoSidebar, RepoSwitcher, SettingsModal
- Polling: wire:poll.visible (staging 3s, sidebar 10s, auto-fetch indicator 30s)

## Project Scaffolding - Feb 12, 2026

### NativePHP Desktop v2 Setup
- NativePHP v2.1.0 installed successfully via `composer require nativephp/desktop`
- Native installer partially failed due to TTY mode requirement in automated environment
- Workaround: Manually published config/provider with `php artisan vendor:publish --provider="Native\Desktop\NativeServiceProvider"`
- Electron installed via `npm install --save-dev electron`
- Added `native:dev` script to composer.json for development
- NativeAppServiceProvider configured with window settings (1200x800, min 900x600) and comprehensive menu bar

### Menu API Pattern
- NativePHP v2 uses `Menu::make()` to create menus with submenu items
- Menu items created with `Menu::label('text')->hotkey('key')->event('event-name')`
- Separators: `Menu::separator()`
- Menu structure: `Menu::create(Menu::app(), Menu::make(...)->label('Menu Name'))`
- Hotkeys use format: 'CmdOrCtrl+Key' for cross-platform shortcuts

### Livewire v4.1.4 Integration
- Livewire includes Alpine.js bundled - no separate installation needed
- Layout requires `@livewireStyles` in head and `@livewireScripts` before closing body
- Use `{{ $slot }}` in layouts for component-based rendering (not `@yield`)

### Flux UI v2.12.0 Setup
- Flux UI free version installed via `composer require livewire/flux`
- Requires `@fluxAppearance` in head for dark mode handling
- Requires `@fluxScripts` before closing body tag
- Default dark mode support with `.dark` class management
- Pre-styled with Tailwind CSS (bg-white dark:bg-zinc-900)

### Database Configuration
- SQLite configured by default in Laravel 12
- Database file auto-created at `database/database.sqlite`
- Migrations for repositories (path unique, name, last_opened_at) and settings (key unique, value nullable) created
- All migrations ran successfully in batch 2

### Package Versions Locked
- gitonomy/gitlib: 1.6.0
- spatie/shiki-php: 2.3.3
- nativephp/desktop: 2.1.0
- livewire/livewire: 4.1.4
- livewire/flux: 2.12.0

### Project Structure
- Laravel 12.51.0 base
- PHP 8.4.17, Composer 2.9.2
- Node v24.13.0, npm 11.6.2
- SQLite as primary database
- .sisyphus folder preserved during scaffolding


## Git Service Layer Implementation - Feb 12, 2026

### TDD Approach Success
- Followed strict TDD: wrote all 50 tests FIRST, then implemented services
- All tests passed on first full run after implementation
- Test coverage: 50 service tests across 8 test files (GitService: 9, StagingService: 7, CommitService: 5, BranchService: 7, RemoteService: 6, StashService: 7, DiffService: 6, GitOperationQueue: 3)

### Service Architecture
- All services validate .git directory in constructor
- All services use Laravel Process facade for git CLI commands
- Process::fake() works perfectly for mocking git output in tests
- Used /tmp/gitty-test-repo/.git for test directory (created once, reused across all tests)

### DTO Patterns
- Readonly classes with public properties
- Static factory methods: fromOutput(), fromArray(), fromLogLine(), etc.
- GitStatus parses porcelain v2 format (complex but well-documented)
- DiffResult/DiffFile/Hunk/HunkLine hierarchy for diff parsing
- Collections used for multi-item results (commits, branches, remotes, stashes)

### Git Porcelain v2 Format
- Header lines start with `# branch.`
- Changed files: `1 XY ...` (ordinary), `2 XY ...` (renamed/copied), `u XY ...` (unmerged)
- XY status: first char = index (staged), second char = worktree (unstaged)
- Status chars: M=modified, A=added, D=deleted, R=renamed, C=copied, .=unmodified, ?=untracked, U=unmerged

### Diff Parsing
- Unified diff format: `diff --git`, `---`, `+++`, `@@ ... @@`
- Hunk parsing with line numbers (oldLineNum, newLineNum)
- Shiki-php for syntax highlighting with fallback to plain HTML
- Patch generation for hunk staging/unstaging

### GitOperationQueue
- Cache::lock() for mutex (30 second timeout)
- Prevents concurrent git operations on same repo
- isLocked() checks lock status without blocking

### Testing Patterns
- Process::fake() with array mapping commands to outputs
- GitOutputFixtures provides realistic git CLI output samples
- Process::assertRan() for command verification
- Temporary .git directory for validation tests

### Files Created
- 10 DTOs: GitStatus, Commit, Branch, Remote, Stash, DiffResult, DiffFile, Hunk, HunkLine, MergeResult
- 8 Services: GitService, StagingService, CommitService, BranchService, RemoteService, StashService, DiffService, GitOperationQueue
- 1 Validator: GitConfigValidator
- 1 Exception: GitOperationInProgressException
- 8 Test files with 50 total tests

### Key Learnings
- TDD with Process::fake() is extremely effective for git operations
- Porcelain v2 format is verbose but provides all needed metadata
- Readonly DTOs with factory methods keep data immutable and parsing logic centralized
- Cache locks work well for preventing concurrent git operations
- Shiki-php handles syntax highlighting gracefully with try/catch fallback

## Staging Panel Livewire Component - Feb 12, 2026

### TDD Success
- Wrote 11 tests FIRST, then implemented component and view
- All tests passed on first run after implementation
- Test coverage: mount, file separation, stage/unstage/discard operations, events, empty state, refresh

### Livewire 4 Patterns
- `mount()` initializes component properties (replaces __construct for Livewire)
- `wire:poll.3s.visible="refreshStatus"` polls only when component is visible
- `$this->dispatch('event-name', param: $value)` dispatches events to other components
- `wire:click.stop` prevents event bubbling to parent elements
- Alpine.js bundled with Livewire - use `x-data`, `x-show`, `@click` directives
- `x-model` for two-way binding with Alpine state

### Flux UI Components
- `<flux:badge variant="solid" color="green">` for status indicators
- `<flux:button variant="ghost" size="sm">` for subtle action buttons
- `<flux:modal x-model="showModal">` for confirmation dialogs
- `<flux:tooltip :content="$variable">` for hover tooltips
- Colors: zinc, red, orange, amber, yellow, lime, green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia, pink, rose
- Variants: default, solid, ghost, subtle, primary, filled, danger

### Polling Debounce Pattern
- After user action (stage/unstage/discard), pause polling for 5s to prevent UI flash
- Use Alpine.js timer: `setTimeout(() => { $wire.resumePolling(); }, 5000);`
- Component property `pausePolling` flag to control polling behavior
- Dispatch `status-updated` event after operations to trigger timer

### Git Status File Separation Logic
- Untracked: `indexStatus === '?' && worktreeStatus === '?'`
- Unstaged: `worktreeStatus !== '.'` (has worktree changes)
- Staged: `indexStatus !== '.' && indexStatus !== '?'` (has index changes, not untracked)
- Files can appear in BOTH unstaged and staged (e.g., MM status)

### Design Aesthetic - Brutalist/Industrial
- Monospace font (font-mono) for developer tool feel
- Dark theme: bg-zinc-950, text-zinc-100, borders zinc-800
- Sharp edges, high contrast, geometric precision
- Uppercase tracking-widest headers for industrial feel
- Status icons with semantic colors: M=yellow ●, A=green +, D=red −, R=blue →, U=orange U, ?=green ?
- Hover states: opacity transitions, color shifts
- Empty state: large checkmark icon with uppercase label

### Test Patterns
- Shared `/tmp/gitty-test-repo/.git` directory across all tests
- Create in `beforeEach` if missing, but DON'T clean up in `afterEach` (shared resource)
- `Livewire::test(Component::class, ['prop' => 'value'])` for component testing
- `->assertSet('prop', 'value')` to verify property values
- `->assertSee('text')` to verify rendered output
- `->assertDispatched('event-name', param: 'value')` to verify events
- `->call('methodName', 'param')` to invoke component methods
- `Process::fake()` works perfectly with Livewire components

### Files Created
- `app/Livewire/StagingPanel.php` - Livewire component with file separation logic
- `resources/views/livewire/staging-panel.blade.php` - Brutalist UI with Flux components
- `tests/Feature/Livewire/StagingPanelTest.php` - 11 comprehensive tests

### Key Learnings
- Livewire 4 + Flux UI + Alpine.js is a powerful stack for reactive UIs
- TDD with Livewire::test() is extremely effective
- Polling with visibility detection prevents unnecessary server requests
- Debouncing after user actions prevents UI flash during rapid updates
- Git status parsing requires careful handling of index vs worktree status
- Shared test resources (like .git directory) should not be cleaned up between tests

## Commit Panel Livewire Component - Feb 12, 2026

### TDD Success
- Wrote 10 tests FIRST, then implemented component and view
- All tests passed on first run after implementation
- Test coverage: mount, staged count, commit, commit+push, amend, toggle amend, error handling, empty message validation

### Flux UI Dropdown Component
- `<flux:dropdown position="top">` positions dropdown above trigger (useful for bottom panels)
- `<flux:button square icon:trailing="chevron-up">` creates icon-only button for dropdown trigger
- `<flux:menu>` contains menu items with icons
- `<flux:menu.item wire:click="method" icon="name">` for action items

### Flux UI Textarea Component
- `<flux:textarea rows="auto">` enables auto-resizing based on content
- `resize="vertical"` allows vertical resizing only
- `wire:model.live="message"` for real-time updates

### Flux UI Checkbox Component
- `<flux:checkbox wire:click="method" :checked="$variable">` for controlled checkbox
- `label` attribute for inline label text

### Git Commit Error Handling
- `Process::run()` does NOT throw exceptions on failure by default
- Must check `$result->exitCode() !== 0` and throw manually
- `$result->errorOutput()` contains stderr output for error messages
- This pattern applies to all git commands that can fail

### Livewire Events
- `#[On('event-name')]` attribute for listening to events from other components
- `$this->dispatch('event-name')` to dispatch events after operations
- Listen for `status-updated` from StagingPanel to refresh staged count

### Commit Panel State Management
- `$message` - commit message input (cleared after successful commit)
- `$isAmend` - toggle for amend mode (cleared after commit)
- `$stagedCount` - number of staged files (auto-refreshed from status)
- `$error` - error message display (cleared before each operation)
- Message NOT cleared on failure (user can retry)

### Disabled Button Logic
- Button disabled when: `$stagedCount === 0 || empty(trim($message))`
- Use PHP logic in Blade: `:disabled="$stagedCount === 0 || empty(trim($message))"`
- Both primary button and dropdown trigger disabled with same logic

### Process::fake() Patterns
- Use closure for exit codes: `'command' => function() { return Process::result('output', exitCode: 1); }`
- Cannot use `Process::result('', 1)` directly (doesn't set exit code properly)
- Closure pattern required for testing failure scenarios

### Design Consistency
- Match StagingPanel aesthetic: bg-zinc-950, border-zinc-800, font-mono
- Uppercase tracking-wider for buttons and labels
- Character count displayed in zinc-500 monospace
- Error messages: bg-red-950, border-red-800, text-red-200, uppercase
- Border-t-2 to separate from staging panel above

### Files Created
- `app/Livewire/CommitPanel.php` - Livewire component with commit/amend/commitAndPush
- `resources/views/livewire/commit-panel.blade.php` - Brutalist UI with Flux components
- `tests/Feature/Livewire/CommitPanelTest.php` - 10 comprehensive tests

### Key Learnings
- TDD with Livewire::test() continues to be extremely effective
- Process error handling requires explicit exit code checks and exceptions
- Flux UI dropdown positioning (top/bottom) is configurable
- Auto-resizing textarea improves UX for commit messages
- Event-driven architecture keeps components loosely coupled
- Disabled state logic prevents invalid operations at UI level

## Branch Manager Livewire Component - Feb 12, 2026

### TDD Success
- Wrote 10 tests FIRST, then implemented component and view
- All tests passed on first run after implementation
- Test coverage: mount, display current branch, switch, create, delete, prevent delete current, merge success, merge conflicts, detached HEAD, refresh

### Livewire 4 DTO Serialization Issue
- **CRITICAL**: Livewire 4 cannot serialize readonly DTOs directly in public properties
- Error: "Property type not supported in Livewire for property: [{"name":"main",...}]"
- **Solution**: Convert DTOs to arrays before storing in Livewire properties
- Pattern: `$this->branches = $branchService->branches()->map(fn($b) => ['name' => $b->name, ...])->toArray();`
- Use `collect($branches)` in Blade when filtering/mapping arrays

### Branch Management Patterns
- Display current branch prominently with ahead/behind badges (↑N green, ↓N red)
- Prevent deleting current branch (check before calling BranchService)
- Merge conflict detection: `MergeResult::hasConflicts` with `conflictFiles` list
- Error message display: `"Merge conflicts detected in: {$conflictList}"`
- Detached HEAD warning banner with "Create branch here" button

### Flux UI Components Used
- `<flux:dropdown position="left">` for branch action menus
- `<flux:menu>` with `<flux:menu.item>`, `<flux:menu.separator>`
- `<flux:modal wire:model="showCreateModal">` for create branch dialog
- `<flux:select>` with `<flux:select.option>` (NOT `<flux:option>`)
- `<flux:field>`, `<flux:label>`, `<flux:input>` for form fields

### VS Code-Style Branch Picker Design
- Current branch at top with large bold text and badges
- Local branches separated from remote branches
- Current branch marked with green ✓ checkmark
- Remote branches displayed as italic/dimmed with "remotes/" prefix cleaned
- Branch actions in dropdown menu (switch, merge, delete) on hover
- Create branch button in header with "+" icon

### Filtering Local vs Remote Branches
```php
$localBranches = collect($branches)->filter(fn($b) => !$b['isRemote'] && !str_contains($b['name'], 'remotes/'));
$remoteBranches = collect($branches)->filter(fn($b) => $b['isRemote'] || str_contains($b['name'], 'remotes/'));
```

### Flux Select Component Correct Usage
- Correct: `<flux:select.option value="{{ $value }}">Label</flux:select.option>`
- WRONG: `<flux:option>` (component doesn't exist)
- Must use `flux:select.option` not `flux:option`

### Error Handling Pattern
- Clear error before each operation: `$this->error = '';`
- Set error for validation failures: `$this->error = 'Cannot delete the current branch';`
- Return early after validation error (prevent operation from running)
- Display error at top of component: `@if($error)` with red background

### Event Dispatching
- Dispatch `status-updated` after branch operations (switch, create, delete, merge)
- Allows other components (StagingPanel, CommitPanel) to refresh their state
- Pattern: `$this->dispatch('status-updated');` after `$this->refreshBranches();`

### Polling Pattern
- `wire:poll.5s.visible="refreshBranches"` on root element
- Slower polling (5s) for branch manager vs staging panel (3s)
- Only polls when component is visible
- Ensures branches list stays current with background git operations

### Design Consistency
- Monospace font (font-mono) throughout
- Zinc-950 background, zinc-800 borders, zinc-100 text
- Uppercase tracking-widest headers for brutalist feel
- Hover opacity transitions on action buttons
- Color coding: green for success/current, red for danger/behind, orange for warnings

### Files Created
- `app/Livewire/BranchManager.php` - Livewire component with branch operations
- `resources/views/livewire/branch-manager.blade.php` - VS Code-style branch picker UI
- `tests/Feature/Livewire/BranchManagerTest.php` - 10 comprehensive tests

### Key Learnings
- TDD with Livewire::test() continues to be extremely effective
- Readonly DTOs must be converted to arrays for Livewire properties
- Flux UI component names matter: `flux:select.option` not `flux:option`
- Branch operations require careful validation (e.g., can't delete current branch)
- Merge conflicts are handled gracefully with MergeResult DTO
- VS Code-style branch picker is intuitive and familiar to developers
- Clear separation of local vs remote branches improves UX

## Stash Panel Livewire Component - Feb 12, 2026

### TDD Success
- Wrote 10 tests FIRST, then implemented component and view
- All tests passed on first run after implementation
- Test coverage: mount, empty state, create with/without untracked, apply, pop, drop, DTO conversion, refresh, error clearing
- All 94 tests now passing (84 existing + 10 new StashPanel tests)

### Livewire 4 DTO-to-Array Pattern (CRITICAL)
- Readonly DTOs MUST be converted to arrays for Livewire public properties
- Pattern: `$this->stashes = $service->stashList()->map(fn($s) => ['index' => $s->index, ...])->toArray();`
- Same pattern used in BranchManager - consistent across all components
- Use `@foreach($stashes as $stash)` in Blade (arrays work fine)

### Stash Display Pattern
- Stash DTO parses `stash@{0}: WIP on main: a1b2c3d feat: add new feature` into:
  - index: 0
  - branch: "main"
  - message: "feat: add new feature" (cleaned, no "WIP on" prefix)
  - sha: "a1b2c3d"
- Display pattern: badge for stash@{N}, badge for branch, message as main text, SHA as dimmed footer
- Tests should assert on PARSED message, not full stash line format

### Flux UI Components Used
- `<flux:modal wire:model="showCreateModal">` - standard modal for create stash
- `<flux:modal x-model="confirmDropIndex !== null">` - Alpine.js-controlled modal for confirmation
- `<flux:tooltip content="...">` - hover tooltips for icon buttons
- `<flux:badge variant="solid" color="zinc">` - for stash index
- `<flux:badge variant="subtle" color="blue">` - for branch name
- `<flux:checkbox wire:model="includeUntracked">` - for untracked files toggle
- `<flux:button variant="danger">` - for destructive drop action

### Alpine.js Confirmation Pattern
- Use `x-data="{ confirmDropIndex: null }"` on root element
- Drop button: `@click="confirmDropIndex = {{ $stash['index'] }}"`
- Confirmation modal: `x-model="confirmDropIndex !== null"`
- Confirm button: `@click="$wire.dropStash(confirmDropIndex); confirmDropIndex = null"`
- Cancel button: `@click="confirmDropIndex = null"`
- Display value in modal: `<span x-text="confirmDropIndex"></span>`
- This pattern avoids needing separate modal state in Livewire component

### Stash Actions UX
- **Apply**: Downloads stash to working tree, keeps stash in list (icon: arrow-down-tray)
- **Pop**: Applies stash AND removes from list (icon: arrow-down-circle)
- **Drop**: Deletes stash permanently with confirmation (icon: trash)
- All action buttons in icon-only format (square buttons)
- Actions revealed on hover: `opacity-0 group-hover:opacity-100`
- Apply/Pop dispatch `status-updated` event (change working tree)
- Drop also dispatches `status-updated` (changes stash list)

### Create Stash Modal
- Message input (required - button disabled if empty)
- "Include untracked files" checkbox
- Modal closes after successful stash creation
- Message field cleared after creation
- includeUntracked reset to false after creation

### Polling Pattern
- `wire:poll.5s.visible="refreshStashes"` on root element
- Slower polling (5s) for stash panel vs staging panel (3s)
- Only polls when component visible
- Ensures stash list stays current

### Error Handling
- Clear `$this->error = ''` before each operation
- Error display at top: `@if($error)` with red background
- Error banner: bg-red-950, border-red-800, text-red-200, uppercase tracking-wider

### Design Consistency
- Monospace font (font-mono) throughout
- Zinc-950 background, zinc-800 borders, zinc-100 text
- Uppercase tracking-widest headers for brutalist feel
- Empty state: large checkmark icon with uppercase label
- Hover opacity transitions on action buttons
- Color coding: zinc for stash index, blue for branch, red for danger actions

### Files Created
- `app/Livewire/StashPanel.php` - Livewire component with stash operations
- `resources/views/livewire/stash-panel.blade.php` - Brutalist UI with confirmation dialog
- `tests/Feature/Livewire/StashPanelTest.php` - 10 comprehensive tests

### Key Learnings
- TDD with Livewire::test() continues to be extremely effective
- Alpine.js confirmation pattern is cleaner than multiple Livewire modal states
- Stash DTO parsing extracts clean message from full stash line format
- Tests should assert on parsed data, not raw git output format
- Icon-only buttons with tooltips provide clean, space-efficient action UI
- Drop confirmation prevents accidental data loss (UX best practice)
- Consistent DTO-to-array conversion pattern across all Livewire components

## Diff Viewer Livewire Component - Feb 12, 2026

### TDD Success
- Wrote 8 tests FIRST, then implemented component and view
- All tests passed after fixing Livewire root element constraint
- Test coverage: mount, load unstaged/staged diffs, empty state, binary files, event listening, status badges, Shiki rendering
- All 102 tests now passing (94 existing + 8 new DiffViewer tests)

### Livewire 4 Single Root Element Constraint
- **CRITICAL**: Livewire 4 requires exactly ONE root element per component
- `<style>` tags count as separate root elements if placed outside the main div
- **Solution**: Move `<style>` tag INSIDE the root div (as first child)
- Pattern: `<div class="root">` → `<style>...</style>` → content → `</div>`
- This allows component-scoped styles without violating single-root constraint

### DiffViewer Component Architecture
- Properties: `$repoPath`, `$file`, `$isStaged`, `$diffData` (array), `$renderedHtml`, `$isEmpty`, `$isBinary`
- Listens to `file-selected` event from StagingPanel with `#[On('file-selected')]`
- Uses `GitService::diff($file, $staged)` to get raw diff
- Converts DiffFile DTO to array for Livewire serialization (same pattern as BranchManager/StashPanel)
- Uses `DiffService::renderDiffHtml()` for Shiki-highlighted HTML output
- Empty state handling: no file selected vs file with no changes

### Diff Rendering with Shiki
- DiffService::renderDiffHtml() already handles Shiki highlighting with try/catch fallback
- Rendered HTML uses semantic CSS classes: `.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context`
- Line numbers displayed in gutter with `.line-number` class
- Hunk headers styled as separators with uppercase tracking
- Component-scoped styles override DiffService defaults for brutalist aesthetic

### Brutalist Diff Viewer Design
- Empty states with large geometric symbols: ⊘ (no file), ∅ (no changes), ⬢ (binary)
- Header: filename + status badge (MODIFIED/ADDED/DELETED) + +N/-N stats
- Diff body: monospace font, line numbers in gutter, color-coded backgrounds
- Addition lines: green-950/30 background, green-400 line numbers, green-100 text
- Deletion lines: red-950/30 background, red-400 line numbers, red-100 text
- Context lines: zinc-950 background, zinc-600 line numbers, zinc-300 text
- Hunk headers: zinc-900 background, uppercase tracking-wider, dimmed text
- Hover states: opacity transitions on all line types
- Sticky header for filename/stats when scrolling

### CSS Styling Patterns
- Component-scoped styles using Tailwind @apply directives
- `.diff-container` wraps rendered HTML from DiffService
- Hide `.diff-file-header` (already shown in component header)
- Line structure: flex layout with fixed-width line numbers + flexible content
- `whitespace-pre-wrap` + `break-all` for long lines
- `select-none` on line numbers to prevent selection
- Transparent backgrounds for `<pre>` and `<code>` tags from Shiki

### Test Patterns
- Process::fake() with diff fixtures (diffUnstaged, diffStaged)
- Test empty state, binary files, event listening, status badges
- Verify `renderedHtml` is populated after loadDiff()
- Assert on visible content (filename, stats, diff content)
- Use `->assertSet()` to verify component properties
- Binary diff fixture: "Binary files a/image.png and b/image.png differ"

### Files Created
- `app/Livewire/DiffViewer.php` - Livewire component with file-selected listener
- `resources/views/livewire/diff-viewer.blade.php` - Brutalist UI with Shiki rendering
- `tests/Feature/Livewire/DiffViewerTest.php` - 8 comprehensive tests

### Key Learnings
- TDD with Livewire::test() continues to be extremely effective
- Livewire 4 single-root constraint requires `<style>` inside root div
- DiffService::renderDiffHtml() provides Shiki highlighting out of the box
- Component-scoped styles can override service-generated HTML classes
- Empty states with geometric symbols create strong visual hierarchy
- Sticky headers improve UX for long diffs
- DTO-to-array conversion pattern consistent across all Livewire components
- Brutalist design with monospace fonts and high contrast works well for code diffs

## Sync Panel Livewire Component - Feb 12, 2026

### TDD Success
- Wrote 11 tests FIRST, then implemented component and view
- All tests passed after fixing error handling and output trimming
- Test coverage: mount, push/pull/fetch/fetchAll/forcePushWithLease operations, error handling, detached HEAD prevention, operation output storage
- All 113 tests now passing (102 existing + 11 new SyncPanel tests)

### Livewire Method Name Conflicts (CRITICAL)
- **CRITICAL**: Livewire\Component has a `pull()` method that conflicts with custom methods
- Error: "Method 'App\Livewire\SyncPanel::pull()' is not compatible with method 'Livewire\Component::pull()'"
- **Solution**: Prefix all sync operation methods to avoid conflicts: `syncPush()`, `syncPull()`, `syncFetch()`, `syncFetchAll()`, `syncForcePushWithLease()`
- This pattern prevents naming collisions with Livewire's internal methods

### Git Process Error Handling Pattern
- Process::result() in tests can set exitCode but may not populate errorOutput()
- **Pattern**: Use `trim($result->errorOutput() ?: $result->output())` to fall back to stdout when stderr is empty
- Always trim output to remove trailing newlines: `trim($result->output())`
- This ensures error messages are captured correctly in both real usage and tests

### Sync Panel Component Architecture
- Properties: `$repoPath`, `$isOperationRunning` (bool), `$operationOutput` (string), `$error` (string), `$lastOperation` (string)
- Uses GitService::status() to get current branch and detect detached HEAD state
- Calls Process directly (not RemoteService) to capture output and errors without throwing exceptions
- Sets `$isOperationRunning` flag during operations to disable buttons
- Stores operation output in `$operationOutput` for collapsible log display
- Dispatches `status-updated` event after successful operations

### Detached HEAD Prevention
- Check `GitService::isDetachedHead()` before push/pull/forcePush operations
- Fetch operations work fine in detached HEAD (no branch needed)
- Error message: "Cannot push from detached HEAD state"
- This prevents confusing errors from git commands that require a branch

### Brutalist Sync Panel Design
- Vertical button layout with uppercase tracking-wider labels and semantic icons
- Push ↑ (amber), Pull ↓ (cyan), Fetch ↻ (green), Fetch All ⇄ (green)
- Force Push ⚠ (orange) separated by border-top with confirmation modal
- Custom CSS classes: `.sync-button`, `.sync-button-push`, `.sync-button-pull`, `.sync-button-fetch`
- Loading spinner (⟳) displayed inline during operation with `animate-spin`
- Operation log: collapsible `<pre>` block with scrollable max-height, Alpine.js toggle
- Confirmation modal for force push with `--force-with-lease` explanation

### Alpine.js State Management
- `x-data="{ showOutputLog: false, confirmForcePush: false }"` for local UI state
- Toggle log visibility with `@click="showOutputLog = !showOutputLog"`
- Confirmation modal triggered by `@click="confirmForcePush = true"`
- Button disabled state with `:disabled="$wire.isOperationRunning"` (Alpine + Livewire)
- Conditional rendering of spinner based on `lastOperation` and `isOperationRunning`

### Force Push Safety
- Uses `git push --force-with-lease` instead of bare `--force`
- Requires confirmation modal with warning message explaining `--force-with-lease`
- Modal heading in orange (warning color) with uppercase tracking
- Subheading explains safety mechanism: "prevents overwriting others' work"
- This UX pattern prevents accidental destructive pushes

### Operation Output Log Pattern
- Display output log only when `$operationOutput && !$isOperationRunning`
- Collapsible toggle button with arrow indicator (▶ collapsed, ▼ expanded)
- Pre-formatted text with `whitespace-pre-wrap break-words` for long lines
- Max height with overflow-y-auto for long outputs
- Zinc-900 background matching other code displays
- x-transition for smooth expand/collapse animation

### Test Patterns for Process Mocking
- Mock both success and failure scenarios with different exit codes
- Use `Process::result('output', exitCode: 1)` for failures
- Test error message format: "Operation failed: error text"
- Test operation output storage with exact string matching
- Test `isOperationRunning` flag is false after operation completes
- Test detached HEAD prevention for push/pull but not fetch

### Files Created
- `app/Livewire/SyncPanel.php` - Livewire component with sync operations
- `resources/views/livewire/sync-panel.blade.php` - Brutalist UI with confirmation modal
- `tests/Feature/Livewire/SyncPanelTest.php` - 11 comprehensive tests

### Key Learnings
- TDD with Livewire::test() continues to be extremely effective
- Livewire method name conflicts require careful naming (use prefixes)
- Process error handling needs fallback when errorOutput() is empty
- Detached HEAD checks prevent confusing error messages
- Force push requires extra safety (confirmation + --force-with-lease)
- Collapsible operation log improves UX without cluttering UI
- Alpine.js local state management works well for modals and toggles
- Custom CSS classes improve maintainability of component styles
