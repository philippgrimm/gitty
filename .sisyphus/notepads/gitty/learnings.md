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

