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

- Size: `w-2 h-2 rounded-full`
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

## Releasing

See [docs/RELEASING.md](docs/RELEASING.md) for the complete release process.

**Quick release:**
```bash
./bin/release
```

This creates a version tag. Push with:
```bash
git push origin main && git push origin v1.2.3
```

GitHub Actions automatically builds DMGs for macOS (Intel + Apple Silicon) and creates a GitHub Release.

## References

- Catppuccin Latte: https://catppuccin.com/palette/
- Flux UI: https://fluxui.dev
- Phosphor Icons: https://phosphoricons.com
- Tailwind CSS v4: https://tailwindcss.com/docs
- NativePHP: https://nativephp.com

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.17
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `fluxui-development` — Develops UIs with Flux UI Free components. Activates when creating buttons, forms, modals, inputs, dropdowns, checkboxes, or UI components; replacing HTML form elements with Flux; working with flux: components; or when the user mentions Flux, component library, UI components, form fields, or asks about available Flux components.
- `livewire-development` — Develops reactive Livewire 4 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== fluxui-free/core rules ===

# Flux UI Free

- Flux UI is the official Livewire component library. This project uses the free edition, which includes all free components and variants but not Pro components.
- Use `<flux:*>` components when available; they are the recommended way to build Livewire interfaces.
- IMPORTANT: Activate `fluxui-development` when working with Flux UI components.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces using only PHP — no JavaScript required.
- Instead of writing frontend code in JavaScript frameworks, you use Alpine.js to build the UI when client-side interactions are required.
- State lives on the server; the UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- IMPORTANT: Activate `livewire-development` every time you're working with Livewire-related tasks.

=== boost/core rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

</laravel-boost-guidelines>
