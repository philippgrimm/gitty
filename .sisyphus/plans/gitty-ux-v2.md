# Gitty UX Overhaul Plan v2

## Root Cause Analysis

The app has a **fundamental layout architecture problem**: the BranchManager component is designed as a full-height panel (`h-full flex flex-col` with its own scrolling list) but is embedded inside a toolbar `<div>` in `app-layout.blade.php` (line 34). This causes the branch list to expand the toolbar to fill the entire viewport, pushing the staging panel, commit panel, and diff viewer completely below the fold.

**Result**: The user sees nothing but a branch list. They cannot see changes, stage files, write commits, or view diffs without scrolling past 30+ branches.

## Target Layout (VS Code-inspired)

```
┌─────────────────────────────────────────────────────────┐
│ TOP BAR: [Repo ▾] [⎇ feat/MFO-1814 ▾] [↑2 ↓0] [⚙]   │
├────────────┬────────────────────────────────────────────┤
│ SIDEBAR    │  CHANGES (1)        [Stage All] [⟲]       │
│ (optional) │  ● CreateUserFromOkta.php    [+ ×]        │
│            │                                            │
│ Branches   │──────────────────────────────────────────  │
│ Remotes    │  STAGED (0)         [Unstage All]          │
│ Tags       │  (empty)                                   │
│ Stashes    │──────────────────────────────────────────  │
│            │  Commit message...                         │
│            │  [Commit ⌘↵] [▾ Commit & Push]            │
│            ├────────────────────────────────────────────┤
│            │  DIFF VIEWER                               │
│            │  (file content with syntax highlighting)   │
│            │                                            │
├────────────┴────────────────────────────────────────────┤
│ STATUS BAR: feat/MFO-1814 │ ↑2 ↓0 │ Auto-fetch: 2m ago│
└─────────────────────────────────────────────────────────┘
```

## Tasks (Priority Order)

### Phase 1: CRITICAL — Fix Layout (app is currently unusable)

- [ ] **1.1 Convert BranchManager from full-panel to compact dropdown/picker**
  - Current: Full-height panel with scrolling branch list embedded in toolbar
  - Target: A compact button showing current branch name that opens a dropdown/modal with search
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`
  - Complexity: L

- [ ] **1.2 Redesign app-layout to VS Code-inspired 3-panel layout**
  - Current: Toolbar with embedded BranchManager takes full height
  - Target: Thin top bar → [sidebar | changes+commit | diff] horizontal split
  - The top bar should contain: repo picker, branch picker (dropdown), sync buttons, settings
  - Files: `resources/views/livewire/app-layout.blade.php`, `app/Livewire/AppLayout.php`
  - Complexity: L

- [ ] **1.3 Move SyncPanel buttons into top bar (compact)**
  - Current: Sync buttons are at the bottom of the branch list (invisible)
  - Target: Compact icon buttons in the top bar (↑ ↓ ↻) with tooltips
  - Files: `resources/views/livewire/sync-panel.blade.php`
  - Complexity: S

### Phase 2: HIGH — Essential UX Features

- [ ] **2.1 Add branch search/filter to the branch picker dropdown**
  - With 30+ branches, a flat list is unusable
  - Add a search input at the top of the branch dropdown
  - Filter branches as user types, show "N results"
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`
  - Complexity: M

- [ ] **2.2 Show current branch prominently in top bar**
  - Display branch name as a pill/badge next to repo name
  - Show ahead/behind counts (↑2 ↓0)
  - Clicking opens the branch picker dropdown
  - Files: `resources/views/livewire/app-layout.blade.php`, `app/Livewire/BranchManager.php`
  - Complexity: M

- [ ] **2.3 Make staging panel the primary left panel (not branches)**
  - Changes/staged files should be the first thing users see
  - File list with status icons (M, A, D, R, U)
  - Stage/unstage/discard actions on hover
  - Files: `resources/views/livewire/staging-panel.blade.php`
  - Complexity: S

- [ ] **2.4 Fix commit panel visibility and usability**
  - Commit panel should be directly below the staging panel, always visible
  - Show staged file count prominently
  - Commit button should be clearly actionable
  - Files: `resources/views/livewire/commit-panel.blade.php`
  - Complexity: S

### Phase 3: MEDIUM — Dark Mode & Contrast

- [ ] **3.1 Enforce dark-only mode consistently**
  - Force `<html class="dark">` at layout level
  - Remove light/system theme options from settings (or disable them)
  - Files: `resources/views/layouts/app.blade.php`, `resources/views/livewire/settings-modal.blade.php`
  - Complexity: S

- [ ] **3.2 Fix contrast issues across all panels**
  - Raise secondary text from zinc-500/600 to zinc-300/400
  - Ensure all interactive elements have visible hover/focus states
  - Replace hardcoded `text-white` with `text-zinc-100`
  - Files: All blade templates
  - Complexity: M

- [ ] **3.3 Extract inline styles from diff-viewer into CSS classes**
  - Move `<style>@apply...</style>` blocks into `resources/css/app.css`
  - Files: `resources/views/livewire/diff-viewer.blade.php`, `resources/css/app.css`
  - Complexity: S

### Phase 4: LOW — Polish

- [ ] **4.1 Add status bar at bottom**
  - Show: current branch, ahead/behind, auto-fetch status, last fetch time
  - Files: `resources/views/livewire/app-layout.blade.php`
  - Complexity: M

- [ ] **4.2 Collapse sidebar branches by default (reduce duplication)**
  - RepoSidebar duplicates branch info from BranchManager
  - Default branches section to collapsed
  - Files: `resources/views/livewire/repo-sidebar.blade.php`
  - Complexity: S

- [ ] **4.3 Make Force Push less prominent**
  - Move behind a dropdown or secondary section
  - Files: `resources/views/livewire/sync-panel.blade.php`
  - Complexity: S

## Definition of Done
- [ ] User can see changes, stage files, write commits, and view diffs without scrolling
- [ ] Current branch is visible at all times in the top bar
- [ ] Branch picker has search/filter functionality
- [ ] All panels are visible in the default viewport (1200x800)
- [ ] Dark mode is consistent across all components
- [ ] All 240 existing tests pass
