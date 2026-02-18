
## Architecture Documentation Patterns

### Document Structure
- Start with comprehensive table of contents linking to all sections
- Use ASCII diagrams (max 80 chars wide) for visual clarity
- Include file path references for every code excerpt
- Use imperative mood ("Use X to achieve Y", not "We use X")
- Assume expert audience (no framework basics)

### ASCII Diagram Best Practices
- Layer diagrams: Show vertical flow with boxes and arrows
- Component trees: Use ├── └── characters for hierarchy
- Keep width under 80 characters for readability
- Add explanatory text below diagrams

### Code Excerpts
- Always include file path before code block
- Show relevant context, not entire files
- Use real code from the codebase, not pseudocode
- Highlight key patterns (factory methods, error handling, etc.)

### Architecture Layers in gitty
1. Git CLI (system binary)
2. GitCommandRunner (command building, escaping)
3. Git Services (business logic, cache invalidation)
4. DTOs (immutable value objects, factory methods)
5. Livewire Components (UI state, event handling)
6. Blade/Flux Views (rendering, Alpine.js)

### Key Patterns Documented
- **AbstractGitService**: Base class validates .git, provides cache + commandRunner
- **GitCommandRunner**: Wraps Laravel Process, escapes args with escapeshellarg()
- **Service Instantiation**: Per-request with repoPath, NOT dependency injection
- **DTO Parsing**: Factory methods (fromOutput, fromLine) parse porcelain v2
- **Cache Strategy**: Group-based invalidation (status, history, branches, etc.)
- **Concurrency Control**: Cache-based locks prevent parallel operations
- **Error Handling**: Git stderr → exception → translation → Livewire event

### Data Flow Trace Pattern
Step-by-step trace with:
1. User interaction (Blade template)
2. Livewire method call
3. Error handling wrapper
4. Service instantiation
5. Command execution
6. Cache invalidation
7. Event dispatch
8. Component refresh
9. DTO parsing
10. UI re-render
11. Other components react

### NativePHP Integration
- Window configuration (1200x800, hiddenInset titlebar)
- Native menu bar with hotkeys
- Traffic light spacer (64px, -webkit-app-region: drag)
- Buttons opt out of drag region (-webkit-app-region: no-drag)

### Design System Reference
- Link to AGENTS.md for colors, icons, Flux UI patterns
- Don't duplicate content, just reference it
- List key topics covered in AGENTS.md


## DTO Documentation Patterns

### DTO Structure Documentation
- Property tables with columns: Property | Type | Description
- Factory method signatures with input format examples
- Key helper methods with return types and descriptions
- Producer/consumer mapping (which services create, which components use)

### DTO Parsing Patterns
- All DTOs parse git's porcelain v2 format (machine-readable, stable)
- Factory methods follow pattern: split → iterate → match → extract → construct
- Error handling: throw InvalidArgumentException for invalid input, return empty collections for empty output
- Collection usage: `Collection<int, DTO>` for lists, enables fluent operations

### DTO Relationships
- GitStatus is root DTO: contains AheadBehind + Collection<ChangedFile>
- DiffResult has deep nesting: DiffResult → DiffFile → Hunk → HunkLine
- MergeResult contains conflict detection and file list
- Branch/Commit are standalone DTOs used across multiple services

### Immutability Patterns
- Most DTOs use `readonly class` for full immutability
- ChangedFile uses `readonly` properties + ArrayAccess for Livewire compatibility
- ArrayAccess implementation throws LogicException on write attempts

### Factory Method Patterns
- `fromOutput()`: Parse multi-line git output (GitStatus, DiffResult, Commit)
- `fromLine()`: Parse single-line output (Branch, Stash)
- `fromArray()`: Convert intermediate array to DTO (DiffFile)
- `fromRawLines()`: Parse raw diff lines into collections (Hunk)

### Producer/Consumer Mapping
- GitService produces: GitStatus, DiffResult, Commit
- BranchService produces: Collection<Branch>, MergeResult
- CommitService produces: Commit, MergeResult
- StashService produces: Collection<Stash>
- RemoteService produces: array<Remote>
- DiffService produces: DiffResult

### Future DTOs
- BlameLine: Reserved for git blame UI
- GraphNode: Reserved for visual commit graph
- ConflictFile: Reserved for conflict resolution UI


## Service API Documentation Patterns

### Service Organization
- **Git Services**: 14 services extending AbstractGitService (GitService, StagingService, CommitService, BranchService, DiffService, RemoteService, StashService, ResetService, RebaseService, TagService, ConflictService, BlameService, SearchService, GraphService)
- **Infrastructure Services**: 5 low-level services (GitCommandRunner, GitCacheService, GitOperationQueue, GitErrorHandler, GitConfigValidator)
- **Application Services**: 5 high-level services (RepoManager, SettingsService, EditorService, NotificationService, AutoFetchService)

### Documentation Structure
- Summary table at top: Service → Responsibility → Cache Group → Key Methods
- Group services by category (Git, Infrastructure, Application)
- For each service: file path, purpose, constructor, all public methods
- For each method: signature, parameters, returns, cache behavior, invalidation, throws

### Cache Group Patterns
- **status**: status, diff (invalidated by staging, commits, pulls)
- **history**: log (invalidated by commits, pulls, resets)
- **branches**: branches (invalidated by branch operations, fetches)
- **remotes**: remotes (long TTL, rarely invalidated)
- **stashes**: stashes (invalidated by stash operations)
- **tags**: tags (invalidated by tag operations)

### Method Documentation Format
```
##### `methodName(Type $param): ReturnType`

Brief description.

- **Cache:** Group name, TTL (or "None")
- **Invalidates:** Cache groups (or "None")
- **Parameters:** Param descriptions
- **Returns:** Return value description
- **Throws:** Exception types and conditions
- **Uses:** Git commands or special techniques
```

### DiffService Special Case
- Document protected `generatePatch()` and `generateLinePatch()` methods
- These explain hunk staging internals (line selection, count recalculation)
- Include algorithm details for line staging (context, selected additions, unselected additions, deletions)

### Service Instantiation Pattern
- All git services: `new ServiceName($repoPath)` (per-request, NOT dependency injection)
- Application services: Use constructor injection for dependencies (SettingsService, etc.)

### Error Handling Patterns
- `run()`: Returns ProcessResult, check `successful()` or `exitCode()`
- `runOrFail()`: Throws `GitCommandFailedException` on failure
- Custom exceptions: `GitConflictException`, `GitOperationInProgressException`, `InvalidRepositoryException`
- GitErrorHandler: Translate git stderr to user-friendly messages

### Concurrency Control
- GitOperationQueue: Cache-based locks (30 second duration)
- `execute()`: Throws `GitOperationInProgressException` if locked
- `isLocked()`: Check lock status without acquiring

### Usage Examples
- Include practical examples for complex patterns (hunk staging, line staging, concurrency control)
- Show error handling with GitErrorHandler
- Demonstrate cache invalidation flow


## Event System Documentation Patterns

### Event Mapping Strategy
- Grep for `$this->dispatch(` to find all event dispatchers
- Grep for `#[On(` to find all event listeners
- Grep for `$dispatch(` in Blade files to find Alpine.js dispatchers
- Cross-reference to build dispatcher → listener mapping

### Event Categories
Group events by purpose:
1. **Core Events**: status-updated, file-selected, repo-switched (drive main workflow)
2. **Keyboard Events**: keyboard-* (dispatched from Alpine.js @keydown handlers)
3. **Command Palette Events**: palette-* (dispatched from CommandPalette)
4. **UI Toggle Events**: toggle-*, open-* (control modal/panel visibility)
5. **Git Operation Events**: committed, stash-created (fired after git operations)
6. **Notification Events**: show-error, show-success, show-warning (user feedback)
7. **Theme Events**: theme-updated, theme-changed (theme switching)

### Event Flow Diagrams
Create ASCII diagrams for key workflows:
- **Staging Flow**: User action → component → trait → service → cache → event → listeners
- **Commit Flow**: Keyboard/button → CommitPanel → CommitService → committed → status-updated
- **Branch Switch Flow**: BranchManager → BranchService → status-updated → all components
- **Sync Flow**: Push/pull/fetch → RemoteService → status-updated → notification

### Keyboard Shortcut Pipeline
Document the 5-step flow:
1. User presses shortcut
2. Alpine.js captures @keydown.window event
3. $wire.$dispatch('event-name') fires Livewire event
4. Component #[On('event-name')] listener receives event
5. Component executes action

### Command Palette Event Dispatch
Document the 5-step flow:
1. User opens palette (⌘K)
2. User types search query (filters 28 commands)
3. User selects command (Enter or click)
4. CommandPalette->executeCommand() dispatches event
5. Listening component executes action

### Key Patterns Discovered
- **status-updated is the backbone**: Fired after every git mutation, listened by 8+ components
- **HandlesGitOperations auto-dispatch**: Trait automatically dispatches status-updated on success, show-error on failure
- **Event naming conventions**: keyboard-*, palette-*, open-*, toggle-*, show-*, *-updated, *-selected, *-created
- **⌘B is special**: Only keyboard shortcut that calls Livewire method directly instead of dispatching event
- **Commands requiring input**: create-branch switches to input mode, prompts for name, then dispatches with parameter
- **Disabled commands**: Computed based on repo state (no repo, no staged files)

### Master Event Table Structure
Columns:
- Event name
- Payload (with types)
- Dispatched by (component/file)
- Listened by (component/file)
- Purpose (one-line description)

Include file path references for every dispatcher and listener.

### Event Count
Total: 50 events across the application
- Core: 8 events
- Keyboard: 7 events
- Command Palette: 18 events
- UI Toggle: 10 events
- Git Operation: 4 events
- Notification: 3 events
- Theme: 2 events

Most important: status-updated (8+ listeners), file-selected, repo-switched, show-error


## Frontend Documentation Patterns

### Layout Structure Documentation
- Use ASCII diagrams for visual clarity (max 80 chars wide)
- Show three-panel layout: sidebar (250px) + left panel (resizable) + right panel (flex-1)
- Document panel dimensions, default widths, min/max constraints
- Explain panel switching logic (diff/history/blame)

### CSS Architecture Documentation
- Explain WHY two systems exist (@theme for Flux, :root for custom)
- Critical gotcha: Flux accent MUST be in @theme, NOT :root
- Document hardcoded hex values pattern (intentional for grep-ability)
- Show custom CSS classes (diff-line-*, animations)
- Include dark mode (.dark {}) alongside light mode

### Alpine.js Component Documentation
- Document inline x-data components (panel resize, theme toggle, file selection)
- Show complete code excerpts with all methods
- Explain localStorage persistence patterns
- Document keyboard shortcut bindings (@keydown.window.meta.*)
- Show multi-select patterns (Cmd+Click, Shift+Click, right-click)

### Flux UI Pattern Documentation
- Document all button variants (primary, ghost, subtle, danger)
- Show split button pattern with flux:button.group
- Explain dropdown sticky background requirement (bg-white on sticky elements)
- Document modal patterns (wire:model vs x-model)
- Show tooltip wrapping for icon-only buttons

### Icon Documentation
- Distinguish header icons (light variant) from action icons (regular)
- Document icon centering requirement (flex items-center justify-center)
- Explain icon color rule (text-secondary for headers, not border color)

### NativePHP Integration Documentation
- Document window configuration (1200x800, hiddenInset titlebar)
- Explain traffic light spacer (64px, -webkit-app-region: drag)
- Show drag region pattern (header draggable, buttons opt out)
- Document native menu bar with hotkeys and events

### JavaScript Module Documentation
- Document Highlight.js integration (language registration, incremental highlighting)
- Show Livewire hook integration (morph.updated)
- Explain graceful degradation (silently fail if language not supported)

### Design System Reference Pattern
- Link to AGENTS.md for complete color palette
- List key topics covered in AGENTS.md
- Do NOT duplicate content, just reference it
- Explain what AGENTS.md covers (colors, icons, Flux UI, animations, etc.)

### File Path References
- Always include file path before code excerpts
- Use relative paths from project root
- Group related files together (e.g., all Alpine.js components from app-layout.blade.php)

### Code Excerpt Best Practices
- Show complete Alpine.js x-data objects (all methods, not just init)
- Include surrounding Blade context (parent div, event listeners)
- Use real code from codebase, not pseudocode
- Highlight key patterns (localStorage persistence, event dispatching)


## Component Documentation Patterns

### Component Reference Structure
- Table of contents with anchor links to all sections
- Component hierarchy diagram (ASCII tree) showing parent-child relationships
- Trait documentation before components (HandlesGitOperations)
- Components grouped by function: Core, Header, Sidebar, History, Conflict, Overlay, Utility
- Each component section includes: file path, view path, purpose, properties table, actions list, events, services

### Property Tables
- Format: Property | Type | Default | Description
- Include visibility (public properties only)
- Note private properties in description when relevant
- Use PHP type syntax: `string`, `?string`, `array<int, string>`, `Collection`

### Event Documentation
- Events Listened: `#[On('event-name')]` handlers
- Events Dispatched: `$this->dispatch('event-name')` calls with payload
- Global event reference table: Event | Payload | Dispatched By | Listened By
- Group palette events separately (28 commands)

### Service Usage Pattern
- All services instantiated per-request: `new GitService($this->repoPath)`
- NOT dependency injection (allows multi-repo support)
- Services are stateless, accept repoPath in constructor

### Key Component Patterns Documented
- **Hash-based refresh**: StagingPanel, RepoSidebar use MD5 hash to skip rebuilds
- **HandlesGitOperations trait**: Wraps operations in try/catch, dispatches events
- **Auto-stash flow**: BranchManager, RepoSidebar handle dirty tree errors
- **DTO hydration**: DiffViewer rebuilds DTOs from arrays (Livewire serialization)
- **Computed properties**: BranchManager filters branches by query
- **History cycling**: CommitPanel saves draft, cycles through stored messages
- **Template system**: CommitPanel supports 10 types + custom .gitmessage

### Component Counts
- 18 total Livewire components
- 1 trait (HandlesGitOperations)
- 28 commands in CommandPalette
- 48 event listeners across all components
- 109 event dispatches across all components

### Complex Components (Detailed Documentation)
- **StagingPanel**: Hash refresh, tree view, multi-select, 3 file collections
- **CommitPanel**: Templates, history cycling, amend mode, undo with warnings
- **DiffViewer**: Hunk/line staging, image diffs, split view, language detection
- **BranchManager**: Auto-stash modal, filtered computed properties, remote filtering
- **CommandPalette**: 28 commands, input mode, disabled state logic
- **HistoryPanel**: Reset/revert/cherry-pick modals, graph, pagination, push warnings

### Event Flow Patterns
- `repo-switched` → invalidates cache, reloads all components
- `status-updated` → refreshes staged count, ahead/behind in header + commit panel
- `file-selected` → loads diff in DiffViewer
- `refresh-staging` → reloads StagingPanel after hunk/line staging
- `show-error` → global ErrorBanner displays all errors/success/warnings

### Testing Reference
- All components tested in `tests/Feature/Livewire/`
- Use `Livewire::test()` helper
- Example pattern: `->assertSet()`, `->call()`, `->assertDispatched()`

</EOF>
echo "Appended component documentation findings to learnings.md"
## Testing Documentation Patterns

### Test Infrastructure Overview
- **Pest 4**: Functional syntax with test() and expect()
- **79 test files**: 60 Feature, 3 Unit, 16 Browser
- **Three test helpers**: GitTestHelper (repo scaffolding), GitOutputFixtures (deterministic output), BrowserTestHelper (browser utilities)
- **Test organization**: Feature/Services/, Feature/Livewire/, Unit/DTOs/, Browser/Components/

### GitTestHelper Patterns
- `createTestRepo()`: Creates fresh git repo with initial commit (init, config user, add README, commit)
- `addTestFiles()`: Adds new files and stages them (does NOT commit)
- `modifyTestFiles()`: Modifies existing files (does NOT stage or commit)
- `createConflict()`: Creates merge conflict by branching, adding conflicting content, merging
- `createDetachedHead()`: Checks out current commit SHA to enter detached HEAD state
- `cleanupTestRepo()`: Deletes test repo directory (call in afterEach)

### GitOutputFixtures Patterns
- **Purpose**: Deterministic git output for DTO parsing tests (no real git operations)
- **Format**: All output uses porcelain v2 (machine-readable, stable)
- **Categories**: Status (11 fixtures), Log (3), Diff (3), Branch (2), Stash (1), Remote (2), Tag (1)
- **Key fixtures**: statusClean, statusWithMixedChanges, statusWithConflict, statusDetachedHead, diffUnstaged, logOneline
- **Usage**: Process::fake(['git status ...' => GitOutputFixtures::statusClean()])

### Service Testing Patterns
1. **Validate repo path**: expect(fn () => new Service('/invalid'))->toThrow(InvalidArgumentException)
2. **Mock git output**: Process::fake(['git status ...' => GitOutputFixtures::statusClean()])
3. **Assert commands ran**: Process::assertRan("git add 'README.md'")
4. **Assert with closure**: Process::assertRan(fn ($p) => str_contains($p->command, 'git add'))
5. **Real git operations**: Use GitTestHelper for complex operations (rebase, merge, conflicts)

### Livewire Component Testing Patterns
1. **Component mounting**: Livewire::test(Component::class, ['repoPath' => $path])->assertSet('repoPath', $path)
2. **Test actions**: ->call('stageFile', 'README.md')->assertDispatched('status-updated')
3. **Test events**: ->call('selectFile', 'file.txt')->assertDispatched('file-selected', file: 'file.txt')
4. **Test computed properties**: $component->get('unstagedFiles')->toHaveCount(2)
5. **Test empty states**: ->assertSee('No changes')
6. **Test HTML output**: ->assertSeeHtml('w-2 h-2 rounded-full')

### DTO Testing Patterns
1. **Test constructor**: new ChangedFile(path: 'file.php', ...) → expect($file->path)->toBe('file.php')
2. **Test helper methods**: expect($file->isStaged())->toBeTrue()
3. **Test status labels**: expect($file->statusLabel())->toBe('modified')
4. **Test factory methods**: GitStatus::fromOutput($output) → expect($status->branch)->toBe('main')

### Browser Testing Patterns
1. **Basic page visit**: visit('/')->assertSee('No Repository Selected')
2. **Component interaction**: ->click('[data-action="stage"]')->waitForText('Staged')
3. **Screenshots**: ->screenshot(fullPage: true, filename: 'homepage')
4. **Assert no errors**: ->assertNoJavaScriptErrors()->assertNoConsoleLogs()
5. **Setup**: BrowserTestHelper::setupMockRepo(), ensureScreenshotsDirectory()

### Test Writing Checklist
1. Determine test type (unit, feature, browser)
2. Choose location (Unit/DTOs/, Feature/Services/, Feature/Livewire/, Browser/Components/)
3. Create test file: `php artisan make:test --pest Feature/Services/MyServiceTest`
4. Set up fixtures (Process::fake or GitTestHelper)
5. Write descriptive test names ("it stages a single file", not "stage file")
6. Use fluent assertions (chain with ->and())
7. Test edge cases (empty input, invalid input, files with spaces, conflicts)
8. Assert git commands ran (Process::assertRan)
9. Test event dispatching (->assertDispatched)
10. Run tests before committing: `php artisan test --compact`

### Running Tests
- All tests: `php artisan test --compact`
- Specific file: `php artisan test --compact tests/Feature/Services/GitServiceTest.php`
- Filter: `php artisan test --compact --filter=stageFile`
- Directory: `php artisan test --compact tests/Feature/Services/`
- Browser only: `php artisan test --compact tests/Browser/`

### Code Formatting
- Before committing: `vendor/bin/pint --dirty --format agent`
- Ensures PSR-12 + Laravel conventions

### Key Testing Insights
- **Process::fake() is the backbone**: Mock git commands for deterministic tests
- **GitOutputFixtures for DTO parsing**: No real git operations, just parse fixed output
- **GitTestHelper for complex operations**: Real git repos for rebase, merge, conflicts
- **Livewire::test() for components**: Test mounting, actions, events, computed properties
- **Browser tests for integration**: Full-page interactions, screenshots, JavaScript errors
- **Test edge cases**: Empty arrays, invalid input, files with spaces, conflicts, detached HEAD
- **Always assert commands ran**: Verify correct git commands were executed
- **Test event dispatching**: Verify Livewire events are dispatched correctly


## Common Tasks Documentation Patterns

### Cookbook Structure
- Table of contents with anchor links to all recipes
- Each recipe is a numbered step-by-step guide
- Include file path references for every code excerpt
- Show real code patterns from the codebase, not pseudocode
- Cross-reference other docs (services.md, components.md, events.md, AGENTS.md)

### Recipe Format
1. **Title**: Clear, action-oriented (e.g., "Adding a New Git Operation")
2. **Steps**: Numbered list with file paths and code excerpts
3. **Pattern section**: Explain the pattern being followed
4. **Testing**: Always include test examples
5. **Cross-references**: Link to detailed docs for more info

### Key Recipes Documented
1. **Adding a New Git Operation**: Service → DTO → Component → Blade → Tests
2. **Adding a New Livewire Component**: Create → Props → Trait → Events → Register → Tests
3. **Adding a Command Palette Command**: getCommands() → #[On] handler → disabled logic → Tests
4. **Adding a Keyboard Shortcut**: @keydown handler → event → ShortcutHelp → Tests
5. **Adding a New DTO**: readonly class → factory method → parsing logic → Tests
6. **Modifying Cache Strategy**: Groups → TTLs → invalidation → Tests
7. **Working with Diff Viewer**: Data flow → patch generation → split view → line staging
8. **Adding a New Event**: Name → dispatch → listen → document → Tests
9. **Writing Tests**: Service tests → Component tests → DTO tests → Run commands

### Debugging Tips Section
- Organized by problem type (cache, ports, Flux UI, Tailwind, Livewire, events)
- Each tip has: Problem → Solution → Code example
- Common gotchas from AGENTS.md included
- NativePHP-specific issues (view cache, DevTools)

### Code Excerpt Best Practices
- Always show file path before code block
- Use real code from codebase (StagingService, CommandPalette, etc.)
- Highlight key patterns with comments
- Show both wrong and right approaches for gotchas
- Include complete method signatures with types

### Cross-Reference Strategy
- Link to services.md for detailed service APIs
- Link to components.md for component details
- Link to events.md for event reference
- Link to AGENTS.md for design system
- Don't duplicate content, just reference it

### Testing Patterns
- Use `createTestRepo()` helper for all git tests
- Use `Livewire::test()` for component tests
- Use `expect()` for assertions
- Use `->throws()` for exception testing
- Show test file paths and structure

### Recipe Count
- 9 main recipes (git operation, component, command, shortcut, DTO, cache, diff, event, tests)
- 10 debugging tips (cache, ports, git output, Flux UI, Tailwind, Livewire, events, cache invalidation, test failures, browser errors)
- All recipes include file paths, code excerpts, patterns, and tests

### Key Patterns Highlighted
- Service instantiation: `new Service($repoPath)` (NOT dependency injection)
- Cache invalidation: Always after mutations
- Event naming: `keyboard-*`, `palette-*`, `toggle-*`, `*-updated`
- DTO factory methods: `fromOutput()`, `fromLine()`
- HandlesGitOperations trait: Auto error handling and event dispatch
- Flux UI: Use components, not manual HTML
- Testing: Always test services, components, and DTOs


## Feature Documentation Patterns

### Feature Documentation Structure
- Table of contents with anchor links to all 18+ features
- Each feature section includes: what it does, components involved, services used, key implementation details
- Cross-reference other docs: `See [StagingService](services.md#stagingservice)`
- Include "How to Extend" sections for core features (staging, committing, branch management)
- Use imperative mood, technical writing style

### Feature Categories
Group features by domain:
1. **Staging**: File-level, bulk, multi-select, tree view, hash optimization
2. **Hunk & Line Staging**: Hunk staging, line staging, patch generation
3. **Committing**: Message input, templates, prefill, amend, history cycling, undo
4. **Branch Management**: Switch, create, delete, merge, auto-stash, filtering
5. **Diff Viewing**: Unified/split, language detection, binary/image handling, large files
6. **Stashing**: Create, apply/pop/drop, individual files, auto-generated messages
7. **Push/Pull/Fetch**: Push, pull, fetch, fetch all, force push, detached HEAD guards, notifications
8. **History**: Log pagination, graph, select, reset, revert, cherry-pick
9. **Rebase**: Interactive panel, reordering, action selection
10. **Search**: Commit, file, content search
11. **Blame**: Blame view, commit navigation
12. **Conflict Resolution**: Detection, resolver UI, three-way merge
13. **Command Palette**: 28 commands, search/filter, input mode, disabled state
14. **Keyboard Shortcuts**: 15+ shortcuts, Alpine.js integration, event dispatching
15. **Repository Management**: Open, recent, switch, cache invalidation
16. **Settings**: Editor, auto-fetch, theme
17. **Auto-Fetch**: Background fetch, interval, indicator
18. **Error Handling**: Banner, toasts, translation

### Implementation Details Pattern
For each feature, document:
- **Components**: Link to components.md with anchor
- **Services**: Link to services.md with anchor
- **Events**: List events dispatched/listened
- **File**: Path to primary implementation file with line numbers
- **Code Excerpt**: Show key implementation pattern (not entire file)
- **Key Pattern**: Highlight important architectural pattern
- **Gotcha**: Note common pitfalls or edge cases

### Code Excerpt Best Practices
- Include file path and line numbers before code block
- Show relevant context (5-20 lines)
- Use real code from codebase, not pseudocode
- Highlight key patterns (trait usage, error handling, cache invalidation)

### Cross-Referencing Pattern
- Link to other docs: `[StagingService](services.md#stagingservice)`
- Link to components: `[StagingPanel](components.md#stagingpanel)`
- Link to events: `See [Event System](events.md#staging-flow)`
- Link to AGENTS.md for design system: `[AGENTS.md](../AGENTS.md)`

### "How to Extend" Sections
Include practical extension examples for core features:
- **Staging**: Add new staging operation (e.g., stage by pattern)
- **Committing**: Add new commit template
- **Command Palette**: Add new command

Show complete code examples with file paths and integration points.

### Feature Count
Total: 18+ features across the application
- Core workflow: 8 features (staging, committing, branch management, diff viewing, stashing, sync, history, rebase)
- Advanced: 5 features (search, blame, conflict resolution, command palette, keyboard shortcuts)
- Infrastructure: 5 features (repository management, settings, auto-fetch, error handling, theme)

### Key Patterns Documented
- **HandlesGitOperations trait**: Wraps operations in try/catch, dispatches events
- **Hash-based refresh**: StagingPanel, RepoSidebar skip rebuilds when status unchanged
- **Auto-stash flow**: BranchManager, RepoSidebar handle dirty tree errors
- **DTO hydration**: DiffViewer rebuilds DTOs from arrays (Livewire serialization)
- **Patch generation**: DiffService generates patches for hunk/line staging
- **Event-driven architecture**: status-updated is backbone event, fired after every git mutation
- **Cache invalidation**: Services invalidate cache groups after mutations
- **Native notifications**: NotificationService shows desktop notifications on sync operations
- **Keyboard shortcuts**: Alpine.js captures keydown events, dispatches Livewire events
- **Command palette**: 28 commands with search/filter, input mode, disabled state

### Implementation Highlights
- **Tree View**: FileTreeBuilder converts flat file list to nested tree, built on-demand in render()
- **Line Staging**: DiffService generates patches with recalculated hunk header counts
- **Branch-Based Prefill**: CommitPanel parses branch name pattern, extracts ticket number
- **Commit History Cycling**: Saves draft on first up-arrow, restores on return to index -1
- **Auto-Fetch**: Background fetch with configurable interval, respects operation queue lock
- **Three-Way Merge**: ConflictService retrieves three versions (base, ours, theirs) for resolution
- **Interactive Rebase**: RebaseService uses GIT_SEQUENCE_EDITOR to inject custom plan
- **Image Diff**: Base64 encodes old/new versions, displays side-by-side with size comparison
- **Error Translation**: GitErrorHandler maps git error messages to user-friendly text

### Extension Examples
Documented three "How to Extend" examples:
1. **Adding a New Staging Operation**: Add method to StagingService, wrap in executeGitOperation, add UI button
2. **Adding a New Commit Template**: Add to getTemplates() array, appears in dropdown automatically
3. **Adding a New Command Palette Command**: Add to getCommands() array, add listener to target component

### File References
All feature sections include file path references:
- Livewire components: `app/Livewire/{ComponentName}.php`
- Services: `app/Services/Git/{ServiceName}.php`
- Blade templates: `resources/views/livewire/{component-name}.blade.php`
- Helpers: `app/Helpers/{HelperName}.php`

### Related Documentation Links
- Architecture: System architecture, layers, data flow
- Services: Service API reference
- Components: Livewire component reference
- Events: Event system map
- DTOs: Data transfer objects
- Frontend: Blade templates, Alpine.js, Flux UI
- AGENTS.md: Design system, colors, icons

