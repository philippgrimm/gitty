# Empty State Illustrations — Learnings & Decisions

**Date:** 2026-02-15  
**Task:** Create all 6 polished SVG empty state illustrations

## Design Approach

### Aesthetic Direction
- **Soft-layered composition** — NOT flat wireframes
- **4-layer architecture** (back to front):
  1. Backdrop (large soft circle/rect, fills `#e6e9ef` or `#eff1f5`)
  2. Ground (subtle shadow ellipse, `#dce0e8` opacity ~0.3-0.5)
  3. Subject (main illustration object, `#e6e9ef`/`#dce0e8`, stroke `#ccd0da`)
  4. Detail & Accent (fine lines, Zed Blue, decorative dots)

### Color Strategy
- **Designed BOLDER than final appearance** — all containers apply `opacity: 0.6` via CSS
- Minimum useful fill opacity inside SVG: ~0.25 (becomes ~0.15 after CSS dimming)
- Zed Blue (#084CCF) at full opacity in SVG → appears as medium blue to user
- Avoided sharp contrast — used mid-tones for depth (not black/white extremes)

### Visual Consistency
- **Shared vocabulary** across all 6:
  - Corner radius: `rx="4"` to `rx="8"`
  - Stroke weights: 1.5px primary, 1px fine details
  - Decorative dots: r≈1.5-2px, asymmetric placement
  - All strokes use `round` caps and joins
- **Unifying thread**: Zed Blue accent appears in every illustration
- **Geometric balance**: Mix of circular (no-repo, no-changes, no-diff, binary) and rectangular (no-file, large-file) backdrops

## Technical Decisions

### ViewBox & Scaling
- `viewBox="0 0 160 160"` on all SVGs (changed from previous 120×120)
- Scales perfectly to 48px, 64px, 80px, 96px containers
- All coordinates rounded to 1 decimal max

### File Size Optimization
- Inline path commands (no external `<defs>`)
- No gradients, filters, or animations
- Final sizes: 1.4KB (smallest) to 2.9KB (largest) — well under 15KB limit

### Element Count
- Range: 11-31 elements per SVG
- Binary-file has most (31) due to data block grid
- No-file has least (11) — simple document with fold

## Illustration Rationales

### 1. no-repo.svg (Waiting Folder)
- Most-seen empty state (main app + dropdown)
- Folder slightly "ajar" suggests readiness, not abandonment
- Git branch icon floats above (not inside) — suggests future, not missing content

### 2. no-changes.svg (Zen Check)
- Meditative, NOT celebratory — peaceful completion
- Concentric rings = ripples on still water
- Cardinal dots + sparkles balance symmetry with organic feel

### 3. no-file.svg (Resting Document)
- Clean document, NOT blank/empty
- Cursor hint in Zed Blue suggests "selection coming" not "nothing here"
- Dog-ear fold adds character vs. plain rectangle

### 4. no-diff.svg (Twin Documents)
- Overlapping cards emphasize "comparison" concept
- White front doc pops against gray back doc
- Equals pill subtle (15% opacity fill) — not aggressive

### 5. large-file.svg (Overflow)
- Dashed boundary shows constraint, not error
- Document extends beyond — visual metaphor for "too large"
- Arrows point OUT (overflow), not IN (expand)

### 6. binary-file.svg (Data Grid)
- Fragmented data blocks (varying widths) ≠ spreadsheet
- Diagonal slash subtle (40% opacity) — hints "no preview"
- Mix of Zed Blue/gray blocks creates "data" feeling without text

## QA Results

### Color Audit
- ✅ All colors from approved Catppuccin Latte palette + Zed Blue
- ✅ No old amber (#f59e0b) or generic gray (#71717a)
- Fill colors used: `#ffffff`, `#eff1f5`, `#e6e9ef`, `#dce0e8`, `#ccd0da`, `#9ca0b0`, `#084CCF`, `none`
- Stroke colors used: `#ccd0da`, `#084CCF`

### Technical Validation
- ✅ All SVGs have `viewBox="0 0 160 160"` and `fill="none"` on root
- ✅ All files under 15KB (largest: 2.9KB)
- ✅ Element counts within spec (11-31 elements)
- ✅ No forbidden elements (filter, gradient, animate, text, image, style)
- ✅ All stroked paths use `round` caps and joins

### Visual Testing
- ✅ Legible at 48px (smallest size, repo-switcher)
- ✅ Details crisp at 64px, 80px, 96px
- ✅ Family resemblance across all 6
- ✅ Zed Blue accent visible but not overpowering

## Evidence
- `.sisyphus/evidence/task-1-svg-grid-all-sizes.png` — All 6 SVGs at 4 sizes
- `.sisyphus/evidence/task-1-svg-48px-legibility.png` — 48px-only test

## Migration Notes

### Breaking Changes
- ViewBox changed from `120×120` to `160×160` (scaling may differ if external sizing hard-coded)
- Color palette completely replaced (amber → Zed Blue, generic gray → Catppuccin)

### Non-Breaking
- SVGs loaded via `file_get_contents()` — no path changes
- Container `opacity: 0.6` applied externally — still works
- Inline SVG rendering — no sprite sheet dependencies

## Future Considerations

### If adding new empty states:
1. Start with 4-layer structure (backdrop/ground/subject/detail)
2. Use Zed Blue accent somewhere
3. Add 2-4 decorative dots (asymmetric)
4. Design at 160×160 viewBox
5. Test at 48px first (smallest size)

### If tweaking existing:
- Avoid making strokes thinner (1px already minimum at 48px scale)
- Opacity changes require re-testing CSS `opacity: 0.6` interaction
- Color changes must stay within approved Catppuccin palette

## Gotchas Avoided

1. **CSS opacity stacking** — Designed bolder colors knowing CSS would dim to 60%
2. **ViewBox coordinates** — Rounded to 1 decimal, avoided sub-pixel hairlines
3. **Stroke visibility** — 1.5px primary, 1px fine — any thinner disappears at 48px
4. **Decorative dot sizes** — r≈1.5-2px minimum (smaller becomes invisible after opacity)
5. **File protocol** — Playwright can't read `file://` URLs, needed HTTP server for screenshots

---

**Status:** All 6 SVGs complete, validated, and visually tested. Ready for production.
