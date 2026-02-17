# Common Development Tasks

Practical cookbook for common development tasks in the gitty codebase.

## Table of Contents

- [Adding a New Git Operation](#adding-a-new-git-operation)
- [Adding a New Livewire Component](#adding-a-new-livewire-component)
- [Adding a Command Palette Command](#adding-a-command-palette-command)
- [Adding a Keyboard Shortcut](#adding-a-keyboard-shortcut)
- [Adding a New DTO](#adding-a-new-dto)
- [Modifying the Cache Strategy](#modifying-the-cache-strategy)
- [Working with the Diff Viewer](#working-with-the-diff-viewer)
- [Adding a New Event](#adding-a-new-event)
- [Writing Tests](#writing-tests)
- [Debugging Tips](#debugging-tips)

---

## Adding a New Git Operation

Add a new git operation (e.g., `git tag`, `git cherry-pick`, `git revert`).

### 1. Create or extend a service class

**File:** `app/Services/Git/YourService.php`

Extend `AbstractGitService` to get automatic repository validation, cache, and command runner.

```php
<?php

declare(strict_types=1);

namespace App\Services\Git;

class YourService extends AbstractGitService
{
    public function yourOperation(string $param): void
    {
        // Use commandRunner.run() for operations that might fail gracefully
        $result = $this->commandRunner->run('your-command', [$param]);
        
        // Or use runOrFail() for operations that should throw on failure
        $this->commandRunner->runOrFail('your-command', [$param]);
        
        // Invalidate relevant cache groups after mutation
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }
}
```

**Pattern:**
- `run()` returns `ProcessResult` (check `successful()` or `exitCode()`)
- `runOrFail()` throws `GitCommandFailedException` on failure
- Always invalidate cache groups after mutations (see [services.md](services.md) for cache groups)

### 2. Create or update a DTO if needed

**File:** `app/DTOs/YourDTO.php`

If the operation returns structured data, create a DTO with a factory method.

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class YourDTO
{
    public function __construct(
        public string $property1,
        public string $property2,
    ) {}

    public static function fromOutput(string $output): self
    {
        // Parse git porcelain v2 output
        $lines = explode("\n", trim($output));
        // ... parsing logic ...
        
        return new self(
            property1: $extracted1,
            property2: $extracted2,
        );
    }
}
```

**Pattern:**
- Use `readonly class` for immutability
- Factory methods: `fromOutput()` for multi-line, `fromLine()` for single-line
- Parse git's porcelain v2 format (machine-readable, stable)

### 3. Add Livewire component method

**File:** `app/Livewire/YourComponent.php`

Use `HandlesGitOperations` trait for standardized error handling.

```php
use App\Livewire\Concerns\HandlesGitOperations;

class YourComponent extends Component
{
    use HandlesGitOperations;
    
    public string $repoPath;
    
    public function yourAction(string $param): void
    {
        $this->executeGitOperation(function () use ($param) {
            $service = new YourService($this->repoPath);
            $service->yourOperation($param);
            
            // Refresh component state
            $this->refreshData();
            
            // Dispatch events for other components
            $this->dispatch('status-updated');
        }, dispatchStatusUpdate: false);
    }
}
```

**Pattern:**
- `executeGitOperation()` wraps in try/catch, translates errors, dispatches events
- Set `dispatchStatusUpdate: false` if you manually dispatch `status-updated`
- Instantiate services per-request: `new Service($this->repoPath)` (NOT dependency injection)

### 4. Add Blade view button/UI element

**File:** `resources/views/livewire/your-component.blade.php`

```blade
<flux:button wire:click="yourAction('param')" variant="primary" size="sm">
    Your Action
</flux:button>
```

**Pattern:**
- Use Flux UI components (see [AGENTS.md](../AGENTS.md) for variants and sizes)
- Wrap icon-only buttons in `<flux:tooltip>` for accessibility

### 5. Write tests

**File:** `tests/Feature/Services/YourServiceTest.php`

```php
it('performs your operation', function () {
    $repo = createTestRepo();
    
    $service = new YourService($repo);
    $service->yourOperation('param');
    
    // Assert git state changed
    expect(gitCommand($repo, 'status'))->toContain('expected output');
});
```

**File:** `tests/Feature/Livewire/YourComponentTest.php`

```php
it('calls service when action triggered', function () {
    $repo = createTestRepo();
    
    Livewire::test(YourComponent::class, ['repoPath' => $repo])
        ->call('yourAction', 'param')
        ->assertDispatched('status-updated');
});
```

**Pattern:**
- Service tests: Use `createTestRepo()` helper, assert git state
- Component tests: Use `Livewire::test()`, assert events dispatched
- Run tests: `php artisan test --compact --filter=YourServiceTest`

---

## Adding a New Livewire Component

Create a new reactive component (e.g., a new panel, modal, or widget).

### 1. Create component with Artisan

```bash
php artisan make:livewire YourComponent
```

Check `config/livewire.php` for directory overrides. Default locations:
- Class: `app/Livewire/YourComponent.php`
- View: `resources/views/livewire/your-component.blade.php`

### 2. Accept repoPath prop

**File:** `app/Livewire/YourComponent.php`

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HandlesGitOperations;
use Livewire\Component;

class YourComponent extends Component
{
    use HandlesGitOperations;
    
    public string $repoPath;
    
    public function mount(): void
    {
        // Initialize component state
        $this->loadData();
    }
    
    private function loadData(): void
    {
        // Load data from git services
    }
    
    public function render()
    {
        return view('livewire.your-component');
    }
}
```

**Pattern:**
- Use `HandlesGitOperations` trait for git operations
- Accept `$repoPath` as public property
- Initialize state in `mount()`

### 3. Use HandlesGitOperations trait for git operations

**File:** `app/Livewire/YourComponent.php`

```php
public function performAction(): void
{
    $this->executeGitOperation(function () {
        $service = new YourService($this->repoPath);
        $service->doSomething();
        
        $this->loadData();
        $this->dispatch('status-updated');
    }, dispatchStatusUpdate: false);
}
```

**Pattern:**
- Trait automatically dispatches `show-error` on failure
- Trait automatically dispatches `status-updated` on success (unless `dispatchStatusUpdate: false`)

### 4. Dispatch/listen events for cross-component communication

**File:** `app/Livewire/YourComponent.php`

```php
use Livewire\Attributes\On;

#[On('repo-switched')]
public function handleRepoSwitched(string $path): void
{
    $this->repoPath = $path;
    $this->loadData();
}

#[On('status-updated')]
public function handleStatusUpdated(): void
{
    $this->loadData();
}

public function someAction(): void
{
    // Dispatch event for other components
    $this->dispatch('your-event', param: 'value');
}
```

**Pattern:**
- Listen to `repo-switched` to reload when repository changes
- Listen to `status-updated` to refresh after git operations
- Dispatch custom events for component-specific communication
- See [events.md](events.md) for complete event reference

### 5. Register in app-layout.blade.php

**File:** `resources/views/livewire/app-layout.blade.php`

```blade
@if(!empty($repoPath))
    <div class="...">
        @livewire('your-component', ['repoPath' => $repoPath], key('your-component-' . $repoPath))
    </div>
@endif
```

**Pattern:**
- Use unique `key()` with repoPath to ensure proper component lifecycle
- Wrap in `@if(!empty($repoPath))` if component requires a repository
- Place in appropriate layout section (header, sidebar, main area)

### 6. Write tests

**File:** `tests/Feature/Livewire/YourComponentTest.php`

```php
it('loads data on mount', function () {
    $repo = createTestRepo();
    
    Livewire::test(YourComponent::class, ['repoPath' => $repo])
        ->assertSet('repoPath', $repo)
        ->assertViewHas('someData');
});

it('handles repo-switched event', function () {
    $repo1 = createTestRepo();
    $repo2 = createTestRepo();
    
    Livewire::test(YourComponent::class, ['repoPath' => $repo1])
        ->dispatch('repo-switched', path: $repo2)
        ->assertSet('repoPath', $repo2);
});
```

---

## Adding a Command Palette Command

Add a new command to the command palette (⌘K).

### 1. Add entry to CommandPalette::getCommands() array

**File:** `app/Livewire/CommandPalette.php`

```php
public static function getCommands(): array
{
    return [
        // ... existing commands ...
        [
            'id' => 'your-command',
            'label' => 'Your Command Label',
            'shortcut' => '⌘⇧Y', // or null if no shortcut
            'event' => 'palette-your-command',
            'keywords' => ['your', 'command', 'search', 'terms'],
            'requiresInput' => false, // true if command needs user input
            'icon' => 'phosphor-icon-name',
        ],
    ];
}
```

**Pattern:**
- `id`: Unique identifier (kebab-case)
- `label`: Display name in palette
- `shortcut`: Keyboard shortcut (or `null`)
- `event`: Livewire event to dispatch
- `keywords`: Search terms for filtering
- `requiresInput`: Set to `true` for commands like "Create Branch"
- `icon`: Phosphor icon name (without `x-phosphor-` prefix)

### 2. Add #[On('event-name')] handler in target component

**File:** `app/Livewire/YourComponent.php`

```php
use Livewire\Attributes\On;

#[On('palette-your-command')]
public function handlePaletteYourCommand(): void
{
    $this->yourAction();
}
```

**Pattern:**
- Event name convention: `palette-{command-id}`
- Handler method convention: `handlePalette{CommandId}()`
- If command requires input, add parameter to handler

### 3. Add disabled logic in getDisabledCommands()

**File:** `app/Livewire/CommandPalette.php`

```php
public function getDisabledCommands(): array
{
    $disabled = [];
    
    if (empty($this->repoPath)) {
        $disabled = array_fill_keys([
            'your-command', // Add your command here
            // ... other commands requiring repo ...
        ], true);
    }
    
    // Add conditional disabling
    if ($this->someCondition) {
        $disabled['your-command'] = true;
    }
    
    return $disabled;
}
```

**Pattern:**
- Disable commands when repository not open
- Disable commands based on component state (e.g., no staged files for commit)
- Return array with command IDs as keys, `true` as values

### 4. Write tests

**File:** `tests/Feature/Livewire/CommandPaletteTest.php`

```php
it('includes your command in list', function () {
    $commands = CommandPalette::getCommands();
    
    $command = collect($commands)->firstWhere('id', 'your-command');
    
    expect($command)->not->toBeNull()
        ->and($command['label'])->toBe('Your Command Label')
        ->and($command['event'])->toBe('palette-your-command');
});

it('dispatches event when command executed', function () {
    Livewire::test(CommandPalette::class)
        ->call('executeCommand', 'your-command')
        ->assertDispatched('palette-your-command');
});
```

---

## Adding a Keyboard Shortcut

Add a new keyboard shortcut (e.g., ⌘⇧Y).

### 1. Add @keydown handler in app-layout.blade.php

**File:** `resources/views/livewire/app-layout.blade.php`

```blade
<div 
    class="..."
    @keydown.window.meta.shift.y.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-your-action')"
>
```

**Pattern:**
- `@keydown.window` — Global keyboard listener
- `.meta` — ⌘ (Command key on macOS)
- `.shift` — Shift key
- `.y` — Letter key (lowercase)
- `.prevent` — Prevent default browser behavior
- Check `$wire.repoPath` if action requires repository
- Dispatch Livewire event with `$wire.$dispatch()`

**Modifier keys:**
- `.meta` — ⌘ (Command)
- `.shift` — Shift
- `.alt` — Option
- `.ctrl` — Control

**Special keys:**
- `.enter` — Return/Enter
- `.escape` — Escape
- `.slash` — /
- `.space` — Space

### 2. Add event handler in target component

**File:** `app/Livewire/YourComponent.php`

```php
use Livewire\Attributes\On;

#[On('keyboard-your-action')]
public function handleKeyboardYourAction(): void
{
    $this->yourAction();
}
```

**Pattern:**
- Event name convention: `keyboard-{action-name}`
- Handler method convention: `handleKeyboard{ActionName}()`

### 3. Add to ShortcutHelp display

**File:** `app/Livewire/ShortcutHelp.php`

```php
public function getShortcuts(): array
{
    return [
        'General' => [
            // ... existing shortcuts ...
            ['key' => '⌘⇧Y', 'description' => 'Your Action'],
        ],
        // ... other categories ...
    ];
}
```

**Pattern:**
- Group shortcuts by category (General, Staging, Commit, Navigation)
- Use macOS symbols: ⌘ (Command), ⇧ (Shift), ⌥ (Option), ⌃ (Control), ↵ (Return)

### 4. Write tests

**File:** `tests/Feature/Livewire/KeyboardShortcutsTest.php`

```php
it('triggers action on keyboard shortcut', function () {
    $repo = createTestRepo();
    
    Livewire::test(YourComponent::class, ['repoPath' => $repo])
        ->dispatch('keyboard-your-action')
        ->assertDispatched('some-result-event');
});
```

---

## Adding a New DTO

Create a new Data Transfer Object for structured git data.

### 1. Create readonly class in app/DTOs/

**File:** `app/DTOs/YourDTO.php`

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class YourDTO
{
    public function __construct(
        public string $property1,
        public ?string $property2,
        public int $property3,
    ) {}
}
```

**Pattern:**
- Use `readonly class` for full immutability
- Use `readonly` properties for Livewire compatibility (if needed)
- Use nullable types (`?string`) for optional properties
- Use typed properties (PHP 8.4)

### 2. Add fromOutput() or fromLine() factory method

**File:** `app/DTOs/YourDTO.php`

```php
public static function fromOutput(string $output): self
{
    // Parse multi-line git output
    $lines = explode("\n", trim($output));
    
    // Parse porcelain v2 format
    foreach ($lines as $line) {
        if (str_starts_with($line, '# ')) {
            // Parse header line
        } elseif (str_starts_with($line, '1 ')) {
            // Parse data line
        }
    }
    
    return new self(
        property1: $extracted1,
        property2: $extracted2,
        property3: $extracted3,
    );
}

public static function fromLine(string $line): self
{
    // Parse single-line git output
    $parts = explode(' ', trim($line));
    
    return new self(
        property1: $parts[0],
        property2: $parts[1] ?? null,
        property3: (int) ($parts[2] ?? 0),
    );
}
```

**Pattern:**
- `fromOutput()`: Parse multi-line output (e.g., `git status --porcelain=v2`)
- `fromLine()`: Parse single-line output (e.g., `git branch --list`)
- Use `str_starts_with()` for prefix matching
- Use `explode()` for splitting
- Use null coalescing (`??`) for optional values
- Throw `InvalidArgumentException` for invalid input

### 3. Document parsing logic

Add docblock explaining the git output format being parsed.

```php
/**
 * Parse git status porcelain v2 output.
 * 
 * Format:
 * # branch.oid <commit>
 * # branch.head <branch>
 * 1 <XY> <sub> <mH> <mI> <mW> <hH> <hI> <path>
 * 
 * Example:
 * # branch.oid 1234567890abcdef
 * # branch.head main
 * 1 .M N... 100644 100644 100644 abc123 def456 file.txt
 */
public static function fromOutput(string $output): self
{
    // ...
}
```

**Pattern:**
- Document git command that produces the output
- Show example output format
- Explain field meanings
- Reference git documentation if complex

### 4. Add helper methods if needed

```php
public function isModified(): bool
{
    return $this->status === 'M';
}

public function displayName(): string
{
    return $this->property2 ?? $this->property1;
}
```

**Pattern:**
- Add computed properties as methods
- Use descriptive names (`isModified()`, not `modified()`)
- Return primitive types when possible

### 5. Write tests

**File:** `tests/Feature/DTOs/YourDTOTest.php`

```php
it('parses git output correctly', function () {
    $output = "# branch.oid 1234567890abcdef\n# branch.head main\n1 .M N... 100644 100644 100644 abc123 def456 file.txt";
    
    $dto = YourDTO::fromOutput($output);
    
    expect($dto->property1)->toBe('expected value')
        ->and($dto->property2)->toBe('expected value')
        ->and($dto->property3)->toBe(123);
});

it('handles empty output', function () {
    $dto = YourDTO::fromOutput('');
    
    expect($dto->property1)->toBe('default value');
});
```

---

## Modifying the Cache Strategy

Change cache TTLs, add new cache groups, or modify invalidation logic.

### 1. Understand cache groups in GitCacheService

**File:** `app/Services/Git/GitCacheService.php`

```php
private const GROUPS = [
    'status' => ['status', 'diff'],
    'history' => ['log'],
    'branches' => ['branches'],
    'remotes' => ['remotes'],
    'stashes' => ['stashes'],
    'tags' => ['tags'],
];
```

**Pattern:**
- Each group contains one or more cache keys
- Invalidating a group invalidates all keys in that group
- Groups represent related data that changes together

### 2. Add new group or modify existing TTLs

**File:** `app/Services/Git/GitCacheService.php`

```php
private const GROUPS = [
    // ... existing groups ...
    'your-group' => ['your-key-1', 'your-key-2'],
];
```

**File:** `app/Services/Git/YourService.php`

```php
public function getData(): YourDTO
{
    return $this->cache->get(
        $this->repoPath,
        'your-key-1',
        fn () => $this->fetchData(),
        ttl: 300 // 5 minutes (in seconds)
    );
}
```

**Pattern:**
- Short TTL (60s): Frequently changing data (status, diff)
- Medium TTL (300s): Moderately changing data (branches, log)
- Long TTL (3600s): Rarely changing data (remotes, tags)

### 3. Ensure proper invalidation after mutations

**File:** `app/Services/Git/YourService.php`

```php
public function mutateData(): void
{
    $this->commandRunner->runOrFail('your-command');
    
    // Invalidate affected cache groups
    $this->cache->invalidateGroup($this->repoPath, 'your-group');
    $this->cache->invalidateGroup($this->repoPath, 'status'); // If status affected
}
```

**Pattern:**
- Invalidate immediately after mutation
- Invalidate all affected groups
- Common invalidations:
  - Staging operations → `status`
  - Commits → `status`, `history`
  - Branch operations → `branches`, `status`
  - Push/pull/fetch → `branches`, `status`, `history`
  - Stash operations → `stashes`, `status`

### 4. Write tests

**File:** `tests/Feature/Services/GitCacheServiceTest.php`

```php
it('invalidates group correctly', function () {
    $cache = new GitCacheService;
    $repo = '/path/to/repo';
    
    // Cache some data
    $cache->get($repo, 'your-key-1', fn () => 'value1', 60);
    $cache->get($repo, 'your-key-2', fn () => 'value2', 60);
    
    // Invalidate group
    $cache->invalidateGroup($repo, 'your-group');
    
    // Verify cache cleared
    $cache->get($repo, 'your-key-1', fn () => 'new-value1', 60);
    expect($cache->get($repo, 'your-key-1', fn () => 'should-not-call', 60))
        ->toBe('new-value1');
});
```

---

## Working with the Diff Viewer

Understand how diff data flows and how to modify diff-related features.

### 1. Understand the data flow

```
User selects file
    ↓
StagingPanel dispatches 'file-selected' event
    ↓
DiffViewer receives event, loads diff
    ↓
GitService.diff() executes git diff command
    ↓
DiffResult.fromDiffOutput() parses raw diff
    ↓
DiffResult → DiffFile → Hunk → HunkLine
    ↓
DiffViewer renders hunks with syntax highlighting
```

**Files:**
- `app/Livewire/DiffViewer.php` — Component logic
- `app/Services/Git/GitService.php` — `diff()` method
- `app/Services/Git/DiffService.php` — Hunk/line staging
- `app/DTOs/DiffResult.php` — Root DTO
- `app/DTOs/DiffFile.php` — File-level DTO
- `app/DTOs/Hunk.php` — Hunk-level DTO
- `app/DTOs/HunkLine.php` — Line-level DTO

### 2. How hunk staging generates patches

**File:** `app/Services/Git/DiffService.php`

```php
protected function generatePatch(DiffFile $file, Hunk $hunk): string
{
    $patch = "diff --git a/{$file->oldPath} b/{$file->newPath}\n";
    $patch .= "--- a/{$file->oldPath}\n";
    $patch .= "+++ b/{$file->newPath}\n";
    $patch .= "@@ -{$hunk->oldStart},{$hunk->oldCount} +{$hunk->newStart},{$hunk->newCount} @@ {$hunk->header}\n";
    
    foreach ($hunk->lines as $line) {
        $prefix = match ($line->type) {
            'addition' => '+',
            'deletion' => '-',
            default => ' ',
        };
        $patch .= $prefix.$line->content."\n";
    }
    
    return $patch;
}
```

**Pattern:**
- Patch format: git diff header + hunk header + lines
- Each line prefixed with `+` (addition), `-` (deletion), or ` ` (context)
- Patch piped to `git apply --cached` for staging
- Patch piped to `git apply --cached --reverse` for unstaging

### 3. How split view computes paired lines

**File:** `app/Livewire/DiffViewer.php`

```php
private function computePairedLines(Hunk $hunk): array
{
    $paired = [];
    $leftNum = $hunk->oldStart;
    $rightNum = $hunk->newStart;
    
    foreach ($hunk->lines as $line) {
        if ($line->type === 'context') {
            $paired[] = ['left' => $leftNum++, 'right' => $rightNum++, 'line' => $line];
        } elseif ($line->type === 'deletion') {
            $paired[] = ['left' => $leftNum++, 'right' => null, 'line' => $line];
        } elseif ($line->type === 'addition') {
            $paired[] = ['left' => null, 'right' => $rightNum++, 'line' => $line];
        }
    }
    
    return $paired;
}
```

**Pattern:**
- Context lines: Show in both columns, increment both line numbers
- Deletions: Show in left column only, increment left line number
- Additions: Show in right column only, increment right line number
- Null line numbers render as empty cells

### 4. How line staging works

**File:** `app/Services/Git/DiffService.php`

```php
protected function generateLinePatch(DiffFile $file, Hunk $hunk, array $selectedLineIndices): string
{
    // Recalculate line counts based on selected lines
    $oldCount = 0;
    $newCount = 0;
    $patchLines = [];
    
    foreach ($hunk->lines as $index => $line) {
        $isSelected = in_array($index, $selectedLineIndices, true);
        
        if ($line->type === 'context') {
            // Context lines always included
            $oldCount++;
            $newCount++;
            $patchLines[] = ' '.$line->content;
        } elseif ($line->type === 'addition') {
            if ($isSelected) {
                // Selected additions: include as additions
                $newCount++;
                $patchLines[] = '+'.$line->content;
            } else {
                // Unselected additions: convert to context
                $oldCount++;
                $newCount++;
                $patchLines[] = ' '.$line->content;
            }
        } elseif ($line->type === 'deletion') {
            if ($isSelected) {
                // Selected deletions: include as deletions
                $oldCount++;
                $patchLines[] = '-'.$line->content;
            }
            // Unselected deletions: omit entirely
        }
    }
    
    // Build patch with recalculated counts
    // ...
}
```

**Pattern:**
- Context lines: Always included
- Selected additions: Included as `+` lines
- Unselected additions: Converted to context (` ` lines)
- Selected deletions: Included as `-` lines
- Unselected deletions: Omitted entirely
- Line counts recalculated based on included lines

### 5. Write tests

**File:** `tests/Feature/Services/LineStageTest.php`

```php
it('stages selected lines only', function () {
    $repo = createTestRepo();
    writeFile($repo, 'file.txt', "line1\nline2\nline3\n");
    gitCommand($repo, 'add file.txt');
    gitCommand($repo, 'commit -m "initial"');
    writeFile($repo, 'file.txt', "line1\nmodified2\nline3\nadded4\n");
    
    $gitService = new GitService($repo);
    $diffResult = $gitService->diff('file.txt', staged: false);
    $file = $diffResult->files->first();
    $hunk = $file->hunks->first();
    
    // Stage only the addition (line index 3)
    $diffService = new DiffService($repo);
    $diffService->stageLines($file, $hunk, [3]);
    
    // Verify only addition staged
    $stagedDiff = $gitService->diff('file.txt', staged: true);
    expect($stagedDiff->files->first()->hunks->first()->lines)
        ->toHaveCount(1)
        ->and($stagedDiff->files->first()->hunks->first()->lines[0]->content)
        ->toBe('added4');
});
```

---

## Adding a New Event

Create a new Livewire event for cross-component communication.

### 1. Choose event name and payload

**Convention:**
- Core events: `{noun}-{verb}` (e.g., `status-updated`, `file-selected`)
- Keyboard events: `keyboard-{action}` (e.g., `keyboard-commit`)
- Palette events: `palette-{action}` (e.g., `palette-push`)
- UI toggle events: `toggle-{feature}`, `open-{feature}` (e.g., `toggle-sidebar`)

**Payload:**
- Use named parameters for clarity
- Use primitive types when possible
- Use arrays for complex data

### 2. Dispatch event from source component

**File:** `app/Livewire/SourceComponent.php`

```php
public function someAction(): void
{
    // Perform action
    
    // Dispatch event with payload
    $this->dispatch('your-event', param1: 'value1', param2: 123);
}
```

**Pattern:**
- Use `$this->dispatch()` for Livewire events
- Use named parameters for payload
- Dispatch after action completes

### 3. Listen to event in target component

**File:** `app/Livewire/TargetComponent.php`

```php
use Livewire\Attributes\On;

#[On('your-event')]
public function handleYourEvent(string $param1, int $param2): void
{
    // React to event
    $this->updateState($param1, $param2);
}
```

**Pattern:**
- Use `#[On('event-name')]` attribute
- Method parameters match event payload
- Method name convention: `handle{EventName}()`

### 4. Document in events.md

Add entry to the event reference table.

**File:** `docs/events.md`

```markdown
| `your-event` | `param1: string, param2: int` | SourceComponent | TargetComponent | Brief description of purpose |
```

### 5. Write tests

**File:** `tests/Feature/Livewire/YourEventTest.php`

```php
it('dispatches event when action triggered', function () {
    $repo = createTestRepo();
    
    Livewire::test(SourceComponent::class, ['repoPath' => $repo])
        ->call('someAction')
        ->assertDispatched('your-event', param1: 'value1', param2: 123);
});

it('handles event correctly', function () {
    $repo = createTestRepo();
    
    Livewire::test(TargetComponent::class, ['repoPath' => $repo])
        ->dispatch('your-event', param1: 'value1', param2: 123)
        ->assertSet('someProperty', 'expected value');
});
```

---

## Writing Tests

Write comprehensive tests for new features.

### 1. Service tests

**File:** `tests/Feature/Services/YourServiceTest.php`

```php
<?php

use App\Services\Git\YourService;

beforeEach(function () {
    $this->repo = createTestRepo();
});

it('performs operation correctly', function () {
    $service = new YourService($this->repo);
    
    $result = $service->yourOperation('param');
    
    expect($result)->toBeInstanceOf(YourDTO::class)
        ->and($result->property)->toBe('expected value');
});

it('throws exception on invalid input', function () {
    $service = new YourService($this->repo);
    
    $service->yourOperation('invalid');
})->throws(InvalidArgumentException::class);

it('invalidates cache after mutation', function () {
    $service = new YourService($this->repo);
    
    // Cache some data
    $service->getData();
    
    // Mutate
    $service->yourOperation('param');
    
    // Verify cache invalidated
    $newData = $service->getData();
    expect($newData)->not->toBe($cachedData);
});
```

**Pattern:**
- Use `createTestRepo()` helper to create temporary git repository
- Use `beforeEach()` to set up test state
- Use `expect()` for assertions
- Use `->throws()` for exception testing
- Test cache invalidation for mutations

### 2. Component tests

**File:** `tests/Feature/Livewire/YourComponentTest.php`

```php
<?php

use App\Livewire\YourComponent;
use Livewire\Livewire;

beforeEach(function () {
    $this->repo = createTestRepo();
});

it('loads data on mount', function () {
    Livewire::test(YourComponent::class, ['repoPath' => $this->repo])
        ->assertSet('repoPath', $this->repo)
        ->assertViewHas('someData');
});

it('calls service when action triggered', function () {
    Livewire::test(YourComponent::class, ['repoPath' => $this->repo])
        ->call('yourAction', 'param')
        ->assertDispatched('status-updated');
});

it('handles event correctly', function () {
    Livewire::test(YourComponent::class, ['repoPath' => $this->repo])
        ->dispatch('some-event', param: 'value')
        ->assertSet('someProperty', 'expected value');
});

it('displays error on failure', function () {
    // Create invalid state
    
    Livewire::test(YourComponent::class, ['repoPath' => $this->repo])
        ->call('yourAction')
        ->assertDispatched('show-error');
});
```

**Pattern:**
- Use `Livewire::test()` to test components
- Use `->assertSet()` to verify property values
- Use `->assertDispatched()` to verify events
- Use `->assertViewHas()` to verify view data
- Use `->call()` to trigger component methods
- Use `->dispatch()` to trigger event handlers

### 3. DTO tests

**File:** `tests/Feature/DTOs/YourDTOTest.php`

```php
<?php

use App\DTOs\YourDTO;

it('parses output correctly', function () {
    $output = "line1\nline2\nline3";
    
    $dto = YourDTO::fromOutput($output);
    
    expect($dto->property1)->toBe('expected')
        ->and($dto->property2)->toBe('expected');
});

it('handles empty output', function () {
    $dto = YourDTO::fromOutput('');
    
    expect($dto->property1)->toBe('default');
});

it('throws on invalid input', function () {
    YourDTO::fromOutput('invalid format');
})->throws(InvalidArgumentException::class);
```

**Pattern:**
- Test factory methods with various inputs
- Test edge cases (empty, invalid, malformed)
- Test helper methods
- Use `->throws()` for exception testing

### 4. Run tests

```bash
# Run all tests
php artisan test --compact

# Run specific test file
php artisan test --compact --filter=YourServiceTest

# Run specific test
php artisan test --compact --filter="performs operation correctly"

# Run tests in parallel (faster)
php artisan test --compact --parallel
```

---

## Debugging Tips

Common debugging techniques and solutions to frequent issues.

### 1. Clearing view cache (NativePHP)

Compiled Blade views are cached in NativePHP's application support directory.

```bash
# Clear view cache
rm -rf ~/Library/Application\ Support/gitty-dev/storage/framework/views/*

# Or use Artisan
php artisan view:clear
```

**When to use:**
- Blade template changes don't appear
- Seeing old component markup
- After updating Flux UI components

### 2. Port conflicts

Dev server runs on port 8321. Port 8765 conflicts with another service.

```bash
# Start dev server on correct port
php artisan serve --port=8321

# Or use NativePHP
php artisan native:serve
```

**When to use:**
- "Address already in use" error
- Can't access dev server
- After system restart

### 3. Checking git command output

Add logging to `GitCommandRunner` to see exact git commands and output.

**File:** `app/Services/Git/GitCommandRunner.php`

```php
public function run(string $command, array $args = []): ProcessResult
{
    $fullCommand = $this->buildCommand($command, $args);
    
    // Add temporary logging
    \Log::info('Git command:', ['command' => $fullCommand]);
    
    $result = Process::path($this->repoPath)->run($fullCommand);
    
    // Log output
    \Log::info('Git output:', [
        'exitCode' => $result->exitCode(),
        'output' => $result->output(),
        'errorOutput' => $result->errorOutput(),
    ]);
    
    return $result;
}
```

**When to use:**
- Git operation fails unexpectedly
- Need to verify command arguments
- Debugging cache invalidation issues

### 4. Common Flux UI gotchas

#### Accent color not working

**Problem:** Flux buttons don't use custom accent color.

**Solution:** Accent color must be in `@theme {}`, NOT `:root {}`.

**File:** `resources/css/app.css`

```css
/* WRONG - Flux can't see this */
:root {
    --accent: #084CCF;
}

/* RIGHT - Flux reads this */
@theme {
    --color-accent: #084CCF;
    --color-accent-content: #084CCF;
    --color-accent-foreground: #ffffff;
}
```

#### Split buttons misaligned

**Problem:** Split button borders don't line up.

**Solution:** Use `<flux:button.group>`, never manual `!rounded-*` hacks.

```blade
<!-- WRONG -->
<flux:button class="!rounded-r-none">Left</flux:button>
<flux:button class="!rounded-l-none">Right</flux:button>

<!-- RIGHT -->
<flux:button.group>
    <flux:button>Left</flux:button>
    <flux:button>Right</flux:button>
</flux:button.group>
```

#### Icons off-center

**Problem:** Header icon buttons have vertically misaligned icons.

**Solution:** Add `flex items-center justify-center` to button.

```blade
<flux:button size="xs" square class="flex items-center justify-center">
    <x-phosphor-sidebar-simple class="w-4 h-4" />
</flux:button>
```

#### Dropdown items show through sticky elements

**Problem:** List items visible through sticky search field or footer.

**Solution:** Add `bg-white` to sticky elements.

```blade
<div class="sticky top-0 z-10 bg-white">
    <!-- Search field -->
</div>
```

### 5. Tailwind v4 JIT quirks

**Problem:** Some classes like `-top-1.5` don't compile.

**Solution:** Use inline `style=""` for sub-pixel positioning.

```blade
<!-- WRONG -->
<span class="-top-1.5 -right-1.5">Badge</span>

<!-- RIGHT -->
<span style="top: -6px; right: -6px;">Badge</span>
```

### 6. Livewire component not updating

**Problem:** Component doesn't re-render after property change.

**Solution:** Check if property is public and not `#[Locked]`.

```php
// WRONG - private properties don't trigger re-render
private string $data;

// RIGHT - public properties trigger re-render
public string $data;

// WRONG - locked properties can't be updated from frontend
#[Locked]
public string $data;
```

### 7. Event not firing

**Problem:** Dispatched event not received by listener.

**Solution:** Check event name matches exactly (case-sensitive).

```php
// Dispatcher
$this->dispatch('status-updated'); // lowercase

// Listener
#[On('status-updated')] // Must match exactly
public function handleStatusUpdated(): void
```

### 8. Cache not invalidating

**Problem:** Stale data after git operation.

**Solution:** Verify cache group invalidation after mutation.

```php
public function yourOperation(): void
{
    $this->commandRunner->runOrFail('your-command');
    
    // MUST invalidate affected cache groups
    $this->cache->invalidateGroup($this->repoPath, 'status');
}
```

### 9. Test failures

**Problem:** Tests fail with "Not a git repository" error.

**Solution:** Use `createTestRepo()` helper, not manual directory creation.

```php
// WRONG
$repo = sys_get_temp_dir().'/test-repo';
mkdir($repo);

// RIGHT
$repo = createTestRepo();
```

### 10. Browser console errors

**Problem:** JavaScript errors in Electron DevTools.

**Solution:** Check Livewire wire:model bindings and Alpine.js syntax.

```blade
<!-- WRONG - missing wire: prefix -->
<input model="search" />

<!-- RIGHT -->
<input wire:model.live="search" />

<!-- WRONG - invalid Alpine.js syntax -->
<div x-data="{ open: true }">

<!-- RIGHT -->
<div x-data="{ open: true }">
```

**Open DevTools in NativePHP:**
- Right-click anywhere in app
- Select "Inspect Element"
- Check Console tab for errors

---

## See Also

- [Architecture Overview](architecture.md) — System design and data flow
- [Service API Reference](services.md) — Complete service documentation
- [DTO Reference](dtos.md) — Data Transfer Objects
- [Component Reference](components.md) — Livewire components
- [Event Reference](events.md) — Event system
- [Frontend Guide](frontend.md) — Blade, Alpine.js, Flux UI
- [AGENTS.md](../AGENTS.md) — Design system and conventions
