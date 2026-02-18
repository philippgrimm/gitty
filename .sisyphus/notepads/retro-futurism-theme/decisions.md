## Task 2: Color Palette Definition - Decisions

### Accent Color Strategy
**Decision**: Use different accent colors for light vs dark mode
- Light: `#18206F` (deep cobalt) — references IBM blue, classic UI chrome
- Dark: `#00C3FF` (bright cyan) — references TRON, vector displays, phosphor glow
- Rationale: Each mode has its own aesthetic identity (analog vs digital)

### Flux @theme Block
**Decision**: Keep Flux accent as `#18206F` in both modes
- Flux reads from `@theme {}` block, not `:root {}`
- Buttons use this color, which works well in both modes
- Dark mode uses `--accent` in `:root {}` for custom components

### Semantic Color Adjustments
**Decision**: Darken light mode semantic colors for WCAG AA compliance
- Original Catppuccin colors too bright on warm cream background
- Adjusted: green, yellow, peach (red was already compliant)
- Dark mode colors kept saturated (phosphor aesthetic + high contrast)

### Neumorphic Shadow Colors
**Decision**: Use surface colors for neumorphic effects, not pure black/white
- Light mode: `#FFFFFF` (light) + `#C8C3B8` (dark)
- Dark mode: `#1A1E27` (light) + `#000000` (dark)
- Rationale: Matches surface elevation system, more subtle than pure black/white

### Graph Colors
**Decision**: 8 distinct colors for commit history lanes
- Reuse semantic colors where possible (blue, green, red, mauve, teal, sky)
- Add peach/orange and amber for additional lanes
- Ensures visual distinction without introducing new color families

### Syntax Theme Colors
**Decision**: Map to existing palette colors
- Reuse semantic colors for syntax highlighting
- Maintains color consistency across UI
- No new colors needed (8 syntax roles map to existing palette)

### White Background Color
**Decision**: Use warm cream `#F2EFE9` instead of pure white
- Matches retro aesthetic (aged paper, vintage manuals)
- Reduces eye strain compared to pure white
- Provides better context for warm brown-grey borders
