# Learnings — Command Palette

## Conventions Discovered
- Command registry uses static method `getCommands()` returning array of command definitions
- Each command has: id, label, shortcut (nullable), event (nullable), keywords (array), requiresInput (bool), icon (string)
- Keyboard shortcut conflict resolution: use `if(!$event.shiftKey)` guard to prevent Cmd+K from firing when Cmd+Shift+K is pressed
- Palette event listeners follow pattern: `#[On('palette-{action}')]` with wrapper method calling existing component method
- Icons use phosphor icon component names (e.g., 'phosphor-plus', 'phosphor-git-branch')

## Patterns Found
- Alpine.js keyboard navigation pattern: activeIndex state, navigate(direction) method, selectActive() method
- Filter commands by case-insensitive substring match on both label and keywords array
- Use `#[Computed]` attribute for filteredCommands property to enable reactive filtering
- Keyboard nav scrolls active item into view with `scrollIntoView({ block: 'nearest', behavior: 'smooth' })`
- Commands requiring input (requiresInput: true) return early from executeCommand() - handled in separate task
- Event dispatch pattern: check if event exists, dispatch it, then close palette
- Alpine $watch pattern for reactive updates: `$watch('$wire.query', () => $nextTick(() => this.updateItems()))`

## Inline Input Mode Implementation

### Architecture
- Command palette supports two modes: `search` (default) and `input`
- Mode is controlled by the `$mode` property on the CommandPalette component
- When a command has `requiresInput: true`, executeCommand() transitions to input mode instead of closing

### Input Mode Flow
1. User selects "Create Branch" from command list
2. `executeCommand()` detects `requiresInput: true`
3. Sets `$mode = 'input'`, `$inputCommand = 'create-branch'`, clears `$inputValue` and `$inputError`
4. Blade template conditionally renders input UI based on `$mode`
5. User enters branch name and presses Enter
6. `submitInput()` validates input and dispatches `palette-create-branch` event
7. BranchManager listens via `#[On('palette-create-branch')]` and creates the branch

### Validation Rules
- Branch name cannot be empty
- Branch name cannot contain spaces
- Errors are displayed inline below the input field in Catppuccin Red (#d20f39)

### UI Components
- Input mode replaces the entire search UI (search input + command list)
- Header shows back arrow button and "Create Branch" label
- Input field uses `font-mono` for branch name consistency
- Footer shows keyboard hints: "↵ create · esc back"
- Focus is automatically set to input field when mode switches to 'input'

### Keyboard Shortcuts
- **Enter**: Submit input and create branch
- **Escape**: Cancel input mode and return to search

### Integration with BranchManager
- BranchManager already has `createBranch()` method that uses `$newBranchName` and `$baseBranch`
- New `handlePaletteCreateBranch()` listener sets these properties and calls `createBranch()`
- Base branch defaults to current branch (no selector needed for palette flow)
- Existing Create Branch modal remains unchanged for advanced use cases

### Alpine.js Auto-focus
- Used `x-ref="inputField"` and `x-effect` to auto-focus when `$wire.mode === 'input'`
- `$nextTick()` ensures DOM is ready before focusing

### Color Palette
- Error text: `#d20f39` (Catppuccin Red)
- Border default: `#ccd0da`
- Focus ring: `#084CCF` (Zed Blue)
- Text primary: `#4c4f69`
- Text tertiary: `#8c8fa1`

### Future Extensibility
- The input mode pattern can be reused for other commands that require input
- `$inputCommand` property allows different validation logic per command
- `submitInput()` uses a switch/if statement to handle different command types
