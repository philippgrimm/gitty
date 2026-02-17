# Empty State Illustrations — Design Agency Level

## TL;DR

> **Quick Summary**: Replace all 6 cheap wireframe-level SVG empty state illustrations with polished, soft-layered compositions using the Catppuccin Latte palette and Zed Blue accent. Each illustration should feel like it belongs in a premium macOS app (Linear/Raycast tier).
> 
> **Deliverables**:
> - 6 redesigned SVG files in `resources/svg/empty-states/`
> - Consistent visual language across all 6 (shared motifs, color usage, layering depth)
> - Evidence screenshots proving quality at all container sizes
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: NO — sequential (visual consistency requires single creative pass)
> **Critical Path**: Task 1 (create illustrations) → Task 2 (visual QA validation)

---

## Context

### Original Request
User wants the 6 empty state SVG illustrations in gitty (a macOS-native git client) replaced with "really polished professional illustrations — high class design agency level." Current SVGs are basic wireframe-quality line art with generic gray strokes and an off-brand amber accent.

### Interview Summary
**Key Discussions**:
- **Style direction**: User chose "Soft layered" — filled shapes with subtle opacity layers, soft depth through overlapping forms (like Linear or Raycast empty states)
- **Quality bar**: "Design agency level" — not developer placeholder art

**Research Findings**:
- 6 SVG files found in `resources/svg/empty-states/`
- All loaded inline via `file_get_contents(resource_path('svg/empty-states/...'))`
- Current SVGs have critical issues: wrong accent color (amber #f59e0b instead of Zed Blue #084CCF), no fills/depth (pure 1.5px strokes), only 5-8 elements per SVG, no unified visual language
- Container sizes vary: w-12 (48px), w-16 (64px), w-20 (80px), w-24 (96px) — all square
- All containers apply `opacity-60` via CSS and `animate-fade-in`
- Accompanying text is always uppercase tracking-wider in `#9ca0b0`

### Metis Review
**Identified Gaps** (addressed):
- Container `opacity-60` affects perceived color intensity → art direction accounts for this; colors specified at slightly bolder values
- ViewBox should remain square to match square containers → standardized at 160×160
- Visual metaphors needed explicit definition → detailed concept art direction per SVG below
- File size ceiling needed → capped at 15KB per file
- Element count range needed → 15-50 elements per SVG
- Colors must stay within Catppuccin Latte palette → enforced in guardrails
- SVGs must work at smallest container (48px) without losing legibility → tested at all sizes

---

## Work Objectives

### Core Objective
Create 6 polished, professional SVG empty state illustrations that elevate gitty's visual quality to the level of premium macOS applications like Linear, Raycast, or Tower.

### Concrete Deliverables
- `resources/svg/empty-states/no-repo.svg` — redesigned
- `resources/svg/empty-states/no-file.svg` — redesigned
- `resources/svg/empty-states/no-changes.svg` — redesigned
- `resources/svg/empty-states/no-diff.svg` — redesigned
- `resources/svg/empty-states/large-file.svg` — redesigned
- `resources/svg/empty-states/binary-file.svg` — redesigned

### Definition of Done
- [ ] All 6 SVGs replaced with new soft-layered illustrations
- [ ] All 6 use only approved Catppuccin Latte + Zed Blue colors
- [ ] All 6 render legibly at 48px (smallest container)
- [ ] All 6 render beautifully at 96px (largest container)
- [ ] All 6 look like a cohesive visual family
- [ ] Evidence screenshots captured for all illustrations

### Must Have
- Soft-layered style: filled shapes with opacity layers creating depth
- Catppuccin Latte palette + Zed Blue (#084CCF) as unifying accent color
- Consistent geometric language across all 6 (shared corner radii, stroke weights, motif vocabulary)
- Professional quality — each SVG should have 15-50 thoughtfully composed elements
- Transparent backgrounds (the page background shows through)
- Square viewBox (160×160) so SVGs fill square containers without distortion

### Must NOT Have (Guardrails)
- **NO animations** — no `<animate>`, `<animateTransform>`, CSS animations, or JavaScript within SVGs
- **NO external assets** — no `<image>` tags, no embedded fonts, no linked resources; pure SVG primitives only
- **NO invented colors** — ONLY use colors listed in the approved palette below
- **NO gradients** — use flat fills with varying opacity for depth instead (the Catppuccin way)
- **NO changes to Blade templates** — do NOT modify any `.blade.php` files, container sizes, opacity classes, or text labels
- **NO hover states or variants** — one static SVG per file
- **NO elements exceeding 50 per SVG** — stay in the 15-50 range
- **NO file sizes exceeding 15KB** — keep SVGs lean
- **NO CSS-variable fills** — hardcode hex values (matches app convention per AGENTS.md: "Blade templates use hardcoded hex values... intentional for clarity and grep-ability")
- **NO text elements** — the text labels are in the Blade templates, not the SVGs
- **NO raster effects** — no `<filter>`, `<feGaussianBlur>`, or other filter primitives (they render poorly at small sizes and hurt performance in Electron)

---

## Verification Strategy (MANDATORY)

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.

### Test Decision
- **Infrastructure exists**: YES (Pest)
- **Automated tests**: NO — this is pure visual/asset work, not logic
- **Agent-Executed QA**: YES — Playwright screenshots at all container sizes

---

## Design System Specification

### Approved Color Palette

All colors come from Catppuccin Latte (defined in `resources/css/app.css`). Use these and ONLY these:

**Background fills (large, soft shapes — create depth/halo):**
| Color | Hex | Usage |
|-------|-----|-------|
| Base | `#eff1f5` | Subtle background fills, outer halos |
| Mantle | `#e6e9ef` | Background shapes, depth layers |
| Crust | `#dce0e8` | Mid-ground fills, secondary shapes |

**Structure fills (document/folder bodies):**
| Color | Hex | Usage |
|-------|-----|-------|
| Surface 0 | `#ccd0da` | Primary strokes, borders, panel outlines |
| Surface 1 | `#bcc0cc` | Stronger borders, emphasized outlines |

**Detail strokes (fine lines, content placeholders):**
| Color | Hex | Usage |
|-------|-----|-------|
| Overlay 0 | `#9ca0b0` | Fine detail lines, decorative elements |
| Overlay 1 | `#8c8fa1` | Text placeholder lines, content indicators |
| Subtext 0 | `#6c6f85` | Stronger detail strokes, prominent elements |

**Accent (focal point, unifying thread):**
| Color | Hex | Usage |
|-------|-----|-------|
| Zed Blue | `#084CCF` | Primary accent elements — use at full opacity OR with SVG `opacity` attribute |
| Zed Blue 15% | Use `opacity="0.15"` on `#084CCF` fills | Accent halos, soft highlight areas |
| Zed Blue 30% | Use `opacity="0.3"` on `#084CCF` fills | Medium accent backgrounds |

### Critical: Opacity-60 Design Consideration

All SVG containers apply `opacity-60` via CSS (`class="opacity-60"`). This means:
- Every color in the SVG will appear at 60% of its specified intensity
- Colors must be designed BOLDER than desired final appearance
- Very light fills (opacity < 0.2 inside SVG) will become nearly invisible after the 60% CSS overlay
- Minimum useful fill opacity inside SVG: ~0.25 (becomes ~0.15 visible)
- Zed Blue `#084CCF` at full opacity inside SVG → appears as a medium blue to the user
- Test perception by mentally applying 60% dimming to all colors

### Layering Architecture

Every illustration should follow this 3-4 layer structure (back to front):

```
Layer 1 — BACKDROP (creates spatial context)
  Large, soft geometric shape (circle or rounded rectangle)
  Fill: Mantle #e6e9ef or Base #eff1f5
  Purpose: Creates a "halo" or "cloud" behind the subject, adds visual weight

Layer 2 — GROUND (creates physicality)
  Subtle shadow or surface indicator
  Fill: Crust #dce0e8 at ~50% opacity, or soft ellipse
  Purpose: Objects feel like they sit ON something, not float in void

Layer 3 — SUBJECT (the main illustration)
  The primary object (folder, document, checkmark, etc.)
  Fill: Crust #dce0e8 or Mantle #e6e9ef, Stroke: Surface 0 #ccd0da or #bcc0cc
  Purpose: The thing the user looks at

Layer 4 — DETAIL & ACCENT (the polish layer)
  Fine lines, accent elements, decorative touches
  Accent elements: Zed Blue #084CCF (full or reduced opacity)
  Detail elements: Overlay #9ca0b0 or #8c8fa1
  Purpose: Elevation from "good" to "premium" — this layer is what separates design agency work from developer art
```

### Geometric Vocabulary (Shared Across All 6)

To ensure the 6 illustrations feel like one family:
- **Corner radius**: Use `rx="4"` to `rx="8"` on rectangles (matches app's `--radius-sm: 4px` to `--radius-lg: 8px`)
- **Stroke weight**: 1.5px for primary outlines, 1px for fine details (consistent with app's existing border weights)
- **Stroke caps**: Always `stroke-linecap="round"` and `stroke-linejoin="round"` for softness
- **Small decorative dots**: 1.5-2px radius circles as scatter accents (like dust motes or stars)
- **Rounded corners everywhere**: No sharp corners on any shape
- **Consistent spacing**: Elements should breathe — avoid cramming; use the full 160×160 canvas

### SVG Template Structure

Every SVG should follow this base structure:

```xml
<svg viewBox="0 0 160 160" fill="none" xmlns="http://www.w3.org/2000/svg">
  <!-- Layer 1: Backdrop -->
  <g>
    <!-- Large soft shape -->
  </g>

  <!-- Layer 2: Ground/Shadow -->
  <g>
    <!-- Subtle surface -->
  </g>

  <!-- Layer 3: Subject -->
  <g>
    <!-- Main illustration -->
  </g>

  <!-- Layer 4: Detail & Accent -->
  <g>
    <!-- Fine lines, accent touches, decorative dots -->
  </g>
</svg>
```

---

## Art Direction Per Illustration

### 1. `no-repo.svg` — "No Repository Selected"

**Context & Sizes**: Main app empty state (96×96px in app-layout), also repo-switcher dropdown (48×48px)
**Current state**: Basic folder outline with crude git-branch circles in amber
**Metaphor**: An empty workspace — a folder waiting for its first project

**Concept — "The Waiting Folder"**:
A softly rendered folder, slightly open/ajar to suggest readiness, sitting on a subtle ground shadow. A small, elegant git branch symbol (two small circles connected by a gentle arc) floats above or peeks out of the folder, rendered in Zed Blue. Behind everything, a large soft circle creates spatial depth.

**Composition Details**:
- **Backdrop**: Large circle (r≈55-60) centered, fill `#e6e9ef`
- **Ground**: Horizontal ellipse beneath the folder, fill `#dce0e8` with opacity ~0.5, suggesting a surface/shadow
- **Folder body**: Rounded rectangle with a tab (classic folder silhouette), fill `#dce0e8`, stroke `#ccd0da` at 1.5px. The folder should be slightly open (top edge tilted or gap suggesting openness)
- **Folder interior shade**: A slightly darker rectangle visible through the opening, fill `#ccd0da` with opacity ~0.3
- **Git branch symbol**: Two small circles (r≈3) connected by a smooth arc/line, all in Zed Blue `#084CCF`. Position: floating just above the folder opening or emerging from inside
- **Decorative dots**: 3-5 small circles (r≈1.5) scattered around the composition in `#ccd0da` or `#9ca0b0` — like subtle sparkles or particles. These dots should be placed asymmetrically for visual interest
- **Optional**: A tiny plus (+) or arrow hint near the folder to subtly suggest "add/open"

**Visual weight**: This is the most-seen empty state (it's the app launch view). It should feel welcoming, not sad. The slightly-open folder and floating git icon should convey "ready to go" rather than "something is missing."

---

### 2. `no-file.svg` — "No File Selected"

**Context & Size**: Diff viewer right panel (80×80px)
**Current state**: Document with folded corner and crude cursor pointer in amber
**Metaphor**: A document waiting to be picked from the file list

**Concept — "The Resting Document"**:
A clean document page with a subtle folded corner, resting above a soft shadow. Faint horizontal lines inside suggest content that will appear once a file is selected. A subtle selection indicator — a small cursor arrow or a dashed highlight border segment — hints at the needed interaction. Rendered with restraint; this is seen every time you open the app before clicking a file.

**Composition Details**:
- **Backdrop**: Large rounded rectangle (rx≈12, width≈100, height≈110) centered, fill `#eff1f5`
- **Ground**: Ellipse shadow beneath the document, fill `#dce0e8` opacity ~0.4
- **Document body**: Rectangle with folded corner (classic dog-ear path), fill `#e6e9ef`, stroke `#ccd0da` at 1.5px. Size roughly 60×75 within the canvas
- **Corner fold**: Triangle in upper-right, fill `#dce0e8`, stroke `#ccd0da`
- **Content lines**: 3-4 horizontal lines inside the document, stroke `#ccd0da` at 1px, varying lengths (e.g., full width, 70%, 85%, 50%) — suggesting text blocks without being literal
- **Cursor/pointer hint**: A small, clean cursor arrow shape (like a mouse pointer) positioned near the document's edge or floating beside it, fill `#084CCF` with opacity ~0.6. Keep it subtle — it's a hint, not a call-to-action
- **Decorative dots**: 2-3 tiny dots scattered, `#ccd0da`

---

### 3. `no-changes.svg` — "No Changes" / "No Stashes"

**Context & Sizes**: Staging panel (80×80px), stash panel (64×64px)
**Current state**: Circle with checkmark and radiating line segments in gray/amber
**Metaphor**: All clear — the working tree is clean, everything is committed

**Concept — "The Zen Check"**:
A clean checkmark centered within soft concentric ring arcs, creating a calm, resolving feel. The composition radiates outward from the checkmark like ripples on still water — peaceful completion. This should feel meditative, not celebratory. Soft, quiet confidence.

**Composition Details**:
- **Backdrop ring (outer)**: Circle (r≈65) centered, fill `#eff1f5` — the outermost halo
- **Mid ring**: Circle (r≈50), fill `#e6e9ef` — creates the layered depth
- **Inner ring**: Circle (r≈35), fill `#dce0e8` — the immediate surround of the checkmark
- **Checkmark**: A bold, confident check path, stroke `#084CCF` at 2.5px, stroke-linecap and linejoin round. The checkmark should feel hand-drawn but precise — slightly generous proportions
- **Ring arcs (decorative)**: 2-3 arc segments (partial circles) at various radii between the rings, stroke `#ccd0da` at 1px — like orbital paths, suggesting calm rotation/completion
- **Cardinal dots**: 4 small circles (r≈2) at the cardinal points (top, right, bottom, left) of the mid ring, fill `#ccd0da`. These anchor the composition
- **Sparkle accents**: 2-3 tiny dots (r≈1-1.5) placed asymmetrically, fill `#9ca0b0`

---

### 4. `no-diff.svg` — "No Changes to Display"

**Context & Size**: Diff viewer when file has no modifications (80×80px)
**Current state**: Two side-by-side rectangles with equals sign in amber
**Metaphor**: Two versions are identical — comparison found no differences

**Concept — "The Twin Documents"**:
Two document shapes, slightly overlapping like cards in a hand, that look identical. They're layered with the back one offset slightly, creating a "deck" effect. A small badge or indicator between/on top of them shows they match — an equals sign or small check inside a rounded pill/badge, rendered in Zed Blue.

**Composition Details**:
- **Backdrop**: Large circle (r≈58) centered, fill `#eff1f5`
- **Ground**: Subtle ellipse shadow, fill `#dce0e8` opacity ~0.3
- **Back document**: Rectangle (rx≈6, ~50×65) offset right by ~8px and down by ~8px from center, fill `#e6e9ef`, stroke `#ccd0da` at 1.5px
- **Back document content lines**: 3 horizontal lines inside, stroke `#ccd0da` at 1px
- **Front document**: Same dimensions as back, positioned center-left and up, fill white `#ffffff` (or `#eff1f5`), stroke `#ccd0da` at 1.5px — the "top card" of the pair
- **Front document content lines**: 3 horizontal lines matching the back document exactly (same positions relative to their document), stroke `#ccd0da` at 1px — reinforcing "these are identical"
- **Match indicator**: A small rounded pill (rx≈8, ~28×16) centered between the two documents, fill `#084CCF` with opacity ~0.15, containing a small equals sign or checkmark in `#084CCF` at full opacity. This is the focal accent element
- **Decorative dots**: 2-3 small circles, `#ccd0da`

---

### 5. `large-file.svg` — "File Too Large (>1MB)"

**Context & Size**: Diff viewer for oversized files (80×80px)
**Current state**: Rectangle with horizontal lines and outward-pointing arrows in amber
**Metaphor**: A file that's too big to display — it overflows its container

**Concept — "The Overflow"**:
A document that visually extends beyond a boundary frame, suggesting it's too large to fit. The document body pushes past a dashed or dotted boundary rectangle, with its edges visible beyond the frame. A small size/weight indicator — like measurement marks or an abstract scale — adds clarity. Uses Zed Blue accent (NOT peach) to maintain visual family consistency.

**Composition Details**:
- **Backdrop**: Large rounded rectangle (rx≈12) centered, fill `#eff1f5`
- **Boundary frame**: Dashed rectangle (rx≈6, ~70×80) centered, stroke `#ccd0da` at 1px, `stroke-dasharray="4 3"` — this represents the "allowed size"
- **Document body (oversized)**: Larger rectangle (rx≈6, ~80×100) also centered but extending past the boundary on all sides, fill `#e6e9ef`, stroke `#ccd0da` at 1.5px. The document visually breaks through the boundary frame
- **Document content lines**: 5-6 horizontal lines inside, stroke `#ccd0da` at 1px — more lines than other document illustrations, suggesting density
- **Overflow indicators**: Small arrows or extension marks at the edges where the document exceeds the frame, stroke `#084CCF` at 1.5px — 2 arrows (top and bottom, or left and right) pointing outward from the frame edge
- **Measurement marks**: 2 small tick marks or a ruler segment along one edge, stroke `#084CCF` with opacity ~0.5
- **Decorative dots**: 2-3 small circles, `#ccd0da`

---

### 6. `binary-file.svg` — "Binary File — Cannot Display Diff"

**Context & Size**: Diff viewer for binary files (80×80px)
**Current state**: Container with 3×3 grid of alternating gray/amber squares
**Metaphor**: Data that exists but can't be read as text

**Concept — "The Data Grid"**:
A document frame where instead of readable text lines, the content area is filled with abstract data blocks — small rectangles and squares of varying sizes in a loose grid pattern. The blocks have different opacities, creating a texture that says "data, but not human-readable text." A subtle "eye-off" or "slash" accent element reinforces that this content can't be displayed.

**Composition Details**:
- **Backdrop**: Large circle (r≈58) centered, fill `#eff1f5`
- **Ground**: Subtle shadow ellipse, fill `#dce0e8` opacity ~0.3
- **Document frame**: Rectangle (rx≈6, ~60×75) centered, fill `#e6e9ef`, stroke `#ccd0da` at 1.5px
- **Data blocks**: Inside the document, a grid of small rectangles (varying sizes: 6×4, 8×4, 5×4, 10×4) arranged in 4-5 rows. Use a mix of fills:
  - Some blocks: fill `#ccd0da` (Surface 0)
  - Some blocks: fill `#084CCF` with opacity ~0.2 (Zed Blue tint)
  - Some blocks: fill `#dce0e8` (Crust)
  - Vary block widths within rows to break the grid monotony — this shouldn't look like a spreadsheet, but like fragmented data
- **Diagonal slash (accent)**: A subtle diagonal line crossing over the document (from upper-left area to lower-right area of the document), stroke `#084CCF` at 1.5px, opacity ~0.4. This "cancels" the content — says "can't read this." Keep it subtle, not aggressive
- **Decorative dots**: 2-3 small circles, `#ccd0da`

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
└── Task 1: Create all 6 SVG illustrations (main creative work)

Wave 2 (After Wave 1):
└── Task 2: Visual QA validation across all container sizes

Critical Path: Task 1 → Task 2
Parallel Speedup: N/A — sequential dependency
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | 2 | None |
| 2 | 1 | None | None (final) |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Agents |
|------|-------|-------------------|
| 1 | 1 | task(category="artistry", load_skills=["frontend-ui-ux"], run_in_background=false) |
| 2 | 2 | task(category="visual-engineering", load_skills=["playwright"], run_in_background=false) |

---

## TODOs

- [x] 1. Create All 6 SVG Empty State Illustrations

  **What to do**:
  - Study the existing app's visual language by examining `resources/css/app.css` (design tokens, border radii, color palette) and existing Blade templates (how containers are styled) to absorb the geometric vocabulary
  - Create each SVG following the detailed art direction above, starting with `no-repo.svg` (the most visible one) to establish the visual language, then proceeding through the remaining 5
  - For each SVG:
    1. Start with the layered structure (backdrop → ground → subject → detail)
    2. Use only approved colors from the palette table above
    3. Respect the 160×160 square viewBox
    4. Keep element count between 15-50
    5. Ensure `fill="none"` on the root `<svg>` element (transparent background)
    6. Use `stroke-linecap="round"` and `stroke-linejoin="round"` on all stroked paths
    7. Round all coordinate values to 1 decimal place maximum for clean diffs
  - After creating each SVG, create a temporary test HTML page to visually verify the illustration at all 4 container sizes (48px, 64px, 80px, 96px) with `opacity: 0.6` applied — this mimics the real rendering environment
  - Ensure all 6 feel like a cohesive family when viewed together (consistent layering depth, accent usage, decorative element style)

  **Order of creation** (establish language, then flow):
  1. `no-repo.svg` — the "hero," sets the visual tone
  2. `no-changes.svg` — conceptually different (abstract/circular vs document-based), tests range of the system
  3. `no-file.svg` — document-based, validates the document rendering style
  4. `no-diff.svg` — twin documents, builds on #3's document style
  5. `large-file.svg` — variant document, adds boundary concept
  6. `binary-file.svg` — variant document, adds data block concept

  **Must NOT do**:
  - Do NOT modify any `.blade.php` template files
  - Do NOT add animations or filters to SVGs
  - Do NOT use colors outside the approved palette
  - Do NOT use `<filter>`, `<feGaussianBlur>`, `<linearGradient>`, or `<radialGradient>`
  - Do NOT add `<text>` elements — all text is handled by Blade templates
  - Do NOT exceed 15KB per SVG file
  - Do NOT use CSS variables (e.g., `var(--surface-0)`) — hardcode hex values

  **Recommended Agent Profile**:
  - **Category**: `artistry`
    - Reason: This is creative visual design work requiring aesthetic judgment, not standard engineering. The agent needs to compose shapes, balance whitespace, and make subjective quality decisions about what looks "premium."
  - **Skills**: [`frontend-ui-ux`]
    - `frontend-ui-ux`: Provides design sensibility and understanding of visual hierarchy, spacing, and UI aesthetics — critical for creating illustrations that feel native to a polished macOS app
  - **Skills Evaluated but Omitted**:
    - `playwright`: Not needed for creation — the agent can use a simple HTML file + browser to verify visuals
    - `tailwindcss-development`: Not relevant — SVGs don't use Tailwind classes
    - `livewire-development`: Not relevant — no Livewire components being modified

  **Parallelization**:
  - **Can Run In Parallel**: NO — all 6 SVGs must be created by the same agent to ensure visual consistency
  - **Parallel Group**: Sequential
  - **Blocks**: Task 2 (Visual QA)
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL):

  **Pattern References** (existing code to follow):
  - `resources/svg/empty-states/no-repo.svg` — current SVG to REPLACE (study structure and viewBox, then redesign completely)
  - `resources/svg/empty-states/no-file.svg` — current SVG to REPLACE
  - `resources/svg/empty-states/no-changes.svg` — current SVG to REPLACE
  - `resources/svg/empty-states/no-diff.svg` — current SVG to REPLACE
  - `resources/svg/empty-states/large-file.svg` — current SVG to REPLACE
  - `resources/svg/empty-states/binary-file.svg` — current SVG to REPLACE

  **API/Type References** (color system to implement against):
  - `resources/css/app.css:22-69` — Complete Catppuccin Latte color palette, border radii, shadows — the design tokens that define gitty's visual identity. Every fill and stroke in the SVGs must come from these values.

  **Context References** (how SVGs are displayed):
  - `resources/views/livewire/app-layout.blade.php:46-55` — no-repo.svg usage: `w-24 h-24 opacity-60` in centered flex layout, with "No Repository Selected" text below
  - `resources/views/livewire/staging-panel.blade.php:10-16` — no-changes.svg usage: `w-20 h-20 opacity-60` with "No changes" text
  - `resources/views/livewire/stash-panel.blade.php:28-32` — no-changes.svg reuse: `w-16 h-16 opacity-60` with "No stashes" text
  - `resources/views/livewire/diff-viewer.blade.php:2-49` — ALL four diff-viewer empty states: no-file, no-diff, large-file, binary-file, each at `w-20 h-20 opacity-60` with contextual messages
  - `resources/views/livewire/repo-switcher.blade.php:86-91` — no-repo.svg in dropdown: `w-12 h-12 opacity-60` (smallest container)

  **WHY Each Reference Matters**:
  - The SVG files show current structure to understand file format expectations
  - `app.css` provides the EXACT hex values that must be used — no guessing colors
  - The Blade templates show container sizes and opacity — critical for designing colors that look right after 60% dimming
  - The repo-switcher reference is especially important: at 48×48px with 60% opacity, illustrations must still be legible

  **Acceptance Criteria**:

  - [ ] All 6 SVG files exist at `resources/svg/empty-states/{name}.svg` with correct names
  - [ ] Each SVG has `viewBox="0 0 160 160"` and `fill="none"` on root element
  - [ ] Each SVG has 15-50 shape elements (excluding `<g>` groups and `<svg>` root)
  - [ ] Each SVG file is under 15KB
  - [ ] Color audit passes: only approved Catppuccin Latte hex values + `#084CCF` appear in fill/stroke attributes
  - [ ] No `<filter>`, `<linearGradient>`, `<radialGradient>`, `<animate>`, `<text>`, `<image>`, or `<style>` elements present
  - [ ] All stroked paths use `stroke-linecap="round"` and `stroke-linejoin="round"`
  - [ ] Each SVG uses the layered architecture (backdrop → ground → subject → detail groups)

  **Agent-Executed QA Scenarios (MANDATORY):**

  ```
  Scenario: All SVGs render correctly at all container sizes with opacity
    Tool: Playwright (playwright skill) or standalone HTML test
    Preconditions: All 6 SVGs created
    Steps:
      1. Create a temporary test HTML file (e.g., /tmp/svg-test.html) that displays all 6 SVGs
         in a grid, each at 4 sizes: 48px, 64px, 80px, 96px, with opacity: 0.6 applied,
         on a white (#ffffff) background AND on a base (#eff1f5) background
      2. Open this file in a browser (or use Playwright to navigate to it)
      3. Screenshot the full grid view
      4. Verify: Each SVG is visible and legible at 48px (smallest size)
      5. Verify: Each SVG looks polished and detailed at 96px (largest size)
      6. Verify: All 6 SVGs share a consistent visual language (similar layering depth, accent usage)
      7. Verify: Zed Blue (#084CCF) accent is visible in each SVG even at 60% opacity
    Expected Result: All illustrations render clearly across all sizes, feel like a cohesive family
    Evidence: .sisyphus/evidence/task-1-svg-grid-all-sizes.png
  ```

  ```
  Scenario: Color audit — only approved colors used
    Tool: Bash (grep)
    Preconditions: All 6 SVGs created
    Steps:
      1. Extract all fill colors: grep -ohE 'fill="[^"]*"' resources/svg/empty-states/*.svg | sort -u
      2. Extract all stroke colors: grep -ohE 'stroke="[^"]*"' resources/svg/empty-states/*.svg | sort -u
      3. Verify each color is in the approved list:
         Approved fills: none, #ffffff, #eff1f5, #e6e9ef, #dce0e8, #ccd0da, #bcc0cc, #9ca0b0, #8c8fa1, #6c6f85, #4c4f69, #084CCF
         (opacity attributes on these fills are allowed)
      4. Verify NO amber/yellow colors (#f59e0b) remain from old SVGs
    Expected Result: Zero unapproved colors found
    Evidence: Terminal output captured
  ```

  ```
  Scenario: File size and element count validation
    Tool: Bash
    Preconditions: All 6 SVGs created
    Steps:
      1. Check file sizes: ls -la resources/svg/empty-states/*.svg
      2. Assert: Each file is under 15KB (15360 bytes)
      3. Count shape elements per file:
         for f in resources/svg/empty-states/*.svg; do
           count=$(grep -coE '<(path|circle|rect|ellipse|line|polyline|polygon) ' "$f")
           echo "$f: $count elements"
         done
      4. Assert: Each file has 15-50 elements
      5. Check for forbidden elements:
         grep -lE '<(filter|feGaussianBlur|linearGradient|radialGradient|animate|text |image |style)' resources/svg/empty-states/*.svg
      6. Assert: No files contain forbidden elements (empty output)
    Expected Result: All files pass size, element count, and forbidden element checks
    Evidence: Terminal output captured
  ```

  ```
  Scenario: SVGs render without errors at smallest size (48px)
    Tool: Playwright (playwright skill) or standalone HTML test
    Preconditions: All 6 SVGs created
    Steps:
      1. Create test HTML showing all 6 SVGs at 48×48px with opacity: 0.6 on white background
      2. Screenshot each SVG individually at 48px
      3. Verify: Main subject (folder/document/checkmark) is still recognizable
      4. Verify: Accent element (Zed Blue) is still visible
      5. Verify: No visual artifacts or collapsed shapes
    Expected Result: All illustrations remain legible and recognizable at 48px
    Evidence: .sisyphus/evidence/task-1-svg-48px-legibility.png
  ```

  **Evidence to Capture:**
  - [ ] `.sisyphus/evidence/task-1-svg-grid-all-sizes.png` — grid of all 6 at all 4 sizes
  - [ ] `.sisyphus/evidence/task-1-svg-48px-legibility.png` — close-up at smallest size
  - [ ] Terminal output for color audit and file size validation

  **Commit**: YES
  - Message: `design(staging): replace empty state illustrations with polished soft-layered SVGs`
  - Files: `resources/svg/empty-states/*.svg`
  - Pre-commit: Color audit grep (see QA scenario above)

---

- [x] 2. Visual QA — Validate Illustrations In-App Across All Contexts

  **What to do**:
  - Start the gitty dev server (`php artisan serve --port=8321`)
  - Navigate to each empty state in the actual app using Playwright
  - Screenshot each empty state in its real context (with surrounding UI, text labels, backgrounds)
  - Verify the illustrations look polished and integrated within the actual app layout
  - Capture evidence for each of the 5 unique display contexts:
    1. App layout — no repo selected (96px, the hero view)
    2. Staging panel — no changes (80px)
    3. Stash panel — no stashes (64px)
    4. Diff viewer — no file selected (80px)
    5. Repo-switcher dropdown — no repositories (48px, inside dropdown)
  - Note: diff-viewer states for no-diff, large-file, and binary-file require specific git states that may be hard to trigger — verify these via the standalone HTML test from Task 1 if they can't be triggered in-app

  **Must NOT do**:
  - Do NOT modify any files — this is a read-only validation task
  - Do NOT change SVGs based on findings — just document issues
  - Do NOT change Blade templates, CSS, or any other code

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Browser-based visual validation using Playwright. Requires navigating a running web app, triggering specific UI states, and capturing screenshots — standard visual engineering work
  - **Skills**: [`playwright`]
    - `playwright`: Required for browser automation — navigating to pages, waiting for elements, taking screenshots of specific viewport areas
  - **Skills Evaluated but Omitted**:
    - `frontend-ui-ux`: Not needed for QA — no design decisions being made here
    - `livewire-development`: Not modifying components, just viewing them

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential (Wave 2)
  - **Blocks**: None (final task)
  - **Blocked By**: Task 1 (all SVGs must be created first)

  **References**:

  **Pattern References**:
  - `resources/views/livewire/app-layout.blade.php:46-55` — The no-repo empty state HTML structure (how to identify it in the DOM)
  - `resources/views/livewire/staging-panel.blade.php:10-16` — The no-changes empty state
  - `resources/views/livewire/stash-panel.blade.php:28-32` — The no-stashes empty state
  - `resources/views/livewire/diff-viewer.blade.php:2-49` — All diff-viewer empty states
  - `resources/views/livewire/repo-switcher.blade.php:86-91` — The dropdown empty state

  **WHY Each Reference Matters**:
  - These templates show the DOM structure the agent needs to target with Playwright selectors
  - They reveal which CSS classes to look for (e.g., `animate-fade-in`, `opacity-60`)
  - They show the text labels that should appear alongside illustrations

  **Acceptance Criteria**:

  - [ ] Dev server starts successfully on port 8321
  - [ ] Screenshot captured: app-layout no-repo empty state (96px illustration in context)
  - [ ] Screenshot captured: staging-panel no-changes empty state (80px in context)
  - [ ] Screenshot captured: at least one diff-viewer empty state in context
  - [ ] All captured screenshots show illustrations rendering correctly with no visual artifacts
  - [ ] Illustrations appear visually consistent with surrounding app UI (Catppuccin Latte colors, appropriate weight)

  **Agent-Executed QA Scenarios (MANDATORY):**

  ```
  Scenario: No-repo empty state renders correctly in app layout
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running on localhost:8321, no repository selected
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: .animate-fade-in visible (timeout: 10s) — the empty state container
      3. Assert: SVG content is present inside the opacity-60 container
      4. Assert: Text "No Repository Selected" is visible
      5. Assert: Text "Open a git repository to get started" is visible
      6. Screenshot: full page showing the empty state centered in the app
    Expected Result: Polished folder illustration renders centered with text labels
    Evidence: .sisyphus/evidence/task-2-no-repo-in-app.png
  ```

  ```
  Scenario: Repo-switcher dropdown empty state renders at 48px
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, no repository selected, no recent repos
    Steps:
      1. Navigate to: http://localhost:8321
      2. Click the repo-switcher dropdown button (the button with folder icon and "No repository open" text)
      3. Wait for: dropdown menu visible (timeout: 5s)
      4. Assert: SVG illustration visible inside dropdown at small size
      5. Assert: Text "No repositories yet" visible
      6. Screenshot: dropdown area showing the small empty state illustration
    Expected Result: Illustration is legible and recognizable even at 48px inside dropdown
    Evidence: .sisyphus/evidence/task-2-repo-switcher-dropdown.png
  ```

  ```
  Scenario: Side-by-side comparison — old vs new (optional, if git history available)
    Tool: Bash + Playwright
    Preconditions: Git history contains old SVGs
    Steps:
      1. Check if old SVGs exist in git history: git log --oneline -1 -- resources/svg/empty-states/
      2. If available: create comparison HTML page with old (from git show) and new SVGs side-by-side
      3. Screenshot the comparison
    Expected Result: Clear visual improvement visible in side-by-side
    Evidence: .sisyphus/evidence/task-2-before-after-comparison.png
  ```

  **Evidence to Capture:**
  - [ ] `.sisyphus/evidence/task-2-no-repo-in-app.png` — main empty state in app context
  - [ ] `.sisyphus/evidence/task-2-repo-switcher-dropdown.png` — smallest container in dropdown
  - [ ] `.sisyphus/evidence/task-2-before-after-comparison.png` — old vs new (if feasible)

  **Commit**: NO (no files changed in this task)

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `design(staging): replace empty state illustrations with polished soft-layered SVGs` | `resources/svg/empty-states/*.svg` | Color audit grep + file size check |
| 2 | (no commit) | (no files changed) | Visual QA screenshots |

---

## Success Criteria

### Verification Commands
```bash
# Color audit — no unapproved colors
grep -ohE '(fill|stroke)="[^"]*"' resources/svg/empty-states/*.svg | sort -u
# Expected: Only approved Catppuccin Latte hex values + #084CCF + "none"

# No amber/yellow from old SVGs
grep -l 'f59e0b' resources/svg/empty-states/*.svg
# Expected: No output (no files contain old amber color)

# No forbidden elements
grep -lE '<(filter|feGaussianBlur|linearGradient|radialGradient|animate|text |image )' resources/svg/empty-states/*.svg
# Expected: No output

# File sizes under 15KB
find resources/svg/empty-states -name "*.svg" -size +15k
# Expected: No output

# ViewBox is correct
grep -L 'viewBox="0 0 160 160"' resources/svg/empty-states/*.svg
# Expected: No output (all files have correct viewBox)
```

### Final Checklist
- [ ] All 6 SVGs replaced with polished soft-layered illustrations
- [ ] All use Catppuccin Latte palette + Zed Blue accent exclusively
- [ ] All render legibly at 48px (smallest container)
- [ ] All look premium at 96px (largest container)
- [ ] All feel like a cohesive visual family
- [ ] No amber (#f59e0b) remains from old illustrations
- [ ] All containers/templates unchanged — zero Blade modifications
- [ ] Evidence screenshots captured proving quality
