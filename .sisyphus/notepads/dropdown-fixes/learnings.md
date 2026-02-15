
## Flux Dropdown Close Behavior (2026-02-15)

### Problem
Custom `<div>` elements with `wire:click` inside Flux dropdowns don't auto-close the dropdown like `<flux:menu.item>` does.

### Root Cause
- Flux `<flux:menu>` renders as a `[popover]` element using the native Popover API
- `<flux:menu.item>` has built-in JS that calls `hidePopover()` on click
- Custom divs with `wire:click` don't trigger this mechanism

### Solution
Add Alpine.js click handler to custom menu items:
```blade
<div
    wire:click="switchRepo({{ $repo['id'] }})"
    x-on:click="$el.closest('[popover]')?.hidePopover()"
>
```

### How It Works
1. `$el.closest('[popover]')` finds the nearest ancestor with `[popover]` attribute (the Flux menu panel)
2. `?.hidePopover()` calls the native Popover API method to close it
3. Optional chaining (`?.`) prevents errors if popover not found
4. Alpine and Livewire handle events independently, so both handlers fire

### Files Modified
- `resources/views/livewire/repo-switcher.blade.php` (line 63)
- `resources/views/livewire/branch-manager.blade.php` (line 87)

### Important Notes
- Do NOT add this to buttons that should keep dropdown open (e.g., "Open Repository", "New Branch")
- Do NOT add this to delete/trash buttons with `wire:click.stop` (they already prevent propagation)
- The `wire:click` handler still executes — this just adds the close behavior
- This pattern works for any custom clickable element inside Flux dropdowns

### Alternative Approaches (Not Used)
- `$el.closest('ui-dropdown')?.close?.()` — relies on Flux's custom element API (less stable)
- Dispatching custom events — more complex, unnecessary for this use case
- Replacing with `<flux:menu.item>` — loses custom styling and layout control
