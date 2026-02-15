
## Round 6: Header Layout Philosophy
**Date**: 2026-02-15

### Decision: Linear Header Layout Over Nested Groups
**Context**: Original header used `justify-between` with two nested flex groups (left/right)

**Choice**: Restructured to linear left-to-right layout with explicit spacer

**Rationale**:
1. **Clarity**: Linear layout is more explicit and easier to understand
2. **Flexibility**: Adding/removing elements doesn't require rethinking group membership
3. **Drag Region Control**: Traffic light spacer explicitly reserves space for macOS controls
4. **Visual Order**: Clear left-to-right progression matches user mental model

**Layout Order**:
```
[sidebar-toggle] [traffic-light-spacer] [repo-switcher] [branch-manager] [flex-spacer] [push] [pull] [fetch]
```

### Decision: Remove Overflow Menu
**Context**: Overflow menu (⋯) provided access to Force Push and Fetch All

**Choice**: Removed button entirely, kept modals in template for potential future use

**Rationale**:
1. **Header Simplicity**: Three main actions (push/pull/fetch) are sufficient for common workflows
2. **Discoverability**: Overflow menus hide functionality—better to expose elsewhere
3. **Visual Weight**: Removing the extra button reduces header clutter
4. **Future-Proof**: Modals remain in template, can be triggered via keyboard shortcuts or other UI elements

### Decision: Phosphor Light Variant for All Header Icons
**Context**: Multiple icon weight options available (light, regular, bold, thin)

**Choice**: Used `-light` variant consistently for all header icons

**Rationale**:
1. **Visual Harmony**: Consistent stroke weight across all header icons
2. **Professional Aesthetic**: Lighter weight feels more refined, less aggressive
3. **Small UI Context**: Light icons work better at small sizes (w-4 h-4)
4. **Brand Consistency**: Matches the minimalist, clean aesthetic of the app

