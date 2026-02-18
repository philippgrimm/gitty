# Retro-Futurism "Warm Analog Computing" Theme for Gitty

## TL;DR

> **Quick Summary**: Replace gitty's Catppuccin Latte/Mocha theme entirely with a retro-futuristic "Warm Analog Computing" CRT monitor aesthetic — warm cream beige for light mode ("Daytime Office"), midnight blue with phosphor glow for dark mode ("Power On"). Includes neumorphic buttons, CRT scanlines, Space Mono typography, boot-up loading sequence, and 24-32px bezel-radius containers.
> 
> **Deliverables**:
> - Complete color palette swap across CSS variables + 14 Blade templates
> - Space Mono font integration for headings
> - Neumorphic button styling with physical depress interaction
> - Subtle CRT effects (glow, scanlines in dark mode)
> - Boot-up loading sequence animation
> - Custom retro Highlight.js syntax theme
> - Playwright visual regression tests (5 screens × 2 modes)
> 
> **Estimated Effort**: Large
> **Parallel Execution**: YES — 5 waves
> **Critical Path**: Task 1 (palette) → Task 3 (CSS vars) → Task 7 (template colors) → Task 14 (Playwright tests) → Final Verification

---

## Context

### Original Request
Create a comprehensive retro-futuristic design system for gitty inspired by a CRT monitor icon. The vibe is "high-end technology from 1982, refined for 2024" — tactile, rounded, glowing. Includes both light ("Daytime Office") and dark ("Power On") modes with CRT visual effects.

### Interview Summary
**Key Discussions**:
- **Theme strategy**: Full replacement (not additive). Catppuccin is gone entirely.
- **CRT intensity**: Start subtle & tasteful. Architecture must support escalation later.
- **Font**: Space Mono for headings (most authentic). Keep Instrument Sans body + JetBrains Mono code.
- **Buttons**: Neumorphic style — raised dual-shadow, pressed inverts to inset.
- **Colors**: Blue phosphor family. Deep cobalt surfaces, bright cyan accents. Warm cream (#F2EFE9) light mode.
- **Semantic colors**: Retro-shifted — keep green/red/yellow/orange meaning, use retro shades with glow.
- **Border radius**: Full bezel (24-32px) on main containers. Smaller on sub-elements.
- **Loading**: Full boot-up sequence (multi-line typing animation).
- **Tests**: Playwright visual screenshot tests.
- **Color swap approach**: Direct hex replacement (not migrating to CSS variables).

**Research Findings**:
- 14 active Blade templates with color references (excluding welcome.blade.php)
- CSS architecture: @theme (Flux tokens) + :root (light) + .dark (dark) — all in app.css
- Theme switching works via Alpine.js + localStorage + .dark class — keep this mechanism
- Flux UI reads ONLY --color-accent, --color-accent-content, --color-accent-foreground from @theme
- Hardcoded `#084CCF` appears in 8+ locations outside CSS variables (rgba variants too)
- Badge colors use `{{ $badgeColor }}15` hex concatenation pattern — new colors MUST be hex format
- Space Mono: Google Fonts / Bunny Fonts, 400/700 weights
- All CRT effects achievable with CSS-only (pseudo-elements, gradients, shadows)
- Neumorphism: dual box-shadow, limit to larger elements for performance

### Metis Review
**Identified Gaps** (addressed):
- **Hardcoded accent colors**: 8+ locations with `rgba(8,76,207,...)` need manual find-replace → Added dedicated task
- **Opacity concatenation pattern**: `{{ $badgeColor }}15` requires hex-only format → Enforced in guardrails
- **Flux danger buttons**: Cannot be customized, will stay Flux's built-in red → Accepted, documented
- **Match statement colors**: 4 hardcoded hex values in file status logic → Included in template update tasks
- **WCAG contrast**: Retro neon palettes risk failing AA → Added contrast validation as first task
- **prefers-reduced-motion**: All new animations must respect this → Added as requirement

---

## Work Objectives

### Core Objective
Transform gitty's visual identity from Catppuccin Latte/Mocha to a retro-futuristic "Warm Analog Computing" CRT monitor aesthetic, maintaining full light/dark mode support, semantic color meaning, and Flux UI compatibility.

### Concrete Deliverables
- Updated `resources/css/app.css` with complete retro color palette (light + dark)
- Updated @theme block with new Flux accent color
- Space Mono font loaded via Bunny Fonts CDN
- 14 Blade templates with swapped hardcoded hex colors
- Neumorphic button CSS classes
- CRT effect CSS (scanlines, glow, recessed inputs)
- Boot-up loading sequence component/animation
- Custom Highlight.js syntax theme (retro palette)
- 10 Playwright visual regression test screenshots
- Updated `<meta name="theme-color">` tag

### Definition of Done
- [ ] `grep -r "084CCF\|eff1f5\|e6e9ef\|dce0e8\|ccd0da\|4c4f69\|6c6f85\|8c8fa1" resources/views/` returns empty (no old Catppuccin colors remain)
- [ ] `grep -r "084CCF" resources/css/app.css` returns empty
- [ ] Both light and dark modes render correctly with new palette
- [ ] All Flux UI primary buttons use new accent color
- [ ] WCAG AA contrast ratio ≥ 4.5:1 for all text/background pairs
- [ ] All 10 Playwright visual tests pass
- [ ] `php artisan test --compact` passes (existing tests unbroken)
- [ ] Boot-up animation plays on app load
- [ ] prefers-reduced-motion disables all decorative animations

### Must Have
- Complete color palette replacement (no Catppuccin colors remain)
- Working light/dark mode toggle with new palette
- Space Mono headings with wider letter-spacing
- Neumorphic button interactions (raise/depress)
- Subtle CRT glow on active elements in dark mode
- Faint scanlines overlay in dark mode
- Boot-up loading sequence
- Retro syntax highlighting theme
- Playwright visual tests
- WCAG AA contrast compliance
- prefers-reduced-motion support

### Must NOT Have (Guardrails)
- ❌ Chromatic aberration effect (future enhancement)
- ❌ Full-screen vignette (future enhancement)
- ❌ Screen "turn on" transitions between panels (future)
- ❌ Animated scanline sweep (future)
- ❌ Haptic feedback (requires NativePHP native module)
- ❌ Multi-theme selector UI (this is a full replacement)
- ❌ CSS variable migration for hardcoded hex (out of scope)
- ❌ Custom Flux component overrides (use Flux's API only)
- ❌ Changes to component structure or logic (color-only in templates)
- ❌ Neumorphism on elements smaller than 32px
- ❌ Animations longer than 2 seconds (except boot sequence)
- ❌ CPU-intensive CSS (backdrop-filter, complex SVG filters)
- ❌ rgb/hsl color format for badge colors (must stay hex for concatenation pattern)
- ❌ Changing Flux variant="danger" color (keep built-in red for safety)
- ❌ Over-commenting or excessive JSDoc (existing code has minimal comments)

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES (Pest PHP framework)
- **Automated tests**: Playwright visual screenshot tests (new) + existing Pest tests (must pass)
- **Framework**: Playwright (via playwright skill) for visual QA, Pest for functional
- **Approach**: Tests-after (implement theme, then add Playwright visual tests)

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| CSS/Design tokens | Bash (grep) | Verify old colors absent, new colors present |
| Blade templates | Bash (grep) + Playwright | Verify color values + visual screenshot |
| Animations | Playwright | Record animation, screenshot key frames |
| Contrast ratios | Bash (node script) | Compute contrast programmatically |
| Flux components | Playwright | Navigate, screenshot buttons/inputs |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Foundation — font, palette definition, WCAG validation):
├── Task 1: Define complete retro color palette + WCAG contrast validation [deep]
├── Task 2: Add Space Mono font via Bunny Fonts CDN [quick]
└── Task 3: Update CSS design tokens in app.css (@theme + :root + .dark) [quick]

Wave 2 (Core Visual — templates, effects, typography, all parallel):
├── Task 4: Update header + layout templates (app-layout, layouts/app) [quick]
├── Task 5: Update staging panel + file tree colors [unspecified-high]
├── Task 6: Update diff viewer + blame view colors [unspecified-high]
├── Task 7: Update history panel + rebase panel colors [unspecified-high]
├── Task 8: Update search, command palette, shortcut help colors [unspecified-high]
├── Task 9: Update commit panel, error banner, sync panel, settings, branch manager, repo switcher, repo sidebar, auto-fetch colors [unspecified-high]
├── Task 10: Create CRT effect CSS classes (scanlines, glow, recessed inputs) [visual-engineering]
└── Task 11: Implement neumorphic button + container styling [visual-engineering]

Wave 3 (Animations + Syntax — depends on Wave 2 colors):
├── Task 12: Update existing animations + create new retro animations [visual-engineering]
├── Task 13: Create boot-up loading sequence [visual-engineering]
└── Task 14: Create retro Highlight.js syntax theme [unspecified-high]

Wave 4 (Typography + Integration — depends on Wave 1-3):
├── Task 15: Apply typography hierarchy (Space Mono headers, tracking, border-radius bezel) [visual-engineering]
└── Task 16: Apply CRT effects to components (glow, scanlines, recessed inputs) [visual-engineering]

Wave 5 (Testing — depends on ALL above):
├── Task 17: Create Playwright visual regression tests [unspecified-high]
└── Task 18: Run full verification (grep, contrast, existing tests) [deep]

Wave FINAL (Independent review, 4 parallel):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
└── Task F4: Scope fidelity check (deep)

Critical Path: Task 1 → Task 3 → Tasks 4-9 → Task 15 → Task 17 → F1-F4
Parallel Speedup: ~65% faster than sequential
Max Concurrent: 8 (Wave 2)
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | 3, 4-9 | 1 |
| 2 | — | 15 | 1 |
| 3 | 1 | 4-9, 10, 11 | 1 |
| 4 | 3 | 15, 16, 17 | 2 |
| 5 | 3 | 15, 16, 17 | 2 |
| 6 | 3 | 15, 16, 17 | 2 |
| 7 | 3 | 15, 16, 17 | 2 |
| 8 | 3 | 15, 16, 17 | 2 |
| 9 | 3 | 15, 16, 17 | 2 |
| 10 | 3 | 16 | 2 |
| 11 | 3 | 16 | 2 |
| 12 | 3 | 16 | 3 |
| 13 | 3 | 17 | 3 |
| 14 | 3 | 17 | 3 |
| 15 | 2, 4-9 | 17 | 4 |
| 16 | 10, 11, 12, 4-9 | 17 | 4 |
| 17 | 15, 16, 13, 14 | F1-F4 | 5 |
| 18 | 15, 16, 13, 14 | F1-F4 | 5 |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 1 | **3** | T1 → `deep`, T2 → `quick`, T3 → `quick` |
| 2 | **8** | T4 → `quick`, T5-T9 → `unspecified-high`, T10-T11 → `visual-engineering` |
| 3 | **3** | T12-T13 → `visual-engineering`, T14 → `unspecified-high` |
| 4 | **2** | T15-T16 → `visual-engineering` |
| 5 | **2** | T17 → `unspecified-high`, T18 → `deep` |
| FINAL | **4** | F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep` |

---

## TODOs

- [x] 1. Define Complete Retro Color Palette + WCAG Contrast Validation

  **What to do**:
  - Create a complete color mapping document in `.sisyphus/notepads/retro-palette.md` with:
    - Light mode: All surface, text, border, accent, and semantic color hex values
    - Dark mode: All surface, text, border, accent, and semantic color hex values
    - Catppuccin → Retro conversion table (old hex → new hex for every color)
  - Light mode base colors: Background #F2EFE9 (warm cream), surfaces lighter cream (#F7F5F0, #FFFFFF), text dark grey-blue (~#2C3040), accent deep cobalt (#18206F)
  - Dark mode base colors: Background #0A0E17 (midnight), surfaces deep cobalt (#18206F, #1A2850), text phosphor pale blue (#D0E0FF), accent bright cyan (#00C3FF)
  - Semantic colors retro-shifted: Green (#2D8A20 light / #50FF50 dark), Red (#C41030 / #FF4060), Yellow (#B87A10 / #FFB020), Orange (#D05A00 / #FF8040)
  - Border colors: Warm brown-grey for light (#C8C3B8, #D8D3C8), deep blue-grey for dark (#2A3060, #3A4070)
  - Validate EVERY text/background pair for WCAG AA (≥4.5:1 contrast ratio) using a node script or online calculator
  - Document which pairs pass/fail and adjust colors until ALL pass
  - Define shadow values for neumorphic effects (light side + dark side for both modes)

  **Must NOT do**:
  - Use rgb/hsl format — all colors MUST be hex (badge concatenation pattern requires it)
  - Choose colors purely for aesthetics without contrast validation
  - Skip dark mode palette definition

  **Recommended Agent Profile**:
  - **Category**: `deep`
    - Reason: Requires careful color theory decisions, systematic WCAG validation, and comprehensive documentation
  - **Skills**: []
    - No specific skills needed — this is color palette design and computation

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: Tasks 3, 4-9 (all template tasks need the palette)
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:22-69` — Current :root light mode variables (structure to replicate)
  - `resources/css/app.css:72-109` — Current .dark mode variables (structure to replicate)
  - `resources/css/app.css:10-19` — @theme block (Flux accent tokens)

  **API/Type References**:
  - AGENTS.md "Color System: Catppuccin Latte" section — Complete list of all color tokens and their semantic meanings

  **External References**:
  - WCAG 2.1 AA contrast ratio requirements: minimum 4.5:1 for normal text, 3:1 for large text
  - Catppuccin Latte palette: https://catppuccin.com/palette/ (reference for what we're replacing)

  **WHY Each Reference Matters**:
  - `app.css:22-69` shows the exact variable names and structure that must be preserved (only values change)
  - AGENTS.md documents the semantic meaning of each color (e.g., --color-green = "added/staged") which must be preserved
  - WCAG requirements are critical because retro neon palettes commonly fail contrast checks

  **Acceptance Criteria**:
  - [ ] `.sisyphus/notepads/retro-palette.md` exists with complete light + dark palette
  - [ ] Every text/background pair documented with contrast ratio
  - [ ] All contrast ratios ≥ 4.5:1 (AA compliant)
  - [ ] Conversion table maps every old Catppuccin hex to new retro hex

  **QA Scenarios**:

  ```
  Scenario: Palette document is complete and valid
    Tool: Bash (grep + read)
    Preconditions: Task has been completed
    Steps:
      1. Read .sisyphus/notepads/retro-palette.md
      2. Verify it contains sections: "Light Mode", "Dark Mode", "Conversion Table", "Contrast Ratios"
      3. Grep for all current Catppuccin hex values (#eff1f5, #e6e9ef, #dce0e8, #ccd0da, #4c4f69, #6c6f85, #8c8fa1, #084CCF) and verify each has a mapping
      4. Verify contrast ratio column exists and all values are ≥ 4.5
    Expected Result: Complete palette with all mappings and passing contrast ratios
    Failure Indicators: Missing color mappings, contrast ratios below 4.5:1, missing dark mode section
    Evidence: .sisyphus/evidence/task-1-palette-validation.md
  ```

  **Commit**: NO (documentation only, no code changes)

- [x] 2. Add Space Mono Font via Bunny Fonts CDN

  **What to do**:
  - Update `resources/views/layouts/app.blade.php` to add Space Mono to the Bunny Fonts link
  - Change the existing link from `instrument-sans:400,500,600|jetbrains-mono:400,500,600` to include `space-mono:400,700`
  - Update `resources/css/app.css` @theme block to add a `--font-display` token for Space Mono:
    ```css
    --font-display: 'Space Mono', 'JetBrains Mono', ui-monospace, monospace;
    ```
  - Do NOT change any template typography yet (that's Task 15)

  **Must NOT do**:
  - Change any existing font-family references in templates
  - Remove Instrument Sans or JetBrains Mono
  - Use Google Fonts CDN (project uses Bunny Fonts for privacy)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple 2-file change (font link + CSS token)
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: Task 15 (typography application)
  - **Blocked By**: None

  **References**:

  **Pattern References**:
  - `resources/views/layouts/app.blade.php:8-9` — Current Bunny Fonts link (add space-mono to this URL)
  - `resources/css/app.css:11-13` — Current @theme font definitions (add --font-display here)

  **External References**:
  - Bunny Fonts Space Mono: https://fonts.bunny.net/family/space-mono
  - Space Mono weights needed: 400 (regular), 700 (bold)

  **WHY Each Reference Matters**:
  - `app.blade.php:8-9` shows the exact Bunny Fonts URL format to extend (not replace)
  - `app.css:11-13` shows how to register a new font token in Tailwind v4's @theme block

  **Acceptance Criteria**:
  - [ ] `grep "space-mono" resources/views/layouts/app.blade.php` returns 1 match
  - [ ] `grep "font-display" resources/css/app.css` returns 1 match in @theme block
  - [ ] Page loads without font-loading errors (check browser console)

  **QA Scenarios**:

  ```
  Scenario: Space Mono font loads successfully
    Tool: Playwright (playwright skill)
    Preconditions: App is running via php artisan serve --port=8321
    Steps:
      1. Navigate to http://localhost:8321
      2. Open browser console, check for font loading errors
      3. Execute: document.fonts.check('400 16px "Space Mono"') — should return true
      4. Execute: document.fonts.check('700 16px "Space Mono"') — should return true
      5. Screenshot the page
    Expected Result: Both Space Mono weights load successfully, no console errors
    Failure Indicators: Font loading errors in console, document.fonts.check returns false
    Evidence: .sisyphus/evidence/task-2-font-loading.png
  ```

  **Commit**: YES (groups with Task 3)
  - Message: `design(tokens): add Space Mono font and retro-futurism CSS palette`
  - Files: `resources/views/layouts/app.blade.php`, `resources/css/app.css`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 3. Update CSS Design Tokens in app.css (@theme + :root + .dark)

  **What to do**:
  - Using the palette from Task 1 (`.sisyphus/notepads/retro-palette.md`), update ALL color values in `resources/css/app.css`:
  - **@theme block** (lines 15-18): Update `--color-accent`, `--color-accent-content`, `--color-accent-foreground` to new retro accent colors
  - **:root block** (lines 22-69): Replace ALL Catppuccin Latte values with retro light mode values:
    - Surface colors: --surface-0 through --surface-3
    - Border colors: --border-default, --border-subtle, --border-strong
    - Text colors: --text-primary, --text-secondary, --text-tertiary
    - Accent colors: --accent, --accent-muted, --accent-text
    - Semantic colors: --color-green, --color-red, --color-yellow, --color-peach, --color-blue, --color-mauve, --color-teal, --color-sky, --color-lavender
    - Shadow values: Update --shadow-sm, --shadow-md for warm-toned shadows
    - Border radius: Update --radius-sm (6px), --radius-md (12px), --radius-lg (24px)
  - **.dark block** (lines 72-109): Replace ALL Catppuccin Mocha values with retro dark mode values
    - Add --shadow-glow with phosphor blue glow value
  - Update `<meta name="theme-color" content="#eff1f5">` in `resources/views/layouts/app.blade.php:6` to `#F2EFE9`
  - **Diff viewer light mode** (lines 150-206): Update hardcoded rgba values for addition/deletion/context colors using retro semantic palette
  - **Diff viewer dark mode** (lines 316-350): Same for dark mode with retro dark semantic colors
  - **Highlight.js light mode** (lines 272-313): Do NOT update here — that's Task 14
  - **Highlight.js dark mode** (lines 352-389): Do NOT update here — that's Task 14

  **Must NOT do**:
  - Change CSS variable NAMES (only values)
  - Touch Highlight.js color mappings (lines 272-389) — that's Task 14
  - Add new CSS classes or animations yet — that's Tasks 10-13
  - Change the @theme font tokens (already updated in Task 2)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Straightforward value replacement in a single file, guided by palette document
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES (after Task 1 completes)
  - **Parallel Group**: Wave 1 (starts after Task 1 palette is ready)
  - **Blocks**: Tasks 4-11 (all template and effect tasks need new CSS variables)
  - **Blocked By**: Task 1 (needs the finalized palette)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:10-19` — @theme block (update accent colors)
  - `resources/css/app.css:22-69` — :root light mode (update ALL values)
  - `resources/css/app.css:72-109` — .dark mode (update ALL values)
  - `resources/css/app.css:139-206` — Diff viewer light mode styles (update rgba tints)
  - `resources/css/app.css:316-350` — Diff viewer dark mode styles (update rgba tints)
  - `resources/views/layouts/app.blade.php:6` — meta theme-color tag (update hex)

  **API/Type References**:
  - `.sisyphus/notepads/retro-palette.md` — The palette document from Task 1 (source of all new values)

  **WHY Each Reference Matters**:
  - Each `app.css` line range is a distinct block that needs updating with new values
  - The meta theme-color affects the browser/OS chrome color and must match the new background
  - The palette document is the single source of truth for all new color values

  **Acceptance Criteria**:
  - [ ] `grep -c "eff1f5\|e6e9ef\|dce0e8\|ccd0da\|4c4f69\|6c6f85\|8c8fa1" resources/css/app.css` returns 0
  - [ ] `grep -c "084CCF" resources/css/app.css` returns 0 (not counting comments)
  - [ ] `grep "color-accent" resources/css/app.css` shows new retro accent hex in @theme block
  - [ ] `grep "theme-color" resources/views/layouts/app.blade.php` shows `#F2EFE9`
  - [ ] All CSS variable names are unchanged (only values changed)

  **QA Scenarios**:

  ```
  Scenario: CSS variables contain new retro palette values
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -c "eff1f5\|e6e9ef\|dce0e8\|ccd0da" resources/css/app.css — expect 0
      2. grep -c "084CCF" resources/css/app.css — expect 0 (ignoring comments)
      3. grep "surface-0" resources/css/app.css — verify new hex values present
      4. grep "color-accent:" resources/css/app.css — verify new accent in @theme
      5. Verify :root and .dark blocks both have complete variable sets (count variables)
    Expected Result: Zero old Catppuccin colors, all new retro values present
    Failure Indicators: Any old Catppuccin hex found, missing variables in .dark block
    Evidence: .sisyphus/evidence/task-3-css-variables.txt

  Scenario: Diff viewer uses retro-shifted semantic colors
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep "diff-line-addition" resources/css/app.css — verify new green rgba
      2. grep "diff-line-deletion" resources/css/app.css — verify new red rgba
      3. Verify .dark overrides exist for both addition and deletion
    Expected Result: Diff viewer uses retro green/red with appropriate opacity tints
    Failure Indicators: Old Catppuccin green (#40a02b) or red (#d20f39) rgba values remain
    Evidence: .sisyphus/evidence/task-3-diff-colors.txt
  ```

  **Commit**: YES (groups with Task 2)
  - Message: `design(tokens): add Space Mono font and retro-futurism CSS palette`
  - Files: `resources/css/app.css`, `resources/views/layouts/app.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 4. Update Header + Layout Templates Colors

  **What to do**:
  - Update `resources/views/livewire/app-layout.blade.php`:
    - Replace `bg-[#084CCF]` on resize handle hover (line 177-178) with new accent color
    - Any other hardcoded Catppuccin hex values
  - Update `resources/views/layouts/app.blade.php`:
    - meta theme-color already done in Task 3, verify no other hardcoded colors
  - Use the conversion table from `.sisyphus/notepads/retro-palette.md` for exact replacements
  - Preserve all CSS variable references (`var(--surface-0)` etc.) — these auto-update from Task 3

  **Must NOT do**:
  - Change CSS variable references (they auto-update)
  - Change component structure, Alpine.js logic, or Livewire bindings
  - Change border-radius or typography (that's Tasks 15-16)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small file, few hardcoded colors to replace
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 5-11)
  - **Blocks**: Tasks 15, 16, 17
  - **Blocked By**: Task 3 (needs CSS variables updated first)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php:177-178` — Resize handle with hardcoded `#084CCF`
  - `.sisyphus/notepads/retro-palette.md` — Conversion table (old hex → new hex)

  **WHY Each Reference Matters**:
  - Line 177-178 has the specific hardcoded accent that needs replacing
  - The palette doc has the exact replacement value to use

  **Acceptance Criteria**:
  - [ ] `grep "084CCF" resources/views/livewire/app-layout.blade.php` returns 0
  - [ ] `grep "eff1f5\|e6e9ef\|dce0e8\|ccd0da\|4c4f69\|6c6f85" resources/views/livewire/app-layout.blade.php` returns 0

  **QA Scenarios**:

  ```
  Scenario: No old Catppuccin colors in layout templates
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -n "084CCF\|eff1f5\|e6e9ef\|dce0e8\|ccd0da\|4c4f69\|6c6f85\|8c8fa1\|9ca0b0" resources/views/livewire/app-layout.blade.php resources/views/layouts/app.blade.php
    Expected Result: No matches (empty output)
    Failure Indicators: Any old hex color found
    Evidence: .sisyphus/evidence/task-4-layout-colors.txt
  ```

  **Commit**: YES (groups with Tasks 5-9)
  - Message: `design(templates): swap all Blade template colors to retro palette`
  - Files: `resources/views/livewire/app-layout.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 5. Update Staging Panel + File Tree Colors

  **What to do**:
  - Update `resources/views/livewire/staging-panel.blade.php`:
    - Replace `text-[#9ca0b0]` and `hover:text-[#6c6f85]` (line 95) with retro equivalents
    - Replace `bg-[#084CCF]` in match statements (lines 191, 254) for 'blue' status with new accent
    - Any other hardcoded Catppuccin hex values
  - Update `resources/views/components/file-tree.blade.php`:
    - Replace `bg-[#084CCF]` in match statement (line 55) for 'blue' status
    - Replace `bg-[#9ca0b0]` default status color with retro warm gray
  - Use conversion table from `.sisyphus/notepads/retro-palette.md`
  - Preserve CSS variable references (var(--color-yellow), var(--color-green), etc.)

  **Must NOT do**:
  - Change CSS variable references (they auto-update from Task 3)
  - Change the match statement structure (only change hex values inside)
  - Change hover behavior or Livewire wire: directives

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Multiple files with match statement logic to carefully update
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 6-11)
  - **Blocks**: Tasks 15, 16, 17
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `resources/views/livewire/staging-panel.blade.php:95` — Hardcoded text colors for action icons
  - `resources/views/livewire/staging-panel.blade.php:191,254` — Match statements with blue (#084CCF) status
  - `resources/views/components/file-tree.blade.php:55` — Match statement with blue and default gray
  - `.sisyphus/notepads/retro-palette.md` — Conversion table

  **WHY Each Reference Matters**:
  - These match statements map git status to colors — the mapping logic must stay, only hex values change
  - Line 95 is an icon color that needs updating to retro warm gray

  **Acceptance Criteria**:
  - [ ] `grep "084CCF\|9ca0b0\|6c6f85" resources/views/livewire/staging-panel.blade.php resources/views/components/file-tree.blade.php` returns 0
  - [ ] Match statement still maps 'blue' to a color (verify new accent hex present)

  **QA Scenarios**:

  ```
  Scenario: File status colors use retro palette
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -n "084CCF\|9ca0b0\|6c6f85\|eff1f5" resources/views/livewire/staging-panel.blade.php resources/views/components/file-tree.blade.php
      2. grep "'blue'" resources/views/livewire/staging-panel.blade.php — verify new accent hex
      3. grep "default =>" resources/views/components/file-tree.blade.php — verify retro gray
    Expected Result: No old colors, new accent on 'blue' status, new warm gray on default
    Failure Indicators: Any old Catppuccin hex, missing match case
    Evidence: .sisyphus/evidence/task-5-staging-colors.txt
  ```

  **Commit**: YES (groups with Tasks 4, 6-9)
  - Message: `design(templates): swap all Blade template colors to retro palette`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 6. Update Diff Viewer + Blame View Colors

  **What to do**:
  - Update `resources/views/livewire/diff-viewer.blade.php`:
    - Replace ALL hardcoded Catppuccin hex values: `#fe640b`, `#40a02b`, `#d20f39`, `#df8e1d`, `#084CCF`, `#9ca0b0` (lines 31, 56-59, 167, 183, 201, 222-226, 391)
    - These are in: inline styles for badges, match statements for status colors, line selection borders
    - Preserve the `15` suffix opacity pattern (e.g., `style="background-color: #NEW_HEX15; color: #NEW_HEX"`)
    - Verify new hex colors work with the `15` suffix concatenation
  - Update `resources/views/livewire/blame-view.blade.php`:
    - Replace `text-[#084CCF]` (line 51) with new accent color

  **Must NOT do**:
  - Change the `{{ $badgeColor }}15` pattern — only change the hex values assigned to $badgeColor
  - Change diff viewer layout or line numbering logic
  - Touch highlight.js integration (that's Task 14)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Many color references, critical badge opacity pattern preservation
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4, 5, 7-11)
  - **Blocks**: Tasks 15, 16, 17
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `resources/views/livewire/diff-viewer.blade.php:31` — Untracked badge (inline style with #fe640b15 opacity)
  - `resources/views/livewire/diff-viewer.blade.php:56-59` — Match statement: isNew → green, isDeleted → red, isModified → yellow
  - `resources/views/livewire/diff-viewer.blade.php:167,183` — Added/deleted count badges with `#40a02b15` and `#d20f3915`
  - `resources/views/livewire/diff-viewer.blade.php:222-226` — Match statement for status badge colors (MODIFIED, ADDED, DELETED, RENAMED)
  - `resources/views/livewire/diff-viewer.blade.php:391` — Selection border with `#084CCF`
  - `resources/views/livewire/blame-view.blade.php:51` — Commit link accent color

  **WHY Each Reference Matters**:
  - The badge opacity pattern (`#HEX15`) is critical — new colors must be valid 6-digit hex
  - Match statements define the color semantics for file status — only values change
  - Selection border is the active state indicator

  **Acceptance Criteria**:
  - [ ] `grep "084CCF\|40a02b\|d20f39\|df8e1d\|fe640b\|9ca0b0" resources/views/livewire/diff-viewer.blade.php resources/views/livewire/blame-view.blade.php` returns 0
  - [ ] Badge opacity pattern still uses `{{ $badgeColor }}15` format (structure preserved)

  **QA Scenarios**:

  ```
  Scenario: No old colors in diff viewer
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -n "084CCF\|40a02b\|d20f39\|df8e1d\|fe640b\|9ca0b0\|1e66f5" resources/views/livewire/diff-viewer.blade.php resources/views/livewire/blame-view.blade.php
    Expected Result: No matches
    Failure Indicators: Any old Catppuccin hex found
    Evidence: .sisyphus/evidence/task-6-diff-colors.txt

  Scenario: Badge opacity pattern preserved
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep "badgeColor.*15" resources/views/livewire/diff-viewer.blade.php — verify pattern intact
      2. grep "background-color:.*15;" resources/views/livewire/diff-viewer.blade.php — verify inline styles use hex15 format
    Expected Result: Opacity pattern preserved with new hex values
    Failure Indicators: Pattern broken, rgb/hsl used instead of hex
    Evidence: .sisyphus/evidence/task-6-badge-opacity.txt
  ```

  **Commit**: YES (groups with Tasks 4, 5, 7-9)
  - Message: `design(templates): swap all Blade template colors to retro palette`
  - Files: `resources/views/livewire/diff-viewer.blade.php`, `resources/views/livewire/blame-view.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 7. Update History Panel + Rebase Panel Colors

  **What to do**:
  - Update `resources/views/livewire/history-panel.blade.php`:
    - Replace graph color array (lines 68-75): `#1e66f5`, `#8839ef`, `#40a02b`, `#fe640b`, `#179299`, `#d20f39`, `#04a5e5`, `#df8e1d` with retro equivalents
    - Replace commit SHA accent color `text-[#084CCF]` (line 164)
    - Replace tag badge colors in PHP block (lines 192-202): `#fe640b`, `#8839ef`, `#179299`, `#40a02b` + white text
    - Replace `border-[#084CCF]` and `bg-[rgba(8,76,207,0.05)]` in reset mode selectors (lines 299, 308)
  - Update `resources/views/livewire/rebase-panel.blade.php`:
    - Replace `border-[#084CCF]` and `bg-[rgba(8,76,207,0.05)]` drag-over state (line 62)
    - Replace `text-[#084CCF]` commit SHA (line 71)

  **Must NOT do**:
  - Change the graph color array length (keep 8 colors)
  - Change component logic for reset modes or drag-and-drop
  - Modify tag badge structure (only change color values)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Multiple color types (graph colors, badge colors, accent highlights, rgba values)
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4-6, 8-11)
  - **Blocks**: Tasks 15, 16, 17
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `resources/views/livewire/history-panel.blade.php:68-75` — Graph color array (8 colors for commit graph lanes)
  - `resources/views/livewire/history-panel.blade.php:164` — Commit SHA with accent color
  - `resources/views/livewire/history-panel.blade.php:192-202` — Tag badge colors (PHP match-like block)
  - `resources/views/livewire/history-panel.blade.php:299,308` — Reset mode selection borders with rgba accent
  - `resources/views/livewire/rebase-panel.blade.php:62` — Drag-over state with accent border/bg
  - `resources/views/livewire/rebase-panel.blade.php:71` — Commit SHA accent
  - `.sisyphus/notepads/retro-palette.md` — Conversion table

  **WHY Each Reference Matters**:
  - Graph colors must be visually distinct from each other AND work on both light/dark backgrounds
  - The rgba(8,76,207,0.05) pattern needs conversion to new accent with same opacity
  - Tag badge colors must map to retro semantic equivalents

  **Acceptance Criteria**:
  - [ ] `grep "084CCF\|1e66f5\|8839ef\|40a02b\|fe640b\|179299\|d20f39\|04a5e5\|df8e1d\|rgba(8,76,207" resources/views/livewire/history-panel.blade.php resources/views/livewire/rebase-panel.blade.php` returns 0
  - [ ] Graph color array still has exactly 8 entries

  **QA Scenarios**:

  ```
  Scenario: No old colors in history and rebase panels
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -cn "084CCF\|1e66f5\|8839ef\|40a02b\|fe640b\|179299\|d20f39\|04a5e5\|df8e1d" resources/views/livewire/history-panel.blade.php resources/views/livewire/rebase-panel.blade.php
      2. grep "rgba(8,76,207" resources/views/livewire/history-panel.blade.php resources/views/livewire/rebase-panel.blade.php
    Expected Result: 0 matches for all old colors
    Failure Indicators: Any old color hex or rgba found
    Evidence: .sisyphus/evidence/task-7-history-colors.txt
  ```

  **Commit**: YES (groups with Tasks 4-6, 8-9)
  - Message: `design(templates): swap all Blade template colors to retro palette`
  - Files: `resources/views/livewire/history-panel.blade.php`, `resources/views/livewire/rebase-panel.blade.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 8. Update Search, Command Palette, Shortcut Help Colors

  **What to do**:
  - Update `resources/views/livewire/search-panel.blade.php` (22 color refs):
    - Replace ALL hardcoded hex: `#ccd0da` (border), `#dce0e8` (dividers), `#8c8fa1` (icons, placeholders), `#4c4f69` (text), `#6c6f85` (secondary text), `#084CCF` (active tab bg), `#eff1f5` (hover bg)
    - Lines 24, 65, 67, 72, 80, 83, 89, 95, 103, 113, 116-117, 125, 128, 131-132, 134, 137, 147, 151
  - Update `resources/views/livewire/command-palette.blade.php`:
    - Replace `#8c8fa1` placeholder color (lines 82, 104)
    - Replace `#084CCF` focus ring color (line 82)
  - Update `resources/views/livewire/shortcut-help.blade.php`:
    - Replace any hardcoded Catppuccin hex values

  **Must NOT do**:
  - Change Alpine.js logic (x-show, x-data, x-on)
  - Change keyboard shortcut bindings
  - Change search/filter logic

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: search-panel.blade.php has 22 color references across many lines
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4-7, 9-11)
  - **Blocks**: Tasks 15, 16, 17
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `resources/views/livewire/search-panel.blade.php:24` — Modal border (`#ccd0da`)
  - `resources/views/livewire/search-panel.blade.php:83,89,95` — Scope tab active state (`bg-[#084CCF] text-white` vs inactive `text-[#6c6f85] hover:bg-[#eff1f5]`)
  - `resources/views/livewire/search-panel.blade.php:113` — Result hover state (`hover:bg-[#eff1f5]`)
  - `resources/views/livewire/command-palette.blade.php:82` — Input focus ring (`focus:ring-[#084CCF] focus:border-[#084CCF]`)

  **WHY Each Reference Matters**:
  - Search panel is heavily hardcoded — most colors are NOT CSS variables
  - The active tab pattern (`bg-[#084CCF] text-white` vs inactive) needs careful replacement

  **Acceptance Criteria**:
  - [ ] `grep "084CCF\|eff1f5\|ccd0da\|dce0e8\|8c8fa1\|4c4f69\|6c6f85" resources/views/livewire/search-panel.blade.php resources/views/livewire/command-palette.blade.php resources/views/livewire/shortcut-help.blade.php` returns 0

  **QA Scenarios**:

  ```
  Scenario: No old colors in search and palette components
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -cn "084CCF\|eff1f5\|ccd0da\|dce0e8\|8c8fa1\|4c4f69\|6c6f85\|9ca0b0" resources/views/livewire/search-panel.blade.php resources/views/livewire/command-palette.blade.php resources/views/livewire/shortcut-help.blade.php
    Expected Result: 0 matches
    Failure Indicators: Any old hex found
    Evidence: .sisyphus/evidence/task-8-search-colors.txt
  ```

  **Commit**: YES (groups with Tasks 4-7, 9)
  - Message: `design(templates): swap all Blade template colors to retro palette`
  - Files: search-panel, command-palette, shortcut-help blade files
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 9. Update Remaining Component Templates Colors

  **What to do**:
  - Update `resources/views/livewire/commit-panel.blade.php`:
    - Replace `#d20f39` border (line 10), `#9ca0b0` placeholder (line 64), `#084CCF` focus ring (line 64), `#8c8fa1` hint text (line 70)
  - Update `resources/views/livewire/error-banner.blade.php`:
    - Replace `#d20f39` (error), `#fe640b` (warning), `#084CCF` (info), `#40a02b` (success) in border-color bindings (lines 21-24, 50, 64)
  - Update `resources/views/livewire/sync-panel.blade.php`:
    - Replace `#eff1f5` ring color (lines 30, 52)
  - Update `resources/views/livewire/branch-manager.blade.php`:
    - Replace `#fe640b` border (line 6)
  - Update `resources/views/livewire/repo-switcher.blade.php`:
    - Replace `#d20f39` border (line 3)
  - Update `resources/views/livewire/repo-sidebar.blade.php`:
    - Replace `#ccd0da` divider (line 57)
  - Update `resources/views/livewire/settings-modal.blade.php`:
    - Verify no hardcoded colors (uses CSS variables)
  - Update `resources/views/livewire/auto-fetch-indicator.blade.php`:
    - Replace `bg-[#6c6f85]` (line 20)

  **Must NOT do**:
  - Change Livewire component logic
  - Change error banner type/variant logic (only colors)
  - Touch the settings modal theme selector behavior

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Many files but each has few color changes — needs thoroughness
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4-8, 10-11)
  - **Blocks**: Tasks 15, 16, 17
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `resources/views/livewire/commit-panel.blade.php:10,64,70` — Error border, input styling, hint text
  - `resources/views/livewire/error-banner.blade.php:21-24,50,64` — Semantic color borders (error/warning/info/success)
  - `resources/views/livewire/sync-panel.blade.php:30,52` — Status dot ring colors
  - `resources/views/livewire/branch-manager.blade.php:6` — Detached HEAD warning border
  - `resources/views/livewire/repo-switcher.blade.php:3` — Error banner border
  - `resources/views/livewire/repo-sidebar.blade.php:57` — Divider color
  - `resources/views/livewire/auto-fetch-indicator.blade.php:20` — Indicator dot color

  **WHY Each Reference Matters**:
  - error-banner.blade.php maps semantic types to specific colors — critical for UX meaning
  - sync-panel rings must match the new background (currently #eff1f5 to blend with surface-0)

  **Acceptance Criteria**:
  - [ ] `grep -r "084CCF\|eff1f5\|d20f39\|fe640b\|40a02b\|9ca0b0\|6c6f85\|8c8fa1\|ccd0da" resources/views/livewire/commit-panel.blade.php resources/views/livewire/error-banner.blade.php resources/views/livewire/sync-panel.blade.php resources/views/livewire/branch-manager.blade.php resources/views/livewire/repo-switcher.blade.php resources/views/livewire/repo-sidebar.blade.php resources/views/livewire/auto-fetch-indicator.blade.php` returns 0

  **QA Scenarios**:

  ```
  Scenario: No old colors in remaining component templates
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. Run the acceptance criteria grep command above
      2. Verify error-banner still has 4 distinct semantic colors (error, warning, info, success)
    Expected Result: Zero old hex matches, 4 distinct retro semantic colors in error-banner
    Failure Indicators: Any old color found, error-banner missing a semantic variant
    Evidence: .sisyphus/evidence/task-9-remaining-colors.txt
  ```

  **Commit**: YES (groups with Tasks 4-8)
  - Message: `design(templates): swap all Blade template colors to retro palette`
  - Files: commit-panel, error-banner, sync-panel, branch-manager, repo-switcher, repo-sidebar, auto-fetch-indicator blade files
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 10. Create CRT Effect CSS Classes (Scanlines, Glow, Recessed Inputs)

  **What to do**:
  - Add new CSS classes to `resources/css/app.css` (append after the animation section, before Highlight.js section):
  - **Scanlines overlay** (dark mode only):
    ```css
    .dark .crt-scanlines::after {
      content: '';
      position: absolute;
      inset: 0;
      background: repeating-linear-gradient(0deg, rgba(255,255,255,0.03) 0px, transparent 1px, transparent 2px, rgba(255,255,255,0.03) 3px);
      pointer-events: none;
      z-index: 1;
    }
    ```
  - **Phosphor glow** (dark mode, for active/focused elements):
    ```css
    .dark .phosphor-glow {
      box-shadow: 0 0 10px rgba(0, 195, 255, 0.3), 0 0 20px rgba(0, 195, 255, 0.15);
    }
    .dark .phosphor-text-glow {
      text-shadow: 0 0 8px rgba(0, 195, 255, 0.6), 0 0 15px rgba(0, 195, 255, 0.3);
    }
    ```
    (Use actual new accent cyan hex from palette doc)
  - **Recessed input** (inner shadow for screen glass look):
    ```css
    .input-recessed {
      box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.08), inset -1px -1px 2px rgba(255, 255, 255, 0.5);
    }
    .dark .input-recessed {
      box-shadow: inset 2px 2px 4px rgba(0, 0, 0, 0.3), inset -1px -1px 2px rgba(255, 255, 255, 0.05);
    }
    ```
  - **prefers-reduced-motion**: Wrap all glow animations in `@media (prefers-reduced-motion: no-preference) { ... }`
  - Do NOT apply these classes to templates yet (that's Task 16)

  **Must NOT do**:
  - Apply classes to templates (Task 16 does that)
  - Add chromatic aberration or vignette (future enhancement)
  - Use backdrop-filter or complex SVG filters
  - Add animations (that's Task 12)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: CSS visual effects requiring aesthetic judgment
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4-9, 11)
  - **Blocks**: Task 16 (CRT effect application)
  - **Blocked By**: Task 3 (needs accent color values)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:217-270` — Existing animation/micro-interaction section (place new classes after this)
  - `.sisyphus/notepads/retro-palette.md` — Accent colors for glow rgba values

  **External References**:
  - CRT scanline technique: repeating-linear-gradient with 2-3px spacing, very low opacity (0.03-0.05)
  - Neumorphic input reference: dual inset shadows (dark corner + light corner)

  **WHY Each Reference Matters**:
  - Placement after existing animations keeps the file organized
  - Palette doc provides exact accent hex for glow rgba calculations

  **Acceptance Criteria**:
  - [ ] `grep "crt-scanlines" resources/css/app.css` returns match
  - [ ] `grep "phosphor-glow" resources/css/app.css` returns match
  - [ ] `grep "input-recessed" resources/css/app.css` returns match
  - [ ] `grep "prefers-reduced-motion" resources/css/app.css` returns match
  - [ ] All new classes have both light and dark mode variants where appropriate

  **QA Scenarios**:

  ```
  Scenario: CRT effect classes exist and are valid CSS
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -c "crt-scanlines\|phosphor-glow\|phosphor-text-glow\|input-recessed" resources/css/app.css
      2. grep "prefers-reduced-motion" resources/css/app.css — verify glow effects are wrapped
      3. Run npm run build — verify CSS compiles without errors
    Expected Result: All 4 class families found, reduced-motion media query present, build succeeds
    Failure Indicators: Missing classes, build failure, no reduced-motion support
    Evidence: .sisyphus/evidence/task-10-crt-effects.txt
  ```

  **Commit**: YES (groups with Task 11)
  - Message: `feat(design): add CRT effect classes and neumorphic button styling`
  - Files: `resources/css/app.css`
  - Pre-commit: `npm run build`

- [x] 11. Implement Neumorphic Button + Container Styling

  **What to do**:
  - Add neumorphic CSS classes to `resources/css/app.css` (append near CRT effects from Task 10):
  - **Neumorphic raised container** (for main content panels):
    ```css
    .neu-container {
      border-radius: 24px;
      box-shadow: 6px 6px 12px rgba(180, 170, 155, 0.4), -6px -6px 12px rgba(255, 255, 255, 0.7);
    }
    .dark .neu-container {
      box-shadow: 6px 6px 12px rgba(0, 0, 0, 0.5), -6px -6px 12px rgba(30, 40, 80, 0.3);
    }
    ```
    (Adjust shadow colors to match retro palette from palette doc)
  - **Neumorphic button** (raised, with physical depress interaction):
    ```css
    .btn-neu {
      border-radius: 999px; /* pill shape */
      box-shadow: 4px 4px 8px rgba(180, 170, 155, 0.4), -4px -4px 8px rgba(255, 255, 255, 0.6);
      transition: all 0.15s ease;
    }
    .btn-neu:active {
      box-shadow: inset 3px 3px 6px rgba(180, 170, 155, 0.4), inset -3px -3px 6px rgba(255, 255, 255, 0.5);
      transform: scale(0.98);
    }
    .dark .btn-neu { /* dark variants */ }
    .dark .btn-neu:active { /* dark active variants */ }
    ```
  - **prefers-reduced-motion**: Disable transform animation for button press
  - Do NOT apply these classes to templates yet (Task 16)

  **Must NOT do**:
  - Apply to templates (Task 16)
  - Add neumorphism to elements smaller than 32px
  - Use animations longer than 200ms for button interactions

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Neumorphic design requires fine-tuned shadow values
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Tasks 4-10)
  - **Blocks**: Task 16
  - **Blocked By**: Task 3

  **References**:

  **Pattern References**:
  - `resources/css/app.css:217-270` — Existing animation section (place near CRT effects)
  - `.sisyphus/notepads/retro-palette.md` — Shadow colors derived from palette

  **WHY Each Reference Matters**:
  - Shadow colors must be derived from the palette's surface colors for natural neumorphic look
  - File placement keeps styles organized

  **Acceptance Criteria**:
  - [ ] `grep "neu-container\|btn-neu" resources/css/app.css` returns matches
  - [ ] Both light and dark variants exist
  - [ ] `grep "prefers-reduced-motion" resources/css/app.css` covers button transform
  - [ ] `npm run build` succeeds

  **QA Scenarios**:

  ```
  Scenario: Neumorphic classes compile and have both modes
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep -A5 "neu-container" resources/css/app.css — verify light mode shadows
      2. grep -A5 "dark .neu-container" resources/css/app.css — verify dark mode shadows
      3. grep -A5 "btn-neu:active" resources/css/app.css — verify depress effect
      4. Run npm run build — verify compilation
    Expected Result: Both modes defined, depress uses inset shadows + scale, build succeeds
    Failure Indicators: Missing dark variants, no :active state, build failure
    Evidence: .sisyphus/evidence/task-11-neumorphic.txt
  ```

  **Commit**: YES (groups with Task 10)
  - Message: `feat(design): add CRT effect classes and neumorphic button styling`
  - Files: `resources/css/app.css`
  - Pre-commit: `npm run build`

- [x] 12. Update Existing Animations + Create New Retro Animations

  **What to do**:
  - **Update existing animations** in `resources/css/app.css`:
    - `commitFlash` (line 229-236): Change `rgba(8, 76, 207, 0.4)` → new accent rgba
    - Verify other animations (slideIn, syncPulse, fadeIn) are color-neutral (they are — no changes needed)
  - **Create new keyframe animations** (append to animation section):
    - `@keyframes glow-pulse` — Subtle pulsing glow for active elements in dark mode (2s cycle, ease-in-out)
    - `@keyframes cursor-blink` — Block cursor blink using step-end (1s cycle)
    - `@keyframes boot-line` — Text line appear (opacity 0→1, 200ms)
  - **Create utility classes**:
    - `.animate-glow-pulse` — applies glow-pulse animation
    - `.animate-cursor-blink` — applies cursor-blink
    - `.animate-boot-line` — applies boot-line with staggered delays
  - Wrap all decorative animations in `@media (prefers-reduced-motion: no-preference) { ... }`

  **Must NOT do**:
  - Create animations longer than 2 seconds (except boot sequence — that's Task 13)
  - Add screen turn-on or scanline sweep animations (future)
  - Use CPU-intensive CSS like backdrop-filter

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Animation timing and easing requires design sensibility
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 13, 14)
  - **Blocks**: Task 16
  - **Blocked By**: Task 3 (needs accent color for commitFlash update)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:229-236` — commitFlash animation (needs accent color update)
  - `resources/css/app.css:256-270` — Existing utility classes (naming pattern: `.animate-*`)
  - `.sisyphus/notepads/retro-palette.md` — New accent rgba for glow animations

  **WHY Each Reference Matters**:
  - commitFlash uses hardcoded Zed Blue rgba — needs new accent
  - Utility class naming convention (`.animate-*`) must be followed for consistency

  **Acceptance Criteria**:
  - [ ] `grep "rgba(8, 76, 207" resources/css/app.css` returns 0 (commitFlash updated)
  - [ ] `grep "glow-pulse\|cursor-blink\|boot-line" resources/css/app.css` returns matches
  - [ ] `grep "prefers-reduced-motion" resources/css/app.css` wraps decorative animations
  - [ ] `npm run build` succeeds

  **QA Scenarios**:

  ```
  Scenario: New animations defined and old accent removed
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep "rgba(8, 76, 207" resources/css/app.css — expect 0
      2. grep "@keyframes glow-pulse" resources/css/app.css — expect 1
      3. grep "@keyframes cursor-blink" resources/css/app.css — expect 1
      4. grep "prefers-reduced-motion" resources/css/app.css — expect ≥1
    Expected Result: Old accent removed, new animations present, reduced-motion supported
    Failure Indicators: Old accent rgba, missing keyframes
    Evidence: .sisyphus/evidence/task-12-animations.txt
  ```

  **Commit**: YES (groups with Task 13)
  - Message: `feat(design): add retro animations and boot-up loading sequence`
  - Files: `resources/css/app.css`
  - Pre-commit: `npm run build`

- [x] 13. Create Boot-Up Loading Sequence

  **What to do**:
  - Create a boot-up loading sequence that displays when the app launches (no repo selected state)
  - The sequence should show multi-line text appearing one at a time:
    ```
    > INITIALIZING SYSTEM...
    > LOADING MODULES...
    > SCANNING REPOSITORIES...
    > READY.
    ```
  - **Implementation approach**: Modify the "No Repository Selected" empty state in `resources/views/livewire/app-layout.blade.php` (lines 93-101):
    - Add Alpine.js x-data with a `booting` state that runs on mount
    - Show boot text lines with staggered delays (200ms between lines)
    - After boot sequence completes (~1.5s), show the normal "No Repository Selected" content
    - Use Space Mono font, retro accent color, blinking cursor at the end
  - Add CSS for boot sequence in `resources/css/app.css`:
    - `.boot-line` class with typewriter-like appearance
    - `.boot-cursor` — blinking block cursor (█) using cursor-blink animation from Task 12
  - Wrap in `@media (prefers-reduced-motion: no-preference)` — if reduced-motion, skip boot and show content immediately

  **Must NOT do**:
  - Change the boot sequence to play on EVERY page navigation (only on initial load / no-repo state)
  - Make the boot sequence blocking (content should be available after ~1.5s)
  - Use JavaScript setTimeout chains — use CSS animation delays instead
  - Make boot text longer than 4-5 lines

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Animation choreography and retro aesthetic
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 12, 14)
  - **Blocks**: Task 17 (Playwright needs to test this)
  - **Blocked By**: Task 3 (needs palette colors), Task 12 (needs cursor-blink animation)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php:93-101` — Current empty state (modify this block)
  - `resources/css/app.css:256-270` — Existing animation utility classes (follow pattern)

  **External References**:
  - Terminal boot sequence UX: Lines appear sequentially, cursor blinks at end
  - prefers-reduced-motion: Skip animation entirely, show final state immediately

  **WHY Each Reference Matters**:
  - Lines 93-101 are the exact code block to enhance with the boot sequence
  - Animation utilities show naming conventions to follow

  **Acceptance Criteria**:
  - [ ] Boot sequence text appears in no-repo empty state
  - [ ] Lines appear sequentially with ~200ms delays
  - [ ] Blinking cursor (█) shows after last line
  - [ ] After ~1.5s, normal "No Repository Selected" content is visible
  - [ ] `@media (prefers-reduced-motion: reduce)` skips animation

  **QA Scenarios**:

  ```
  Scenario: Boot sequence plays on app load
    Tool: Playwright (playwright skill)
    Preconditions: App running, no repo selected
    Steps:
      1. Navigate to http://localhost:8321
      2. Wait 500ms — screenshot (should show partial boot text)
      3. Wait 2000ms — screenshot (should show full boot text + normal content)
      4. Verify text "INITIALIZING" or "READY" appears on screen
    Expected Result: Boot text visible, cursor blinking, content appears after sequence
    Failure Indicators: No boot text, content appears immediately without sequence, cursor missing
    Evidence: .sisyphus/evidence/task-13-boot-sequence.png

  Scenario: Boot sequence respects reduced motion
    Tool: Playwright (playwright skill)
    Preconditions: App running, no repo selected
    Steps:
      1. Set emulateMedia({ reducedMotion: 'reduce' })
      2. Navigate to http://localhost:8321
      3. Screenshot immediately — should show final content state (no animation)
    Expected Result: Content shows immediately without boot animation
    Failure Indicators: Boot animation plays despite reduced-motion
    Evidence: .sisyphus/evidence/task-13-boot-reduced-motion.png
  ```

  **Commit**: YES (groups with Task 12)
  - Message: `feat(design): add retro animations and boot-up loading sequence`
  - Files: `resources/views/livewire/app-layout.blade.php`, `resources/css/app.css`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 14. Create Retro Highlight.js Syntax Theme

  **What to do**:
  - Update ALL Highlight.js color mappings in `resources/css/app.css`:
  - **Light mode** (lines 272-313) — Replace Catppuccin Latte syntax colors with retro equivalents:
    - `.hljs-keyword/.hljs-name/.hljs-tag` (currently #8839ef) → retro purple/mauve
    - `.hljs-string/.hljs-title/.hljs-type` (currently #40a02b) → retro green
    - `.hljs-comment/.hljs-quote` (currently #9ca0b0) → retro warm gray
    - `.hljs-meta` (currently #fe640b) → retro orange
    - `.hljs-number/.hljs-link` (currently #df8e1d) → retro amber
    - `.hljs-variable/.hljs-params` (currently #4c4f69) → retro primary text
    - `.hljs-function` (currently #1e66f5) → retro accent blue
    - `.hljs-regexp` (currently #179299) → retro teal
    - `.hljs-addition` (currently #40a02b) → retro green
  - **Dark mode** (lines 352-389) — Replace Catppuccin Mocha syntax colors with retro dark equivalents:
    - Use brighter, more luminous versions of each color (phosphor glow aesthetic)
    - Optionally add subtle text-shadow glow to keywords and strings in dark mode
  - Use palette document for exact color values

  **Must NOT do**:
  - Change the CSS selector structure (only change color values)
  - Add text-shadow glow to ALL tokens (too much visual noise — only keywords if at all)
  - Touch the JavaScript Highlight.js integration in app.js

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Systematic color replacement across 16+ selectors, needs both modes
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 3 (with Tasks 12, 13)
  - **Blocks**: Task 17
  - **Blocked By**: Task 3 (needs palette)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:272-313` — Light mode Highlight.js (8 color groups, ~10 selectors)
  - `resources/css/app.css:352-389` — Dark mode Highlight.js (same structure, Mocha colors)
  - `.sisyphus/notepads/retro-palette.md` — Retro semantic and accent colors

  **WHY Each Reference Matters**:
  - Lines 272-313 and 352-389 are the exact blocks to update — they must stay structurally identical
  - Palette doc provides the source colors to derive syntax shades from

  **Acceptance Criteria**:
  - [ ] `grep "8839ef\|40a02b\|9ca0b0\|fe640b\|df8e1d\|4c4f69\|1e66f5\|179299" resources/css/app.css` returns 0 (all old syntax colors gone)
  - [ ] `grep "cba6f7\|a6e3a1\|6c7086\|fab387\|f9e2af\|cdd6f4\|89b4fa\|94e2d5" resources/css/app.css` returns 0 (all old Mocha syntax colors gone)
  - [ ] Both light and dark hljs sections have complete color definitions

  **QA Scenarios**:

  ```
  Scenario: No old syntax colors remain
    Tool: Bash (grep)
    Preconditions: Task completed
    Steps:
      1. grep "8839ef\|40a02b\|9ca0b0\|fe640b\|df8e1d\|1e66f5\|179299" resources/css/app.css — expect 0
      2. grep "cba6f7\|a6e3a1\|6c7086\|fab387\|f9e2af\|89b4fa\|94e2d5" resources/css/app.css — expect 0
      3. grep -c "\.hljs-" resources/css/app.css — expect same count as before (structure preserved)
    Expected Result: All old syntax colors replaced, selector count unchanged
    Failure Indicators: Any old Catppuccin syntax hex found, selector count changed
    Evidence: .sisyphus/evidence/task-14-syntax-theme.txt
  ```

  **Commit**: YES
  - Message: `design(tokens): create retro Highlight.js syntax theme`
  - Files: `resources/css/app.css`
  - Pre-commit: `npm run build`

- [x] 15. Apply Typography Hierarchy + Border-Radius Bezel

  **What to do**:
  - **Typography changes across templates**:
    - Section headers (e.g., "Staged Changes", "Changes", "Commit") should use Space Mono (`font-display` or `font-[var(--font-display)]`)
    - Add `uppercase tracking-wider` to section headers if not already present (most already have this per AGENTS.md)
    - Body text: Add `tracking-wide` (0.02em) to body content areas for terminal-like spacing
    - Ensure commit messages, file paths, SHAs remain in JetBrains Mono (font-mono)
  - **Border radius bezel**:
    - Main content cards/panels: Apply `rounded-3xl` (24px) or `rounded-[32px]` border-radius
    - This includes: the staging panel wrapper, diff viewer wrapper, commit panel wrapper, history panel wrapper
    - Sub-elements (buttons, inputs, badges): Keep current radius or use `rounded-xl` (12px)
    - The outer app container should NOT be rounded (it's the window itself)
  - **Affected templates**: app-layout.blade.php (panel containers), staging-panel, diff-viewer, commit-panel, history-panel, search-panel (modal), settings-modal, command-palette, shortcut-help
  - Update CSS `--radius-sm`, `--radius-md`, `--radius-lg` variables if not already done in Task 3

  **Must NOT do**:
  - Change the font for code/diff content (keep JetBrains Mono)
  - Round the outer app window or header bar
  - Add border-radius to individual file list items (keep edge-to-edge per AGENTS.md tree view rules)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Typography hierarchy and spacing requires design judgment
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Task 16)
  - **Blocks**: Task 17
  - **Blocked By**: Task 2 (font), Tasks 4-9 (template colors must be updated first)

  **References**:

  **Pattern References**:
  - `resources/css/app.css:10-13` — @theme font definitions (--font-display added in Task 2)
  - `resources/views/livewire/app-layout.blade.php:162-171` — Panel container divs (add border-radius here)
  - `resources/views/livewire/staging-panel.blade.php` — Section headers ("Staged Changes", "Changes")
  - `resources/views/livewire/commit-panel.blade.php` — Commit panel wrapper
  - AGENTS.md "Tree View > Rules" — "Items use py-1.5 and gap-2.5" (preserve density)

  **WHY Each Reference Matters**:
  - Panel containers in app-layout are where bezel radius is most impactful
  - Section headers in staging-panel are the primary targets for Space Mono
  - AGENTS.md rules on tree view density must be preserved despite radius changes

  **Acceptance Criteria**:
  - [ ] Section headers use Space Mono font (verify via Playwright computed styles)
  - [ ] Main panel containers have ≥24px border-radius
  - [ ] Code/diff content still uses JetBrains Mono
  - [ ] Body text has wider letter-spacing than before

  **QA Scenarios**:

  ```
  Scenario: Typography hierarchy is correct
    Tool: Playwright (playwright skill)
    Preconditions: App running with repo open
    Steps:
      1. Navigate to app with a repo selected
      2. Inspect "Staged Changes" header computed font-family — should contain "Space Mono"
      3. Inspect a file path in the file list — should contain "JetBrains Mono"
      4. Inspect a diff line — should contain "JetBrains Mono"
      5. Screenshot the staging panel showing headers and file list
    Expected Result: Headers in Space Mono, code content in JetBrains Mono, body in Instrument Sans
    Failure Indicators: Wrong font on headers, code using Space Mono instead of JetBrains
    Evidence: .sisyphus/evidence/task-15-typography.png

  Scenario: Border radius bezel applied to containers
    Tool: Playwright (playwright skill)
    Preconditions: App running with repo open
    Steps:
      1. Inspect main panel container computed border-radius — should be ≥24px
      2. Inspect a button — should have smaller radius (≤16px)
      3. Screenshot full app showing panel bezels
    Expected Result: Panels have large CRT-like bezels, buttons have smaller radius
    Failure Indicators: Panels still have 4-8px radius, buttons rounded too large
    Evidence: .sisyphus/evidence/task-15-bezel-radius.png
  ```

  **Commit**: YES (groups with Task 16)
  - Message: `design(layout): apply Space Mono typography, bezel radius, and CRT effects`
  - Files: Multiple blade templates
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [x] 16. Apply CRT Effects to Components (Glow, Scanlines, Recessed Inputs)

  **What to do**:
  - Apply the CSS classes created in Tasks 10 and 11 to actual components:
  - **Scanlines**: Add `crt-scanlines` class + `relative` to:
    - Diff viewer container (dark mode background area)
    - History panel main area (dark mode)
  - **Phosphor glow**: Add `phosphor-glow` class to:
    - Focused/active input fields (commit message textarea, search inputs)
    - Selected file items in staging panel
    - Active buttons on hover/focus in dark mode
  - **Phosphor text glow**: Add `phosphor-text-glow` to:
    - Section headers in dark mode (Staged Changes, Changes, etc.)
    - Commit SHA text in dark mode
  - **Recessed inputs**: Add `input-recessed` class to:
    - Commit message textarea
    - Search input fields
    - Settings modal inputs
  - **Neumorphic containers**: Add `neu-container` class to:
    - Main staging panel wrapper
    - Main diff viewer wrapper
    - Main commit panel wrapper
  - **Neumorphic buttons**: Add `btn-neu` class to primary Flux buttons:
    - Commit button
    - Push/Pull/Fetch buttons (if they have primary/accent styling)
  - Use `dark:phosphor-glow` and `dark:phosphor-text-glow` to scope effects to dark mode only

  **Must NOT do**:
  - Add neumorphism to elements smaller than 32px (badges, status dots, etc.)
  - Add glow to ALL text (only headers and interactive elements)
  - Add scanlines to light mode
  - Change Flux component internals (use wrapper divs or additional classes)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Applying effects tastefully requires design judgment about where effects enhance vs clutter
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 4 (with Task 15)
  - **Blocks**: Task 17
  - **Blocked By**: Tasks 10, 11 (CRT CSS classes), Tasks 4-9 (templates with updated colors)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/staging-panel.blade.php` — Panel wrapper, section headers, file items
  - `resources/views/livewire/diff-viewer.blade.php` — Diff container, line items
  - `resources/views/livewire/commit-panel.blade.php` — Commit textarea, button group
  - `resources/views/livewire/app-layout.blade.php:162-171` — Panel container structure

  **WHY Each Reference Matters**:
  - These are the templates where CRT effects have the most visual impact
  - The panel structure in app-layout determines where containers get neumorphic treatment

  **Acceptance Criteria**:
  - [ ] `grep "crt-scanlines\|phosphor-glow\|input-recessed\|neu-container\|btn-neu" resources/views/livewire/` returns matches in multiple files
  - [ ] Dark mode shows subtle scanlines on diff viewer
  - [ ] Dark mode shows glow on active elements
  - [ ] Inputs look recessed in both modes
  - [ ] Primary buttons have neumorphic shadow

  **QA Scenarios**:

  ```
  Scenario: CRT effects visible in dark mode
    Tool: Playwright (playwright skill)
    Preconditions: App running in dark mode with repo open
    Steps:
      1. Toggle to dark mode
      2. Navigate to staging panel with files
      3. Screenshot diff viewer area — should show faint scanlines
      4. Click on a file — screenshot selected state — should show glow
      5. Focus commit message textarea — should show recessed + glow
    Expected Result: Scanlines visible on dark background, glow on active elements, recessed inputs
    Failure Indicators: No visible scanlines, no glow, flat inputs
    Evidence: .sisyphus/evidence/task-16-dark-crt-effects.png

  Scenario: Neumorphic styling on containers and buttons
    Tool: Playwright (playwright skill)
    Preconditions: App running in light mode with repo open
    Steps:
      1. Toggle to light mode
      2. Screenshot full app — verify panel containers have raised shadow
      3. Screenshot commit button — verify pill-shaped with raised shadow
      4. Click and hold commit button — verify depress effect (scale + inset shadow)
    Expected Result: Containers raised, buttons pill-shaped with depress interaction
    Failure Indicators: Flat containers, no button depress, shadows missing
    Evidence: .sisyphus/evidence/task-16-light-neumorphic.png
  ```

  **Commit**: YES (groups with Task 15)
  - Message: `design(layout): apply Space Mono typography, bezel radius, and CRT effects`
  - Files: Multiple blade templates
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [ ] 17. Create Playwright Visual Regression Tests

  **What to do**:
  - Create a Playwright test file that captures visual screenshots of 5 key screens in both light and dark modes (10 total):
  - **Screens to test**:
    1. **Staging panel** — With files (staged + unstaged), showing status dots, section headers
    2. **Diff viewer** — With a diff loaded, showing line colors, hunk headers, status badges
    3. **Commit panel** — With commit message typed, showing textarea, buttons, split button
    4. **Empty state** — No repo selected, showing boot-up sequence result
    5. **Search panel** — Opened with search results visible
  - For EACH screen, capture in BOTH light and dark mode (toggle via `document.documentElement.classList.toggle('dark')`)
  - Use Playwright's `toHaveScreenshot()` for visual comparison
  - Save baseline screenshots to `tests/screenshots/`
  - The test file should be at `tests/Feature/VisualRegressionTest.php` (Pest format) or `tests/Browser/` depending on project setup
  - If Playwright/Dusk isn't set up, use `php artisan dusk:install` or document manual setup steps

  **Must NOT do**:
  - Test functional behavior (that's existing Pest tests)
  - Take screenshots of every single component (just the 5 key screens)
  - Hard-code pixel positions for comparisons (use toHaveScreenshot threshold)

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Needs Playwright setup knowledge and careful test design
  - **Skills**: [`playwright`]
    - `playwright`: Required for browser automation and screenshot capture

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5 (with Task 18)
  - **Blocks**: Final Verification Wave
  - **Blocked By**: Tasks 15, 16, 13, 14 (all visual changes must be complete)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php:93-101` — Empty state (boot sequence)
  - `resources/views/livewire/staging-panel.blade.php` — Staging panel layout
  - `resources/views/livewire/diff-viewer.blade.php` — Diff viewer layout

  **External References**:
  - Playwright toHaveScreenshot() API for visual comparison
  - Laravel Dusk (if Playwright isn't directly available) for browser testing

  **WHY Each Reference Matters**:
  - Template files show what elements to wait for before taking screenshots
  - Testing framework choice depends on what's already set up in the project

  **Acceptance Criteria**:
  - [ ] Test file exists with 10 visual test cases (5 screens × 2 modes)
  - [ ] Baseline screenshots captured and saved
  - [ ] All 10 tests pass on first run (baselines match)

  **QA Scenarios**:

  ```
  Scenario: All visual regression tests pass
    Tool: Bash
    Preconditions: All visual changes complete (Tasks 1-16)
    Steps:
      1. Run the visual regression test suite
      2. Verify all 10 tests pass (5 screens × 2 modes)
      3. Check that baseline screenshots exist in tests/screenshots/
    Expected Result: 10/10 tests pass, 10 baseline screenshots saved
    Failure Indicators: Test failures, missing screenshots, timeouts
    Evidence: .sisyphus/evidence/task-17-playwright-results.txt
  ```

  **Commit**: YES (groups with Task 18)
  - Message: `test(visual): add Playwright visual regression tests for retro theme`
  - Files: `tests/`
  - Pre-commit: `php artisan test --compact`

- [ ] 18. Run Full Verification (Grep, Contrast, Existing Tests)

  **What to do**:
  - Run comprehensive verification to ensure theme replacement is complete:
  - **Color grep check**: Run against ALL Blade templates + CSS to verify ZERO old Catppuccin colors remain:
    ```bash
    grep -r "084CCF\|eff1f5\|e6e9ef\|dce0e8\|ccd0da\|4c4f69\|6c6f85\|8c8fa1\|8839ef\|1e66f5\|9ca0b0\|40a02b\|d20f39\|df8e1d\|fe640b\|179299\|04a5e5\|bcc0cc" resources/views/livewire/ resources/views/components/ resources/views/layouts/ resources/css/app.css
    ```
  - **Dark mode Catppuccin Mocha check**:
    ```bash
    grep -r "1e1e2e\|181825\|11111b\|313244\|45475a\|cdd6f4\|a6adc8\|7f849c\|cba6f7\|a6e3a1\|f38ba8\|fab387\|f9e2af\|89b4fa\|94e2d5\|89dceb\|b4befe\|6c7086\|bac2de" resources/css/app.css
    ```
  - **Run existing tests**: `php artisan test --compact` — all must pass
  - **Run Pint**: `vendor/bin/pint --dirty --format agent` — all formatting clean
  - **Run build**: `npm run build` — must compile without errors
  - **Contrast validation**: For each key text/background pair, compute contrast ratio and verify ≥ 4.5:1
  - Document all results in `.sisyphus/evidence/task-18-full-verification.md`

  **Must NOT do**:
  - Skip any verification step
  - Accept failures without investigation
  - Modify code to fix issues (flag them for re-work)

  **Recommended Agent Profile**:
  - **Category**: `deep`
    - Reason: Thorough verification requires systematic checking of multiple dimensions
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 5 (with Task 17)
  - **Blocks**: Final Verification Wave
  - **Blocked By**: Tasks 15, 16, 13, 14

  **References**:

  **Pattern References**:
  - All files in `resources/views/livewire/`, `resources/views/components/`, `resources/views/layouts/`
  - `resources/css/app.css` — Full CSS file

  **WHY Each Reference Matters**:
  - Every file that could contain old colors must be checked
  - The CSS file is the single source of truth for design tokens

  **Acceptance Criteria**:
  - [ ] Color grep returns 0 matches for ALL old Catppuccin hex values
  - [ ] `php artisan test --compact` passes
  - [ ] `vendor/bin/pint --dirty --format agent` shows no issues
  - [ ] `npm run build` succeeds
  - [ ] All key contrast ratios ≥ 4.5:1

  **QA Scenarios**:

  ```
  Scenario: Complete theme replacement verified
    Tool: Bash (grep + test runners)
    Preconditions: All implementation tasks complete
    Steps:
      1. Run full Catppuccin color grep (both Latte and Mocha)
      2. Run php artisan test --compact
      3. Run vendor/bin/pint --dirty --format agent
      4. Run npm run build
      5. Document all results
    Expected Result: Zero old colors, all tests pass, build succeeds
    Failure Indicators: Any old color found, test failures, build errors
    Evidence: .sisyphus/evidence/task-18-full-verification.md
  ```

  **Commit**: YES (groups with Task 17)
  - Message: `test(visual): add Playwright visual regression tests for retro theme`
  - Files: `.sisyphus/evidence/`
  - Pre-commit: `php artisan test --compact`

---

## Final Verification Wave

> 4 review agents run in PARALLEL. ALL must APPROVE. Rejection → fix → re-run.

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists (read file, grep for colors, run command). For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Code Quality Review** — `unspecified-high`
  Run `php artisan test --compact` + `vendor/bin/pint --dirty --format agent`. Review all changed files for: hardcoded old Catppuccin colors, broken CSS variables, missing dark mode overrides, orphaned animation classes, console.log in prod, commented-out code. Check that no `backdrop-filter` or complex SVG filters were introduced.
  Output: `Tests [PASS/FAIL] | Pint [PASS/FAIL] | Old Colors [CLEAN/N found] | Dark Mode [COMPLETE/N missing] | VERDICT`

- [ ] F3. **Real Manual QA** — `unspecified-high` (+ `playwright` skill)
  Start from clean state. Open gitty in both light and dark modes. Navigate every screen (staging, diff viewer, history, search, blame, settings, command palette, branch manager). Verify: no old colors visible, neumorphic buttons depress correctly, scanlines visible in dark mode, glow on active elements, boot sequence plays, Space Mono on headings. Capture screenshots for every screen in both modes. Save to `.sisyphus/evidence/final-qa/`.
  Output: `Screens [N/N pass] | Light Mode [PASS/FAIL] | Dark Mode [PASS/FAIL] | Effects [N/N working] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff (git log/diff). Verify 1:1 — everything in spec was built (no missing), nothing beyond spec was built (no creep). Check "Must NOT do" compliance: no chromatic aberration, no vignette, no haptics, no multi-theme UI, no component logic changes. Detect cross-task contamination. Flag unaccounted changes.
  Output: `Tasks [N/N compliant] | Contamination [CLEAN/N issues] | Unaccounted [CLEAN/N files] | VERDICT`

---

## Commit Strategy

| After Task(s) | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `docs(tokens): define retro-futurism color palette with WCAG validation` | .sisyphus/ | — |
| 2, 3 | `design(tokens): replace Catppuccin with retro-futurism palette in CSS` | resources/css/app.css, resources/views/layouts/app.blade.php | grep for old colors |
| 4-9 | `design(templates): swap all Blade template colors to retro palette` | resources/views/livewire/*.blade.php, resources/views/components/*.blade.php | grep for old colors |
| 10, 11 | `feat(design): add CRT effect classes and neumorphic button styling` | resources/css/app.css | visual inspection |
| 12, 13 | `feat(design): add retro animations and boot-up loading sequence` | resources/css/app.css, resources/views/ | animation playback |
| 14 | `design(tokens): create retro Highlight.js syntax theme` | resources/css/app.css | diff viewer inspection |
| 15, 16 | `design(layout): apply Space Mono typography, bezel radius, and CRT effects` | resources/views/, resources/css/app.css | visual inspection |
| 17, 18 | `test(visual): add Playwright visual regression tests for retro theme` | tests/ | php artisan test |

---

## Success Criteria

### Verification Commands
```bash
# No old Catppuccin colors remain in templates
grep -r "084CCF\|eff1f5\|e6e9ef\|dce0e8\|ccd0da\|4c4f69\|6c6f85\|8c8fa1\|8839ef\|1e66f5\|9ca0b0" resources/views/livewire/ resources/views/components/ resources/views/layouts/
# Expected: (empty - no matches)

# No old colors in CSS (except in comments)
grep -v "^/\*\|^ \*" resources/css/app.css | grep -i "084CCF\|eff1f5\|e6e9ef"
# Expected: (empty - no matches)

# New accent color present in @theme
grep "color-accent" resources/css/app.css
# Expected: contains new retro accent hex

# Space Mono font loaded
grep "space-mono" resources/views/layouts/app.blade.php
# Expected: 1 match in font link

# All existing tests pass
php artisan test --compact
# Expected: all tests pass

# Pint formatting clean
vendor/bin/pint --dirty --format agent
# Expected: no formatting issues
```

### Final Checklist
- [ ] All "Must Have" items present and verified
- [ ] All "Must NOT Have" items absent (grep verification)
- [ ] All 10 Playwright visual tests pass
- [ ] WCAG AA contrast ≥ 4.5:1 for all text/background pairs
- [ ] Both light and dark modes render correctly
- [ ] Boot-up animation plays
- [ ] prefers-reduced-motion disables decorative animations
- [ ] Flux UI primary buttons use new accent
- [ ] Badge opacity concatenation pattern works with new hex colors
