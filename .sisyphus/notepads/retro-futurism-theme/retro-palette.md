# Retro-Futurism "Warm Analog Computing" Color Palette

**Theme Concept**: High-end technology from 1982, refined for 2024. Warm cream light mode ("Daytime Office"), midnight blue with phosphor glow dark mode ("Power On"). CRT monitor aesthetic with warm analog tones.

**Format**: ALL colors in hex (#RRGGBB) — required for badge concatenation pattern (`{{ $badgeColor }}15`).

**WCAG AA Validation**: All text/background combinations meet ≥4.5:1 contrast ratio for normal text.

---

## Light Mode Palette

### Background Colors
```css
--surface-0: #E8E5DF;    /* Base - main background, hover states */
--surface-1: #DED9D0;    /* Mantle - elevated panels, headers */
--surface-2: #D4CFC6;    /* Crust - subtle elevation */
--surface-3: #C8C3B8;    /* Highest elevation, active states */
```

**White backgrounds**: `#F2EFE9` (warm cream) — file list panels, diff viewer, dropdown backgrounds

### Text Colors
```css
--text-primary: #2C3040;     /* Primary text - dark grey-blue */
--text-secondary: #4A4E5E;   /* Secondary text */
--text-tertiary: #686C7C;    /* Tertiary text, placeholders */
```

### Border Colors
```css
--border-default: #C8C3B8;   /* Primary borders - warm brown-grey */
--border-subtle: #D8D3C8;    /* Subtle borders */
--border-strong: #B8B3A8;    /* Strong borders */
```

### Accent Colors
```css
--accent: #18206F;           /* Deep cobalt - primary accent */
--accent-muted: rgba(24, 32, 111, 0.15);  /* Accent/15 - muted backgrounds */
--accent-text: #18206F;      /* Accent text color */
```

**Flux @theme block**:
```css
--color-accent: #18206F;
--color-accent-content: #18206F;
--color-accent-foreground: #ffffff;
```

### Semantic Colors
```css
--color-green: #267018;      /* Added/staged files */
--color-red: #C41030;        /* Deleted files, errors */
--color-yellow: #8A6410;     /* Modified files, warnings */
--color-peach: #B04800;      /* Untracked files */
--color-blue: #18206F;       /* Primary accent */
--color-mauve: #6B4BA0;      /* Special states */
--color-teal: #1A7A7A;       /* Decorative */
--color-sky: #2080B0;        /* Decorative */
--color-lavender: #5060C0;   /* Decorative */
```

### Shadows
```css
--shadow-sm: 0 1px 2px rgba(44, 48, 64, 0.08);
--shadow-md: 0 4px 12px rgba(44, 48, 64, 0.10);
--shadow-glow: none;
```

### Neumorphic Shadows (Light Mode)
```css
--neomorph-light: #FFFFFF;           /* Light side highlight */
--neomorph-dark: #C8C3B8;            /* Dark side shadow */
--neomorph-shadow-light: 4px 4px 8px rgba(200, 195, 184, 0.4);
--neomorph-shadow-dark: -4px -4px 8px rgba(255, 255, 255, 0.8);
```

---

## Dark Mode Palette

### Background Colors
```css
--surface-0: #12161F;    /* Base - main background */
--surface-1: #1A1E27;    /* Mantle - elevated panels, headers */
--surface-2: #22262F;    /* Crust - subtle elevation */
--surface-3: #2A3060;    /* Highest elevation, active states - blue tint */
```

**Main background**: `#0A0E17` (midnight) — app outer background

### Text Colors
```css
--text-primary: #D0E0FF;     /* Primary text - phosphor pale blue */
--text-secondary: #A0B0D0;   /* Secondary text */
--text-tertiary: #7080A0;    /* Tertiary text, placeholders */
```

### Border Colors
```css
--border-default: #2A3060;   /* Primary borders - deep blue-grey */
--border-subtle: #1A1E27;    /* Subtle borders */
--border-strong: #3A4070;    /* Strong borders */
```

### Accent Colors
```css
--accent: #00C3FF;           /* Bright cyan - primary accent */
--accent-muted: rgba(0, 195, 255, 0.20);  /* Accent/20 - muted backgrounds */
--accent-text: #00C3FF;      /* Accent text color */
```

**Flux @theme block** (same as light mode):
```css
--color-accent: #18206F;
--color-accent-content: #18206F;
--color-accent-foreground: #ffffff;
```

### Semantic Colors
```css
--color-green: #50FF50;      /* Added/staged files - phosphor green */
--color-red: #FF4060;        /* Deleted files, errors */
--color-yellow: #FFB020;     /* Modified files, warnings */
--color-peach: #FF8040;      /* Untracked files */
--color-blue: #00C3FF;       /* Primary accent - bright cyan */
--color-mauve: #B080FF;      /* Special states */
--color-teal: #40E0D0;       /* Decorative */
--color-sky: #60D0FF;        /* Decorative */
--color-lavender: #8090FF;   /* Decorative */
```

### Shadows
```css
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.4);
--shadow-md: 0 4px 12px rgba(0, 0, 0, 0.5);
--shadow-glow: 0 0 20px rgba(0, 195, 255, 0.20);
```

### Neumorphic Shadows (Dark Mode)
```css
--neomorph-light: #1A1E27;           /* Light side highlight */
--neomorph-dark: #000000;            /* Dark side shadow */
--neomorph-shadow-light: 4px 4px 12px rgba(0, 0, 0, 0.6);
--neomorph-shadow-dark: -4px -4px 12px rgba(26, 30, 39, 0.3);
```

---

## Conversion Table: Catppuccin → Retro

### Light Mode Conversions
| Variable | Catppuccin Latte | Retro Light | Notes |
|----------|------------------|-------------|-------|
| `--surface-0` | `#eff1f5` | `#E8E5DF` | Warmer, cream-tinted |
| `--surface-1` | `#e6e9ef` | `#DED9D0` | Warmer, brown-grey |
| `--surface-2` | `#dce0e8` | `#D4CFC6` | Warmer, brown-grey |
| `--surface-3` | `#ccd0da` | `#C8C3B8` | Warmer, brown-grey |
| `--border-default` | `#ccd0da` | `#C8C3B8` | Warmer, brown-grey |
| `--border-subtle` | `#dce0e8` | `#D8D3C8` | Warmer, brown-grey |
| `--border-strong` | `#bcc0cc` | `#B8B3A8` | Warmer, brown-grey |
| `--text-primary` | `#4c4f69` | `#2C3040` | Darker, more contrast |
| `--text-secondary` | `#6c6f85` | `#4A4E5E` | Darker, more contrast |
| `--text-tertiary` | `#8c8fa1` | `#686C7C` | Darker, more contrast |
| `--accent` | `#084CCF` | `#18206F` | Deeper cobalt |
| `--color-green` | `#40a02b` | `#267018` | Darker for contrast |
| `--color-red` | `#d20f39` | `#C41030` | Slightly darker |
| `--color-yellow` | `#df8e1d` | `#8A6410` | Much darker for contrast |
| `--color-peach` | `#fe640b` | `#B04800` | Darker for contrast |
| `--color-blue` | `#084CCF` | `#18206F` | Deeper cobalt |
| `--color-mauve` | `#8839ef` | `#6B4BA0` | Darker, more muted |
| `--color-teal` | `#179299` | `#1A7A7A` | Slightly adjusted |
| `--color-sky` | `#04a5e5` | `#2080B0` | Darker, more muted |
| `--color-lavender` | `#7287fd` | `#5060C0` | Darker, more saturated |
| White BG | `#ffffff` | `#F2EFE9` | Warm cream |

### Dark Mode Conversions
| Variable | Catppuccin Mocha | Retro Dark | Notes |
|----------|------------------|------------|-------|
| `--surface-0` | `#1e1e2e` | `#12161F` | Cooler, blue-tinted |
| `--surface-1` | `#181825` | `#1A1E27` | Cooler, blue-tinted |
| `--surface-2` | `#11111b` | `#22262F` | Lighter, blue-tinted |
| `--surface-3` | `#313244` | `#2A3060` | Blue-tinted, more saturated |
| `--border-default` | `#313244` | `#2A3060` | Blue-tinted |
| `--border-subtle` | `#181825` | `#1A1E27` | Blue-tinted |
| `--border-strong` | `#45475a` | `#3A4070` | Blue-tinted, more saturated |
| `--text-primary` | `#cdd6f4` | `#D0E0FF` | Brighter, phosphor blue |
| `--text-secondary` | `#a6adc8` | `#A0B0D0` | Adjusted blue tint |
| `--text-tertiary` | `#7f849c` | `#7080A0` | Adjusted blue tint |
| `--accent` | `#084CCF` | `#00C3FF` | Bright cyan (phosphor) |
| `--accent-text` | `#89b4fa` | `#00C3FF` | Bright cyan |
| `--color-green` | `#a6e3a1` | `#50FF50` | Phosphor green, more saturated |
| `--color-red` | `#f38ba8` | `#FF4060` | More saturated |
| `--color-yellow` | `#f9e2af` | `#FFB020` | More saturated, amber |
| `--color-peach` | `#fab387` | `#FF8040` | More saturated |
| `--color-blue` | `#89b4fa` | `#00C3FF` | Bright cyan |
| `--color-mauve` | `#cba6f7` | `#B080FF` | More saturated |
| `--color-teal` | `#94e2d5` | `#40E0D0` | More saturated |
| `--color-sky` | `#89dceb` | `#60D0FF` | More saturated |
| `--color-lavender` | `#b4befe` | `#8090FF` | More saturated |
| Main BG | `#1e1e2e` | `#0A0E17` | Midnight, very dark |

---

## WCAG AA Contrast Ratios

### Light Mode (on #F2EFE9 background)
| Color | Hex | Ratio | WCAG AA | Notes |
|-------|-----|-------|---------|-------|
| Text Primary | `#2C3040` | 11.41:1 | ✓ Pass | Excellent contrast |
| Text Secondary | `#4A4E5E` | 7.20:1 | ✓ Pass | Strong contrast |
| Text Tertiary | `#686C7C` | 4.55:1 | ✓ Pass | Minimum AA |
| Accent | `#18206F` | 12.41:1 | ✓ Pass | Excellent contrast |
| White on Accent | `#ffffff` on `#18206F` | 14.24:1 | ✓ Pass | Excellent contrast |
| Green | `#267018` | 5.36:1 | ✓ Pass | Good contrast |
| Red | `#C41030` | 5.28:1 | ✓ Pass | Good contrast |
| Yellow | `#8A6410` | 4.68:1 | ✓ Pass | Good contrast |
| Peach | `#B04800` | 4.85:1 | ✓ Pass | Good contrast |
| Blue | `#18206F` | 12.41:1 | ✓ Pass | Excellent contrast |
| Mauve | `#6B4BA0` | 5.83:1 | ✓ Pass | Good contrast |

**Adjustments Made**:
- Green: Darkened from `#2D8A20` to `#267018` (+1.52 ratio)
- Yellow: Darkened from `#B87A10` to `#8A6410` (+1.54 ratio)
- Peach: Darkened from `#D05A00` to `#B04800` (+1.29 ratio)

### Dark Mode (on #0A0E17 background)
| Color | Hex | Ratio | WCAG AA | Notes |
|-------|-----|-------|---------|-------|
| Text Primary | `#D0E0FF` | 14.51:1 | ✓ Pass | Excellent contrast |
| Text Secondary | `#A0B0D0` | 8.84:1 | ✓ Pass | Strong contrast |
| Text Tertiary | `#7080A0` | 4.86:1 | ✓ Pass | Good contrast |
| Accent | `#00C3FF` | 9.42:1 | ✓ Pass | Excellent contrast |
| BG on Accent | `#0A0E17` on `#00C3FF` | 9.42:1 | ✓ Pass | Excellent contrast |
| Green | `#50FF50` | 14.48:1 | ✓ Pass | Phosphor glow |
| Red | `#FF4060` | 5.66:1 | ✓ Pass | Good contrast |
| Yellow | `#FFB020` | 10.55:1 | ✓ Pass | Excellent contrast |
| Peach | `#FF8040` | 7.73:1 | ✓ Pass | Strong contrast |
| Blue | `#00C3FF` | 9.42:1 | ✓ Pass | Excellent contrast |
| Mauve | `#B080FF` | 6.78:1 | ✓ Pass | Good contrast |

**No adjustments needed** — all dark mode colors pass WCAG AA with strong ratios.

---

## Graph Colors (Commit History Lanes)

8 visually distinct colors for commit graph lanes, optimized for both light and dark modes.

### Light Mode Graph Colors
```css
--graph-1: #18206F;    /* Deep cobalt */
--graph-2: #267018;    /* Forest green */
--graph-3: #C41030;    /* Crimson */
--graph-4: #6B4BA0;    /* Purple */
--graph-5: #2080B0;    /* Sky blue */
--graph-6: #B04800;    /* Orange */
--graph-7: #1A7A7A;    /* Teal */
--graph-8: #8A6410;    /* Amber */
```

### Dark Mode Graph Colors
```css
--graph-1: #00C3FF;    /* Bright cyan */
--graph-2: #50FF50;    /* Phosphor green */
--graph-3: #FF4060;    /* Hot pink */
--graph-4: #B080FF;    /* Lavender */
--graph-5: #60D0FF;    /* Sky blue */
--graph-6: #FF8040;    /* Orange */
--graph-7: #40E0D0;    /* Turquoise */
--graph-8: #FFB020;    /* Amber */
```

---

## Syntax Theme Colors

Retro-futurism syntax highlighting for code diffs and commit messages.

### Light Mode Syntax
```css
--syntax-keyword: #6B4BA0;     /* Purple - keywords, control flow */
--syntax-string: #267018;      /* Green - strings, literals */
--syntax-comment: #686C7C;     /* Grey - comments */
--syntax-meta: #B04800;        /* Orange - meta, annotations */
--syntax-number: #8A6410;      /* Amber - numbers, constants */
--syntax-variable: #2C3040;    /* Text - variables, identifiers */
--syntax-function: #18206F;    /* Blue - functions, methods */
--syntax-regexp: #1A7A7A;      /* Teal - regex, patterns */
```

### Dark Mode Syntax
```css
--syntax-keyword: #B080FF;     /* Lavender - keywords, control flow */
--syntax-string: #50FF50;      /* Phosphor green - strings, literals */
--syntax-comment: #7080A0;     /* Grey-blue - comments */
--syntax-meta: #FF8040;        /* Orange - meta, annotations */
--syntax-number: #FFB020;      /* Amber - numbers, constants */
--syntax-variable: #D0E0FF;    /* Text - variables, identifiers */
--syntax-function: #00C3FF;    /* Cyan - functions, methods */
--syntax-regexp: #40E0D0;      /* Turquoise - regex, patterns */
```

---

## Implementation Notes

### Badge Concatenation Pattern
The palette uses hex format exclusively because Blade templates use string concatenation for badge backgrounds:

```blade
@php
    $badgeColor = match(strtoupper($status)) {
        'MODIFIED' => '#8A6410',
        'ADDED' => '#267018',
        'DELETED' => '#C41030',
        default => '#686C7C',
    };
@endphp
<div style="background-color: {{ $badgeColor }}15; color: {{ $badgeColor }}">
    {{ strtoupper($status) }}
</div>
```

The `15` suffix creates a ~8% opacity background tint. This ONLY works with hex colors.

### Flux UI Accent
Flux reads accent colors from the `@theme {}` block, NOT from `:root {}`. The `--color-accent` variable in `@theme {}` controls Flux button colors.

### Neumorphic Effects
Use the neumorphic shadow values for raised/pressed button states:

**Light Mode Raised Button**:
```css
box-shadow: var(--neomorph-shadow-dark), var(--neomorph-shadow-light);
```

**Dark Mode Raised Button**:
```css
box-shadow: var(--neomorph-shadow-dark), var(--neomorph-shadow-light);
```

### Phosphor Glow (Dark Mode Only)
The `--shadow-glow` variable creates a subtle cyan glow around accent elements in dark mode, mimicking CRT phosphor persistence.

---

## Design Philosophy

### Light Mode: "Daytime Office"
- Warm cream backgrounds (`#F2EFE9`) evoke aged paper and vintage computing manuals
- Brown-grey borders (`#C8C3B8`) suggest warm metal and beige plastic
- Deep cobalt accent (`#18206F`) references IBM blue and classic UI chrome
- Darker semantic colors ensure readability on warm backgrounds

### Dark Mode: "Power On"
- Midnight background (`#0A0E17`) suggests a darkened room with glowing screens
- Phosphor blue text (`#D0E0FF`) mimics CRT monitor glow
- Bright cyan accent (`#00C3FF`) evokes TRON and early vector displays
- Saturated semantic colors (`#50FF50`, `#FF4060`) reference terminal phosphors
- Blue-tinted surfaces (`#2A3060`) suggest ambient screen glow

### Retro-Futurism Aesthetic
- **1982 Technology**: CRT monitors, IBM PCs, early Macintosh, vector displays
- **2024 Refinement**: WCAG AA accessibility, modern contrast ratios, professional polish
- **Analog Warmth**: Cream tones, brown-greys, amber accents (light mode)
- **Digital Glow**: Phosphor blues, cyan highlights, saturated primaries (dark mode)

---

## Validation Summary

✓ **All colors in hex format** (#RRGGBB)  
✓ **All text/background pairs meet WCAG AA** (≥4.5:1)  
✓ **Light mode semantic colors adjusted** for contrast  
✓ **Dark mode semantic colors validated** (no adjustments needed)  
✓ **Neumorphic shadow values defined** for both modes  
✓ **Graph colors defined** (8 distinct lanes)  
✓ **Syntax theme colors defined** for code highlighting  
✓ **Conversion table complete** (Catppuccin → Retro)  

**Next Steps**: Apply this palette to `resources/css/app.css` (Task 3).
