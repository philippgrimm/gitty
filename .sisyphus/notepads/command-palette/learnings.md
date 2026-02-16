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
