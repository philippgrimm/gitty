# Staging Panel Polish: Dots, Truncation & Resizable Width

## TL;DR

> **Quick Summary**: Polish the staging panel file list with smaller status dots, proper filename truncation that accounts for hover action buttons, and a mouse-draggable panel width with localStorage persistence.
> 
> **Deliverables**:
> - Smaller status dots (8px instead of 10px) across flat view + tree view
> - Proper filename truncation with ellipsis, accounting for hover button space
> - Draggable resize handle on staging panel right edge (min 200px, max 50%)
> - Width persistence via localStorage
> - Updated tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 3 waves
> **Critical Path**: Tasks 1+2+3 (parallel) → Task 4 (tests)

---

## Context

### Original Request
User wants four improvements to the staging panel (changes list):
1. Status marker dots are too large — make them a bit smaller
2. File names should truncate with ellipsis when too long
3. Hover action buttons must be considered for truncation width
4. Panel width should be resizable via mouse drag

### Interview Summary
**Key Discussions**:
- Dot size: Reduce from `w-2.5 h-2.5` (10px) to `w-2 h-2` (8px)
- Resize persistence: Save to localStorage, restore on app start
- Width limits: Min 200px, Max 50% of available container width

**Research Findings**:
- Status dots appear in 3 locations: `staging-panel.blade.php` (lines 104, 163) and `file-tree.blade.php` (line 52)
- `truncate` class already exists on filename `<div>`, but `<flux:tooltip>` wrapper may prevent proper truncation in flex context (missing `min-w-0`)
- Hover buttons already use `opacity-0 group-hover:opacity-100` — elements are always in the DOM but invisible, so they already reserve space
- Panel width is fixed `w-1/3` in `app-layout.blade.php` line 69
- The panel sits between sidebar (250px/0px) and diff viewer (flex-1)

### Metis Review
**Identified Gaps** (addressed):
- Resize handle visual design → Auto-resolved: Invisible handle with cursor change + subtle accent highlight on hover (VS Code style)
- Truncation method → Auto-resolved: Buttons always reserve space (already do via opacity-0), fix tooltip wrapper truncation
- Persistence scope → Auto-resolved: Global (single localStorage key)
- Edge cases → Auto-resolved: Fall back to default on invalid localStorage, respect max 50% on window resize

---

## Work Objectives

### Core Objective
Polish the staging panel file list to feel more refined (smaller dots, proper truncation) and add drag-to-resize for the panel width.

### Concrete Deliverables
- `staging-panel.blade.php`: Updated dot size (2 locations), fixed truncation layout
- `file-tree.blade.php`: Updated dot size (1 location), fixed truncation layout
- `app-layout.blade.php`: Resizable panel with Alpine.js drag handler
- `AGENTS.md`: Updated dot size reference
- `StagingPanelTest.php`: Updated/added test for dot size
- `AppLayoutTest.php`: Test for resize panel rendering (if applicable)

### Definition of Done
- [ ] Status dots render at 8px (w-2 h-2) in flat view and tree view
- [ ] Long filenames truncate with ellipsis (`...`)
- [ ] Filename truncation accounts for hover button space (no layout shift on hover)
- [ ] Staging panel right edge is draggable to resize
- [ ] Panel width persists in localStorage across app restarts
- [ ] Panel width is clamped to min 200px, max 50% of container
- [ ] All existing tests pass
- [ ] New/updated tests pass

### Must Have
- Dot size `w-2 h-2` (8px) in all 3 template locations
- Filename truncation with `...` when name exceeds available width
- No layout shift when hover buttons appear (buttons reserve space even when invisible)
- Drag handle between staging panel and diff viewer
- Min/max width constraints (200px / 50%)
- localStorage persistence with key `gitty-panel-width`
- Fallback to ~33% default when no localStorage value exists

### Must NOT Have (Guardrails)
- NO changes to status dot colors — only size
- NO changes to hover button icons, actions, or behavior
- NO visible/styled resize handle — cursor change + subtle hover highlight only
- NO resize animation/transitions during drag (instant feedback)
- NO changes to sidebar or diff viewer panels (they adapt automatically via flex)
- NO new Livewire component properties for panel width (client-side only via Alpine.js)
- NO changes to git operations (stage/unstage/discard)
- NO changes to commit panel layout or height
- NO per-repository width persistence — single global value
- NO tooltips on truncated filenames beyond existing `<flux:tooltip>` (already present)

---

## Verification Strategy

> **UNIVERSAL RULE: ZERO HUMAN INTERVENTION**
>
> ALL tasks in this plan MUST be verifiable WITHOUT any human action.

### Test Decision
- **Infrastructure exists**: YES (Pest + Livewire testing)
- **Automated tests**: Tests-after (update/add after implementation)
- **Framework**: Pest v4 with Livewire::test()

### Agent-Executed QA Scenarios (MANDATORY — ALL tasks)

Every task includes QA scenarios using Playwright (via playwright skill) since this is a visual UI change best verified in browser.

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately):
├── Task 1: Reduce status dot size (no dependencies)
├── Task 2: Fix filename truncation + hover button space (no dependencies)
└── Task 3: Add resizable panel width (no dependencies)

Wave 2 (After Wave 1):
└── Task 4: Update tests + AGENTS.md

Critical Path: Any Wave 1 task → Task 4
Parallel Speedup: ~50% faster than sequential
```

### Dependency Matrix

| Task | Depends On | Blocks | Can Parallelize With |
|------|------------|--------|---------------------|
| 1 | None | 4 | 2, 3 |
| 2 | None | 4 | 1, 3 |
| 3 | None | 4 | 1, 2 |
| 4 | 1, 2, 3 | None | None (final) |

### Agent Dispatch Summary

| Wave | Tasks | Recommended Agents |
|------|-------|-------------------|
| 1 | 1, 2, 3 | `task(category="quick", load_skills=["tailwindcss-development", "livewire-development"], ...)` for each |
| 2 | 4 | `task(category="quick", load_skills=["pest-testing", "livewire-development"], ...)` |

---

## TODOs

- [x] 1. Reduce Status Dot Size

  **What to do**:
  - Change `w-2.5 h-2.5` to `w-2 h-2` in exactly 3 locations:
    1. `staging-panel.blade.php` line 104 — staged files flat view
    2. `staging-panel.blade.php` line 163 — unstaged files flat view
    3. `file-tree.blade.php` line 52 — tree view (both staged and unstaged)
  - Keep `rounded-full shrink-0` and all color classes unchanged

  **Must NOT do**:
  - Do NOT change dot colors
  - Do NOT change dot positioning or gap spacing
  - Do NOT change anything else in the file item layout

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple CSS class replacement in 3 known locations
  - **Skills**: [`tailwindcss-development`]
    - `tailwindcss-development`: Tailwind size class change

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: Task 4
  - **Blocked By**: None

  **References**:

  **Pattern References** (exact code to change):
  - `resources/views/livewire/staging-panel.blade.php:104` — Staged files dot: `<div class="w-2.5 h-2.5 rounded-full shrink-0 {{ match(...) }}">` → change to `w-2 h-2`
  - `resources/views/livewire/staging-panel.blade.php:163` — Unstaged files dot: same pattern, same change
  - `resources/views/components/file-tree.blade.php:52` — Tree view dot: same pattern, same change

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Status dots render at 8px in flat view
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running (php artisan serve --port=8321), repo with staged + unstaged files open
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: .group element in staging panel visible (timeout: 10s)
      3. Evaluate: document.querySelector('.group .rounded-full').offsetWidth
      4. Assert: offsetWidth equals 8
      5. Evaluate: document.querySelector('.group .rounded-full').offsetHeight
      6. Assert: offsetHeight equals 8
      7. Screenshot: .sisyphus/evidence/task-1-dot-size.png
    Expected Result: Dots are 8x8px (w-2 h-2)
    Evidence: .sisyphus/evidence/task-1-dot-size.png
  ```

  **Commit**: YES (groups with Tasks 2, 3)
  - Message: `design(staging): reduce status dot size to 8px`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`

---

- [x] 2. Fix Filename Truncation + Hover Button Space

  **What to do**:
  - Ensure filenames properly truncate with ellipsis when they exceed available width
  - The key issue: `<flux:tooltip>` wrapping the filename div may not have `min-w-0`, preventing truncation in the flex container
  - Fix approach for all 3 file item locations (staged flat, unstaged flat, tree view):
    1. Ensure the `<flux:tooltip>` element has `min-w-0` class so it can shrink in the flex container (or wrap the tooltip differently)
    2. Ensure the button area always reserves its space (buttons already use `opacity-0` so they're in the DOM — verify this is working correctly)
    3. Add `shrink-0` to the button container/wrapper if not present, to prevent buttons from being compressed
  - The filename area (`<div class="text-sm truncate ...">`) should be the only element that shrinks

  **Staged files layout** (staging-panel.blade.php lines 90-123):
  ```
  Current:
  div.group.flex.justify-between.gap-3
    div.flex.gap-2.5.flex-1.min-w-0       ← left (dot + name)
      div.w-2.h-2.shrink-0                ← dot
      flux:tooltip                         ← ⚠️ may not have min-w-0
        div.truncate                       ← filename
    flux:tooltip                           ← button tooltip
      flux:button.opacity-0               ← single unstage button
  ```

  Fix: Add `min-w-0` (and optionally `flex-1`) to the filename `<flux:tooltip>` wrapper. Add `shrink-0` to the button `<flux:tooltip>` wrapper.

  **Unstaged files layout** (staging-panel.blade.php lines 148-194):
  ```
  Current:
  div.group.flex.justify-between.gap-3
    div.flex.gap-2.5.flex-1.min-w-0       ← left (dot + name)
      div.w-2.h-2.shrink-0                ← dot
      flux:tooltip                         ← ⚠️ may not have min-w-0
        div.truncate                       ← filename
    div.flex.gap-1.opacity-0              ← buttons container (2 buttons)
      flux:tooltip > flux:button           ← stage
      flux:tooltip > flux:button           ← discard
  ```

  Fix: Same tooltip fix. Add `shrink-0` to the buttons container div.

  **Tree view layout** (file-tree.blade.php lines 33-95):
  Same pattern as flat view — apply identical fixes.

  **Must NOT do**:
  - Do NOT remove or replace `<flux:tooltip>` — keep tooltip functionality
  - Do NOT change hover button visibility behavior (keep opacity-0/100 pattern)
  - Do NOT change button icons, sizes, or actions
  - Do NOT add additional tooltip wrappers or title attributes

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Layout class adjustments in 3 template locations, no logic changes
  - **Skills**: [`tailwindcss-development`, `fluxui-development`, `livewire-development`]
    - `tailwindcss-development`: Flex layout and overflow utilities
    - `fluxui-development`: Understanding Flux tooltip component rendering and class passthrough
    - `livewire-development`: Blade template context

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: Task 4
  - **Blocked By**: None

  **References**:

  **Pattern References** (exact code to modify):
  - `resources/views/livewire/staging-panel.blade.php:105-109` — Staged file tooltip + truncation:
    ```blade
    <flux:tooltip :content="$file['path']">
        <div class="text-sm truncate text-[#5c5f77] group-hover:text-[#4c4f69] transition-colors">
            {{ basename($file['path']) }}
        </div>
    </flux:tooltip>
    ```
    Add `class="min-w-0"` to `<flux:tooltip>` (or wrap differently if Flux doesn't support class passthrough)

  - `resources/views/livewire/staging-panel.blade.php:111-123` — Staged file button (single button wrapped in tooltip):
    ```blade
    <flux:tooltip content="Unstage">
        <flux:button ... class="opacity-0 group-hover:opacity-100 transition-opacity">
    ```
    Ensure this tooltip+button combo has `shrink-0` behavior

  - `resources/views/livewire/staging-panel.blade.php:164-168` — Unstaged file tooltip + truncation: Same fix as staged
  - `resources/views/livewire/staging-panel.blade.php:170` — Unstaged file buttons container:
    ```blade
    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
    ```
    Add `shrink-0` to this div

  - `resources/views/components/file-tree.blade.php:53-57` — Tree view tooltip + truncation: Same fix
  - `resources/views/components/file-tree.blade.php:60-93` — Tree view buttons: Same `shrink-0` fixes

  **Documentation References**:
  - `AGENTS.md` section "Hover & Interaction States" — hover behavior patterns
  - `AGENTS.md` section "Flux UI Integration" — Flux component usage

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Long filename truncates with ellipsis in staging panel
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo open with a file that has a long name (20+ chars)
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: file list items visible in staging panel (timeout: 10s)
      3. Evaluate: Check if any filename element has text-overflow: ellipsis computed style
      4. Evaluate: Compare filename element scrollWidth vs clientWidth — scrollWidth > clientWidth means truncation is active
      5. Assert: For a long filename, scrollWidth > clientWidth (text is truncated)
      6. Screenshot: .sisyphus/evidence/task-2-truncation.png
    Expected Result: Long filenames show "..." and don't overflow the panel
    Evidence: .sisyphus/evidence/task-2-truncation.png

  Scenario: No layout shift when hover buttons appear
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo open with unstaged changes
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: file list items visible (timeout: 10s)
      3. Select first file item in "Changes" section
      4. Evaluate: Record the file item's total offsetWidth before hover
      5. Hover: Move mouse over the file item
      6. Wait for: 200ms (transition completes)
      7. Evaluate: Record the file item's total offsetWidth after hover
      8. Assert: offsetWidth before === offsetWidth after (no layout shift)
      9. Assert: Filename element clientWidth is same before and after hover
      10. Screenshot: .sisyphus/evidence/task-2-no-layout-shift.png
    Expected Result: File item width stays constant, buttons appear without pushing content
    Evidence: .sisyphus/evidence/task-2-no-layout-shift.png
  ```

  **Commit**: YES (groups with Tasks 1, 3)
  - Message: `design(staging): fix filename truncation and hover button space`
  - Files: `resources/views/livewire/staging-panel.blade.php`, `resources/views/components/file-tree.blade.php`

---

- [x] 3. Add Resizable Panel Width

  **What to do**:
  - Replace the fixed `w-1/3` on the staging+commit panel container with a dynamic width controlled by Alpine.js
  - Add an invisible drag handle between the staging panel and diff viewer
  - Implement mouse drag interaction to resize the panel
  - Persist width to localStorage, restore on load
  - Clamp width to min 200px, max 50% of container

  **Implementation approach**:

  In `app-layout.blade.php`, the current structure (lines 68-81):
  ```blade
  <div class="flex-1 flex overflow-hidden">
      <div class="w-1/3 flex flex-col border-r border-[#ccd0da] overflow-hidden">
          <div class="flex-1 overflow-hidden">
              @livewire('staging-panel', ...)
          </div>
          <div class="h-64 border-t border-[#dce0e8] overflow-hidden">
              @livewire('commit-panel', ...)
          </div>
      </div>
      <div class="flex-1 overflow-hidden">
          @livewire('diff-viewer', ...)
      </div>
  </div>
  ```

  Change to:
  ```blade
  <div class="flex-1 flex overflow-hidden"
       x-data="{
           panelWidth: null,
           isDragging: false,
           startX: 0,
           startWidth: 0,
           init() {
               const saved = localStorage.getItem('gitty-panel-width');
               if (saved && !isNaN(parseInt(saved))) {
                   this.panelWidth = parseInt(saved);
               }
           },
           get effectiveWidth() {
               if (this.panelWidth) return this.panelWidth;
               return Math.round(this.$el.offsetWidth / 3);
           },
           startDrag(e) {
               this.isDragging = true;
               this.startX = e.clientX;
               this.startWidth = this.effectiveWidth;
               document.body.style.cursor = 'col-resize';
               document.body.style.userSelect = 'none';
           },
           onDrag(e) {
               if (!this.isDragging) return;
               const delta = e.clientX - this.startX;
               const maxWidth = Math.round(this.$el.offsetWidth * 0.5);
               this.panelWidth = Math.min(Math.max(this.startWidth + delta, 200), maxWidth);
           },
           stopDrag() {
               if (!this.isDragging) return;
               this.isDragging = false;
               document.body.style.cursor = '';
               document.body.style.userSelect = '';
               if (this.panelWidth) {
                   localStorage.setItem('gitty-panel-width', this.panelWidth.toString());
               }
           }
       }"
       @mousemove.window="onDrag($event)"
       @mouseup.window="stopDrag()"
  >
      <!-- Staging + Commit Panel -->
      <div class="flex flex-col overflow-hidden"
           :style="'width: ' + effectiveWidth + 'px'"
      >
          <div class="flex-1 overflow-hidden">
              @livewire('staging-panel', ...)
          </div>
          <div class="h-64 border-t border-[#dce0e8] overflow-hidden">
              @livewire('commit-panel', ...)
          </div>
      </div>

      <!-- Resize Handle -->
      <div @mousedown.prevent="startDrag($event)"
           class="w-[5px] flex-shrink-0 cursor-col-resize relative group/resize"
      >
          <div class="absolute inset-y-0 left-[2px] w-px bg-[#ccd0da] group-hover/resize:bg-[#084CCF] transition-colors"
               :class="isDragging ? 'bg-[#084CCF]' : ''"
          ></div>
      </div>

      <!-- Diff Viewer -->
      <div class="flex-1 overflow-hidden">
          @livewire('diff-viewer', ...)
      </div>
  </div>
  ```

  **Key design decisions**:
  - Resize handle: 5px wide invisible div with a 1px centered border line
  - On hover: border line turns accent blue (`#084CCF`)
  - During drag: `cursor: col-resize` on entire document, `user-select: none` to prevent text selection
  - On drag end: save to localStorage
  - Default: `Math.round(containerWidth / 3)` — matches current `w-1/3` behavior
  - localStorage key: `gitty-panel-width`
  - Invalid/missing localStorage: Falls back to 1/3 of container width
  - Remove `border-r` from the staging panel div — the resize handle's inner line replaces it
  - Also remove the `border-r` from the staging-panel.blade.php root div (line 8): `border-r border-[#ccd0da]` since the handle provides the visual border now

  **Must NOT do**:
  - Do NOT add Livewire properties for panel width — this is purely client-side Alpine.js state
  - Do NOT add transition/animation on the panel width during drag (must be instant)
  - Do NOT add visible handle styling beyond cursor change + 1px line highlight
  - Do NOT change sidebar toggle behavior or sidebar width
  - Do NOT change commit panel height (h-64)
  - Do NOT touch diff viewer internals
  - Do NOT store width per-repository — single global value

  **Recommended Agent Profile**:
  - **Category**: `unspecified-low`
    - Reason: Alpine.js drag handler with DOM manipulation, not pure visual work
  - **Skills**: [`tailwindcss-development`, `livewire-development`]
    - `tailwindcss-development`: Flex layout, sizing utilities
    - `livewire-development`: Understanding Livewire component rendering in Alpine.js context

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: Task 4
  - **Blocked By**: None

  **References**:

  **Pattern References** (exact code to modify):
  - `resources/views/livewire/app-layout.blade.php:68-81` — The entire panel layout section to restructure:
    ```blade
    <div class="flex-1 flex overflow-hidden">
        <div class="w-1/3 flex flex-col border-r border-[#ccd0da] overflow-hidden">
            ...staging + commit panels...
        </div>
        <div class="flex-1 overflow-hidden">
            ...diff viewer...
        </div>
    </div>
    ```
    Replace `w-1/3` with Alpine.js dynamic width, add drag handle between panels

  - `resources/views/livewire/app-layout.blade.php:1-9` — Existing Alpine.js patterns: keyboard shortcuts use `@keydown.window`, which is the same pattern as `@mousemove.window` and `@mouseup.window` for drag

  - `resources/views/livewire/staging-panel.blade.php:8` — Remove `border-r border-[#ccd0da]` from staging panel root div since the resize handle now provides the visual border:
    ```blade
    class="h-full flex flex-col bg-white text-[#4c4f69] font-mono border-r border-[#ccd0da]"
    ```
    Change to:
    ```blade
    class="h-full flex flex-col bg-white text-[#4c4f69] font-mono"
    ```

  **Documentation References**:
  - `AGENTS.md` section "Header Layout" — existing `-webkit-app-region` patterns
  - `AGENTS.md` section "Color System" — accent color `#084CCF` for hover highlight

  **Acceptance Criteria**:

  **Agent-Executed QA Scenarios:**

  ```
  Scenario: Panel can be resized by dragging the handle
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running on localhost:8321, repo open
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: staging panel visible (timeout: 10s)
      3. Evaluate: Record initial staging panel width (offsetWidth)
      4. Locate: The resize handle element (cursor: col-resize)
      5. Perform mouse drag: mousedown on handle → mousemove 100px to the right → mouseup
      6. Evaluate: Record new staging panel width
      7. Assert: New width is approximately initialWidth + 100px (±10px tolerance)
      8. Screenshot: .sisyphus/evidence/task-3-resize-wider.png
    Expected Result: Panel width increases by ~100px after dragging right
    Evidence: .sisyphus/evidence/task-3-resize-wider.png

  Scenario: Panel respects minimum width of 200px
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo open
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: staging panel visible (timeout: 10s)
      3. Perform mouse drag: mousedown on handle → mousemove far left (e.g., -500px) → mouseup
      4. Evaluate: Record staging panel width
      5. Assert: Width is >= 200px
      6. Screenshot: .sisyphus/evidence/task-3-min-width.png
    Expected Result: Panel cannot be shrunk below 200px
    Evidence: .sisyphus/evidence/task-3-min-width.png

  Scenario: Panel respects maximum width of 50%
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo open
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: staging panel visible (timeout: 10s)
      3. Evaluate: Record container width (parent element)
      4. Perform mouse drag: mousedown on handle → mousemove far right (e.g., +500px) → mouseup
      5. Evaluate: Record staging panel width
      6. Assert: Width is <= containerWidth * 0.5
      7. Screenshot: .sisyphus/evidence/task-3-max-width.png
    Expected Result: Panel cannot exceed 50% of container width
    Evidence: .sisyphus/evidence/task-3-max-width.png

  Scenario: Panel width persists across page reload
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo open
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: staging panel visible (timeout: 10s)
      3. Perform mouse drag: resize panel to ~400px wide
      4. Evaluate: Record exact panel width after drag
      5. Evaluate: Read localStorage.getItem('gitty-panel-width')
      6. Assert: localStorage value matches panel width
      7. Reload page
      8. Wait for: staging panel visible (timeout: 10s)
      9. Evaluate: Record panel width after reload
      10. Assert: Width after reload matches width before reload (±5px tolerance)
      11. Screenshot: .sisyphus/evidence/task-3-persistence.png
    Expected Result: Panel width is restored from localStorage after reload
    Evidence: .sisyphus/evidence/task-3-persistence.png

  Scenario: Resize handle shows visual feedback on hover
    Tool: Playwright (playwright skill)
    Preconditions: Dev server running, repo open
    Steps:
      1. Navigate to: http://localhost:8321
      2. Wait for: staging panel visible (timeout: 10s)
      3. Locate: Resize handle (cursor: col-resize)
      4. Evaluate: Record the inner border line's background-color before hover
      5. Hover: Move mouse over resize handle
      6. Wait for: 200ms
      7. Evaluate: Record the inner border line's background-color after hover
      8. Assert: Color changed to accent blue (#084CCF or rgb(8, 76, 207))
      9. Screenshot: .sisyphus/evidence/task-3-handle-hover.png
    Expected Result: Border line turns blue on hover
    Evidence: .sisyphus/evidence/task-3-handle-hover.png
  ```

  **Commit**: YES (groups with Tasks 1, 2)
  - Message: `feat(staging): add resizable panel width with drag handle`
  - Files: `resources/views/livewire/app-layout.blade.php`, `resources/views/livewire/staging-panel.blade.php`

---

- [x] 4. Update Tests & AGENTS.md

  **What to do**:
  - Update `AGENTS.md` dot size reference from `w-2.5 h-2.5` to `w-2 h-2`
  - Add Pest feature test verifying dot size classes in rendered templates
  - Run existing tests to verify nothing is broken
  - Run `vendor/bin/pint --dirty --format agent` to fix code formatting

  **AGENTS.md update**:
  In the "Status Indicators > File Status Dots" section, change:
  ```
  - Size: `w-2.5 h-2.5 rounded-full`
  ```
  to:
  ```
  - Size: `w-2 h-2 rounded-full`
  ```

  **Test to add** (in `tests/Feature/Livewire/StagingPanelTest.php`):
  ```php
  test('staging panel renders 8px status dots', function () {
      Process::fake([
          'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithMixedChanges()),
      ]);

      Livewire::test(StagingPanel::class, ['repoPath' => $this->testRepoPath])
          ->assertSeeHtml('w-2 h-2 rounded-full')
          ->assertDontSeeHtml('w-2.5 h-2.5');
  });
  ```

  **Must NOT do**:
  - Do NOT delete existing tests
  - Do NOT change test fixtures or mocks
  - Do NOT add browser tests for resize (Alpine.js client-side behavior — verified via QA scenarios)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Small test addition + docs update
  - **Skills**: [`pest-testing`, `livewire-development`]
    - `pest-testing`: Pest test patterns and assertions
    - `livewire-development`: Livewire::test() assertions

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Sequential (Wave 2)
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 3

  **References**:

  **Pattern References** (existing tests to follow):
  - `tests/Feature/Livewire/StagingPanelTest.php:17-28` — Test pattern: Process::fake + Livewire::test + assertSee
  - `tests/Feature/Livewire/StagingPanelTest.php:135-142` — Empty state test pattern for assertSee usage

  **Documentation References**:
  - `AGENTS.md` lines containing `w-2.5 h-2.5` — Update dot size reference

  **Test References**:
  - `tests/Mocks/GitOutputFixtures.php` — Git output fixtures used by all staging panel tests

  **Acceptance Criteria**:

  ```
  Scenario: All tests pass
    Tool: Bash
    Steps:
      1. Run: php artisan test --compact tests/Feature/Livewire/StagingPanelTest.php
      2. Assert: Exit code 0
      3. Assert: Output contains "PASS" for the new dot size test
      4. Run: php artisan test --compact
      5. Assert: Exit code 0 (no regressions)
    Expected Result: All tests pass including the new dot size test

  Scenario: Code formatting passes
    Tool: Bash
    Steps:
      1. Run: vendor/bin/pint --dirty --format agent
      2. Assert: No formatting errors or all auto-fixed
    Expected Result: Code matches project style
  ```

  **Commit**: YES
  - Message: `test(staging): add dot size verification and update AGENTS.md`
  - Files: `tests/Feature/Livewire/StagingPanelTest.php`, `AGENTS.md`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1, 2, 3 (grouped) | `design(staging): polish dots, truncation, and resizable panel` | staging-panel.blade.php, file-tree.blade.php, app-layout.blade.php | Visual verification via Playwright |
| 4 | `test(staging): add dot size verification and update AGENTS.md` | StagingPanelTest.php, AGENTS.md | `php artisan test --compact` |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact tests/Feature/Livewire/StagingPanelTest.php  # Expected: all tests PASS
php artisan test --compact  # Expected: no regressions, all PASS
vendor/bin/pint --dirty --format agent  # Expected: clean or auto-fixed
```

### Final Checklist
- [ ] Status dots are 8px (w-2 h-2) in flat view and tree view
- [ ] Long filenames show "..." truncation
- [ ] No layout shift when hover buttons appear/disappear
- [ ] Staging panel is resizable via drag handle
- [ ] Width persists in localStorage across reloads
- [ ] Min 200px and max 50% constraints enforced
- [ ] Resize handle shows accent blue on hover
- [ ] All existing tests still pass
- [ ] New dot size test passes
- [ ] AGENTS.md updated with correct dot size
- [ ] Code formatted with Pint
