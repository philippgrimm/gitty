# gitty-tauri: Full Rewrite in Tauri + React + Rust

## TL;DR

> **Quick Summary**: Rewrite the gitty macOS git client from NativePHP/Laravel/Livewire/Electron to Tauri 2.x + React + TypeScript + Rust. Full feature parity with the current app (20 Livewire components, 20 git service classes, 15 DTOs). All git operations move to a Rust backend using git2-rs (primary) with CLI fallback. React frontend replicates the Catppuccin Latte UI with Phosphor Icons.
>
> **Deliverables**:
> - Complete Tauri 2.x desktop application in `../gitty-tauri/`
> - Rust backend: git2-rs wrappers for all git operations + CLI fallback
> - React frontend: all 18 UI components with exact visual parity
> - Full TDD test suite (Rust unit tests + React component tests + Playwright E2E)
> - Architecture documentation (ARCHITECTURE.md, IPC schema, Rust style guide)
>
> **Estimated Effort**: XL (4-6 weeks with parallel execution)
> **Parallel Execution**: YES — 6 waves
> **Critical Path**: Task 1 (scaffold) → Task 7 (git2 audit) → Task 9 (core git service) → Task 14 (staging UI) → Task 22 (integration) → Task 27 (final QA)

---

## Context

### Original Request
User asked: "Would this app be better written in Tauri + React + Rust?" After deep research comparing NativePHP vs Tauri (bundle size: 8.6MB vs 244MB, memory: 172MB vs 409MB, GitButler as proof of concept), the answer was a strong YES. User then requested a full rewrite plan.

### Interview Summary
**Key Discussions**:
- **Stack confirmed**: Tauri 2.x, React 18+, TypeScript, Rust, git2-rs + CLI fallback, Zustand + React Query, react-diff-view, Tailwind CSS v4, Catppuccin Latte, Phosphor Icons React
- **Scope**: Full feature parity with all 18 UI components and all git operations
- **Testing**: TDD (red-green-refactor) for both Rust and React
- **No Rust experience**: Agents write all code; plan must be extra explicit about Rust patterns
- **Target**: `../gitty-tauri/` — completely separate from current NativePHP app
- **macOS only** for v1 (no Windows/Linux despite Tauri's cross-platform capability)

**Research Findings**:
- GitButler (17.8K stars) validates exact same stack pattern (Tauri + Rust + git2)
- react-diff-view (191K weekly npm downloads) is best React diff library
- git2-rs covers ~95% of needed git operations; CLI fallback for the rest
- Tauri 2.x has all needed plugins: dialog, notification, shell, fs, menu
- NativePHP risks: bus factor=1, beta status, no complex production apps, Tauri support paused

### Metis Review
**Identified Gaps** (addressed in plan):
- Migration/coexistence strategy: Both apps can run simultaneously, separate configs
- Concurrent git operations: Queue operations in Rust, don't block main thread
- Error handling: git2-rs failure → automatic CLI fallback with notification
- Performance budgets: <100ms diff rendering, virtualization for 10K+ line diffs
- IPC benchmarking: Must verify <100ms for 10MB payloads before building features
- git2-rs audit: Must map all 70+ git CLI calls to git2-rs APIs before implementing
- Edge cases: Detached HEAD, merge in progress, empty repo, corrupted repo, binary files, large files
- Rust learning curve: Mandate `cargo clippy`, `anyhow` for errors, async for all git ops, no `unwrap()`

---

## Work Objectives

### Core Objective
Build a complete, production-ready macOS git client in Tauri 2.x + React + Rust that replicates every feature of the current NativePHP gitty app, with better performance, smaller bundle size, and a sustainable technology foundation.

### Concrete Deliverables
- `../gitty-tauri/` — Complete Tauri application
- `../gitty-tauri/src-tauri/` — Rust backend with git2-rs integration
- `../gitty-tauri/src/` — React + TypeScript frontend
- `../gitty-tauri/src-tauri/src/git/` — Git service modules (porting 20 PHP services to Rust)
- `../gitty-tauri/src/components/` — 18 React UI components
- `../gitty-tauri/ARCHITECTURE.md` — System architecture documentation
- Full test suites: `cargo test`, `vitest`, Playwright E2E

### Definition of Done
- [ ] `npm run tauri build` produces a working `.dmg` for macOS
- [ ] All 18 UI components render correctly with Catppuccin Latte theme
- [ ] All git operations work: status, stage, unstage, commit, push, pull, fetch, branch, merge, rebase, stash, blame, search, cherry-pick, reset, revert, tags, conflict resolution
- [ ] `cargo test` passes with 0 failures
- [ ] `npm run test` (vitest) passes with 0 failures
- [ ] Playwright E2E tests pass for critical flows
- [ ] Bundle size < 15MB (target: ~8-10MB)
- [ ] Memory usage < 200MB for typical repo

### Must Have
- Full feature parity with current NativePHP gitty app
- git2-rs as primary git backend with CLI fallback
- Catppuccin Latte color palette (exact hex values from current `resources/css/app.css`)
- Phosphor Icons (React version)
- Keyboard shortcuts matching current app (⌘↵ commit, ⌘⇧↵ commit+push, ⌘⇧K stage all, etc.)
- macOS native window controls (traffic light buttons)
- Async git operations (never block UI thread)
- TDD test suite

### Must NOT Have (Guardrails)
- ❌ Windows/Linux support in v1 (macOS only)
- ❌ Dark mode / theme switcher (Catppuccin Latte only)
- ❌ GitHub/GitLab/Bitbucket API integration (git protocol only)
- ❌ Git LFS support
- ❌ Submodule management UI (CLI fallback acceptable)
- ❌ Git worktree UI
- ❌ GPG commit signing UI (use git config)
- ❌ Custom merge tools integration
- ❌ Git hooks management UI
- ❌ Plugin system / extensions
- ❌ AI features (no commit message generation)
- ❌ Internationalization (English only)
- ❌ `unwrap()` or `expect()` in Rust production code (use `?` + `anyhow`)
- ❌ Synchronous git operations on main thread
- ❌ Committed test fixture repos (generate in test setup)
- ❌ Inline file editing (open in external editor only)
- ❌ Over-abstracted "RepositoryProvider" for future VCS systems
- ❌ Event sourcing or CQRS patterns (overkill for desktop app)
- ❌ JSDoc on every React component (TypeScript types are self-documenting)
- ❌ Rustdoc on private functions (public API only)

---

## Verification Strategy (MANDATORY)

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: NO (greenfield project)
- **Automated tests**: YES (TDD — red-green-refactor)
- **Rust tests**: `cargo test` (built-in)
- **React tests**: Vitest + React Testing Library
- **E2E tests**: Playwright (Tauri WebDriver)
- **Each task follows**: RED (failing test) → GREEN (minimal impl) → REFACTOR

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| Rust backend | Bash (`cargo test`, `cargo clippy`) | Run tests, check clippy warnings |
| React components | Bash (`npm run test`) | Vitest + React Testing Library |
| Full app UI | Playwright (playwright skill) | Navigate, interact, assert DOM, screenshot |
| IPC integration | Bash (`npm run tauri dev` + curl/test) | Start app, invoke commands, verify responses |
| Build artifacts | Bash (`npm run tauri build`) | Verify .dmg exists, check bundle size |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Foundation — start immediately, 7 parallel tasks):
├── Task 1: Scaffold Tauri project [quick]
├── Task 2: Design system + Tailwind CSS v4 setup [quick]
├── Task 3: TypeScript types + IPC schema [quick]
├── Task 4: Rust project structure + error handling [quick]
├── Task 5: Test infrastructure (Rust + Vitest + Playwright) [quick]
├── Task 6: Architecture documentation [writing]
└── Task 7: git2-rs API audit + compatibility matrix [deep]

Wave 2 (Core Rust Backend — after Wave 1, 6 parallel tasks):
├── Task 8: Git repository + status service (depends: 4, 7) [deep]
├── Task 9: Staging + diff service (depends: 4, 7) [deep]
├── Task 10: Commit + history service (depends: 4, 7) [deep]
├── Task 11: Branch + merge service (depends: 4, 7) [deep]
├── Task 12: Remote + sync service (depends: 4, 7) [deep]
└── Task 13: Tauri IPC command handlers (depends: 3, 4) [unspecified-high]

Wave 3 (Core React UI — after Wave 1, parallel with Wave 2):
├── Task 14: App shell + layout + header (depends: 2, 3) [visual-engineering]
├── Task 15: Staging panel + file list (depends: 2, 3) [visual-engineering]
├── Task 16: Diff viewer (depends: 2, 3) [visual-engineering]
├── Task 17: Commit panel (depends: 2, 3) [visual-engineering]
└── Task 18: Branch manager dropdown (depends: 2, 3) [visual-engineering]

Wave 4 (Advanced Features — after Waves 2+3, 7 parallel tasks):
├── Task 19: Sync panel + push/pull/fetch (depends: 12, 13, 14) [visual-engineering]
├── Task 20: Stash service + UI (depends: 8, 13, 14) [unspecified-high]
├── Task 21: Blame view + service (depends: 8, 13, 14) [unspecified-high]
├── Task 22: Search panel + service (depends: 8, 13, 14) [unspecified-high]
├── Task 23: Rebase panel + service (depends: 11, 13, 14) [deep]
├── Task 24: Conflict resolver + service (depends: 11, 13, 14) [deep]
└── Task 25: History panel + graph (depends: 10, 13, 14) [visual-engineering]

Wave 5 (Polish + Integration — after Wave 4, 6 parallel tasks):
├── Task 26: Repo switcher + sidebar (depends: 8, 14) [visual-engineering]
├── Task 27: Settings modal + auto-fetch (depends: 13, 14) [unspecified-high]
├── Task 28: Command palette + keyboard shortcuts (depends: 14) [unspecified-high]
├── Task 29: Error banner + notifications (depends: 13) [quick]
├── Task 30: Tags + reset/revert services (depends: 8, 13) [unspecified-high]
└── Task 31: Shortcut help modal (depends: 28) [quick]

Wave 6 (Final QA + Build — after Wave 5, 4 parallel tasks):
├── Task F1: Plan compliance audit [oracle]
├── Task F2: Code quality review (cargo clippy + tsc + vitest) [unspecified-high]
├── Task F3: Full E2E QA with Playwright [unspecified-high]
└── Task F4: Scope fidelity check [deep]

Critical Path: Task 1 → Task 4 → Task 7 → Task 9 → Task 13 → Task 15 → Task 22 → F1-F4
Parallel Speedup: ~65% faster than sequential
Max Concurrent: 7 (Waves 1 and 4)
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | 2-7, all | 1 |
| 2 | 1 | 14-18 | 1 |
| 3 | 1 | 13, 14-18 | 1 |
| 4 | 1 | 8-13 | 1 |
| 5 | 1 | all tests | 1 |
| 6 | 1 | — (reference only) | 1 |
| 7 | 1, 4 | 8-12 | 1 |
| 8 | 4, 7 | 20-22, 26, 30 | 2 |
| 9 | 4, 7 | 15 (integration) | 2 |
| 10 | 4, 7 | 25 | 2 |
| 11 | 4, 7 | 23, 24 | 2 |
| 12 | 4, 7 | 19 | 2 |
| 13 | 3, 4 | 19-30 | 2 |
| 14 | 2, 3 | 19-31 | 3 |
| 15 | 2, 3 | — | 3 |
| 16 | 2, 3 | — | 3 |
| 17 | 2, 3 | — | 3 |
| 18 | 2, 3 | — | 3 |
| 19 | 12, 13, 14 | — | 4 |
| 20 | 8, 13, 14 | — | 4 |
| 21 | 8, 13, 14 | — | 4 |
| 22 | 8, 13, 14 | — | 4 |
| 23 | 11, 13, 14 | — | 4 |
| 24 | 11, 13, 14 | — | 4 |
| 25 | 10, 13, 14 | — | 4 |
| 26 | 8, 14 | — | 5 |
| 27 | 13, 14 | — | 5 |
| 28 | 14 | 31 | 5 |
| 29 | 13 | — | 5 |
| 30 | 8, 13 | — | 5 |
| 31 | 28 | — | 5 |
| F1-F4 | ALL | — | 6 |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 1 | **7** | T1-T5 → `quick`, T6 → `writing`, T7 → `deep` |
| 2 | **6** | T8-T12 → `deep`, T13 → `unspecified-high` |
| 3 | **5** | T14-T18 → `visual-engineering` |
| 4 | **7** | T19, T25 → `visual-engineering`, T20-T22 → `unspecified-high`, T23-T24 → `deep` |
| 5 | **6** | T26 → `visual-engineering`, T27-T28, T30 → `unspecified-high`, T29, T31 → `quick` |
| 6 | **4** | F1 → `oracle`, F2-F3 → `unspecified-high`, F4 → `deep` |

---

## TODOs

### Wave 1: Foundation

- [x] 1. Scaffold Tauri 2.x Project

  **What to do**:
  - Create `../gitty-tauri/` directory
  - Run `npm create tauri-app@latest` with React + TypeScript template
  - Configure `tauri.conf.json`: window title "gitty", default size 1200x800, min 900x600, macOS titleBarStyle "hiddenInset"
  - Install core dependencies: `@phosphor-icons/react`, `zustand`, `@tanstack/react-query`, `react-diff-view`, `unidiff`
  - Install dev dependencies: `tailwindcss@4`, `@tailwindcss/vite`, `vitest`, `@testing-library/react`, `@playwright/test`
  - Configure `vite.config.ts` for Tauri
  - Verify `npm run tauri dev` launches a window with React dev server

  **Must NOT do**:
  - Don't install unnecessary Tauri plugins yet (add per-task as needed)
  - Don't add dark mode or theme switching
  - Don't configure for Windows/Linux

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`tailwindcss-development`]
    - `tailwindcss-development`: Needed for Tailwind CSS v4 setup with Vite

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2-7)
  - **Blocks**: Tasks 2-7 (all), then everything else
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References**:
  - `app/Providers/NativeAppServiceProvider.php` — Window configuration (1200x800, min 900x600, hiddenInset titlebar style) to replicate in `tauri.conf.json`
  - `package.json` — Current dependencies for reference (what NOT to carry over — no Livewire, no Laravel, no Electron)

  **External References**:
  - Tauri 2.x Getting Started: `https://v2.tauri.app/start/create-project/`
  - Tauri configuration reference: `https://v2.tauri.app/reference/config/`

  **WHY Each Reference Matters**:
  - `NativeAppServiceProvider.php` contains exact window dimensions and titlebar config to replicate
  - Tauri 2.x docs confirm the `npm create tauri-app@latest` workflow and config schema

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Tauri app launches successfully
    Tool: Bash
    Preconditions: ../gitty-tauri/ exists with all dependencies installed
    Steps:
      1. cd ../gitty-tauri && npm run tauri dev &
      2. Wait 30 seconds for compilation and launch
      3. Check process list: ps aux | grep tauri | grep -v grep
      4. Kill the dev process
    Expected Result: Tauri process is running, no compilation errors
    Failure Indicators: "error[E" in stderr, process exits immediately
    Evidence: .sisyphus/evidence/task-1-tauri-launch.txt

  Scenario: Project structure is correct
    Tool: Bash
    Preconditions: ../gitty-tauri/ exists
    Steps:
      1. Verify directories exist: src/, src-tauri/, src-tauri/src/
      2. Verify key files: src/App.tsx, src-tauri/src/main.rs, src-tauri/tauri.conf.json, vite.config.ts
      3. Verify tauri.conf.json contains: "title": "gitty", width: 1200, height: 800
      4. Verify package.json contains all required dependencies
    Expected Result: All files and directories exist with correct content
    Failure Indicators: Missing files, wrong config values
    Evidence: .sisyphus/evidence/task-1-project-structure.txt
  ```

  **Commit**: YES
  - Message: `feat(scaffold): initialize Tauri 2.x project with React + TypeScript`
  - Files: `../gitty-tauri/**`
  - Pre-commit: `cd ../gitty-tauri && npm run build`

---

- [x] 2. Design System + Tailwind CSS v4 Setup

  **What to do**:
  - Configure Tailwind CSS v4 with Vite plugin in `../gitty-tauri/`
  - Create `src/styles/app.css` with complete Catppuccin Latte palette (copy exact hex values from current `resources/css/app.css`)
  - Define CSS custom properties in `:root {}`: all surface, text, border, and semantic colors
  - Define Tailwind `@theme {}` block with accent color (`#084CCF`), font families (Instrument Sans, JetBrains Mono)
  - Create diff viewer CSS classes (`.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context`)
  - Create animation keyframes (slide-in, commit-flash, sync-pulse, fade-in)
  - Verify Tailwind classes compile correctly with `npm run build`

  **Must NOT do**:
  - Don't add dark mode variants
  - Don't create component-specific styles (those go in components)
  - Don't use Flux UI (React project — no Livewire)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`tailwindcss-development`]
    - `tailwindcss-development`: Critical for Tailwind v4 setup, `@theme` block syntax, CSS custom properties

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3-7)
  - **Blocks**: Tasks 14-18 (all React UI components)
  - **Blocked By**: Task 1 (needs project scaffold)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:1-180` — COMPLETE Catppuccin Latte palette, all CSS custom properties, `@theme` block, diff viewer styles, animation keyframes. Copy ALL hex values exactly.

  **External References**:
  - Catppuccin Latte palette: `https://catppuccin.com/palette/`

  **WHY Each Reference Matters**:
  - `app.css` is the SINGLE SOURCE OF TRUTH for every color value, font, animation, and design token. The Tauri app must use identical values.

  **Acceptance Criteria**:
  - [ ] `npm run build` succeeds with no Tailwind compilation errors
  - [ ] All Catppuccin Latte colors available as CSS custom properties
  - [ ] Accent color `#084CCF` configured in `@theme` block

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Tailwind CSS compiles with all design tokens
    Tool: Bash
    Preconditions: ../gitty-tauri/ has Tailwind configured
    Steps:
      1. cd ../gitty-tauri && npm run build
      2. Check output CSS file exists in dist/
      3. Grep compiled CSS for key values: "#eff1f5", "#084CCF", "#4c4f69", "#df8e1d"
      4. Grep for animation names: "slide-in", "commit-flash"
    Expected Result: Build succeeds, all color values present in output CSS
    Failure Indicators: Build error, missing color values
    Evidence: .sisyphus/evidence/task-2-tailwind-build.txt

  Scenario: CSS custom properties defined correctly
    Tool: Bash
    Preconditions: src/styles/app.css exists
    Steps:
      1. Grep app.css for --surface-0, --text-primary, --border-default, --color-green, --color-red
      2. Verify @theme block contains --color-accent: #084CCF
      3. Verify font families: Instrument Sans, JetBrains Mono
    Expected Result: All properties defined with exact Catppuccin Latte hex values
    Failure Indicators: Missing properties, wrong hex values
    Evidence: .sisyphus/evidence/task-2-css-properties.txt
  ```

  **Commit**: YES (groups with Task 1)
  - Message: `feat(design): add Catppuccin Latte design system with Tailwind CSS v4`
  - Files: `src/styles/app.css`, `tailwind.config.*`

---

- [x] 3. TypeScript Types + IPC Schema

  **What to do**:
  - Create `src/types/` directory with all TypeScript type definitions
  - Port all 15 DTOs from PHP to TypeScript interfaces:
    - `GitStatus`, `ChangedFile`, `AheadBehind`, `Branch`, `Commit`, `DiffFile`, `DiffResult`, `Hunk`, `HunkLine`, `BlameLine`, `ConflictFile`, `MergeResult`, `Stash`, `GraphNode`, `Remote`
  - Create `src/types/ipc.ts` — IPC command/response types for Tauri `invoke()`
  - Create `src/types/ui.ts` — UI state types (selected file, view mode, panel visibility)
  - Ensure all types are exported from `src/types/index.ts` barrel file
  - Verify with `tsc --noEmit`

  **Must NOT do**:
  - Don't add `any` types
  - Don't create React components (types only)
  - Don't implement IPC handlers (types/contracts only)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1-2, 4-7)
  - **Blocks**: Tasks 13 (IPC handlers), 14-18 (React components use these types)
  - **Blocked By**: Task 1 (needs project scaffold)

  **References**:

  **Pattern References**:
  - `app/DTOs/GitStatus.php` — Root status type: branch, upstream, aheadBehind, changedFiles array
  - `app/DTOs/ChangedFile.php` — File status type: path, status (modified/added/deleted/renamed/unmerged), staged boolean
  - `app/DTOs/AheadBehind.php` — Simple value object: ahead (int), behind (int)
  - `app/DTOs/Branch.php` — Branch type: name, isCurrent, isRemote, upstream, lastCommit
  - `app/DTOs/Commit.php` — Commit type: hash, shortHash, message, author, date, parents
  - `app/DTOs/DiffFile.php` — Diff file type: path, oldPath, status, hunks array
  - `app/DTOs/DiffResult.php` — Diff result: files array, stats (additions, deletions)
  - `app/DTOs/Hunk.php` — Hunk type: header, oldStart, oldLines, newStart, newLines, lines array
  - `app/DTOs/HunkLine.php` — Line type: content, type (add/delete/context), oldNumber, newNumber
  - `app/DTOs/BlameLine.php` — Blame line: lineNumber, commit, author, date, content
  - `app/DTOs/ConflictFile.php` — Conflict: path, ours, theirs, base content
  - `app/DTOs/MergeResult.php` — Merge result: success, conflicts array, message
  - `app/DTOs/Stash.php` — Stash entry: index, message, date, branch
  - `app/DTOs/GraphNode.php` — Graph node: commit hash, parents, column, color
  - `app/DTOs/Remote.php` — Remote: name, fetchUrl, pushUrl

  **WHY Each Reference Matters**:
  - Each PHP DTO defines the exact data contract that the Rust backend will produce and the React frontend will consume. TypeScript types must match these shapes exactly.

  **Acceptance Criteria**:
  - [ ] `tsc --noEmit` passes with 0 errors
  - [ ] All 15 DTO types defined as TypeScript interfaces
  - [ ] IPC command types defined for all git operations
  - [ ] Barrel export from `src/types/index.ts`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: TypeScript types compile without errors
    Tool: Bash
    Preconditions: ../gitty-tauri/src/types/ exists with all type files
    Steps:
      1. cd ../gitty-tauri && npx tsc --noEmit
      2. Count type files: ls src/types/*.ts | wc -l
      3. Verify barrel export: grep -c "export" src/types/index.ts
    Expected Result: tsc passes, at least 4 type files (git.ts, ipc.ts, ui.ts, index.ts), barrel exports all types
    Failure Indicators: tsc errors, missing type files
    Evidence: .sisyphus/evidence/task-3-types-compile.txt

  Scenario: All 15 DTOs have TypeScript equivalents
    Tool: Bash
    Preconditions: src/types/ exists
    Steps:
      1. Grep for each interface name: GitStatus, ChangedFile, AheadBehind, Branch, Commit, DiffFile, DiffResult, Hunk, HunkLine, BlameLine, ConflictFile, MergeResult, Stash, GraphNode, Remote
      2. Verify each has at least 2 properties
    Expected Result: All 15 interfaces found with properties matching PHP DTOs
    Failure Indicators: Missing interfaces, mismatched property names/types
    Evidence: .sisyphus/evidence/task-3-dto-coverage.txt
  ```

  **Commit**: YES
  - Message: `feat(types): define TypeScript interfaces for all git DTOs and IPC schema`
  - Files: `src/types/**`

---

- [x] 4. Rust Project Structure + Error Handling

  **What to do**:
  - Create Rust module structure in `src-tauri/src/`:
    - `git/mod.rs` — Git module root
    - `git/repository.rs` — Repository management (open, validate, watch)
    - `git/status.rs` — Status operations
    - `git/staging.rs` — Stage/unstage operations
    - `git/diff.rs` — Diff generation
    - `git/commit.rs` — Commit operations
    - `git/branch.rs` — Branch operations
    - `git/remote.rs` — Remote + push/pull/fetch
    - `git/stash.rs` — Stash operations
    - `git/blame.rs` — Blame operations
    - `git/search.rs` — Search operations
    - `git/rebase.rs` — Rebase operations
    - `git/conflict.rs` — Conflict resolution
    - `git/tags.rs` — Tag operations
    - `git/reset.rs` — Reset/revert operations
    - `git/graph.rs` — Commit graph
    - `git/history.rs` — Commit history/log
  - Create `error.rs` — Unified error handling with `anyhow` and `thiserror`
    - Define `GitError` enum with variants for common failures
    - Implement `From<git2::Error>` for `GitError`
    - Implement serialization for Tauri IPC
  - Create `cli_fallback.rs` — CLI fallback utility for operations git2-rs can't handle
  - Add `Cargo.toml` dependencies: `git2`, `anyhow`, `thiserror`, `serde`, `serde_json`, `tokio`
  - Verify `cargo build` succeeds
  - Verify `cargo clippy -- -D warnings` passes

  **Must NOT do**:
  - Don't implement git operations yet (just module stubs with pub trait/fn signatures)
  - Don't use `unwrap()` or `expect()` anywhere
  - Don't add unnecessary abstractions (no "RepositoryProvider" trait for multiple VCS)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1-3, 5-7)
  - **Blocks**: Tasks 8-13 (all Rust backend tasks)
  - **Blocked By**: Task 1 (needs project scaffold)

  **References**:

  **Pattern References**:
  - `app/Services/Git/AbstractGitService.php` — Base service pattern: repository path, shared utilities. Port concept to a Rust `GitContext` struct.
  - `app/Services/Git/GitService.php` — Root service: delegates to specialized services. Port as `git/mod.rs` public API.
  - `app/Services/Git/GitCommandRunner.php` — CLI command execution pattern. Port as `cli_fallback.rs`.
  - `app/Services/Git/GitErrorHandler.php` — Error handling patterns. Port as `error.rs` with Rust error types.

  **External References**:
  - git2-rs docs: `https://docs.rs/git2/latest/git2/`
  - GitButler's Rust structure: `https://github.com/gitbutlerapp/gitbutler/tree/master/crates`

  **WHY Each Reference Matters**:
  - `AbstractGitService.php` shows the shared context pattern (repo path, common methods) to replicate in Rust
  - `GitCommandRunner.php` shows exactly how CLI commands are spawned — same pattern needed for CLI fallback in Rust
  - `GitErrorHandler.php` shows error categories to map to Rust error enum variants

  **Acceptance Criteria**:
  - [ ] `cargo build` succeeds with 0 errors
  - [ ] `cargo clippy -- -D warnings` passes with 0 warnings
  - [ ] All 17 git module files exist with stub signatures
  - [ ] `error.rs` defines `GitError` enum with serialization

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Rust project compiles and passes clippy
    Tool: Bash
    Preconditions: ../gitty-tauri/src-tauri/ has Rust project structure
    Steps:
      1. cd ../gitty-tauri/src-tauri && cargo build 2>&1
      2. cargo clippy -- -D warnings 2>&1
      3. ls src/git/*.rs | wc -l
    Expected Result: Build succeeds, clippy passes, at least 17 git module files
    Failure Indicators: Compilation errors, clippy warnings, missing modules
    Evidence: .sisyphus/evidence/task-4-rust-build.txt

  Scenario: Error types are serializable for IPC
    Tool: Bash
    Preconditions: error.rs exists with GitError enum
    Steps:
      1. Grep for "Serialize" derive on GitError
      2. Grep for "impl From<git2::Error> for GitError"
      3. Verify no unwrap() or expect() in any .rs file: grep -r "unwrap()\|expect(" src/ --include="*.rs"
    Expected Result: GitError implements Serialize and From<git2::Error>, zero unwrap/expect calls
    Failure Indicators: Missing derives, unwrap() found
    Evidence: .sisyphus/evidence/task-4-error-handling.txt
  ```

  **Commit**: YES
  - Message: `feat(rust): scaffold Rust git service modules with error handling`
  - Files: `src-tauri/src/**`
  - Pre-commit: `cd ../gitty-tauri/src-tauri && cargo clippy -- -D warnings`

---

- [x] 5. Test Infrastructure (Rust + Vitest + Playwright)

  **What to do**:
  - Configure Rust test infrastructure:
    - Create `src-tauri/tests/` directory
    - Create `src-tauri/tests/helpers/mod.rs` — Test helper: `create_test_repo()` function that creates a temporary git repo with configurable commits, branches, modified files
    - Create example test: `src-tauri/tests/git_status_test.rs`
    - Verify `cargo test` runs
  - Configure Vitest:
    - Create `vitest.config.ts` with React + TypeScript support
    - Create `src/test/setup.ts` — Test setup with React Testing Library
    - Create example test: `src/test/example.test.tsx`
    - Verify `npm run test` runs
  - Configure Playwright:
    - Create `playwright.config.ts` for Tauri WebDriver testing
    - Create `e2e/example.spec.ts` — Example E2E test
    - Document Tauri-specific Playwright setup in `tests/README.md`
  - Create `tests/fixtures/README.md` — Document fixture generation strategy (no committed repos)

  **Must NOT do**:
  - Don't commit test fixture repos (generate programmatically)
  - Don't write actual feature tests (just infrastructure + examples)

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Understanding test patterns and TDD workflow, though this is Vitest not Pest

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1-4, 6-7)
  - **Blocks**: All tasks with tests (every subsequent task uses this infrastructure)
  - **Blocked By**: Task 1 (needs project scaffold)

  **References**:

  **Pattern References**:
  - `tests/` — Current PHP test directory structure for organizational reference
  - `phpunit.xml` — Current test configuration pattern (adapt concept for Vitest)

  **External References**:
  - Vitest docs: `https://vitest.dev/guide/`
  - Playwright + Tauri: `https://v2.tauri.app/develop/tests/webdriver/`
  - GitButler test setup: `https://github.com/gitbutlerapp/gitbutler/tree/master/crates/gitbutler-testsupport`

  **WHY Each Reference Matters**:
  - GitButler's test support crate shows how to create temporary git repos for testing — exactly what we need
  - Tauri's WebDriver docs show Playwright integration specifics

  **Acceptance Criteria**:
  - [ ] `cargo test` runs and passes (at least 1 test)
  - [ ] `npm run test` (vitest) runs and passes (at least 1 test)
  - [ ] `create_test_repo()` helper generates a valid git repo with commits
  - [ ] `tests/README.md` documents fixture strategy

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All test runners work
    Tool: Bash
    Preconditions: Test infrastructure configured
    Steps:
      1. cd ../gitty-tauri/src-tauri && cargo test 2>&1
      2. cd ../gitty-tauri && npm run test -- --run 2>&1
      3. Verify create_test_repo creates a valid git repo: cargo test test_create_repo 2>&1
    Expected Result: Both test runners execute, example tests pass, fixture helper works
    Failure Indicators: Test runner errors, fixture creation fails
    Evidence: .sisyphus/evidence/task-5-test-infrastructure.txt
  ```

  **Commit**: YES
  - Message: `feat(tests): set up Rust, Vitest, and Playwright test infrastructure`
  - Files: `vitest.config.ts`, `playwright.config.ts`, `src-tauri/tests/**`, `src/test/**`, `e2e/**`, `tests/README.md`

---

- [x] 6. Architecture Documentation

  **What to do**:
  - Create `../gitty-tauri/ARCHITECTURE.md` documenting:
    - System overview diagram (ASCII): Tauri window → React UI → IPC bridge → Rust backend → git2-rs/CLI
    - Rust module structure and responsibilities
    - IPC command/event schema (all commands, request/response types)
    - React component hierarchy (matching current 18 components)
    - State management flow: Zustand stores (repoStore, uiStore, settingsStore) + React Query (git operations)
    - Error handling strategy: Rust `anyhow::Error` → serialized → TypeScript `Error` → React error boundary
    - File watching strategy: notify crate for filesystem events → debounced status refresh
    - Testing strategy: unit (cargo test + vitest), integration (IPC tests), E2E (Playwright)
  - Create `../gitty-tauri/RUST_STYLE.md`:
    - No `unwrap()` / `expect()` in production code
    - Use `?` operator + `anyhow::Context` for error propagation
    - All git operations are `async`
    - Use `cargo clippy -- -D warnings` (zero warnings policy)
    - Naming conventions: snake_case for functions, PascalCase for types
  - Create `../gitty-tauri/IPC_SCHEMA.md`:
    - Complete list of Tauri commands with request/response types
    - Grouped by domain: repository, status, staging, diff, commit, branch, remote, stash, blame, search, rebase, conflict, tags, reset, graph, history, settings

  **Must NOT do**:
  - Don't over-document (keep concise, reference code)
  - Don't write tutorials (this is for agents executing tasks)

  **Recommended Agent Profile**:
  - **Category**: `writing`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1-5, 7)
  - **Blocks**: None (reference document, not a dependency)
  - **Blocked By**: Task 1 (needs project scaffold)

  **References**:

  **Pattern References**:
  - `AGENTS.md` — Current project documentation style and structure
  - `app/Services/Git/` — All 20 service files define the operation categories for IPC schema
  - `app/Livewire/` — All 18 components define the React component hierarchy

  **WHY Each Reference Matters**:
  - `AGENTS.md` shows the documentation style the project uses
  - Service and component directories define the exact modules to document

  **Acceptance Criteria**:
  - [ ] `ARCHITECTURE.md` exists with system overview, module structure, state flow
  - [ ] `RUST_STYLE.md` exists with coding standards
  - [ ] `IPC_SCHEMA.md` exists with complete command list grouped by domain

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Documentation files exist and are comprehensive
    Tool: Bash
    Preconditions: ../gitty-tauri/ exists
    Steps:
      1. Verify files exist: ls ../gitty-tauri/ARCHITECTURE.md ../gitty-tauri/RUST_STYLE.md ../gitty-tauri/IPC_SCHEMA.md
      2. Check ARCHITECTURE.md has key sections: grep -c "## " ../gitty-tauri/ARCHITECTURE.md (expect >= 5)
      3. Check IPC_SCHEMA.md covers all domains: grep -c "### " ../gitty-tauri/IPC_SCHEMA.md (expect >= 10)
      4. Check RUST_STYLE.md mentions unwrap ban: grep "unwrap" ../gitty-tauri/RUST_STYLE.md
    Expected Result: All 3 files exist, have proper sections, cover all domains
    Failure Indicators: Missing files, sparse content
    Evidence: .sisyphus/evidence/task-6-architecture-docs.txt
  ```

  **Commit**: YES (groups with Task 1)
  - Message: `docs(architecture): add architecture, Rust style guide, and IPC schema documentation`
  - Files: `ARCHITECTURE.md`, `RUST_STYLE.md`, `IPC_SCHEMA.md`

---

- [x] 7. git2-rs API Audit + Compatibility Matrix

  **What to do**:
  - Extract ALL git CLI calls from current NativePHP codebase:
    - Use `grep -r "Process::" app/Services/Git/` to find every shell-out
    - Document each git command, its arguments, and which service uses it
  - Map each operation to git2-rs API:
    - ✅ git2-rs native: status, add, reset, diff, commit, branch, checkout, log, blame, tag, remote, fetch, merge, stash
    - ⚠️ CLI fallback needed: push (with progress), pull (fetch+merge), rebase (interactive), cherry-pick, rev-parse (some forms)
    - ❌ Unsupported: filter-branch, bisect UI
  - Create `../gitty-tauri/GIT2_AUDIT.md`:
    - Complete compatibility matrix (operation → git2-rs API → CLI fallback)
    - Document CLI fallback pattern (Rust `tokio::process::Command`)
    - List any operations requiring different approach in Rust
  - Identify the ~5% that needs CLI fallback and document the exact commands

  **Must NOT do**:
  - Don't implement the operations (just audit and document)
  - Don't audit operations not in current gitty (no scope creep)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1-6)
  - **Blocks**: Tasks 8-12 (all Rust git service implementations depend on this audit)
  - **Blocked By**: Task 1 (needs project), Task 4 (needs to know Rust module structure)

  **References**:

  **Pattern References**:
  - `app/Services/Git/StagingService.php` — Stage/unstage operations: `git add`, `git reset HEAD`, `git checkout --`
  - `app/Services/Git/DiffService.php` — Diff operations: `git diff`, `git diff --cached`, `git diff --name-status`
  - `app/Services/Git/CommitService.php` — Commit operations: `git commit`, `git log`, `git show`
  - `app/Services/Git/BranchService.php` — Branch operations: `git branch`, `git checkout`, `git merge`
  - `app/Services/Git/RemoteService.php` — Remote operations: `git push`, `git pull`, `git fetch`, `git remote`
  - `app/Services/Git/StashService.php` — Stash operations: `git stash`, `git stash pop`, `git stash list`
  - `app/Services/Git/BlameService.php` — Blame: `git blame`
  - `app/Services/Git/SearchService.php` — Search: `git grep`, `git log --grep`
  - `app/Services/Git/RebaseService.php` — Rebase: `git rebase`
  - `app/Services/Git/ConflictService.php` — Conflict resolution: `git checkout --ours/--theirs`
  - `app/Services/Git/ResetService.php` — Reset/revert: `git reset`, `git revert`
  - `app/Services/Git/TagService.php` — Tags: `git tag`
  - `app/Services/Git/GraphService.php` — Graph: `git log --graph`
  - `app/Services/Git/GitCommandRunner.php` — How CLI commands are currently spawned

  **External References**:
  - git2-rs API docs: `https://docs.rs/git2/latest/git2/`
  - git2-rs Repository type: `https://docs.rs/git2/latest/git2/struct.Repository.html`

  **WHY Each Reference Matters**:
  - Every PHP service file contains the exact git commands we need to replicate. The audit maps each command to its git2-rs equivalent or flags it for CLI fallback.

  **Acceptance Criteria**:
  - [ ] `GIT2_AUDIT.md` exists with complete compatibility matrix
  - [ ] Every `Process::` call in `app/Services/Git/` is accounted for
  - [ ] Each operation marked as ✅ git2-rs, ⚠️ CLI fallback, or ❌ unsupported
  - [ ] CLI fallback pattern documented with example

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Audit covers all git operations
    Tool: Bash
    Preconditions: GIT2_AUDIT.md exists
    Steps:
      1. Count Process:: calls in PHP services: grep -r "Process::" app/Services/Git/ | wc -l
      2. Count operations documented in audit: grep -c "✅\|⚠️\|❌" ../gitty-tauri/GIT2_AUDIT.md
      3. Verify coverage: documented count should be >= 90% of Process:: count
    Expected Result: Audit covers at least 90% of current git CLI calls
    Failure Indicators: Large gap between Process:: count and documented operations
    Evidence: .sisyphus/evidence/task-7-git2-audit.txt
  ```

  **Commit**: YES
  - Message: `docs(git2): audit git2-rs API coverage with compatibility matrix`
  - Files: `GIT2_AUDIT.md`

---

### Wave 2: Core Rust Backend

- [x] 8. Git Repository + Status Service

  **What to do**:
  - Implement `git/repository.rs`:
    - `open_repo(path: &str) -> Result<Repository>` — Open and validate git repository
    - `get_repo_info()` — Return repo name, current branch, clean/dirty state
    - File watching with `notify` crate — detect filesystem changes, debounce, emit status refresh event
  - Implement `git/status.rs`:
    - `get_status() -> Result<GitStatus>` — Full repository status (branch, upstream, ahead/behind, changed files)
    - Map git2 `StatusEntry` to our `ChangedFile` type
    - Handle all file statuses: modified, added, deleted, renamed, unmerged, untracked
    - Detect detached HEAD state
    - Handle empty repository (no commits yet)
  - TDD: Write tests FIRST using `create_test_repo()` helper
    - Test: empty repo returns valid status
    - Test: repo with modified file shows correct status
    - Test: repo with staged files distinguishes staged vs unstaged
    - Test: detached HEAD is correctly reported
  - Run `cargo clippy -- -D warnings` (zero warnings)

  **Must NOT do**:
  - Don't block the main thread (use async)
  - Don't use `unwrap()` / `expect()`
  - Don't implement file watching for Windows/Linux

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 9-13)
  - **Blocks**: Tasks 20-22, 26, 30
  - **Blocked By**: Tasks 4 (Rust structure), 7 (git2 audit)

  **References**:

  **Pattern References**:
  - `app/Services/Git/GitService.php` — Root service that delegates to specialized services. Port `getStatus()`, `getRepoName()`, `getCurrentBranch()`.
  - `app/DTOs/GitStatus.php` — Status DTO structure: `branch`, `upstream`, `aheadBehind`, `changedFiles`, `isDetachedHead`, `mergeHead`
  - `app/DTOs/ChangedFile.php` — Changed file DTO: `path`, `status`, `staged`, `oldPath` (for renames)
  - `app/DTOs/AheadBehind.php` — Ahead/behind DTO: `ahead` (int), `behind` (int)

  **External References**:
  - git2-rs StatusOptions: `https://docs.rs/git2/latest/git2/struct.StatusOptions.html`
  - git2-rs Repository::statuses: `https://docs.rs/git2/latest/git2/struct.Repository.html#method.statuses`

  **WHY Each Reference Matters**:
  - `GitService.php` shows the exact public API surface we need to replicate
  - The DTOs define the exact data shapes the React frontend expects

  **Acceptance Criteria**:
  - [ ] `cargo test git::status` passes with all tests green
  - [ ] `get_status()` returns correct data for: clean repo, modified files, staged files, detached HEAD, empty repo
  - [ ] `cargo clippy -- -D warnings` passes

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Status service handles various repo states
    Tool: Bash
    Preconditions: Rust git module compiled
    Steps:
      1. cargo test test_status_clean_repo -- --nocapture
      2. cargo test test_status_modified_files -- --nocapture
      3. cargo test test_status_staged_files -- --nocapture
      4. cargo test test_status_detached_head -- --nocapture
      5. cargo test test_status_empty_repo -- --nocapture
      6. cargo clippy -- -D warnings
    Expected Result: All 5 test scenarios pass, clippy clean
    Failure Indicators: Test failures, clippy warnings
    Evidence: .sisyphus/evidence/task-8-status-service.txt

  Scenario: Error handling for invalid repository
    Tool: Bash
    Steps:
      1. cargo test test_open_invalid_repo -- --nocapture
      2. Verify error type is GitError::RepositoryNotFound (not a panic)
    Expected Result: Returns proper GitError, no panic
    Evidence: .sisyphus/evidence/task-8-error-handling.txt
  ```

  **Commit**: YES
  - Message: `feat(git): implement repository and status service with git2-rs`
  - Files: `src-tauri/src/git/repository.rs`, `src-tauri/src/git/status.rs`, `src-tauri/tests/git_status_test.rs`
  - Pre-commit: `cargo clippy -- -D warnings && cargo test`

---

- [x] 9. Staging + Diff Service

  **What to do**:
  - Implement `git/staging.rs`:
    - `stage_file(path: &str) -> Result<()>` — Stage a single file
    - `unstage_file(path: &str) -> Result<()>` — Unstage a single file
    - `stage_all() -> Result<()>` — Stage all changes
    - `unstage_all() -> Result<()>` — Unstage all changes
    - `discard_file(path: &str) -> Result<()>` — Discard working directory changes for a file
    - `discard_all() -> Result<()>` — Discard all working directory changes
  - Implement `git/diff.rs`:
    - `get_diff(staged: bool) -> Result<DiffResult>` — Get diff for staged or unstaged changes
    - `get_file_diff(path: &str, staged: bool) -> Result<DiffFile>` — Get diff for a single file
    - Parse hunks and lines into our DTO types
    - Handle binary files (detect and return "Binary file" indicator)
    - Handle renamed files (detect old_path)
    - Handle large files (truncate diff at configurable max lines)
  - TDD: Write tests FIRST

  **Must NOT do**:
  - Don't implement image diff (that's a frontend concern)
  - Don't use `unwrap()` / `expect()`

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 8, 10-13)
  - **Blocks**: Task 15 (Staging UI integration)
  - **Blocked By**: Tasks 4 (Rust structure), 7 (git2 audit)

  **References**:

  **Pattern References**:
  - `app/Services/Git/StagingService.php` — Stage/unstage operations: exact git commands used, error handling for partial failures
  - `app/Services/Git/DiffService.php` — Diff generation: `git diff`, `git diff --cached`, unified format parsing, binary detection, rename detection
  - `app/DTOs/DiffFile.php` — Diff file structure: path, oldPath, status, hunks
  - `app/DTOs/DiffResult.php` — Diff result: files array, stats
  - `app/DTOs/Hunk.php` — Hunk structure: header, line ranges, lines array
  - `app/DTOs/HunkLine.php` — Line structure: content, type (add/delete/context), line numbers

  **External References**:
  - git2-rs Index (staging): `https://docs.rs/git2/latest/git2/struct.Index.html`
  - git2-rs Diff: `https://docs.rs/git2/latest/git2/struct.Diff.html`

  **Acceptance Criteria**:
  - [ ] `cargo test git::staging` passes
  - [ ] `cargo test git::diff` passes
  - [ ] Stage/unstage single file and all files works correctly
  - [ ] Diff output matches expected hunk/line structure
  - [ ] Binary files detected and flagged
  - [ ] `cargo clippy -- -D warnings` passes

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Stage and unstage workflow
    Tool: Bash
    Steps:
      1. cargo test test_stage_single_file -- --nocapture
      2. cargo test test_unstage_single_file -- --nocapture
      3. cargo test test_stage_all -- --nocapture
      4. cargo test test_discard_file -- --nocapture
    Expected Result: All staging operations work correctly
    Evidence: .sisyphus/evidence/task-9-staging.txt

  Scenario: Diff generation with edge cases
    Tool: Bash
    Steps:
      1. cargo test test_diff_modified_file -- --nocapture
      2. cargo test test_diff_binary_file -- --nocapture
      3. cargo test test_diff_renamed_file -- --nocapture
      4. cargo test test_diff_staged_vs_unstaged -- --nocapture
    Expected Result: All diff scenarios produce correct output
    Evidence: .sisyphus/evidence/task-9-diff.txt
  ```

  **Commit**: YES
  - Message: `feat(git): implement staging and diff services with git2-rs`
  - Files: `src-tauri/src/git/staging.rs`, `src-tauri/src/git/diff.rs`, tests
  - Pre-commit: `cargo clippy -- -D warnings && cargo test`

---

- [x] 10. Commit + History Service

  **What to do**:
  - Implement `git/commit.rs`:
    - `create_commit(message: &str) -> Result<String>` — Create commit, return hash
    - `amend_commit(message: &str) -> Result<String>` — Amend last commit
    - `get_commit(hash: &str) -> Result<Commit>` — Get commit details
    - `cherry_pick(hash: &str) -> Result<MergeResult>` — Cherry-pick a commit
  - Implement `git/history.rs`:
    - `get_log(limit: usize, skip: usize) -> Result<Vec<Commit>>` — Paginated commit history
    - `get_file_log(path: &str, limit: usize) -> Result<Vec<Commit>>` — File history
    - `search_commits(query: &str) -> Result<Vec<Commit>>` — Search commit messages
  - TDD: Test commit creation, log retrieval, pagination, cherry-pick

  **Must NOT do**:
  - Don't implement interactive rebase here (Task 23)
  - Don't implement graph visualization here (Task 25)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 8-9, 11-13)
  - **Blocks**: Task 25 (History panel)
  - **Blocked By**: Tasks 4 (Rust structure), 7 (git2 audit)

  **References**:

  **Pattern References**:
  - `app/Services/Git/CommitService.php` — Commit operations: create, amend, cherry-pick, log parsing
  - `app/DTOs/Commit.php` — Commit DTO: hash, shortHash, message, author, authorEmail, date, parents
  - `app/DTOs/MergeResult.php` — Cherry-pick/merge result: success, conflicts, message

  **Acceptance Criteria**:
  - [ ] `cargo test git::commit` passes
  - [ ] `cargo test git::history` passes
  - [ ] Commit creation returns valid hash
  - [ ] Log pagination works correctly

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Commit creation and retrieval
    Tool: Bash
    Steps:
      1. cargo test test_create_commit -- --nocapture
      2. cargo test test_amend_commit -- --nocapture
      3. cargo test test_get_log_paginated -- --nocapture
      4. cargo test test_search_commits -- --nocapture
    Expected Result: All commit operations work correctly
    Evidence: .sisyphus/evidence/task-10-commit.txt
  ```

  **Commit**: YES
  - Message: `feat(git): implement commit and history services`
  - Files: `src-tauri/src/git/commit.rs`, `src-tauri/src/git/history.rs`, tests
  - Pre-commit: `cargo clippy -- -D warnings && cargo test`

---

- [x] 11. Branch + Merge Service

  **What to do**:
  - Implement `git/branch.rs`:
    - `list_branches(include_remote: bool) -> Result<Vec<Branch>>` — List branches
    - `create_branch(name: &str) -> Result<Branch>` — Create new branch
    - `delete_branch(name: &str, force: bool) -> Result<()>` — Delete branch
    - `checkout_branch(name: &str) -> Result<()>` — Switch branch
    - `rename_branch(old: &str, new: &str) -> Result<()>` — Rename branch
    - `merge_branch(name: &str) -> Result<MergeResult>` — Merge branch into current
    - Filter remote branches: hide remotes that have corresponding local branch
  - TDD: Test branch CRUD, checkout, merge with conflicts, remote branch filtering

  **Must NOT do**:
  - Don't implement rebase here (Task 23)
  - Don't implement conflict resolution UI here (Task 24)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 8-10, 12-13)
  - **Blocks**: Tasks 23 (Rebase), 24 (Conflict resolver)
  - **Blocked By**: Tasks 4 (Rust structure), 7 (git2 audit)

  **References**:

  **Pattern References**:
  - `app/Services/Git/BranchService.php` — Branch operations: list, create, delete, checkout, rename, merge, remote branch filtering logic
  - `app/DTOs/Branch.php` — Branch DTO: name, isCurrent, isRemote, upstream, lastCommit, trackingStatus
  - `app/DTOs/MergeResult.php` — Merge result DTO

  **Acceptance Criteria**:
  - [ ] `cargo test git::branch` passes
  - [ ] All branch CRUD operations work
  - [ ] Remote branch filtering matches current behavior

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Branch management workflow
    Tool: Bash
    Steps:
      1. cargo test test_list_branches -- --nocapture
      2. cargo test test_create_delete_branch -- --nocapture
      3. cargo test test_checkout_branch -- --nocapture
      4. cargo test test_merge_branch -- --nocapture
      5. cargo test test_merge_with_conflicts -- --nocapture
    Expected Result: All branch operations work, conflicts detected correctly
    Evidence: .sisyphus/evidence/task-11-branch.txt
  ```

  **Commit**: YES
  - Message: `feat(git): implement branch and merge services`
  - Files: `src-tauri/src/git/branch.rs`, tests
  - Pre-commit: `cargo clippy -- -D warnings && cargo test`

---

- [x] 12. Remote + Sync Service

  **What to do**:
  - Implement `git/remote.rs`:
    - `list_remotes() -> Result<Vec<Remote>>` — List configured remotes
    - `fetch(remote: &str) -> Result<()>` — Fetch from remote (with progress callback)
    - `pull(remote: &str, branch: &str) -> Result<MergeResult>` — Pull (fetch + merge)
    - `push(remote: &str, branch: &str, force: bool) -> Result<()>` — Push to remote
    - `get_ahead_behind() -> Result<AheadBehind>` — Commits ahead/behind upstream
  - Use CLI fallback for push/pull (git2-rs push requires complex credential handling)
  - Implement progress streaming via Tauri events (not just return value)
  - Handle auth: SSH key (system keychain), HTTPS (credential helper)
  - TDD: Test fetch, ahead/behind count, remote listing

  **Must NOT do**:
  - Don't implement GitHub/GitLab API integration
  - Don't handle credential UI (use system credential helper)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 8-11, 13)
  - **Blocks**: Task 19 (Sync panel UI)
  - **Blocked By**: Tasks 4 (Rust structure), 7 (git2 audit)

  **References**:

  **Pattern References**:
  - `app/Services/Git/RemoteService.php` — Remote operations: fetch, pull, push, ahead/behind calculation
  - `app/Livewire/SyncPanel.php` — Sync UI state: push/pull/fetch buttons, progress indicators, ahead/behind display
  - `app/DTOs/AheadBehind.php` — Ahead/behind DTO
  - `app/DTOs/Remote.php` — Remote DTO: name, fetchUrl, pushUrl

  **Acceptance Criteria**:
  - [ ] `cargo test git::remote` passes
  - [ ] Fetch works with progress events
  - [ ] Ahead/behind count matches git output
  - [ ] CLI fallback for push/pull works correctly

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Remote operations
    Tool: Bash
    Steps:
      1. cargo test test_list_remotes -- --nocapture
      2. cargo test test_get_ahead_behind -- --nocapture
      3. cargo test test_fetch_progress -- --nocapture
    Expected Result: Remote operations work, progress events emitted
    Evidence: .sisyphus/evidence/task-12-remote.txt

  Scenario: CLI fallback for push
    Tool: Bash
    Steps:
      1. cargo test test_push_cli_fallback -- --nocapture
      2. Verify git CLI is invoked when git2-rs push fails
    Expected Result: CLI fallback executes successfully
    Evidence: .sisyphus/evidence/task-12-cli-fallback.txt
  ```

  **Commit**: YES
  - Message: `feat(git): implement remote and sync services with CLI fallback`
  - Files: `src-tauri/src/git/remote.rs`, tests
  - Pre-commit: `cargo clippy -- -D warnings && cargo test`

---

- [x] 13. Tauri IPC Command Handlers

  **What to do**:
  - Create `src-tauri/src/commands/mod.rs` — Command module root
  - Create command handlers for each git domain:
    - `commands/repo.rs` — open_repo, get_repo_info, watch_repo
    - `commands/status.rs` — get_status
    - `commands/staging.rs` — stage_file, unstage_file, stage_all, unstage_all, discard_file, discard_all
    - `commands/diff.rs` — get_diff, get_file_diff
    - `commands/commit.rs` — create_commit, amend_commit, get_commit, cherry_pick
    - `commands/branch.rs` — list_branches, create_branch, delete_branch, checkout_branch, merge_branch
    - `commands/remote.rs` — list_remotes, fetch, pull, push, get_ahead_behind
    - `commands/history.rs` — get_log, get_file_log, search_commits
  - Register ALL commands in `main.rs` with `tauri::generate_handler![]`
  - Create `src/lib/tauri.ts` — TypeScript wrapper functions that call `invoke()` with proper types
  - Implement Tauri event listeners for progress updates (fetch/push/pull progress)
  - Handle errors: Rust `anyhow::Error` → serialized error response → TypeScript Error

  **Must NOT do**:
  - Don't implement commands for features not yet built (stash, blame, rebase, etc. — those come in Wave 4)
  - Don't use `any` type in TypeScript wrappers

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 8-12)
  - **Blocks**: Tasks 19-30 (all frontend components that call IPC)
  - **Blocked By**: Tasks 3 (TypeScript types), 4 (Rust structure)

  **References**:

  **Pattern References**:
  - `app/Livewire/StagingPanel.php` — Shows which operations the UI calls: stage, unstage, discard, stage_all, unstage_all
  - `app/Livewire/CommitPanel.php` — Shows commit operations: create_commit, amend_commit
  - `app/Livewire/BranchManager.php` — Shows branch operations: list, create, delete, checkout, merge
  - `app/Livewire/SyncPanel.php` — Shows sync operations: push, pull, fetch
  - TypeScript types from Task 3: `src/types/ipc.ts` — Command request/response types

  **External References**:
  - Tauri 2.x Commands: `https://v2.tauri.app/develop/calling-rust/`
  - Tauri IPC: `https://v2.tauri.app/concept/inter-process-communication/`

  **WHY Each Reference Matters**:
  - Each Livewire component shows the exact operations the frontend needs — these become Tauri commands
  - Tauri docs show the `#[tauri::command]` attribute pattern and `invoke()` syntax

  **Acceptance Criteria**:
  - [ ] All Tauri commands registered in `main.rs`
  - [ ] TypeScript wrappers in `src/lib/tauri.ts` with proper types
  - [ ] `cargo build` succeeds
  - [ ] `tsc --noEmit` passes
  - [ ] Error serialization works (Rust error → TypeScript error)

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: IPC commands compile and register
    Tool: Bash
    Preconditions: All Wave 2 Rust services built
    Steps:
      1. cd ../gitty-tauri/src-tauri && cargo build 2>&1
      2. grep -c "#\[tauri::command\]" src/commands/*.rs (count registered commands)
      3. cd ../gitty-tauri && npx tsc --noEmit 2>&1
      4. grep -c "invoke(" src/lib/tauri.ts (count TypeScript wrappers)
    Expected Result: Build succeeds, at least 15 Tauri commands registered, matching TypeScript wrappers
    Failure Indicators: Build errors, type mismatches, missing commands
    Evidence: .sisyphus/evidence/task-13-ipc-commands.txt
  ```

  **Commit**: YES
  - Message: `feat(ipc): implement Tauri command handlers and TypeScript wrappers`
  - Files: `src-tauri/src/commands/**`, `src/lib/tauri.ts`
  - Pre-commit: `cargo build && npx tsc --noEmit`

---

### Wave 3: Core React UI

- [x] 14. App Shell + Layout + Header

  **What to do**:
  - Create `src/App.tsx` — Root component with layout structure
  - Create `src/components/layout/AppLayout.tsx`:
    - Fixed header (h-9, bg-[#e6e9ef], border-b border-[#ccd0da])
    - 64px traffic light spacer (macOS window controls) with `-webkit-app-region: drag`
    - Sidebar toggle button
    - Repo switcher trigger (folder icon + repo name + chevron)
    - Branch manager trigger (git-branch icon + branch name + chevron)
    - Flex spacer
    - Push/Pull/Fetch buttons with ahead/behind counts
    - Three-panel layout: sidebar (optional) | staging panel | diff viewer
  - Create `src/components/layout/Header.tsx` — Header bar component
  - Create `src/stores/repoStore.ts` — Zustand store: currentRepo, currentBranch, status, aheadBehind
  - Create `src/stores/uiStore.ts` — Zustand store: sidebarVisible, selectedFile, viewMode (flat/tree), activePanels
  - Set up React Query provider in App.tsx
  - Set up error boundary component
  - Phosphor Icons React: use `@phosphor-icons/react` (Light variant for header icons)

  **Must NOT do**:
  - Don't implement sidebar content yet (Task 26)
  - Don't implement dropdown menus yet (Tasks 18, 26)
  - Don't add bottom status bar (removed in current app as redundant)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 15-18)
  - **Blocks**: Tasks 19-31 (all depend on app shell layout)
  - **Blocked By**: Tasks 2 (design system), 3 (TypeScript types)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php` — COMPLETE layout structure: header, sidebar, staging panel, diff viewer positioning, keyboard shortcuts
  - `app/Livewire/AppLayout.php` — Layout PHP logic: sidebar state, panel sizing, keyboard shortcut handling
  - `resources/css/app.css` — Header styles: h-9, bg-[#e6e9ef], border-b, traffic light spacer, drag regions
  - `AGENTS.md` — Header layout specification, icon sizes, button variants, traffic light spacer details

  **WHY Each Reference Matters**:
  - `app-layout.blade.php` is the EXACT template to replicate — every div, every class, every icon
  - `AGENTS.md` documents the header layout specification with exact pixel values and color codes

  **Acceptance Criteria**:
  - [ ] App renders with correct layout: header + three-panel structure
  - [ ] Header shows: traffic light spacer, repo name, branch name, push/pull/fetch buttons
  - [ ] Zustand stores initialized with default state
  - [ ] React Query provider configured
  - [ ] Vitest component tests pass

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: App shell renders correctly
    Tool: Playwright (playwright skill)
    Preconditions: npm run tauri dev running
    Steps:
      1. Navigate to app window
      2. Assert header exists: page.locator('[data-testid="app-header"]')
      3. Assert header height is 36px (h-9)
      4. Assert header background color is #e6e9ef
      5. Assert traffic light spacer is 64px wide
      6. Assert three-panel layout visible
    Expected Result: Layout matches current gitty app structure
    Evidence: .sisyphus/evidence/task-14-app-shell.png

  Scenario: Header buttons render with correct icons
    Tool: Playwright
    Steps:
      1. Assert sidebar toggle button exists with phosphor icon
      2. Assert push/pull/fetch buttons exist
      3. Assert Phosphor Icons render (Light variant for header)
    Expected Result: All header elements present with correct icons
    Evidence: .sisyphus/evidence/task-14-header-buttons.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement app shell, layout, and header with Catppuccin theme`
  - Files: `src/App.tsx`, `src/components/layout/**`, `src/stores/**`
  - Pre-commit: `npm run test -- --run && tsc --noEmit`

---

- [x] 15. Staging Panel + File List

  **What to do**:
  - Create `src/components/staging/StagingPanel.tsx`:
    - Two sections: "Staged Changes" and "Changes" with collapsible headers
    - Section headers: bg-[#e6e9ef] (Mantle), with file count and action buttons (Stage All, Unstage All, Discard All)
    - File list: white background, hover bg-[#eff1f5] (Base)
    - Each file item: status dot (colored by status), filename, action buttons (stage/unstage/discard)
    - Flat view (default) and tree view toggle
  - Create `src/components/staging/FileItem.tsx`:
    - Status dot: w-2 h-2 rounded-full, colored by status (modified=#df8e1d, added=#40a02b, deleted=#d20f39, renamed=#084CCF, unmerged=#fe640b)
    - File name with path
    - Action buttons with tooltips (Stage, Unstage, Discard)
    - Click to select file → show diff in DiffViewer
  - Create `src/components/staging/FileTreeView.tsx`:
    - Collapsible folder structure with indentation (16px per level)
    - Folder icon: phosphor-folder-simple
    - Collapse chevron: small arrow, rotates 90° when expanded
    - Same density as flat view (py-1.5, gap-2.5)
  - Wire to Zustand store + React Query for status data
  - File tree builder utility matching current `FileTreeBuilder.php` logic

  **Must NOT do**:
  - Don't use Flux UI components (React project)
  - Don't add dividers between file items (edge-to-edge like current flat view)
  - Don't add borders on folders in tree view

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 14, 16-18)
  - **Blocks**: None (standalone UI component)
  - **Blocked By**: Tasks 2 (design system), 3 (TypeScript types)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/staging-panel.blade.php` — COMPLETE staging panel template: section headers, file list, action buttons, flat/tree toggle
  - `app/Livewire/StagingPanel.php` — Staging logic: file filtering, sorting, stage/unstage/discard actions
  - `app/Helpers/FileTreeBuilder.php` — File tree building algorithm: path splitting, nesting, sorting
  - `AGENTS.md` — Status dot colors, hover states, tree view rules, tooltip requirements, icon specifications

  **WHY Each Reference Matters**:
  - `staging-panel.blade.php` is the exact template to replicate in React
  - `FileTreeBuilder.php` contains the tree-building algorithm to port to TypeScript
  - `AGENTS.md` has pixel-perfect specifications for dots, hover states, and tree view

  **Acceptance Criteria**:
  - [ ] Staging panel renders with Staged and Changes sections
  - [ ] File items show correct status dots with exact Catppuccin colors
  - [ ] Flat view and tree view both work
  - [ ] Action buttons (stage/unstage/discard) have tooltips
  - [ ] Vitest tests pass

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Staging panel displays files correctly
    Tool: Playwright
    Preconditions: App running with test repo containing modified, staged, and untracked files
    Steps:
      1. Assert "Staged Changes" section header visible
      2. Assert "Changes" section header visible
      3. Assert file items show status dots with correct colors
      4. Assert modified file dot is #df8e1d (yellow)
      5. Assert added file dot is #40a02b (green)
      6. Click flat/tree toggle — verify tree view renders with folder icons
    Expected Result: Staging panel matches current gitty layout exactly
    Evidence: .sisyphus/evidence/task-15-staging-panel.png

  Scenario: Stage/unstage actions work
    Tool: Playwright
    Steps:
      1. Click stage button on a file in Changes section
      2. Assert file moves to Staged Changes section
      3. Click unstage button on the staged file
      4. Assert file moves back to Changes section
    Expected Result: Files move between sections correctly
    Evidence: .sisyphus/evidence/task-15-stage-actions.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement staging panel with file list, tree view, and actions`
  - Files: `src/components/staging/**`
  - Pre-commit: `npm run test -- --run`

---

- [x] 16. Diff Viewer

  **What to do**:
  - Create `src/components/diff/DiffViewer.tsx`:
    - Use `react-diff-view` library for rendering
    - White background diff area with sticky header
    - Diff header: file path, status badge (colored like current app), +/- line counts
    - Status badge: inline-styled div (NOT a UI library badge), bg at 15% opacity of status color
    - Support unified and split view modes
    - Line numbers in gutter
    - Diff line backgrounds: addition rgba(64,160,43,0.1), deletion rgba(210,15,57,0.1), context var(--surface-0)
    - Syntax highlighting (use react-diff-view's built-in or highlight.js)
  - Create `src/components/diff/DiffHeader.tsx`:
    - Sticky top-0 z-10 with box-shadow
    - File path, status badge, +N/-N counts
    - Same padding as staging toolbar (px-4 py-2)
  - Handle edge cases:
    - Binary file: show "Binary file" message
    - Empty diff: show "No changes" message
    - Large file: show "File too large to display" with line count
    - No file selected: show empty state with fade-in animation

  **Must NOT do**:
  - Don't implement image diff viewer yet (can be enhancement)
  - Don't use Flux badges (use inline-styled divs for exact color matching)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 14-15, 17-18)
  - **Blocks**: None
  - **Blocked By**: Tasks 2 (design system), 3 (TypeScript types)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/diff-viewer.blade.php` — COMPLETE diff viewer template: header, line rendering, status badges, empty states
  - `app/Livewire/DiffViewer.php` — Diff viewer logic: file selection, view mode switching, diff parsing
  - `resources/css/app.css` — Diff CSS classes: `.diff-line-addition`, `.diff-line-deletion`, `.diff-line-context` with exact rgba values
  - `AGENTS.md` — Diff header badge colors, status badge implementation (inline-styled div, NOT badge component)

  **Acceptance Criteria**:
  - [ ] Diff viewer renders with react-diff-view
  - [ ] Status badges use inline-styled divs with Catppuccin colors
  - [ ] Diff line backgrounds match current app (green/red tints at 10% opacity)
  - [ ] Unified and split view modes work
  - [ ] Edge cases handled: binary, empty, large file, no selection

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Diff viewer renders correctly
    Tool: Playwright
    Preconditions: App running with test repo, modified file selected
    Steps:
      1. Assert diff header shows file path
      2. Assert status badge has correct background color (e.g., modified = #df8e1d at 15% opacity)
      3. Assert addition lines have green-tinted background
      4. Assert deletion lines have red-tinted background
      5. Assert line numbers visible in gutter
    Expected Result: Diff viewer matches current gitty diff styling
    Evidence: .sisyphus/evidence/task-16-diff-viewer.png

  Scenario: Diff viewer handles edge cases
    Tool: Playwright
    Steps:
      1. Select binary file — assert "Binary file" message shown
      2. Select file with no changes — assert "No changes" message
      3. Deselect all files — assert empty state with fade-in animation
    Expected Result: All edge cases handled gracefully
    Evidence: .sisyphus/evidence/task-16-diff-edge-cases.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement diff viewer with react-diff-view and Catppuccin styling`
  - Files: `src/components/diff/**`
  - Pre-commit: `npm run test -- --run`

---

- [x] 17. Commit Panel

  **What to do**:
  - Create `src/components/commit/CommitPanel.tsx`:
    - Commit message textarea (mono font, placeholder text)
    - Split button: "Commit (⌘↵)" primary + dropdown with "Commit & Push (⌘⇧↵)"
    - Amend checkbox
    - Author display (from git config)
    - Disabled state when no staged files
    - Loading state during commit operation
  - Wire to IPC: create_commit, amend_commit
  - Keyboard shortcut handlers: ⌘↵ (commit), ⌘⇧↵ (commit + push)

  **Must NOT do**:
  - Don't use Flux UI split button pattern (React — implement with standard HTML/Tailwind)
  - Don't add AI commit message generation

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 14-16, 18)
  - **Blocks**: None
  - **Blocked By**: Tasks 2 (design system), 3 (TypeScript types)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/commit-panel.blade.php` — Commit panel template: message area, split button, amend checkbox
  - `app/Livewire/CommitPanel.php` — Commit logic: validation, commit creation, amend flow
  - `AGENTS.md` — Split button pattern (button group), keyboard shortcuts, button variants

  **Acceptance Criteria**:
  - [ ] Commit panel renders with message textarea, split button, amend checkbox
  - [ ] Commit button disabled when no staged files
  - [ ] ⌘↵ creates commit, ⌘⇧↵ creates commit + push
  - [ ] Loading state during commit

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Commit workflow
    Tool: Playwright
    Preconditions: App running with staged files
    Steps:
      1. Type commit message in textarea
      2. Click "Commit" button
      3. Assert staged files section becomes empty
      4. Assert commit message textarea is cleared
    Expected Result: Commit created successfully, UI updates
    Evidence: .sisyphus/evidence/task-17-commit.png

  Scenario: Commit button disabled without staged files
    Tool: Playwright
    Preconditions: No staged files
    Steps:
      1. Assert commit button is disabled
      2. Assert button has disabled visual state
    Expected Result: Button disabled, not clickable
    Evidence: .sisyphus/evidence/task-17-commit-disabled.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement commit panel with split button and keyboard shortcuts`
  - Files: `src/components/commit/**`
  - Pre-commit: `npm run test -- --run`

---

- [x] 18. Branch Manager Dropdown

  **What to do**:
  - Create `src/components/branch/BranchManager.tsx`:
    - Dropdown triggered from header (git-branch icon + branch name)
    - Search/filter field (sticky top, white background)
    - Local branches section with current branch highlighted
    - Remote branches section (filtered: hide remotes with corresponding local branch)
    - Create new branch button (sticky bottom, white background)
    - Branch actions: checkout, merge, delete, rename
  - Wire to IPC: list_branches, checkout_branch, create_branch, delete_branch, merge_branch

  **Must NOT do**:
  - Don't use Flux UI dropdown (React — implement with Headless UI or custom)
  - Don't implement rebase from branch manager (separate panel)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 14-17)
  - **Blocks**: None
  - **Blocked By**: Tasks 2 (design system), 3 (TypeScript types)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/branch-manager.blade.php` — Branch dropdown template: search field, branch lists, action buttons, sticky areas with bg-white
  - `app/Livewire/BranchManager.php` — Branch manager logic: filtering, search, checkout, create, delete
  - `AGENTS.md` — Dropdown background rules: sticky areas need explicit bg-white, remote branch filtering logic

  **Acceptance Criteria**:
  - [ ] Branch dropdown renders with search, local/remote sections
  - [ ] Current branch highlighted
  - [ ] Remote branches filtered (hide those with local counterpart)
  - [ ] Sticky search and footer have bg-white
  - [ ] Branch checkout, create, delete work

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Branch manager dropdown
    Tool: Playwright
    Steps:
      1. Click branch trigger in header
      2. Assert dropdown opens with search field
      3. Assert current branch is highlighted
      4. Type in search — assert branches are filtered
      5. Assert remote branches without local counterpart are shown
      6. Assert remote branches with local counterpart are hidden
    Expected Result: Branch manager matches current gitty behavior
    Evidence: .sisyphus/evidence/task-18-branch-manager.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement branch manager dropdown with search and filtering`
  - Files: `src/components/branch/**`
  - Pre-commit: `npm run test -- --run`

---

### Wave 4: Advanced Features

- [x] 19. Sync Panel + Push/Pull/Fetch

  **What to do**:
  - Create `src/components/sync/SyncPanel.tsx`:
    - Push button with ahead count badge
    - Pull button with behind count badge
    - Fetch button
    - Loading/spinning states during operations
    - Progress indicators for long operations
  - Wire to IPC: push, pull, fetch, get_ahead_behind
  - Tauri event listeners for progress updates
  - Auto-refresh status after sync operations

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Tasks 20-25)
  - **Blocked By**: Tasks 12 (Remote service), 13 (IPC), 14 (App shell)

  **References**:
  - `resources/views/livewire/sync-panel.blade.php` — Sync panel template
  - `app/Livewire/SyncPanel.php` — Sync logic

  **Acceptance Criteria**:
  - [ ] Push/pull/fetch buttons work with loading states
  - [ ] Ahead/behind counts displayed correctly
  - [ ] Progress shown during fetch

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Sync operations
    Tool: Playwright
    Steps:
      1. Assert ahead/behind counts match git status
      2. Click fetch — assert loading spinner appears
      3. Wait for fetch complete — assert spinner disappears
    Expected Result: Sync operations work with visual feedback
    Evidence: .sisyphus/evidence/task-19-sync.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement sync panel with push/pull/fetch and progress`

---

- [x] 20. Stash Service + UI

  **What to do**:
  - Implement Rust `git/stash.rs`: stash_save, stash_pop, stash_apply, stash_drop, stash_list
  - Add IPC commands for stash operations
  - Create `src/components/stash/StashPanel.tsx`: stash list, apply/pop/drop actions
  - TDD for Rust stash service

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4
  - **Blocked By**: Tasks 8, 13, 14

  **References**:
  - `app/Services/Git/StashService.php` — Stash operations
  - `app/DTOs/Stash.php` — Stash DTO

  **Acceptance Criteria**:
  - [ ] `cargo test git::stash` passes
  - [ ] Stash save/pop/apply/drop/list all work
  - [ ] UI shows stash list with actions

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Stash workflow
    Tool: Bash + Playwright
    Steps:
      1. cargo test test_stash_save_and_pop -- --nocapture
      2. In app: make changes, click stash, assert changes disappear
      3. Click pop stash, assert changes reappear
    Expected Result: Full stash workflow works
    Evidence: .sisyphus/evidence/task-20-stash.txt
  ```

  **Commit**: YES
  - Message: `feat(stash): implement stash service and UI`

---

- [x] 21. Blame View + Service

  **What to do**:
  - Implement Rust `git/blame.rs`: get_blame(path) -> Vec<BlameLine>
  - Add IPC command for blame
  - Create `src/components/blame/BlameView.tsx`: inline annotations showing commit, author, date per line
  - TDD for Rust blame service

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4
  - **Blocked By**: Tasks 8, 13, 14

  **References**:
  - `app/Services/Git/BlameService.php` — Blame operations
  - `app/DTOs/BlameLine.php` — Blame line DTO
  - `resources/views/livewire/blame-view.blade.php` — Blame UI

  **Acceptance Criteria**:
  - [ ] `cargo test git::blame` passes
  - [ ] Blame view shows line-by-line annotations with commit, author, date

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Blame view renders
    Tool: Playwright
    Steps:
      1. Open blame for a file with multiple authors
      2. Assert blame annotations show commit hash, author, date
      3. Assert line content is displayed
    Expected Result: Blame view renders correctly
    Evidence: .sisyphus/evidence/task-21-blame.png
  ```

  **Commit**: YES
  - Message: `feat(blame): implement blame service and view`

---

- [x] 22. Search Panel + Service

  **What to do**:
  - Implement Rust `git/search.rs`: search_files(query) -> results, search_commits(query) -> results
  - Add IPC commands for search
  - Create `src/components/search/SearchPanel.tsx`: search input, results list with file/commit tabs
  - TDD for Rust search service

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4
  - **Blocked By**: Tasks 8, 13, 14

  **References**:
  - `app/Services/Git/SearchService.php` — Search operations
  - `resources/views/livewire/search-panel.blade.php` — Search UI

  **Acceptance Criteria**:
  - [ ] `cargo test git::search` passes
  - [ ] Search panel shows results for file content and commit messages

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Search finds results
    Tool: Playwright
    Steps:
      1. Open search panel
      2. Type search query matching known content
      3. Assert results appear with file paths and line numbers
    Expected Result: Search returns matching results
    Evidence: .sisyphus/evidence/task-22-search.png
  ```

  **Commit**: YES
  - Message: `feat(search): implement search service and panel`

---

- [x] 23. Rebase Panel + Service

  **What to do**:
  - Implement Rust `git/rebase.rs`: start_rebase, continue_rebase, abort_rebase, get_rebase_status
  - Use CLI fallback for interactive rebase
  - Add IPC commands for rebase
  - Create `src/components/rebase/RebasePanel.tsx`: rebase status, step counter, continue/abort buttons
  - Handle rebase conflicts → redirect to conflict resolver
  - TDD for Rust rebase service

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4
  - **Blocked By**: Tasks 11, 13, 14

  **References**:
  - `app/Services/Git/RebaseService.php` — Rebase operations
  - `resources/views/livewire/rebase-panel.blade.php` — Rebase UI

  **Acceptance Criteria**:
  - [ ] `cargo test git::rebase` passes
  - [ ] Rebase panel shows step counter during rebase
  - [ ] Continue/abort buttons work

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Rebase with conflict
    Tool: Bash
    Steps:
      1. Create test repo with divergent branches
      2. cargo test test_rebase_with_conflict -- --nocapture
    Expected Result: Rebase detects conflicts correctly
    Evidence: .sisyphus/evidence/task-23-rebase.txt
  ```

  **Commit**: YES
  - Message: `feat(rebase): implement rebase service and panel`

---

- [x] 24. Conflict Resolver + Service

  **What to do**:
  - Implement Rust `git/conflict.rs`: get_conflicts, resolve_with_ours, resolve_with_theirs, mark_resolved
  - Add IPC commands for conflict resolution
  - Create `src/components/conflict/ConflictResolver.tsx`: conflicting files list, ours/theirs/manual resolution, mark resolved
  - TDD for Rust conflict service

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4
  - **Blocked By**: Tasks 11, 13, 14

  **References**:
  - `app/Services/Git/ConflictService.php` — Conflict resolution operations
  - `app/DTOs/ConflictFile.php` — Conflict file DTO
  - `resources/views/livewire/conflict-resolver.blade.php` — Conflict resolver UI

  **Acceptance Criteria**:
  - [ ] `cargo test git::conflict` passes
  - [ ] Ours/theirs resolution works
  - [ ] Mark as resolved clears conflict state

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Resolve merge conflict
    Tool: Bash + Playwright
    Steps:
      1. cargo test test_resolve_ours -- --nocapture
      2. cargo test test_resolve_theirs -- --nocapture
    Expected Result: Conflict resolution works
    Evidence: .sisyphus/evidence/task-24-conflict.txt
  ```

  **Commit**: YES
  - Message: `feat(conflict): implement conflict resolution service and UI`

---

- [x] 25. History Panel + Graph

  **What to do**:
  - Implement Rust `git/graph.rs`: get_graph(limit) -> Vec<GraphNode>
  - Add IPC commands for graph
  - Create `src/components/history/HistoryPanel.tsx`: commit list with graph visualization, pagination
  - Click commit to see its diff
  - TDD for Rust graph service

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`, `frontend-ui-ux`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4
  - **Blocked By**: Tasks 10, 13, 14

  **References**:
  - `app/Services/Git/GraphService.php` — Graph generation
  - `app/DTOs/GraphNode.php` — Graph node DTO
  - `resources/views/livewire/history-panel.blade.php` — History panel UI

  **Acceptance Criteria**:
  - [ ] `cargo test git::graph` passes
  - [ ] History panel shows commits with graph lines
  - [ ] Click commit shows diff

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: History panel renders
    Tool: Playwright
    Steps:
      1. Open history panel
      2. Assert commit list shows commits
      3. Click a commit — assert diff viewer shows changes
    Expected Result: History panel works with graph
    Evidence: .sisyphus/evidence/task-25-history.png
  ```

  **Commit**: YES
  - Message: `feat(history): implement history panel with commit graph`

---

### Wave 5: Polish + Integration

- [x] 26. Repo Switcher + Sidebar

  **What to do**:
  - Create `src/components/repo/RepoSwitcher.tsx`: dropdown to switch between recently opened repos
  - Create `src/components/repo/RepoSidebar.tsx`: sidebar panel showing recent repos, open folder button
  - Store recent repos in Tauri's app data directory
  - Wire sidebar toggle to header button

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`tailwindcss-development`]

  **Blocked By**: Tasks 8, 14

  **References**:
  - `resources/views/livewire/repo-switcher.blade.php` — Repo switcher dropdown
  - `resources/views/livewire/repo-sidebar.blade.php` — Sidebar template
  - `app/Livewire/RepoSwitcher.php` — Repo switching logic

  **Acceptance Criteria**:
  - [ ] Repo switcher shows recent repos
  - [ ] Sidebar toggles with button and ⌘B

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Repo switching
    Tool: Playwright
    Steps:
      1. Open repo switcher dropdown
      2. Assert recent repos listed
    Expected Result: Repo switching works
    Evidence: .sisyphus/evidence/task-26-repo-switcher.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement repo switcher and sidebar`

---

- [x] 27. Settings Modal + Auto-Fetch

  **What to do**:
  - Create `src/components/settings/SettingsModal.tsx`: settings form
  - Implement auto-fetch timer: configurable interval
  - Store settings in Tauri app data directory (JSON file)
  - Create `src/components/AutoFetchIndicator.tsx`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`tailwindcss-development`]

  **Blocked By**: Tasks 13, 14

  **References**:
  - `resources/views/livewire/settings-modal.blade.php` — Settings modal template
  - `app/Livewire/AutoFetchIndicator.php` — Auto-fetch indicator

  **Acceptance Criteria**:
  - [ ] Settings modal opens and saves preferences
  - [ ] Auto-fetch runs at configured interval

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Settings persistence
    Tool: Playwright
    Steps:
      1. Open settings, change auto-fetch interval
      2. Close and reopen — assert value persisted
    Expected Result: Settings saved and restored
    Evidence: .sisyphus/evidence/task-27-settings.png
  ```

  **Commit**: YES
  - Message: `feat(settings): implement settings modal and auto-fetch`

---

- [x] 28. Command Palette + Keyboard Shortcuts

  **What to do**:
  - Create `src/components/CommandPalette.tsx`: fuzzy search command palette (⌘K)
  - Register all keyboard shortcuts: ⌘↵, ⌘⇧↵, ⌘⇧K, ⌘⇧U, ⌘B, Esc

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: []

  **Blocked By**: Task 14

  **References**:
  - `resources/views/livewire/command-palette.blade.php` — Command palette template
  - `app/Livewire/CommandPalette.php` — Command palette logic

  **Acceptance Criteria**:
  - [ ] Command palette opens with ⌘K
  - [ ] All keyboard shortcuts work

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Keyboard shortcuts
    Tool: Playwright
    Steps:
      1. Press ⌘K — assert command palette opens
      2. Press Escape — assert palette closes
      3. Press ⌘B — assert sidebar toggles
    Expected Result: All shortcuts work
    Evidence: .sisyphus/evidence/task-28-shortcuts.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement command palette and keyboard shortcuts`

---

- [x] 29. Error Banner + Notifications

  **What to do**:
  - Create `src/components/ErrorBanner.tsx`: dismissible error banner
  - Integrate macOS native notifications via Tauri notification plugin
  - Error boundary component for React crash recovery

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Blocked By**: Task 13

  **References**:
  - `resources/views/livewire/error-banner.blade.php` — Error banner template

  **Acceptance Criteria**:
  - [ ] Error banner displays on git operation failure
  - [ ] Banner is dismissible

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Error banner
    Tool: Playwright
    Steps:
      1. Trigger git error
      2. Assert error banner appears
      3. Click dismiss — assert banner disappears
    Expected Result: Error handling works
    Evidence: .sisyphus/evidence/task-29-errors.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement error banner and notifications`

---

- [x] 30. Tags + Reset/Revert Services

  **What to do**:
  - Implement Rust `git/tags.rs`: list_tags, create_tag, delete_tag
  - Implement Rust `git/reset.rs`: reset_soft, reset_mixed, reset_hard, revert_commit
  - Add IPC commands
  - TDD for both services

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: []

  **Blocked By**: Tasks 8, 13

  **References**:
  - `app/Services/Git/TagService.php` — Tag operations
  - `app/Services/Git/ResetService.php` — Reset/revert operations

  **Acceptance Criteria**:
  - [ ] `cargo test git::tags` passes
  - [ ] `cargo test git::reset` passes

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Tags and reset
    Tool: Bash
    Steps:
      1. cargo test test_create_tag -- --nocapture
      2. cargo test test_reset_soft -- --nocapture
      3. cargo test test_revert_commit -- --nocapture
    Expected Result: All operations work
    Evidence: .sisyphus/evidence/task-30-tags-reset.txt
  ```

  **Commit**: YES
  - Message: `feat(git): implement tag management and reset/revert`

---

- [x] 31. Shortcut Help Modal

  **What to do**:
  - Create `src/components/ShortcutHelp.tsx`: modal showing all keyboard shortcuts
  - Triggered by `?` key or command palette

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`tailwindcss-development`]

  **Blocked By**: Task 28

  **References**:
  - `resources/views/livewire/shortcut-help.blade.php` — Shortcut help template

  **Acceptance Criteria**:
  - [ ] Modal shows all shortcuts
  - [ ] Opens with `?` key

  **QA Scenarios (MANDATORY):**
  ```
  Scenario: Shortcut help
    Tool: Playwright
    Steps:
      1. Press ? — assert modal opens
      2. Assert all shortcuts listed
      3. Press Escape — assert closes
    Expected Result: Shortcut help works
    Evidence: .sisyphus/evidence/task-31-shortcuts-help.png
  ```

  **Commit**: YES
  - Message: `feat(ui): implement shortcut help modal`

---

## Final Verification Wave (MANDATORY — after ALL implementation tasks)

> 4 review agents run in PARALLEL. ALL must APPROVE. Rejection → fix → re-run.

- [x] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists. For each "Must NOT Have": search codebase for forbidden patterns. Check evidence files exist.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT`

- [x] F2. **Code Quality Review** — `unspecified-high`
  Run `cargo clippy -- -D warnings` + `tsc --noEmit` + `npm run test -- --run` + `cargo test`. Review for: `unwrap()`, `expect()`, `any` types, console.log, dead code.
  Output: `Build [PASS/FAIL] | Clippy [PASS/FAIL] | Tests [N pass/N fail] | VERDICT`

- [x] F3. **Full E2E QA** — `unspecified-high` (+ `playwright` skill)
  Execute EVERY QA scenario from EVERY task. Test cross-task integration. Test edge cases: empty repo, detached HEAD, merge conflict, large file, binary file.
  Output: `Scenarios [N/N pass] | Integration [N/N] | VERDICT`

- [x] F4. **Scope Fidelity Check** — `deep`
  For each task: verify actual implementation matches spec 1:1. Check "Must NOT do" compliance. Detect scope creep. Verify feature parity.
  Output: `Tasks [N/N compliant] | Creep [CLEAN/N] | Parity [N/N] | VERDICT`

---

## Commit Strategy

| After Task(s) | Message | Pre-commit |
|---------------|---------|------------|
| 1 | `feat(scaffold): initialize Tauri 2.x project` | `npm run build` |
| 2 | `feat(design): add Catppuccin Latte design system` | `npm run build` |
| 3 | `feat(types): define TypeScript interfaces and IPC schema` | `tsc --noEmit` |
| 4 | `feat(rust): scaffold Rust git service modules` | `cargo clippy && cargo build` |
| 5 | `feat(tests): set up test infrastructure` | `cargo test && npm run test` |
| 6 | `docs(architecture): add architecture documentation` | — |
| 7 | `docs(git2): audit git2-rs API coverage` | — |
| 8-12 | Individual commits per service | `cargo clippy && cargo test` |
| 13 | `feat(ipc): implement Tauri command handlers` | `cargo build && tsc --noEmit` |
| 14-18 | Individual commits per component | `npm run test` |
| 19-25 | Individual commits per feature | `cargo test && npm run test` |
| 26-31 | Individual commits per feature | `npm run test` |

---

## Success Criteria

### Verification Commands
```bash
cd ../gitty-tauri && npm run tauri build       # Expected: .dmg created
cd ../gitty-tauri/src-tauri && cargo test      # Expected: all tests pass
cd ../gitty-tauri/src-tauri && cargo clippy -- -D warnings  # Expected: 0 warnings
cd ../gitty-tauri && npx tsc --noEmit          # Expected: 0 errors
cd ../gitty-tauri && npm run test -- --run     # Expected: all tests pass
du -sh ../gitty-tauri/src-tauri/target/release/bundle/macos/*.app  # Expected: < 15MB
```

### Final Checklist
- [ ] All "Must Have" features present and working
- [ ] All "Must NOT Have" items absent from codebase
- [ ] All 31 implementation tasks completed
- [ ] All Rust tests pass (`cargo test`)
- [ ] All React tests pass (`npm run test`)
- [ ] `cargo clippy -- -D warnings` clean
- [ ] `tsc --noEmit` clean
- [ ] Bundle size < 15MB
- [ ] Memory usage < 200MB
- [ ] All 18 UI components render with Catppuccin Latte theme
- [ ] All keyboard shortcuts functional
- [ ] Feature parity with NativePHP gitty app verified
