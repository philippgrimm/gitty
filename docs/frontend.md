# Frontend Architecture

Comprehensive guide to gitty's frontend stack: layout structure, CSS architecture, Alpine.js components, Flux UI patterns, and NativePHP integration.

## Table of Contents

1. [Overview](#overview)
2. [Layout Structure](#layout-structure)
3. [CSS Architecture](#css-architecture)
4. [Alpine.js Components](#alpinejs-components)
5. [Flux UI Usage Patterns](#flux-ui-usage-patterns)
6. [Phosphor Icons](#phosphor-icons)
7. [Custom Blade Components](#custom-blade-components)
8. [NativePHP Integration](#nativephp-integration)
9. [JavaScript Modules](#javascript-modules)
10. [Design System Reference](#design-system-reference)

---

## Overview

gitty's frontend is built with:

- **Laravel Blade** — server-rendered templates
- **Livewire 4** — reactive PHP components (no JavaScript required)
- **Alpine.js** — client-side interactivity (panel resize, theme toggle, keyboard shortcuts)
- **Flux UI** — Livewire component library (buttons, dropdowns, modals, tooltips)
- **Tailwind CSS v4** — utility-first CSS with JIT compilation
- **Phosphor Icons** — light variant for headers, regular for actions
- **NativePHP** — Electron wrapper for macOS desktop app
- **Highlight.js** — syntax highlighting for diff viewer

All views are server-rendered. Livewire handles state management and reactivity. Alpine.js provides lightweight client-side interactions where needed (drag handles, theme toggle, keyboard shortcuts).

---

## Layout Structure

### Base HTML Layout

**File:** `resources/views/layouts/app.blade.php`

Minimal HTML wrapper. Loads fonts, Vite assets, Livewire, and Flux scripts.

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#eff1f5">
    <title>{{ config('app.name', 'Gitty') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|jetbrains-mono:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    @livewireStyles
</head>
<body class="min-h-screen bg-[var(--surface-0)] text-[var(--text-primary)]">
    {{ $slot }}
    
    @livewireScripts
    @fluxScripts
</body>
</html>
```

**Key Points:**

- Preconnects to fonts.bunny.net for faster font loading
- Vite compiles `resources/css/app.css` and `resources/js/app.js`
- `@fluxAppearance` injects Flux UI theme detection
- `@livewireScripts` and `@fluxScripts` load required JavaScript

### App Layout (Three-Panel Structure)

**File:** `resources/views/livewire/app-layout.blade.php`

Full application layout with header, sidebar, and three-panel workspace.

```
┌─────────────────────────────────────────────────────────────────┐
│ Header (36px, h-9)                                              │
│ [traffic-light-spacer] [sidebar-toggle] [repo ∨] [branch ∨]    │
│ [flex-1 spacer] [↑ push] [↓ pull] [↻ fetch] [theme-toggle]     │
├─────────────────────────────────────────────────────────────────┤
│ ┌─────────┬──────────────────┬──────────────────────────────┐  │
│ │ Sidebar │ Left Panel       │ Right Panel                  │  │
│ │ (250px) │ (resizable)      │ (flex-1)                     │  │
│ │         │                  │                              │  │
│ │ Stashes │ ┌──────────────┐ │ ┌──────────────────────────┐ │  │
│ │ Remotes │ │ Staging      │ │ │ Diff Viewer              │ │  │
│ │ Tags    │ │ Panel        │ │ │ (or History/Blame)       │ │  │
│ │         │ │              │ │ │                          │ │  │
│ │         │ └──────────────┘ │ └──────────────────────────┘ │  │
│ │         │ ┌──────────────┐ │                              │  │
│ │         │ │ Commit Panel │ │                              │  │
│ │         │ └──────────────┘ │                              │  │
│ └─────────┴──────────────────┴──────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

**Panel Dimensions:**

- **Header:** 36px (`h-9`)
- **Sidebar:** 250px (collapsible to 0px)
- **Left Panel:** Resizable (default: 1/3 of workspace width, min: 200px, max: 50%)
- **Right Panel:** Flex-1 (fills remaining space)

**Panel Switching:**

Right panel switches between three views:

- **Diff Viewer** (default) — shows file diffs
- **History Panel** — commit history
- **Blame View** — git blame for selected file

Controlled by Alpine.js `activeRightPanel` state. Events trigger panel switches:

- `@file-selected.window` → switch to diff
- `@toggle-history-panel.window` → toggle history
- `@show-blame.window` → switch to blame

---

## CSS Architecture

**File:** `resources/css/app.css`

Two CSS systems coexist:

1. **`@theme {}`** — Tailwind/Flux design tokens (fonts, accent colors)
2. **`:root {}`** — CSS custom properties (Catppuccin colors, surfaces, borders)

### Why Two Systems?

- **Flux UI reads from `@theme {}`** — Flux components use `--color-accent`, `--font-sans`, etc.
- **Custom styles use `:root {}`** — Blade templates reference `var(--surface-0)`, `var(--text-primary)`, etc.

**Critical Gotcha:** Flux accent color MUST be in `@theme {}`, NOT `:root {}`. A `--accent` variable in `:root {}` is invisible to Flux.

### `@theme {}` — Tailwind/Flux Design Tokens

```css
@theme {
    /* Typography */
    --font-sans: 'Instrument Sans', 'Inter', system-ui, ui-sans-serif, sans-serif, ...;
    --font-mono: 'JetBrains Mono', 'SF Mono', 'Fira Code', ui-monospace, monospace;
    
    /* Flux Accent Colors - Zed Blue */
    --color-accent: #084CCF;
    --color-accent-content: #084CCF;
    --color-accent-foreground: #ffffff;
}
```

**Usage in Tailwind:**

- `font-sans` → Instrument Sans
- `font-mono` → JetBrains Mono
- Flux buttons with `variant="primary"` use `--color-accent`

### `:root {}` — CSS Custom Properties

```css
:root {
    /* Catppuccin Latte - Background Colors */
    --surface-0: #eff1f5;        /* Base - main background */
    --surface-1: #e6e9ef;        /* Mantle - elevated panels, headers */
    --surface-2: #dce0e8;        /* Crust - hover states, subtle elevation */
    --surface-3: #ccd0da;        /* Surface 0 - highest elevation, active states */
    
    /* Catppuccin Latte - Border Colors */
    --border-default: #ccd0da;   /* Surface 0 */
    --border-subtle: #dce0e8;    /* Crust */
    --border-strong: #bcc0cc;    /* Surface 1 */
    
    /* Catppuccin Latte - Text Colors */
    --text-primary: #4c4f69;     /* Text */
    --text-secondary: #6c6f85;   /* Subtext 0 */
    --text-tertiary: #8c8fa1;    /* Overlay 1 */
    
    /* Accent Colors - Zed's Brand Blue */
    --accent: #084CCF;           /* Zed blue - primary accent */
    --accent-muted: rgba(8, 76, 207, 0.15);  /* Zed blue/15 */
    --accent-text: #084CCF;      /* Zed blue */
    
    /* Catppuccin Latte - Semantic Colors */
    --color-green: #40a02b;      /* Green - added/staged */
    --color-red: #d20f39;        /* Red - deleted/errors */
    --color-yellow: #df8e1d;     /* Yellow - modified/warnings */
    --color-peach: #fe640b;      /* Peach - untracked */
    --color-blue: #084CCF;       /* Zed blue - primary */
    --color-mauve: #8839ef;      /* Mauve - special */
    --color-teal: #179299;       /* Teal */
    --color-sky: #04a5e5;        /* Sky */
    --color-lavender: #7287fd;   /* Lavender */
}
```

**Usage in Blade:**

```blade
<div class="bg-[var(--surface-0)] text-[var(--text-primary)] border-[var(--border-default)]">
```

### Hardcoded Hex Values Pattern

Blade templates use hardcoded hex values for Catppuccin colors. This is **intentional** for grep-ability and clarity.

```blade
<div class="bg-[#eff1f5] text-[#4c4f69] border-[#ccd0da]">
```

**Why?** Easier to search for color usage across the codebase. `grep "#eff1f5"` finds all instances of Base background.

### Custom CSS Classes

**Diff Viewer Styles:**

```css
.diff-line-addition {
    background-color: rgba(64, 160, 43, 0.1); /* green/10 */
}

.diff-line-deletion {
    background-color: rgba(210, 15, 57, 0.1); /* red/10 */
}

.diff-line-context {
    background-color: #ffffff;
}
```

**Animations:**

```css
@keyframes slideIn {
    from { opacity: 0; transform: translateX(-8px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes commitFlash {
    0% { box-shadow: 0 0 0 0 rgba(8, 76, 207, 0.4); }
    100% { box-shadow: 0 0 0 8px rgba(8, 76, 207, 0); }
}

.animate-slide-in { animation: slideIn 150ms ease-out; }
.animate-commit-flash { animation: commitFlash 200ms ease-out; }
```

**Usage:**

- `.animate-slide-in` — file list items fade in from left
- `.animate-commit-flash` — commit button flashes blue on success
- `.animate-sync-pulse` — sync buttons pulse during fetch/pull/push
- `.animate-fade-in` — empty states fade in

### Dark Mode

Dark mode uses Catppuccin Mocha palette. Defined in `.dark {}` block.

```css
.dark {
    --surface-0: #1e1e2e;        /* Base - main background */
    --surface-1: #181825;        /* Mantle - elevated panels, headers */
    --text-primary: #cdd6f4;     /* Text */
    --text-secondary: #a6adc8;   /* Subtext 0 */
    --color-green: #a6e3a1;      /* Green - added/staged */
    --color-red: #f38ba8;        /* Red - deleted/errors */
    /* ... */
}
```

**Activation:** Alpine.js theme toggle adds/removes `.dark` class on `<html>` element.

---

## Alpine.js Components

Alpine.js provides lightweight client-side interactivity. All Alpine components are defined inline in Blade templates using `x-data`.

### Panel Resize Handler

**File:** `resources/views/livewire/app-layout.blade.php`

Resizable divider between left panel (staging+commit) and right panel (diff/history/blame).

```blade
<div class="flex-1 flex overflow-hidden relative"
     x-data="{
         panelWidth: null,
         isDragging: false,
         startX: 0,
         startWidth: 0,
         init() {
             const saved = localStorage.getItem('gitty-panel-width');
             if (saved && !isNaN(parseInt(saved))) {
                 this.panelWidth = parseInt(saved);
             }
         },
         get effectiveWidth() {
             if (this.panelWidth) return this.panelWidth;
             return Math.round(this.$el.offsetWidth / 3);
         },
         startDrag(e) {
             this.isDragging = true;
             this.startX = e.clientX;
             this.startWidth = this.effectiveWidth;
             document.body.style.cursor = 'col-resize';
             document.body.style.userSelect = 'none';
         },
         onDrag(e) {
             if (!this.isDragging) return;
             const delta = e.clientX - this.startX;
             const maxWidth = Math.round(this.$el.offsetWidth * 0.5);
             this.panelWidth = Math.min(Math.max(this.startWidth + delta, 200), maxWidth);
         },
         stopDrag() {
             if (!this.isDragging) return;
             this.isDragging = false;
             document.body.style.cursor = '';
             document.body.style.userSelect = '';
             if (this.panelWidth) {
                 localStorage.setItem('gitty-panel-width', this.panelWidth.toString());
             }
         }
     }"
     @mousemove.window="onDrag($event)"
     @mouseup.window="stopDrag()"
>
```

**Key Features:**

- **Persistent width:** Saves to `localStorage` as `gitty-panel-width`
- **Default width:** 1/3 of workspace width if no saved value
- **Min width:** 200px
- **Max width:** 50% of workspace width
- **Drag handle:** 5px wide, changes cursor to `col-resize`

### Theme Toggle

**File:** `resources/views/livewire/app-layout.blade.php`

Toggles between light and dark mode. Persists preference to `localStorage`.

```blade
<div x-data="{
         theme: localStorage.getItem('gitty-theme') || 'system',
         init() { 
             this.apply();
             window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => { 
                 if (this.theme === 'system') this.apply();
             });
         },
         apply() {
             const dark = this.theme === 'dark' || 
                 (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
             document.documentElement.classList.toggle('dark', dark);
         },
         toggle() {
             const isDark = document.documentElement.classList.contains('dark');
             this.theme = isDark ? 'light' : 'dark';
             localStorage.setItem('gitty-theme', this.theme);
             this.apply();
             $wire.dispatch('theme-changed', { theme: this.theme });
         }
     }"
     @theme-updated.window="theme = $event.detail.theme; localStorage.setItem('gitty-theme', theme); apply()">
    <flux:button @click="toggle()" variant="ghost" size="xs" square>
        <template x-if="document.documentElement.classList.contains('dark')">
            <x-phosphor-sun-light class="w-4 h-4" />
        </template>
        <template x-if="!document.documentElement.classList.contains('dark')">
            <x-phosphor-moon-light class="w-4 h-4" />
        </template>
    </flux:button>
</div>
```

**Key Features:**

- **Three modes:** `light`, `dark`, `system`
- **System preference:** Listens to `prefers-color-scheme` media query
- **Persistent:** Saves to `localStorage` as `gitty-theme`
- **Icon toggle:** Sun icon in dark mode, moon icon in light mode

### Active Right Panel Switcher

**File:** `resources/views/livewire/app-layout.blade.php`

Switches between diff viewer, history panel, and blame view.

```blade
<div x-data="{ activeRightPanel: 'diff' }"
     @file-selected.window="activeRightPanel = 'diff'"
     @toggle-history-panel.window="activeRightPanel = activeRightPanel === 'history' ? 'diff' : 'history'"
     @show-blame.window="activeRightPanel = 'blame'"
>
    <div x-show="activeRightPanel === 'diff'" class="h-full">
        @livewire('diff-viewer', ['repoPath' => $repoPath], key('diff-viewer-' . $repoPath))
    </div>
    <div x-show="activeRightPanel === 'history'" class="h-full">
        @livewire('history-panel', ['repoPath' => $repoPath], key('history-panel-' . $repoPath))
    </div>
    <div x-show="activeRightPanel === 'blame'" class="h-full">
        @livewire('blame-view', ['repoPath' => $repoPath], key('blame-view-' . $repoPath))
    </div>
</div>
```

**Key Features:**

- **Default panel:** `diff`
- **Event-driven:** Listens to window events to switch panels
- **Conditional rendering:** Uses `x-show` to toggle visibility

### Keyboard Shortcut Bindings

**File:** `resources/views/livewire/app-layout.blade.php`

Global keyboard shortcuts defined on root `<div>` element.

```blade
<div @keydown.window.meta.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit')"
     @keydown.window.meta.shift.enter.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-commit-push')"
     @keydown.window.meta.shift.k.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-stage-all')"
     @keydown.window.meta.shift.u.prevent="if (!$wire.repoPath) return; $wire.$dispatch('keyboard-unstage-all')"
     @keydown.window.meta.b.prevent="if (!$wire.repoPath) return; $wire.toggleSidebar()"
     @keydown.window.escape.prevent="$wire.$dispatch('keyboard-escape')"
>
```

**Shortcuts:**

- `⌘↵` — commit
- `⌘⇧↵` — commit and push
- `⌘⇧K` — stage all
- `⌘⇧U` — unstage all
- `⌘B` — toggle sidebar
- `Esc` — close modals, clear selection

**Pattern:** Alpine.js listens to keyboard events, dispatches Livewire events. Livewire components listen to these events and execute actions.

### File Selection (Multi-Select)

**File:** `resources/views/livewire/staging-panel.blade.php`

Multi-select file list with Cmd+Click (toggle), Shift+Click (range), and right-click context menu.

```blade
<div x-data="{ 
         selectedFiles: [],
         lastClickedFile: null,
         isSelected(path) { 
             return this.selectedFiles.includes(path); 
         },
         handleFileClick(path, staged, event) {
             if (event.metaKey) {
                 // Cmd+Click: toggle
                 if (this.isSelected(path)) {
                     this.selectedFiles = this.selectedFiles.filter(f => f !== path);
                 } else {
                     this.selectedFiles.push(path);
                 }
             } else if (event.shiftKey && this.lastClickedFile) {
                 // Shift+Click: range select using visible DOM order
                 const items = [...this.$el.querySelectorAll('[data-file-path]')];
                 const paths = items.map(el => el.dataset.filePath);
                 const startIdx = paths.indexOf(this.lastClickedFile);
                 const endIdx = paths.indexOf(path);
                 if (startIdx !== -1 && endIdx !== -1) {
                     const [from, to] = [Math.min(startIdx, endIdx), Math.max(startIdx, endIdx)];
                     this.selectedFiles = paths.slice(from, to + 1);
                 }
             } else {
                 // Normal click: clear selection, select one
                 this.selectedFiles = [path];
             }
             this.lastClickedFile = path;
             $wire.selectFile(path, staged);
         },
         clearSelection() { 
             this.selectedFiles = []; 
             this.lastClickedFile = null; 
         }
     }">
```

**Key Features:**

- **Cmd+Click:** Toggle individual file selection
- **Shift+Click:** Range select from last clicked file
- **Normal click:** Clear selection, select one file
- **Right-click:** Show context menu (stage/unstage/discard)

### Dropdown Keyboard Navigation

**File:** `resources/views/livewire/repo-switcher.blade.php`, `resources/views/livewire/branch-manager.blade.php`

Arrow key navigation in dropdowns (repo switcher, branch manager).

```blade
<div x-data="{
         activeIndex: -1,
         items: [],
         init() {
             this.updateItems();
         },
         updateItems() {
             this.items = [...this.$el.querySelectorAll('[data-repo-item]')];
             this.activeIndex = -1;
         },
         navigate(direction) {
             if (this.items.length === 0) return;
             if (direction === 'down') {
                 this.activeIndex = this.activeIndex < this.items.length - 1 ? this.activeIndex + 1 : 0;
             } else {
                 this.activeIndex = this.activeIndex > 0 ? this.activeIndex - 1 : this.items.length - 1;
             }
             this.items[this.activeIndex]?.scrollIntoView({ block: 'nearest' });
         },
         selectActive() {
             if (this.activeIndex >= 0 && this.items[this.activeIndex]) {
                 this.items[this.activeIndex].click();
             }
         }
     }"
     @keydown.arrow-down.prevent="navigate('down')"
     @keydown.arrow-up.prevent="navigate('up')"
     @keydown.enter.prevent="selectActive()"
>
```

**Key Features:**

- **Arrow Down/Up:** Navigate through items
- **Enter:** Select active item
- **Auto-scroll:** Active item scrolls into view

---

## Flux UI Usage Patterns

Flux UI is the official Livewire component library. gitty uses the free edition (all free components, no Pro components).

### Button Variants

```blade
<flux:button variant="primary">Commit</flux:button>
<flux:button variant="ghost">Cancel</flux:button>
<flux:button variant="subtle">Open</flux:button>
<flux:button variant="danger">Discard</flux:button>
```

**Variants:**

- `primary` — uses `--color-accent` (Zed blue)
- `ghost` — transparent background, hover state
- `subtle` — bordered, preferred for dropdown triggers
- `danger` — red, for destructive actions

### Button Sizes

```blade
<flux:button size="xs">Small</flux:button>
<flux:button size="sm">Medium</flux:button>
<flux:button square>Icon</flux:button>
```

**Sizes:**

- `xs` — header icon buttons (36px height)
- `sm` — commit/action buttons
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

**Never use manual `!rounded-*` hacks.** They create 1px misalignment.

### Dropdowns

```blade
<flux:dropdown position="bottom-start">
    <flux:button variant="subtle" size="xs">
        <x-phosphor-folder-light class="w-3.5 h-3.5" />
        {{ $currentRepoName }}
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </flux:button>
    <flux:menu class="w-80 max-h-[500px] overflow-hidden !p-0">
        <!-- Dropdown content -->
    </flux:menu>
</flux:dropdown>
```

**Key Points:**

- **Position:** `bottom-start`, `bottom-end`, `top`, etc.
- **Sticky areas need explicit backgrounds:** Search fields and footer buttons must have `bg-white` to prevent list items showing through on scroll.

**Example (sticky search field):**

```blade
<div class="p-2 border-b sticky top-0 z-10 bg-white dark:bg-[var(--surface-0)]">
    <input type="text" wire:model.live.debounce.300ms="branchQuery" placeholder="Search…" />
</div>
```

### Modals

```blade
<flux:modal wire:model="showDiscardModal" class="space-y-6">
    <div>
        <flux:heading size="lg" class="font-mono uppercase tracking-wider">Discard Changes?</flux:heading>
        <flux:subheading class="font-mono">
            This will discard all unstaged changes. This action cannot be undone.
        </flux:subheading>
    </div>
    <div class="flex gap-2 justify-end">
        <flux:button variant="ghost" @click="showDiscardModal = false">Cancel</flux:button>
        <flux:button variant="danger" wire:click="discardAll">Discard</flux:button>
    </div>
</flux:modal>
```

**Key Points:**

- **Livewire binding:** `wire:model="showDiscardModal"` controls visibility
- **Alpine.js binding:** `x-model="showDiscardModal"` for Alpine-only modals

### Tooltips

All icon-only action buttons must be wrapped in `<flux:tooltip>`.

```blade
<flux:tooltip content="Stage All">
    <flux:button wire:click="stageAll" variant="ghost" size="xs" square>
        <x-phosphor-plus class="w-4 h-4" />
    </flux:button>
</flux:tooltip>
```

**Required tooltip labels:** Stage All, Unstage All, Discard All, Stage, Unstage, Discard.

### Form Fields

```blade
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input type="email" wire:model="email" />
    <flux:error name="email" />
</flux:field>
```

---

## Phosphor Icons

All icons from `codeat3/blade-phosphor-icons` package.

### Header Icons (Light Variant)

Use `-light` suffix for header icons:

```blade
<x-phosphor-sidebar-simple-light class="w-4 h-4" />
<x-phosphor-folder-light class="w-4 h-4" />
<x-phosphor-git-branch-light class="w-4 h-4" />
<x-phosphor-arrow-up-light class="w-4 h-4" />
<x-phosphor-arrow-down-light class="w-4 h-4" />
<x-phosphor-arrows-clockwise-light class="w-4 h-4" />
<x-phosphor-circle-notch-light class="w-4 h-4 animate-spin" />
```

### File Action Icons (Regular)

Use regular phosphor (not light) for file actions:

```blade
<x-phosphor-plus class="w-3.5 h-3.5" />        <!-- stage file -->
<x-phosphor-minus class="w-3.5 h-3.5" />       <!-- unstage file -->
<x-phosphor-trash class="w-3.5 h-3.5" />       <!-- discard changes -->
<x-phosphor-arrow-counter-clockwise class="w-3.5 h-3.5" /> <!-- revert -->
```

### Icon Centering

Header icon buttons need `flex items-center justify-center` for proper vertical alignment.

```blade
<flux:button variant="ghost" size="xs" square class="flex items-center justify-center">
    <x-phosphor-sidebar-simple-light class="w-4 h-4" />
</flux:button>
```

### Icon Colors

Header trigger icons (folder, git-branch, chevrons in dropdowns) must use `#6c6f85` (Subtext 0 / text-secondary), NOT `#9ca0b0` (Overlay 0 / border color). Icons should match text weight, not border weight.

```blade
<x-phosphor-folder-light class="w-3.5 h-3.5 text-[#6c6f85]" />
```

---

## Custom Blade Components

### File Tree (Recursive)

**File:** `resources/views/components/file-tree.blade.php`

Recursive tree view for staging panel. Displays directories with collapsible folders and files with status dots.

```blade
<x-file-tree :tree="$stagedTree" :staged="true" />
```

**Props:**

- `tree` — array of nodes (directories and files)
- `staged` — boolean (true for staged files, false for unstaged)
- `level` — integer (indentation level, default: 0)

**Key Features:**

- **Recursive rendering:** Directories render child nodes recursively
- **Collapsible folders:** Alpine.js `x-data="{ expanded: true }"`
- **Indentation:** `padding-left: {{ ($level * 16) + 16 }}px`
- **Folder icon:** `<x-phosphor-folder-simple class="w-3.5 h-3.5 text-[#9ca0b0]" />`
- **Collapse chevron:** Small SVG arrow (`w-3 h-3`) that rotates 90° when expanded
- **No dividers:** File items sit edge-to-edge like flat view
- **Same density:** Items use `py-1.5` and `gap-2.5`, matching flat view exactly

---

## NativePHP Integration

NativePHP wraps the Laravel app in an Electron window for macOS desktop distribution.

### Window Configuration

**File:** `app/Providers/NativeAppServiceProvider.php`

```php
$window = Window::open()
    ->title('Gitty')
    ->width(1200)
    ->height(800)
    ->minWidth(900)
    ->minHeight(600);

// Custom window chrome for macOS (hidden title bar with inset traffic lights)
if (method_exists($window, 'titleBarStyle')) {
    $window->titleBarStyle('hiddenInset');
}
```

**Key Points:**

- **Default size:** 1200x800
- **Min size:** 900x600
- **Title bar style:** `hiddenInset` (hides title bar, shows traffic lights)

### Traffic Light Spacer

macOS window controls (red/yellow/green buttons) occupy ~64px on the left. Header includes a spacer to prevent content overlap.

```blade
<div class="w-16" style="-webkit-app-region: drag;"></div>
```

**Width:** 64px (`w-16`)

### Drag Region

The header is draggable (for moving the window), but buttons must opt out.

```blade
<div class="..." style="-webkit-app-region: drag;">
    <div style="-webkit-app-region: no-drag;">
        <flux:button>...</flux:button>
    </div>
</div>
```

**Pattern:**

- Header wrapper: `-webkit-app-region: drag`
- Interactive elements: `-webkit-app-region: no-drag`

### Native Menu Bar

**File:** `app/Providers/NativeAppServiceProvider.php`

```php
Menu::create(
    Menu::app(),
    Menu::make(
        Menu::label('Open Repository...')->hotkey('CmdOrCtrl+O')->event('menu:file:open-repo'),
        Menu::label('Settings')->hotkey('CmdOrCtrl+,')->event('menu:file:settings'),
        Menu::separator(),
        Menu::label('Quit')->hotkey('CmdOrCtrl+Q')->event('menu:file:quit'),
    )->label('File'),
    Menu::make(
        Menu::label('Commit')->hotkey('CmdOrCtrl+Return')->event('menu:git:commit'),
        Menu::separator(),
        Menu::label('Push')->hotkey('CmdOrCtrl+P')->event('menu:git:push'),
        Menu::label('Pull')->hotkey('CmdOrCtrl+Shift+P')->event('menu:git:pull'),
        Menu::label('Fetch')->hotkey('CmdOrCtrl+T')->event('menu:git:fetch'),
    )->label('Git'),
);
```

**Key Points:**

- **Hotkeys:** `CmdOrCtrl+Key` for cross-platform compatibility
- **Events:** Menu items dispatch events (e.g., `menu:git:commit`)
- **Livewire listeners:** Components listen to these events and execute actions

---

## JavaScript Modules

### Highlight.js Integration

**File:** `resources/js/app.js`

Syntax highlighting for diff viewer. Registers 20+ languages, highlights code on page load and after Livewire updates.

```javascript
import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import javascript from 'highlight.js/lib/languages/javascript';
// ... register 20+ languages

hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);
// ...

function highlightDiffContent() {
    document.querySelectorAll('.diff-file[data-language]').forEach(fileEl => {
        const language = fileEl.dataset.language;
        if (language === 'text') return;
        
        fileEl.querySelectorAll('.line-content').forEach(lineEl => {
            if (lineEl.dataset.highlighted) return;
            
            const text = lineEl.textContent;
            if (!text.trim()) return;
            
            try {
                const result = hljs.highlight(text, { language, ignoreIllegals: true });
                lineEl.innerHTML = result.value;
                lineEl.dataset.highlighted = 'true';
            } catch (e) {
                // Silently fail — keep plain text
            }
        });
    });
}

// Run on initial page load
document.addEventListener('DOMContentLoaded', highlightDiffContent);

// Run after Livewire updates the DOM
Livewire.hook('morph.updated', ({ el }) => {
    if (el.closest('.diff-container') || el.classList?.contains('diff-container')) {
        requestAnimationFrame(highlightDiffContent);
    }
});
```

**Key Points:**

- **Language detection:** Diff viewer sets `data-language` attribute on `.diff-file` elements
- **Incremental highlighting:** Only highlights lines without `data-highlighted="true"`
- **Livewire integration:** Re-highlights after Livewire morphs the DOM
- **Graceful degradation:** Silently fails if language not supported

---

## Design System Reference

For complete design system documentation, see **AGENTS.md**:

- **Color Palette:** Catppuccin Latte (light) and Mocha (dark)
- **Accent Color:** Zed Blue (`#084CCF`)
- **Typography:** Instrument Sans (sans), JetBrains Mono (mono)
- **Status Indicators:** File status dots, diff header badges
- **Hover States:** File item hover, button hover
- **Animations:** Slide-in, commit flash, sync pulse, fade-in
- **Git Commit Style:** `type(scope): lowercase message`

**Key Topics in AGENTS.md:**

1. Color System (Catppuccin Latte)
2. Flux UI Integration
3. Phosphor Icons
4. Status Indicators
5. Hover & Interaction States
6. Tree View
7. Dropdown Backgrounds
8. Header Layout
9. CSS Architecture
10. Typography
11. Animations
12. Keyboard Shortcuts
13. Development Notes
14. Key Gotchas

**Do not duplicate AGENTS.md content.** Reference it for color values, icon usage, and Flux UI patterns.
