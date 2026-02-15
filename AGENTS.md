# AGENTS.md

Design system guidelines and conventions for the gitty macOS git client.

## Project Overview

gitty is a macOS-native git client built with:
- **NativePHP** (Laravel + Electron wrapper)
- **Laravel + Livewire** (reactive PHP components)
- **Flux UI** (Livewire component library by Caleb Porzio)
- **Tailwind CSS v4** (JIT compilation)
- **Catppuccin Latte** color palette
- **Phosphor Icons** (light variant for headers)

Target platform: macOS desktop via Electron (NativePHP).

## Color System: Catppuccin Latte

All values from `resources/css/app.css`.

### Background Colors
- **White**: `#ffffff` — file list panels, diff viewer, dropdown backgrounds
- **Base** (`--surface-0`): `#eff1f5` — hover state on white backgrounds, app outer background
- **Mantle** (`--surface-1`): `#e6e9ef` — section headers (Staged, Changes), main header bar
- **Crust** (`--surface-2`): `#dce0e8` — subtle borders, secondary dividers
- **Surface 0** (`--surface-3`): `#ccd0da` — primary borders, disabled button background

### Text Colors
- **Text** (`--text-primary`): `#4c4f69` — primary text
- **Subtext 0** (`--text-secondary`): `#6c6f85` — secondary text
- **Overlay 1** (`--text-tertiary`): `#8c8fa1` — tertiary text, placeholders

### Border Colors
- **Default** (`--border-default`): `#ccd0da` (Surface 0)
- **Subtle** (`--border-subtle`): `#dce0e8` (Crust)
- **Strong** (`--border-strong`): `#bcc0cc` (Surface 1)

### Accent Color
- **Zed Blue** (`--accent`, `--color-accent`): `#084CCF` — NOT Catppuccin's own blue (`#1e66f5`)
- **Accent Muted**: `rgba(8, 76, 207, 0.15)` — 15% opacity for backgrounds
- **Accent Foreground**: `#ffffff` — text on accent backgrounds

### Semantic Colors
- **Green** (`--color-green`): `#40a02b` — staged/added files
- **Yellow** (`--color-yellow`): `#df8e1d` — modified files
- **Red** (`--color-red`): `#d20f39` — deleted files, errors
- **Peach** (`--color-peach`): `#fe640b` — untracked files
- **Mauve** (`--color-mauve`): `#8839ef` — special states
- **Teal** (`--color-teal`): `#179299`
- **Sky** (`--color-sky`): `#04a5e5`
- **Lavender** (`--color-lavender`): `#7287fd`

Reference: https://catppuccin.com/palette/

## Flux UI Integration

### Accent Color Configuration
Flux reads accent colors from the `@theme {}` block in `app.css`, NOT from `:root {}`.

```css
@theme {
    --color-accent: #084CCF;
    --color-accent-content: #084CCF;
    --color-accent-foreground: #ffffff;
}
```

A `--accent` variable in `:root {}` is invisible to Flux. This is the most common gotcha.

### Button Variants
- `variant="primary"` — uses `--color-accent` (Zed blue)
- `variant="ghost"` — transparent background, hover state
- `variant="subtle"` — bordered, preferred for dropdown triggers
- `variant="danger"` — red, for destructive actions

### Button Sizes
- `size="xs"` — header icon buttons (36px height)
- `size="sm"` — commit/action buttons
- `square` — equal width/height for icon-only buttons

### Split Buttons
Always use `<flux:button.group>` for split buttons. Flux handles border welding, radius, and dividers automatically.

```blade
<flux:button.group class="w-full">
    <flux:button variant="primary" size="sm" class="flex-1">
        Commit (⌘↵)
    </flux:button>
    <flux:dropdown position="top">
        <flux:button icon:trailing="chevron-up" variant="primary" size="sm" square />
        <flux:menu>
            <flux:menu.item icon="check">Commit (⌘↵)</flux:menu.item>
            <flux:menu.item icon="arrow-up-tray">Commit & Push (⌘⇧↵)</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</flux:button.group>
```

Never use manual `!rounded-*` hacks. They create 1px misalignment.

## Icons: Phosphor Light

All icons from `codeat3/blade-phosphor-icons` package.

### Header Icons (Light Variant)
Use `-light` suffix for header icons:
- `<x-phosphor-sidebar-simple class="w-4 h-4" />`
- `<x-phosphor-folder-light class="w-4 h-4" />`
- `<x-phosphor-git-branch-light class="w-4 h-4" />`
- `<x-phosphor-arrow-up-light class="w-4 h-4" />`
- `<x-phosphor-arrow-down-light class="w-4 h-4" />`
- `<x-phosphor-arrows-clockwise-light class="w-4 h-4" />`
- `<x-phosphor-circle-notch-light class="w-4 h-4 animate-spin" />` (spinner)

### File Action Icons (Regular)
Use regular phosphor (not light) for file actions:
- `phosphor-plus` (stage file)
- `phosphor-minus` (unstage file)
- `phosphor-trash` (discard changes)
- `phosphor-arrow-counter-clockwise` (revert)

### Icon Centering
Header icon buttons need `flex items-center justify-center` for proper vertical alignment.

```blade
<flux:button variant="ghost" size="xs" square class="flex items-center justify-center">
    <x-phosphor-sidebar-simple class="w-4 h-4" />
</flux:button>
```

## Status Indicators

### File Status Dots
Use colored dots (not Flux badges) for file status. Consistent across flat view, tree view, and diff header badges.

- Size: `w-2.5 h-2.5 rounded-full`
- Colors (exact Catppuccin hex):
  - Modified: `bg-[#df8e1d]` (Yellow)
  - Added/Untracked: `bg-[#40a02b]` (Green)
  - Deleted: `bg-[#d20f39]` (Red)
  - Renamed: `bg-[#084CCF]` (Zed Blue)
  - Unmerged: `bg-[#fe640b]` (Peach)

### Diff Header Badges
Use inline-styled divs, NOT `<flux:badge>`. Flux badges use their own color palette which doesn't match Catppuccin.

```blade
@php
    $badgeColor = match(strtoupper($status)) {
        'MODIFIED', 'M' => '#df8e1d',
        'ADDED', 'A' => '#40a02b',
        'DELETED', 'D' => '#d20f39',
        'RENAMED', 'R' => '#084CCF',
        default => '#9ca0b0',
    };
@endphp
<div class="px-2 py-0.5 rounded text-xs font-medium uppercase tracking-wider"
     style="background-color: {{ $badgeColor }}15; color: {{ $badgeColor }}">
    {{ strtoupper($status) }}
</div>
```

The `15` suffix on the hex color creates a ~8% opacity background tint.

## Hover & Interaction States

### File Item Hover
On white backgrounds, use `hover:bg-[#eff1f5]` (Base). The previous `hover:bg-[#dce0e8]` (Crust) is too dark on white.

Color scale (light to dark):
- `#ffffff` — item background (white)
- `#eff1f5` — hover state (Base) ← USE THIS
- `#e6e9ef` — section headers (Mantle)
- `#dce0e8` — too dark for hover on white (Crust)

### Tooltips on Action Buttons
All icon-only action buttons must be wrapped in `<flux:tooltip>`:

```blade
<flux:tooltip content="Stage All">
    <flux:button wire:click="stageAll" variant="ghost" size="xs" square>
        <x-phosphor-plus class="w-4 h-4" />
    </flux:button>
</flux:tooltip>
```

Required tooltip labels: Stage All, Unstage All, Discard All, Stage, Unstage, Discard.

## Tree View

The tree view must look visually identical to the flat view, just with indentation and collapsible folders.

### Rules
- **No dividers**: The wrapper `<div>` must NOT have `divide-y`. File items sit edge-to-edge like the flat view.
- **No borders on folders**: Directory wrappers must NOT have `border-b`.
- **Folder icon**: Use `<x-phosphor-folder-simple class="w-3.5 h-3.5 text-[#9ca0b0]" />`, NOT a diamond (◆) or other custom glyph.
- **Same density**: Items use `py-1.5` and `gap-2.5`, matching flat view exactly.
- **Indentation**: `padding-left: {{ ($level * 16) + 16 }}px` via inline style.
- **Collapse chevron**: Small SVG arrow (`w-3 h-3`) that rotates 90° when expanded.

## Dropdown Backgrounds

### Sticky Areas Need Explicit Backgrounds
Sticky search fields and footer buttons in dropdowns (branch-manager, repo-switcher) MUST have `bg-white`. Without it, list items show through on scroll.

```blade
{{-- Search field — sticky at top --}}
<div class="p-2 border-b ... sticky top-0 z-10 bg-white">
    ...
</div>

{{-- Footer button — sticky at bottom --}}
<div class="border-t ... p-2 sticky bottom-0 bg-white">
    ...
</div>
```

## Header Layout

Fixed height: `h-9` (36px). Background: `bg-[#e6e9ef]`. Border: `border-b border-[#ccd0da]`.

```
[64px traffic-light-spacer] [sidebar-toggle] [folder-icon repo ∨] [git-branch-icon branch ∨] [flex-1 spacer] [↑ push] [↓ pull] [↻ fetch]
```

### Traffic Light Spacer
macOS window controls (red/yellow/green buttons) occupy ~64px on the left.

```blade
<div class="w-16" style="-webkit-app-region: drag;"></div>
```

### Drag Region
The header is draggable (for moving the window), but buttons must opt out:

```blade
<div class="..." style="-webkit-app-region: drag;">
    <div style="-webkit-app-region: no-drag;">
        <flux:button>...</flux:button>
    </div>
</div>
```

### No Bottom Status Bar
The bottom status bar was removed as redundant. All status info is in the header or panels.

### Diff Viewer Header
White background (`bg-white`), same padding as staging toolbar (`px-4 py-2`). Sticky with `sticky top-0 z-10` and subtle box-shadow. Shows file path, status badge, and +/- counts.

## CSS Architecture

Two systems coexist in `resources/css/app.css`:

### 1. `@theme {}` — Tailwind/Flux Design Tokens
Available as Tailwind utilities (e.g., `font-sans`, `font-mono`).

```css
@theme {
    --font-sans: 'Instrument Sans', 'Inter', system-ui, ...;
    --font-mono: 'JetBrains Mono', 'SF Mono', ...;
    --color-accent: #084CCF;
    --color-accent-content: #084CCF;
    --color-accent-foreground: #ffffff;
}
```

### 2. `:root {}` — Custom CSS Properties
Used via `var(--name)` or `bg-[var(--name)]` in Tailwind.

```css
:root {
    --surface-0: #eff1f5;
    --text-primary: #4c4f69;
    --border-default: #ccd0da;
    --color-green: #40a02b;
    /* ... */
}
```

### Hardcoded Hex Values
Blade templates use hardcoded hex values (e.g., `bg-[#eff1f5]`) for Catppuccin colors. This is intentional for clarity and grep-ability.

```blade
<div class="bg-[#eff1f5] text-[#4c4f69] border-[#ccd0da]">
```

### Diff Viewer Styles
CSS classes with Catppuccin-tinted backgrounds:

```css
.diff-line-addition {
    background-color: rgba(64, 160, 43, 0.1); /* green/10 */
}

.diff-line-deletion {
    background-color: rgba(210, 15, 57, 0.1); /* red/10 */
}

.diff-line-context {
    background-color: var(--surface-0);
}
```

## Git Commit Style

Format: `type(scope): lowercase message`

### Types
- `feat` — new feature
- `fix` — bug fix
- `design` — UI/UX changes
- `refactor` — code restructuring
- `test` — test additions/changes
- `chore` — tooling, dependencies
- `docs` — documentation

### Scopes
- `backend` — Laravel/PHP logic
- `tokens` — design tokens, CSS variables
- `layout` — app structure, panels
- `header` — header bar, navigation
- `staging` — staging panel, file list
- `panels` — commit panel, diff viewer
- `tests` — test files
- `polish` — micro-interactions, animations
- `modals` — modal dialogs

### Examples
```
feat(staging): add bulk stage/unstage actions
fix(header): correct traffic light spacer width
design(tokens): switch to Catppuccin Latte palette
refactor(panels): extract commit logic to service
```

## Key Gotchas

### 1. Flux Accent ≠ CSS Accent
Flux reads `--color-accent` from `@theme {}`. A `--accent` in `:root {}` is invisible to Flux.

**Wrong:**
```css
:root {
    --accent: #084CCF; /* Flux can't see this */
}
```

**Right:**
```css
@theme {
    --color-accent: #084CCF; /* Flux reads this */
}
```

### 2. NativePHP View Cache
Compiled Blade views are cached in `~/Library/Application Support/gitty-dev/storage/framework/views/`. If template changes don't appear, clear this directory.

```bash
rm -rf ~/Library/Application\ Support/gitty-dev/storage/framework/views/*
```

### 3. Tailwind v4 JIT Quirks
Some classes like `-top-1.5` don't compile. Use inline `style=""` for sub-pixel positioning.

**Wrong:**
```blade
<span class="-top-1.5 -right-1.5">...</span>
```

**Right:**
```blade
<span style="top: 2px; right: 2px;">...</span>
```

### 4. Port Conflicts
Dev server runs on port 8321. Port 8765 conflicts with another service.

```bash
php artisan serve --port=8321
```

### 5. Split Buttons
Always use `<flux:button.group>`, never manual `!rounded-*` hacks. They create 1px misalignment.

**Wrong:**
```blade
<flux:button class="!rounded-r-none">...</flux:button>
<flux:button class="!rounded-l-none">...</flux:button>
```

**Right:**
```blade
<flux:button.group>
    <flux:button>...</flux:button>
    <flux:button>...</flux:button>
</flux:button.group>
```

### 6. Icon Centering
Header icon buttons need `flex items-center justify-center` for proper vertical alignment. Without this, icons sit slightly off-center.

```blade
<flux:button size="xs" square class="flex items-center justify-center">
    <x-phosphor-sidebar-simple class="w-4 h-4" />
</flux:button>
```

### 7. Flux Badge Colors ≠ Catppuccin
`<flux:badge color="yellow">` uses Flux's own yellow, NOT Catppuccin's `#df8e1d`. For exact color matching, use inline-styled divs (see "Diff Header Badges" section).

### 8. Header Icon Colors
Header trigger icons (folder, git-branch, chevrons in dropdowns) must use `#6c6f85` (Subtext 0 / text-secondary), NOT `#9ca0b0` (Overlay 0 / border color). Icons should match text weight, not border weight.

### 9. Remote Branch Filtering
The branch manager only shows remote branches that don't have a corresponding local branch. `origin/main` is hidden when local `main` exists. This avoids redundancy in the dropdown.

## Typography

### Fonts
- **Sans**: Instrument Sans, Inter, system-ui
- **Mono**: JetBrains Mono, SF Mono, Fira Code

### Usage
- Headers, buttons, labels: `font-sans`
- Code, diffs, commit messages: `font-mono`

## Animations

Defined in `app.css`:

- `animate-slide-in` — 150ms slide from left (file list items)
- `animate-commit-flash` — 200ms blue glow (commit button)
- `animate-sync-pulse` — 200ms opacity pulse (sync buttons)
- `animate-fade-in` — 150ms fade in (empty states)

## Keyboard Shortcuts

Defined in `app-layout.blade.php`:

- `⌘↵` — commit
- `⌘⇧↵` — commit and push
- `⌘⇧K` — stage all
- `⌘⇧U` — unstage all
- `⌘B` — toggle sidebar
- `Esc` — close modals, clear selection

## Development Notes

### Running the App
```bash
php artisan native:serve
```

### Clearing Caches
```bash
php artisan view:clear
php artisan config:clear
rm -rf ~/Library/Application\ Support/gitty-dev/storage/framework/views/*
```

### Debugging
- Livewire components: `wire:loading`, `wire:target`
- Alpine.js: `x-data`, `x-show`, `x-bind`
- Browser DevTools: Electron app runs Chromium

## References

- Catppuccin Latte: https://catppuccin.com/palette/
- Flux UI: https://fluxui.dev
- Phosphor Icons: https://phosphoricons.com
- Tailwind CSS v4: https://tailwindcss.com/docs
- NativePHP: https://nativephp.com
