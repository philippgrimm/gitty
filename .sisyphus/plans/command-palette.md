# Command Palette (⌘K / ⌘⇧P)

## TL;DR

> **Quick Summary**: Add a VS Code / Raycast-style command palette to gitty, opened via ⌘K or ⌘⇧P. Surfaces all app actions in a searchable flat list with keyboard shortcuts displayed. Supports an inline input mode for actions that currently need modals (starting with "Create Branch").
> 
> **Deliverables**:
> - New `CommandPalette` Livewire component (PHP + Blade)
> - Keyboard shortcut registration (⌘K + ⌘⇧P)
> - Command registry covering all ~20 app actions
> - Search/filter with keyboard navigation (↑↓↵ Esc)
> - Inline input mode for "Create Branch"
> - Keyboard shortcut badges on commands that have shortcuts
> - Pest feature tests for the component
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: NO — sequential (tight coupling between tasks)
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4 → Task 5 → Task 6

---

## Context

### Original Request
User wants a ⌘P-style command palette (like VS Code or Finder) that opens a searchable overlay listing all main app actions. The palette should also be reusable for interactions that currently require modals (e.g., "Create Branch"). Keyboard shortcuts for actions should be visible in the palette.

### Interview Summary
**Key Discussions**:
- **Shortcut**: Both ⌘K AND ⌘⇧P will open the palette (⌘K is the modern convention used by Linear/Slack/Raycast)
- **Input mode**: Inline in palette — when selecting an input-requiring command, the palette transforms to show a text field (like VS Code / Raycast)
- **Context awareness**: Always show ALL actions — disabled ones greyed out, never hidden
- **Grouping**: Flat list, no category headers. Search is the primary navigation method.
- **Branch creation inline**: Name-only field, defaults to current branch as base. Keep the existing full modal for advanced use.
- **Tests**: After implementation (Pest feature tests for the Livewire component)

### Research Findings
- **11 Livewire components** with event-driven communication via `$dispatch` and `#[On(...)]`
- **8 existing keyboard shortcuts** in `app-layout.blade.php` (⌘↵, ⌘⇧↵, ⌘⇧K, ⌘⇧U, ⌘⇧S, ⌘A, ⌘B, Esc)
- **BranchManager** has a reusable Alpine.js search + keyboard navigation pattern (lines 32-65 of `branch-manager.blade.php`)
- **ErrorBanner** is the best pattern for a global overlay component (mounted at root level in `app-layout.blade.php`)
- **Branch creation** currently uses a Flux modal with `wire:model="showCreateModal"` (BranchManager.php line 87-103)
- Existing Livewire events can be dispatched from any component — the palette can reuse the `keyboard-*` event pattern

### Metis Review
**Identified Gaps** (addressed):
- **State machine definition**: Added explicit state machine (CLOSED → SEARCH → INPUT) with all transitions
- **Disabled command behavior**: Defaults applied — not selectable, greyed out, no error feedback on click
- **Input mode error handling**: Show inline in palette, palette stays open on validation errors
- **⌘K while palette is open**: Toggle close (standard behavior)
- **Esc behavior**: In search mode → close palette; in input mode → return to search
- **Multi-input commands scope**: v1 = Create Branch only. Switch/Merge/Delete Branch via palette deferred to v2.
- **Modal conflicts**: Palette layers on top of everything (z-50), modals remain underneath
- **Empty search results**: Show "No commands found" message
- **Command execution feedback**: Palette closes immediately, errors go through existing ErrorBanner toast system

---

## Work Objectives

### Core Objective
Build a command palette overlay component that provides keyboard-first access to all app actions, with search filtering and inline input for "Create Branch".

### Concrete Deliverables
- `app/Livewire/CommandPalette.php` — Livewire component
- `resources/views/livewire/command-palette.blade.php` — Blade template
- Updated `resources/views/livewire/app-layout.blade.php` — keyboard shortcuts + mount point
- `tests/Feature/Livewire/CommandPaletteTest.php` — Pest feature tests

### Definition of Done
- [ ] ⌘K and ⌘⇧P both open the palette overlay
- [ ] All ~20 app actions appear in the flat list
- [ ] Typing filters the list by label and keywords
- [ ] ↑↓ navigates, ↵ executes, Esc closes
- [ ] Keyboard shortcuts display right-aligned (e.g., "⌘⇧K" next to "Stage All")
- [ ] "Create Branch" enters inline input mode (name field → Enter to create)
- [ ] Disabled actions appear greyed out and cannot be executed
- [ ] All Pest tests pass: `php artisan test --filter=CommandPalette --compact`
- [ ] `vendor/bin/pint --dirty --format agent` passes

### Must Have
- Search/filter working across all commands
- Keyboard navigation (↑↓↵ Esc)
- All existing shortcutted actions show their shortcut badge
- Inline input mode for "Create Branch"
- Toggle behavior: ⌘K while open = close

### Must NOT Have (Guardrails)
- NO category headers or grouped sections (user chose flat list)
- NO multi-input commands in v1 (no dropdowns, no multi-step flows for Merge/Delete/Switch Branch)
- NO custom modal patterns — use Alpine.js x-show/x-transition (not Flux Modal) since this is a custom overlay, not a standard dialog
- NO new CSS files — all styles in Blade template using Tailwind + hardcoded Catppuccin hex values
- NO JavaScript framework (React/Vue) — pure Alpine.js + Livewire
- NO context-aware hiding — all commands always visible (disabled ones greyed out)
- DO NOT modify existing component behavior — only dispatch events to them
- DO NOT break existing keyboard shortcuts — the palette shortcuts (⌘K, ⌘⇧P) must not conflict

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.

### Test Decision
- **Infrastructure exists**: YES (46 test files, Pest framework, Process::fake pattern, Livewire::test pattern)
- **Automated tests**: Tests-after (implementation first, then Pest feature tests)
- **Framework**: Pest v4 with `php artisan test --compact`

### State Machine Definition

```
States:
  CLOSED   — Palette not visible
  SEARCH   — Palette open, user searching/browsing commands
  INPUT    — Palette open, collecting text input for a command (e.g., branch name)

Transitions:
  CLOSED → SEARCH    : ⌘K pressed, OR ⌘⇧P pressed
  SEARCH → CLOSED    : Esc pressed, OR command executed (no input needed), OR ⌘K pressed (toggle)
  SEARCH → INPUT     : User selects a command that requires input (e.g., "Create Branch")
  INPUT  → SEARCH    : Esc pressed (discard input, return to command list)
  INPUT  → CLOSED    : Command submitted successfully, OR ⌘K pressed (toggle)
  INPUT  → INPUT     : Validation error (stays in input mode, shows error)

Invariants:
  - Only ONE state active at a time
  - ⌘K always toggles (any open state → CLOSED)
  - Esc is context-sensitive (INPUT → SEARCH, SEARCH → CLOSED)
  - Opening palette always enters SEARCH (never INPUT directly)
```

### Command Registry (v1 — ~20 commands)

```
ID                     | Label                    | Shortcut | Event/Action                    | Input?
-----------------------|--------------------------|----------|---------------------------------|-------
stage-all              | Stage All                | ⌘⇧K      | keyboard-stage-all              | No
unstage-all            | Unstage All              | ⌘⇧U      | keyboard-unstage-all            | No
discard-all            | Discard All              |          | (direct call on StagingPanel)   | No
stash-all              | Stash All                | ⌘⇧S      | keyboard-stash                  | No
toggle-view            | Toggle File View         |          | (dispatch to StagingPanel)      | No
commit                 | Commit                   | ⌘↵       | keyboard-commit                 | No
commit-push            | Commit & Push            | ⌘⇧↵      | keyboard-commit-push            | No
toggle-amend           | Toggle Amend             |          | (dispatch to CommitPanel)       | No
push                   | Push                     |          | (dispatch to SyncPanel)         | No
pull                   | Pull                     |          | (dispatch to SyncPanel)         | No
fetch                  | Fetch                    |          | (dispatch to SyncPanel)         | No
fetch-all              | Fetch All Remotes        |          | (dispatch to SyncPanel)         | No
force-push             | Force Push (with Lease)  |          | (dispatch to SyncPanel)         | No
create-branch          | Create Branch            |          | (inline input mode)             | Yes
toggle-sidebar         | Toggle Sidebar           | ⌘B       | (dispatch to AppLayout)         | No
open-settings          | Open Settings            |          | open-settings                   | No
open-folder            | Open Repository…         |          | (dispatch to RepoSwitcher)      | No
select-all             | Select All Files         | ⌘A       | keyboard-select-all             | No
```

---

## Execution Strategy

### Sequential Execution (No Parallel Waves)

All tasks are tightly coupled — each builds on the previous one.

```
Task 1: Scaffold component + mount + keyboard shortcuts
    ↓
Task 2: Command registry + search/filter + keyboard nav + execution
    ↓
Task 3: Inline input mode for "Create Branch"
    ↓
Task 4: Disabled states + edge cases + polish
    ↓
Task 5: Pest feature tests
    ↓
Task 6: Pint + final verification
```

### Dependency Matrix

| Task | Depends On | Blocks |
|------|------------|--------|
| 1    | None       | 2, 3, 4, 5 |
| 2    | 1          | 3, 4, 5 |
| 3    | 2          | 4, 5 |
| 4    | 3          | 5 |
| 5    | 4          | 6 |
| 6    | 5          | None |

---

## TODOs

- [ ] 1. Scaffold CommandPalette component, mount in layout, register keyboard shortcuts

  **What to do**:
  - Create `app/Livewire/CommandPalette.php` using `php artisan make:livewire CommandPalette --no-interaction`
  - Add the component class with:
    - `public bool $isOpen = false` — palette visibility
    - `public string $mode = 'search'` — state machine ('search' or 'input')
    - `public string $query = ''` — search input
    - `public string $inputValue = ''` — input mode value
    - `public ?string $inputCommand = null` — which command is in input mode
    - `public ?string $inputError = null` — inline validation error for input mode
    - `#[On('open-command-palette')]` listener that sets `$isOpen = true`, `$mode = 'search'`, resets query
    - `close()` method that resets all state
    - `render()` returning the blade view
  - Create `resources/views/livewire/command-palette.blade.php` with:
    - Fixed overlay: `fixed inset-0 z-50` with semi-transparent backdrop `bg-black/50`
    - Centered container: `flex items-start justify-center pt-[20vh]` (VS Code positioning — top-third of screen)
    - White card: `w-full max-w-xl bg-white rounded-xl shadow-2xl border border-[#ccd0da] overflow-hidden`
    - Search input at top: styled like BranchManager search but larger (text-sm, py-2.5, px-4)
    - Magnifying glass icon: `<x-phosphor-magnifying-glass-light class="w-4 h-4 text-[#8c8fa1]" />`
    - Alpine.js transitions: `x-show="$wire.isOpen"` with `x-transition:enter` fade + scale
    - Empty command list placeholder for now (will be populated in Task 2)
    - `x-cloak` to prevent flash of unstyled content
    - Backdrop click → `$wire.close()`
  - Mount in `app-layout.blade.php`:
    - Add `@livewire('command-palette', key('command-palette'))` on line 12 (after ErrorBanner, before the header div)
    - Add keyboard shortcuts to the main div (lines 3-10 area):
      - `@keydown.window.meta.k.prevent="$wire.$dispatch('open-command-palette')"`
      - `@keydown.window.meta.shift.p.prevent="$wire.$dispatch('open-command-palette')"`

  **Must NOT do**:
  - Don't use `<flux:modal>` — this is a custom overlay with different UX than standard dialogs
  - Don't add any command logic yet (Task 2)
  - Don't modify any existing keyboard shortcuts

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Scaffolding a new component with minimal logic — mostly boilerplate PHP class + Blade template + 2 lines in layout
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Creating a Livewire component with proper lifecycle methods and event listeners
    - `tailwindcss-development`: Styling the overlay with Tailwind v4 utilities and Catppuccin colors
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: Not using Flux components for this custom overlay
    - `pest-testing`: Tests come in Task 5

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential
  - **Blocks**: Tasks 2, 3, 4, 5
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `app/Livewire/ErrorBanner.php` — Global overlay component pattern. Follow the same mount approach: simple Livewire class with `#[On('event')]` listener, `$isOpen` state, `close()` method. The CommandPalette uses the same "globally mounted, event-triggered" architecture.
  - `resources/views/livewire/error-banner.blade.php` — Overlay Blade pattern with `x-show`, `x-transition`, `fixed` positioning, `z-50`. Copy the transition style (fade + scale). Note the `style="display: none;"` for pre-Alpine rendering.
  - `app/Livewire/SettingsModal.php:36-41` — `#[On('open-settings')]` event listener pattern. CommandPalette's `#[On('open-command-palette')]` follows the exact same approach.

  **Layout References**:
  - `resources/views/livewire/app-layout.blade.php:1-12` — Keyboard shortcut registration area (lines 3-10) and component mount area (line 12). Add new `@keydown.window.meta.k.prevent` and `@keydown.window.meta.shift.p.prevent` to lines 3-10. Add `@livewire('command-palette')` near line 12.

  **Style References**:
  - `resources/views/livewire/branch-manager.blade.php:68-79` — Search input styling pattern. Use similar structure (magnifying glass icon + unstyled input) but scale up to text-sm for the palette's larger size.
  - `AGENTS.md` — Catppuccin Latte color palette. Key colors: white bg (`#ffffff`), border (`#ccd0da`), text primary (`#4c4f69`), text tertiary/placeholder (`#8c8fa1`), hover bg (`#eff1f5`).

  **Acceptance Criteria**:

  - [ ] File exists: `app/Livewire/CommandPalette.php` with `isOpen`, `mode`, `query` properties
  - [ ] File exists: `resources/views/livewire/command-palette.blade.php` with overlay structure
  - [ ] `app-layout.blade.php` contains `@livewire('command-palette'` mount
  - [ ] `app-layout.blade.php` contains `@keydown.window.meta.k.prevent` and `@keydown.window.meta.shift.p.prevent`
  - [ ] `vendor/bin/pint --dirty --format agent` passes on new/changed files

  **Agent-Executed QA Scenarios**:

  ```
  Scenario: Palette overlay renders in DOM when opened
    Tool: Bash (php artisan tinker)
    Preconditions: App can boot
    Steps:
      1. Run: php artisan tinker --execute="use App\Livewire\CommandPalette; \$c = new CommandPalette; echo \$c->isOpen ? 'open' : 'closed';"
      2. Assert: Output is "closed" (default state)
    Expected Result: Component instantiates with isOpen=false
    Evidence: Command output

  Scenario: Keyboard shortcuts registered in layout
    Tool: Bash (grep)
    Steps:
      1. grep -c "meta.k.prevent" resources/views/livewire/app-layout.blade.php
      2. Assert: Count is 1 (⌘K shortcut exists but not duplicate with ⌘⇧K)
      3. grep -c "meta.shift.p.prevent" resources/views/livewire/app-layout.blade.php
      4. Assert: Count is 1
      5. grep -c "command-palette" resources/views/livewire/app-layout.blade.php
      6. Assert: Count >= 1 (mount point exists)
    Expected Result: Both shortcuts and mount point present
    Evidence: grep output
  ```

  **Commit**: YES
  - Message: `feat(layout): scaffold command palette component with keyboard shortcuts`
  - Files: `app/Livewire/CommandPalette.php`, `resources/views/livewire/command-palette.blade.php`, `resources/views/livewire/app-layout.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [ ] 2. Build command registry, search/filter, keyboard navigation, and command execution

  **What to do**:
  - In `CommandPalette.php`, add a static method `getCommands(): array` that returns the full command registry. Each command is an array:
    ```php
    [
        'id' => 'stage-all',
        'label' => 'Stage All',
        'shortcut' => '⌘⇧K',           // null if no shortcut
        'event' => 'keyboard-stage-all', // Livewire event to dispatch
        'keywords' => ['stage', 'add', 'all', 'git add'],
        'requiresInput' => false,
        'icon' => 'phosphor-plus',       // Phosphor icon name (regular variant)
    ]
    ```
  - Full command list (see "Command Registry" table above for all ~18 commands). Commands that dispatch to existing `keyboard-*` events use those directly. Commands without existing events need new event listeners added to their target components:
    - `StagingPanel`: add `#[On('palette-discard-all')]` → calls `discardAll()`, add `#[On('palette-toggle-view')]` → calls `toggleView()`
    - `CommitPanel`: add `#[On('palette-toggle-amend')]` → calls `toggleAmend()`
    - `SyncPanel`: add `#[On('palette-push')]` → calls `syncPush()`, `#[On('palette-pull')]` → calls `syncPull()`, `#[On('palette-fetch')]` → calls `syncFetch()`, `#[On('palette-fetch-all')]` → calls `syncFetchAll()`, `#[On('palette-force-push')]` → calls `syncForcePushWithLease()`
    - `RepoSwitcher`: add `#[On('palette-open-folder')]` → calls `openFolderDialog()`
    - `AppLayout`: add `#[On('palette-toggle-sidebar')]` → calls `toggleSidebar()`
  - Add `executeCommand(string $commandId): void` method to CommandPalette that:
    - Looks up the command in the registry
    - If `requiresInput` is true → transition to INPUT mode (handled in Task 3)
    - If `event` is set → `$this->dispatch($event)` and close palette
    - Close the palette after execution
  - Add computed property `getFilteredCommandsProperty()` that:
    - If `$query` is empty → return all commands
    - If `$query` has text → filter by case-insensitive substring match against `label` and all `keywords`
    - Return filtered results maintaining original order
  - In `command-palette.blade.php`, implement:
    - Alpine.js `x-data` with keyboard navigation state (copy pattern from `branch-manager.blade.php:32-65`):
      - `activeIndex: -1`
      - `items: []` (populated via `querySelectorAll('[data-command-item]')`)
      - `navigate(direction)` — cycles through items, scrolls into view
      - `selectActive()` — clicks the active item
      - `init()` with `$watch` on `$wire.query` to reset `activeIndex` and re-query items
    - `@keydown.arrow-down.prevent`, `@keydown.arrow-up.prevent`, `@keydown.enter.prevent` on the palette wrapper
    - Command list rendered as a scrollable `div` with `max-h-80 overflow-y-auto`
    - Each command item:
      - `data-command-item` attribute (for Alpine query)
      - `wire:click="executeCommand('{{ $command['id'] }}')"` handler
      - Left side: icon (`<x-dynamic-component :component="'phosphor-' . $command['icon']'" />`) + label text
      - Right side: keyboard shortcut badge (if present) in a `<kbd>` styled element
      - Hover: `hover:bg-[#eff1f5]` (Catppuccin Base)
      - Active (keyboard selected): `bg-[#eff1f5]` via Alpine `:class="{ 'bg-[#eff1f5]': activeIndex === index }"`
      - Text: `text-[13px] text-[#4c4f69] font-mono`
    - Keyboard shortcut badges: `<kbd>` elements styled with `text-[10px] text-[#6c6f85] bg-[#eff1f5] border border-[#ccd0da] rounded px-1.5 py-0.5 font-mono`
    - Empty state: When filtered list is empty, show "No commands found" centered text in `text-[#8c8fa1] text-sm py-8`
    - Auto-focus search input when palette opens: `x-init="$watch('$wire.isOpen', v => { if(v) $nextTick(() => $refs.searchInput.focus()) })"`

  **Must NOT do**:
  - Don't implement input mode yet (Task 3)
  - Don't implement disabled states yet (Task 4)
  - Don't use Fuse.js or any external search library — simple substring matching is sufficient for ~18 items
  - Don't modify any existing component's core behavior — only add new `#[On()]` event listeners

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Significant implementation work across multiple files — command registry, Alpine.js interactivity, event wiring, Blade template with complex dynamic rendering
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Livewire event dispatch/listen, computed properties, component communication
    - `tailwindcss-development`: Styling command items, kbd badges, hover states, scroll containers
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: No Flux components used in the palette itself
    - `pest-testing`: Tests in Task 5

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential
  - **Blocks**: Tasks 3, 4, 5
  - **Blocked By**: Task 1

  **References**:

  **Pattern References**:
  - `resources/views/livewire/branch-manager.blade.php:32-65` — Alpine.js keyboard navigation pattern. Copy this `x-data` structure exactly: `activeIndex`, `items[]`, `navigate()`, `selectActive()`, `init()`. The palette's navigation is identical, just applied to command items instead of branch items.
  - `resources/views/livewire/branch-manager.blade.php:68-79` — Search input with `wire:model.live.debounce.300ms`. Use the same debounce pattern but with 150ms for the palette (faster feel for a small list).
  - `app/Livewire/BranchManager.php:155-167` — `getFilteredLocalBranchesProperty()` computed property for filtering. CommandPalette's `getFilteredCommandsProperty()` follows the same pattern: `str_contains(strtolower(...))`.

  **Event References**:
  - `resources/views/livewire/app-layout.blade.php:3-10` — All existing `keyboard-*` event names. These are the events the palette dispatches for shortcutted actions.
  - `app/Livewire/CommitPanel.php:42-52` — `#[On('keyboard-commit')]` listener pattern. New `#[On('palette-*')]` listeners follow this exact approach.
  - `app/Livewire/StagingPanel.php:120-154` — `#[On('keyboard-stage-all')]` and `#[On('keyboard-unstage-all')]` listeners. The palette dispatches these same events.
  - `app/Livewire/SyncPanel.php:62-235` — All sync methods (`syncPush`, `syncPull`, `syncFetch`, `syncFetchAll`, `syncForcePushWithLease`). Add `#[On('palette-*')]` wrappers that call these existing methods.
  - `app/Livewire/StagingPanel.php:173-188` — `discardAll()` method. Add `#[On('palette-discard-all')]` listener.
  - `app/Livewire/StagingPanel.php:305-308` — `toggleView()` method. Add `#[On('palette-toggle-view')]` listener.
  - `app/Livewire/CommitPanel.php:101-111` — `toggleAmend()` method. Add `#[On('palette-toggle-amend')]` listener.
  - `app/Livewire/RepoSwitcher.php:85` — `openFolderDialog()` method. Add `#[On('palette-open-folder')]` listener.
  - `app/Livewire/AppLayout.php:64-67` — `toggleSidebar()` method. Add `#[On('palette-toggle-sidebar')]` listener.

  **Style References**:
  - `AGENTS.md:Hover & Interaction States` — `hover:bg-[#eff1f5]` for items on white background
  - `AGENTS.md:Typography` — `font-mono` for code-like elements, `font-sans` for labels
  - `AGENTS.md:Color System` — All hex values for text, borders, backgrounds

  **Acceptance Criteria**:

  - [ ] `CommandPalette::getCommands()` returns array with ≥18 commands
  - [ ] Each command has: id, label, shortcut (nullable), event, keywords, requiresInput, icon
  - [ ] Palette search filters commands by label and keywords (case-insensitive substring)
  - [ ] Arrow keys cycle through visible commands (wraps around)
  - [ ] Enter on selected command triggers execution and closes palette
  - [ ] Keyboard shortcut badges visible on commands that have shortcuts
  - [ ] Empty search shows "No commands found" message
  - [ ] New `#[On('palette-*')]` listeners added to StagingPanel, CommitPanel, SyncPanel, RepoSwitcher, AppLayout
  - [ ] `vendor/bin/pint --dirty --format agent` passes

  **Agent-Executed QA Scenarios**:

  ```
  Scenario: Command registry has all expected commands
    Tool: Bash (php artisan tinker)
    Steps:
      1. Run: php artisan tinker --execute="echo count(\App\Livewire\CommandPalette::getCommands());"
      2. Assert: Output is >= 18
      3. Run: php artisan tinker --execute="echo collect(\App\Livewire\CommandPalette::getCommands())->pluck('id')->join(', ');"
      4. Assert: Output contains "stage-all", "commit", "push", "create-branch", "toggle-sidebar"
    Expected Result: Full command registry loaded
    Evidence: Tinker output

  Scenario: Search filtering works on component level
    Tool: Bash (php artisan tinker)
    Steps:
      1. Run: php artisan tinker --execute="use Livewire\Livewire; \$c = Livewire::test(\App\Livewire\CommandPalette::class); \$c->set('query', 'push'); echo \$c->get('filteredCommands')->count();"
      2. Assert: Count includes commands matching "push" (Push, Commit & Push, Force Push)
    Expected Result: Filtering narrows results correctly
    Evidence: Tinker output

  Scenario: Execute command dispatches correct event
    Tool: Bash (php artisan test)
    Steps:
      1. This will be formally tested in Task 5, but verify component boots:
         php artisan tinker --execute="\$c = new \App\Livewire\CommandPalette; echo \$c->isOpen ? 'open' : 'closed';"
      2. Assert: Output is "closed"
    Expected Result: Component instantiates correctly
    Evidence: Tinker output
  ```

  **Commit**: YES
  - Message: `feat(layout): add command registry, search, keyboard nav and execution to palette`
  - Files: `app/Livewire/CommandPalette.php`, `resources/views/livewire/command-palette.blade.php`, `app/Livewire/StagingPanel.php`, `app/Livewire/CommitPanel.php`, `app/Livewire/SyncPanel.php`, `app/Livewire/RepoSwitcher.php`, `app/Livewire/AppLayout.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [ ] 3. Implement inline input mode for "Create Branch"

  **What to do**:
  - In `CommandPalette.php`:
    - Update `executeCommand()`: when the command has `requiresInput === true`, transition to INPUT mode instead of dispatching an event:
      - Set `$this->mode = 'input'`
      - Set `$this->inputCommand = $commandId`
      - Set `$this->inputValue = ''`
      - Set `$this->inputError = null`
    - Add `submitInput(): void` method that:
      - Gets the command config from registry
      - For `create-branch`: validate branch name (not empty, no spaces, valid git ref), then dispatch event `palette-create-branch` with `name` parameter
      - On success: close palette (`$this->close()`)
      - On validation error: set `$this->inputError` and stay in INPUT mode
    - Add `cancelInput(): void` method that transitions back to SEARCH mode:
      - Set `$this->mode = 'search'`
      - Set `$this->inputCommand = null`
      - Set `$this->inputValue = ''`
      - Set `$this->inputError = null`
  - In `BranchManager.php`:
    - Add `#[On('palette-create-branch')]` listener:
      ```php
      #[On('palette-create-branch')]
      public function handlePaletteCreateBranch(string $name): void
      {
          $this->newBranchName = $name;
          $this->baseBranch = $this->currentBranch; // default to current branch
          $this->createBranch();
      }
      ```
  - In `command-palette.blade.php`:
    - Add conditional rendering based on `$mode`:
      - When `$mode === 'search'`: show the search input + command list (existing behavior)
      - When `$mode === 'input'`: show input mode UI:
        - Header text: "Create Branch" (from command label)
        - Back button or "Esc to go back" hint
        - Text input with placeholder "Branch name (e.g., feature/my-feature)"
        - `wire:model="inputValue"` on the input
        - `wire:keydown.enter.prevent="submitInput"` to submit
        - `wire:keydown.escape.prevent="cancelInput"` to go back to search
        - Inline error message below input if `$inputError` is set (styled `text-[#d20f39] text-xs mt-1`)
        - Auto-focus the input when entering input mode
    - The input mode view should have a subtle breadcrumb-like header showing which command is active

  **Must NOT do**:
  - Don't add input mode for any command other than "Create Branch" in v1
  - Don't add base branch selector — always default to current branch
  - Don't modify `BranchManager.createBranch()` logic — reuse it via the new event listener
  - Don't remove the existing Create Branch modal — it remains for advanced use from the branch dropdown

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Adding a secondary mode to an existing component — moderate scope, well-defined inputs/outputs
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Wire:model binding, event dispatch with parameters, state transitions
    - `tailwindcss-development`: Input field styling, error message display, mode transition UI
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: Using native HTML input, not Flux input (to maintain palette's custom look)

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential
  - **Blocks**: Tasks 4, 5
  - **Blocked By**: Task 2

  **References**:

  **Pattern References**:
  - `app/Livewire/BranchManager.php:87-103` — `createBranch()` method. The new `#[On('palette-create-branch')]` listener sets `newBranchName` and `baseBranch` then calls this existing method. No new git logic needed.
  - `resources/views/livewire/branch-manager.blade.php:194-231` — Existing Create Branch modal UI. Reference for input field styling (font-mono, placeholder text pattern). The palette's input mode is a simplified version of this.
  - `app/Livewire/SettingsModal.php:36-41` — `#[On('open-settings')]` event listener with simple method body. The BranchManager's `#[On('palette-create-branch')]` follows this same lightweight pattern.

  **Validation References**:
  - `app/Services/Git/BranchService.php` — `createBranch()` throws exceptions for invalid names. The palette should do client-side validation (non-empty, no spaces) before dispatching, and let BranchService handle deeper validation via its exception → ErrorBanner flow.

  **Style References**:
  - `resources/views/livewire/error-banner.blade.php:21-25` — Error color: `#d20f39` for red text on validation errors
  - `AGENTS.md:Color System` — Catppuccin Red (`#d20f39`) for error states

  **Acceptance Criteria**:

  - [ ] Selecting "Create Branch" transitions palette to input mode (mode = 'input')
  - [ ] Input field is auto-focused and has placeholder text
  - [ ] Pressing Enter with valid name dispatches `palette-create-branch` event
  - [ ] Pressing Esc in input mode returns to search mode (does NOT close palette)
  - [ ] Empty input shows inline error "Branch name is required"
  - [ ] `BranchManager` has `#[On('palette-create-branch')]` listener
  - [ ] Listener uses current branch as base branch
  - [ ] Successful creation closes palette
  - [ ] `vendor/bin/pint --dirty --format agent` passes

  **Agent-Executed QA Scenarios**:

  ```
  Scenario: Input mode transition in Livewire component
    Tool: Bash (php artisan tinker)
    Steps:
      1. Run: php artisan tinker --execute="use Livewire\Livewire; \$c = Livewire::test(\App\Livewire\CommandPalette::class); \$c->call('executeCommand', 'create-branch'); echo \$c->get('mode');"
      2. Assert: Output is "input"
      3. Run: php artisan tinker --execute="use Livewire\Livewire; \$c = Livewire::test(\App\Livewire\CommandPalette::class); \$c->call('executeCommand', 'create-branch'); \$c->call('cancelInput'); echo \$c->get('mode');"
      4. Assert: Output is "search"
    Expected Result: Mode transitions work correctly
    Evidence: Tinker output

  Scenario: BranchManager receives palette-create-branch event
    Tool: Bash (grep)
    Steps:
      1. grep "palette-create-branch" app/Livewire/BranchManager.php
      2. Assert: Line contains #[On('palette-create-branch')]
    Expected Result: Event listener registered
    Evidence: grep output
  ```

  **Commit**: YES
  - Message: `feat(layout): add inline input mode for branch creation in command palette`
  - Files: `app/Livewire/CommandPalette.php`, `resources/views/livewire/command-palette.blade.php`, `app/Livewire/BranchManager.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [ ] 4. Add disabled command states, edge cases, and visual polish

  **What to do**:
  - In `CommandPalette.php`:
    - Accept `repoPath` as a public property (passed from layout like other components)
    - Add `getCommandStatesProperty(): array` computed property that returns which commands are disabled:
      - `commit` / `commit-push`: disabled if no staged files (need to check or listen to `status-updated`)
      - `push`: disabled if detached HEAD or no remote tracking
      - `pull`: disabled if detached HEAD
      - `force-push`: disabled if detached HEAD
      - All git commands: disabled if no repo is open (`$repoPath` is empty)
      - `discard-all`: disabled if no changes
    - Listen to `#[On('status-updated')]` to track `stagedCount` and `aheadBehind` for enabling/disabling
    - Add `public int $stagedCount = 0` and `public array $aheadBehind = ['ahead' => 0, 'behind' => 0]` properties
    - Merge disabled state into filtered commands output
  - In `command-palette.blade.php`:
    - Disabled commands: `opacity-40 cursor-not-allowed` (no hover effect, not clickable)
    - Add `@if(!$command['disabled'])` check around `wire:click` to prevent execution
    - Toggle behavior: update the `@keydown.window.meta.k` handler in app-layout to toggle:
      ```
      @keydown.window.meta.k.prevent="$dispatch('toggle-command-palette')"
      ```
      And add `#[On('toggle-command-palette')]` in CommandPalette that toggles `isOpen`
    - Esc handling (already handled in Task 1/2, but verify the edge cases):
      - If in SEARCH mode → close palette
      - If in INPUT mode → return to SEARCH mode
    - Visual polish:
      - Subtle border-top on first command item after search input: `border-t border-[#dce0e8]`
      - Smooth scroll behavior on command list
      - Active item highlight: `bg-[#084CCF]/8 text-[#084CCF]` for the keyboard-selected item (accent tint, not just grey hover)
      - Shortcut badge styling refinement: ensure badges align right, don't overflow
      - Footer hint text at bottom of palette: `text-[10px] text-[#8c8fa1] px-4 py-2 border-t border-[#dce0e8]` showing "↑↓ navigate · ↵ select · esc close"

  **Must NOT do**:
  - Don't make extensive API calls to determine disabled state — use the cached `stagedCount` and `aheadBehind` from events
  - Don't hide disabled commands — they must remain visible but greyed out
  - Don't change the behavior of any existing shortcuts

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Heavy focus on disabled states, visual feedback, micro-interactions, and edge case polish
  - **Skills**: [`livewire-development`, `tailwindcss-development`]
    - `livewire-development`: Event listeners for state tracking, computed properties
    - `tailwindcss-development`: Disabled states, opacity, cursor, hover override, footer hints
  - **Skills Evaluated but Omitted**:
    - `fluxui-development`: No Flux components in palette

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential
  - **Blocks**: Task 5
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `app/Livewire/CommitPanel.php:36-40` — `#[On('status-updated')]` listener tracking `stagedCount`. CommandPalette follows this same pattern to know when commit/push is valid.
  - `app/Livewire/SyncPanel.php:42-49` — `#[On('status-updated')]` and `#[On('remote-updated')]` listeners tracking `aheadBehind`. CommandPalette reuses these same events.
  - `resources/views/livewire/commit-panel.blade.php` — How the commit button is disabled when no staged files. Reference for disabled UX pattern.

  **Style References**:
  - `AGENTS.md:Hover & Interaction States` — `hover:bg-[#eff1f5]` for normal items, override disabled items to remove hover
  - `AGENTS.md:Color System` — `#084CCF` accent blue for active/selected item highlight, `#8c8fa1` for footer hint text (tertiary)
  - `resources/views/livewire/error-banner.blade.php:14-18` — `x-transition` patterns for enter/leave animations

  **Acceptance Criteria**:

  - [ ] Commands that require a repo are disabled when no repo is open
  - [ ] Disabled commands show `opacity-40 cursor-not-allowed`
  - [ ] Disabled commands cannot be executed (click does nothing)
  - [ ] ⌘K toggles palette (open → close, close → open)
  - [ ] Keyboard-selected item has accent-tinted highlight
  - [ ] Footer shows keyboard navigation hints
  - [ ] `vendor/bin/pint --dirty --format agent` passes

  **Agent-Executed QA Scenarios**:

  ```
  Scenario: Toggle behavior works
    Tool: Bash (php artisan tinker)
    Steps:
      1. Run Livewire::test for CommandPalette
      2. Dispatch 'open-command-palette' → assert isOpen = true
      3. Dispatch 'toggle-command-palette' → assert isOpen = false
      4. Dispatch 'toggle-command-palette' → assert isOpen = true
    Expected Result: Toggle behavior works correctly
    Evidence: Tinker output

  Scenario: Disabled state when no repo
    Tool: Bash (php artisan tinker)
    Steps:
      1. Test CommandPalette without repoPath
      2. Assert commandStates has most commands disabled
    Expected Result: Git commands disabled without repo
    Evidence: Tinker output
  ```

  **Commit**: YES
  - Message: `feat(layout): add disabled states, toggle behavior and polish to command palette`
  - Files: `app/Livewire/CommandPalette.php`, `resources/views/livewire/command-palette.blade.php`, `resources/views/livewire/app-layout.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

---

- [ ] 5. Write Pest feature tests for CommandPalette

  **What to do**:
  - Create `tests/Feature/Livewire/CommandPaletteTest.php` via `php artisan make:test --pest Livewire/CommandPaletteTest --no-interaction`
  - Follow the existing test patterns from `tests/Feature/Livewire/KeyboardShortcutsTest.php` and `tests/Feature/Livewire/BranchManagerTest.php`:
    - `beforeEach` creating test repo directory
    - `Process::fake()` for git commands
    - `Livewire::test()` for component testing
  - Tests to write:
    1. `test('command palette is closed by default')` — Assert `isOpen === false`, `mode === 'search'`
    2. `test('command palette opens on open-command-palette event')` — Dispatch event, assert `isOpen === true`
    3. `test('command palette closes when close is called')` — Open, close, assert `isOpen === false`
    4. `test('command palette toggles on toggle-command-palette event')` — Open, toggle, assert closed, toggle, assert open
    5. `test('command registry returns all expected commands')` — Assert `getCommands()` count >= 18, assert key commands exist by id
    6. `test('search filters commands by label')` — Set query to "push", assert filtered commands contain push-related items
    7. `test('search filters commands by keywords')` — Set query to "add", assert "Stage All" appears (keyword match)
    8. `test('empty search query returns all commands')` — Set query to "", assert all commands returned
    9. `test('search with no matches returns empty')` — Set query to "xyznonexistent", assert empty result
    10. `test('execute command dispatches correct event')` — Call `executeCommand('stage-all')`, assert `keyboard-stage-all` dispatched
    11. `test('execute command closes palette')` — Call `executeCommand('stage-all')`, assert `isOpen === false`
    12. `test('execute command with requiresInput transitions to input mode')` — Call `executeCommand('create-branch')`, assert `mode === 'input'`
    13. `test('cancel input returns to search mode')` — Enter input mode, call `cancelInput()`, assert `mode === 'search'`
    14. `test('submit input with empty value shows error')` — Enter input mode, call `submitInput()` with empty `inputValue`, assert `inputError` is set
    15. `test('submit input dispatches palette-create-branch event')` — Enter input mode, set `inputValue` to "feature-test", call `submitInput()`, assert `palette-create-branch` dispatched with name "feature-test"
    16. `test('successful input submission closes palette')` — Submit valid input, assert `isOpen === false`
  - Run all tests: `php artisan test --filter=CommandPalette --compact`

  **Must NOT do**:
  - Don't test Alpine.js/JavaScript behavior (that requires browser tests)
  - Don't test existing component behavior (Stage All already tested in StagingPanelTest)
  - Don't delete or modify any existing tests

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Writing Pest tests following well-established patterns in the codebase
  - **Skills**: [`pest-testing`, `livewire-development`]
    - `pest-testing`: Pest v4 syntax, assertions, beforeEach setup, Process::fake
    - `livewire-development`: Livewire::test assertions, event dispatch verification
  - **Skills Evaluated but Omitted**:
    - `tailwindcss-development`: No styling in tests

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential
  - **Blocks**: Task 6
  - **Blocked By**: Task 4

  **References**:

  **Test Pattern References**:
  - `tests/Feature/Livewire/KeyboardShortcutsTest.php` — Closest test file to reference. Shows `beforeEach` with test repo setup, `Process::fake()`, `Livewire::test()`, `->call()`, `->assertDispatched()`. CommandPaletteTest follows this exact structure.
  - `tests/Feature/Livewire/BranchManagerTest.php` — Tests for branch creation including `Process::fake()` for git commands and `->assertDispatched('status-updated')`. Reference for testing the create-branch flow.
  - `tests/Feature/Livewire/ErrorBannerTest.php` — Tests for a global event-driven component. Pattern for testing `#[On()]` listeners and state changes.
  - `tests/Feature/Livewire/CommitPanelTest.php` — Tests for `->set('message', ...)` property assignment and `->call('commit')` action execution.
  - `tests/Mocks/GitOutputFixtures.php` — Fixture class providing mock git output. Use for Process::fake() responses.

  **Acceptance Criteria**:

  - [ ] Test file created: `tests/Feature/Livewire/CommandPaletteTest.php`
  - [ ] All 16 test cases pass: `php artisan test --filter=CommandPalette --compact` → 16 tests, 0 failures
  - [ ] Tests follow existing Pest patterns (beforeEach, Process::fake, Livewire::test)
  - [ ] `vendor/bin/pint --dirty --format agent` passes on test file

  **Agent-Executed QA Scenarios**:

  ```
  Scenario: All command palette tests pass
    Tool: Bash
    Steps:
      1. Run: php artisan test --filter=CommandPalette --compact
      2. Assert: Exit code 0
      3. Assert: Output shows "Tests: X passed" with 0 failures
    Expected Result: All tests green
    Evidence: Test output captured

  Scenario: Full test suite still passes
    Tool: Bash
    Steps:
      1. Run: php artisan test --compact
      2. Assert: Exit code 0
      3. Assert: No new failures introduced
    Expected Result: No regressions
    Evidence: Test output captured
  ```

  **Commit**: YES
  - Message: `test(layout): add feature tests for command palette component`
  - Files: `tests/Feature/Livewire/CommandPaletteTest.php`
  - Pre-commit: `php artisan test --filter=CommandPalette --compact && vendor/bin/pint --dirty --format agent`

---

- [ ] 6. Run Pint, full test suite, and final verification

  **What to do**:
  - Run `vendor/bin/pint --dirty --format agent` across all modified files
  - Run `php artisan test --compact` to verify full suite passes (no regressions)
  - Verify all new files exist and are properly structured
  - Verify keyboard shortcuts don't conflict with existing ones

  **Must NOT do**:
  - Don't make functional changes — this is verification only
  - Don't modify tests to make them pass (fix source code instead if needed)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Running two commands and checking output — minimal effort
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Understanding test output and diagnosing failures
  - **Skills Evaluated but Omitted**:
    - All others: No implementation work in this task

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential (final task)
  - **Blocks**: None
  - **Blocked By**: Task 5

  **References**:
  - `AGENTS.md:Git Commit Style` — Verify commit messages follow the `type(scope): desc` pattern
  - Project convention: `vendor/bin/pint --dirty --format agent` (from Laravel Boost guidelines)

  **Acceptance Criteria**:

  - [ ] `vendor/bin/pint --format agent` — 0 dirty files
  - [ ] `php artisan test --compact` — all tests pass, 0 failures
  - [ ] No keyboard shortcut conflicts (⌘K and ⌘⇧P don't override existing shortcuts)

  **Agent-Executed QA Scenarios**:

  ```
  Scenario: Pint formatting passes
    Tool: Bash
    Steps:
      1. Run: vendor/bin/pint --format agent
      2. Assert: Exit code 0
    Expected Result: Code style clean
    Evidence: Pint output

  Scenario: Full test suite passes
    Tool: Bash
    Steps:
      1. Run: php artisan test --compact
      2. Assert: Exit code 0
      3. Assert: No failures
    Expected Result: All tests pass, no regressions
    Evidence: Test output

  Scenario: No shortcut conflicts
    Tool: Bash (grep)
    Steps:
      1. grep "meta.k" resources/views/livewire/app-layout.blade.php
      2. Assert: Only ⌘K (meta.k) and ⌘⇧K (meta.shift.k) exist — no duplication
      3. grep "meta.shift.p" resources/views/livewire/app-layout.blade.php
      4. Assert: Only one ⌘⇧P handler exists
    Expected Result: No conflicts
    Evidence: grep output
  ```

  **Commit**: NO (verification only)

---

## Commit Strategy

| After Task | Message | Key Files | Verification |
|------------|---------|-----------|--------------|
| 1 | `feat(layout): scaffold command palette component with keyboard shortcuts` | CommandPalette.php, command-palette.blade.php, app-layout.blade.php | `vendor/bin/pint --dirty --format agent` |
| 2 | `feat(layout): add command registry, search, keyboard nav and execution to palette` | CommandPalette.php, command-palette.blade.php, + 5 component files | `vendor/bin/pint --dirty --format agent` |
| 3 | `feat(layout): add inline input mode for branch creation in command palette` | CommandPalette.php, command-palette.blade.php, BranchManager.php | `vendor/bin/pint --dirty --format agent` |
| 4 | `feat(layout): add disabled states, toggle behavior and polish to command palette` | CommandPalette.php, command-palette.blade.php, app-layout.blade.php | `vendor/bin/pint --dirty --format agent` |
| 5 | `test(layout): add feature tests for command palette component` | CommandPaletteTest.php | `php artisan test --filter=CommandPalette --compact` |

---

## Success Criteria

### Verification Commands
```bash
# All command palette tests pass
php artisan test --filter=CommandPalette --compact  # Expected: 16 tests, 0 failures

# Full test suite passes (no regressions)
php artisan test --compact  # Expected: all pass

# Pint formatting clean
vendor/bin/pint --format agent  # Expected: 0 dirty files

# Component exists and has commands
php artisan tinker --execute="echo count(\App\Livewire\CommandPalette::getCommands());"  # Expected: >= 18

# Keyboard shortcuts registered
grep -c "open-command-palette\|toggle-command-palette" resources/views/livewire/app-layout.blade.php  # Expected: >= 2
```

### Final Checklist
- [ ] All "Must Have" present (search, keyboard nav, shortcuts display, input mode, toggle)
- [ ] All "Must NOT Have" absent (no categories, no multi-input commands, no context-aware hiding, no Flux Modal)
- [ ] All tests pass
- [ ] Pint clean
- [ ] Existing shortcuts unaffected
