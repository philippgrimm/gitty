# Word-Level Diff Highlighting

## TL;DR

> **Quick Summary**: Add inline word-level (character-level) diff highlighting within changed lines, so users can immediately see which specific words or characters changed within a modified line, not just that the entire line changed.
> 
> **Deliverables**:
> - Word-level diff algorithm (PHP or JS) comparing paired addition/deletion lines
> - Updated diff rendering in `diff-viewer.blade.php` with inline `<span>` highlights
> - CSS classes for word-level addition/deletion highlights
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Original Request
Currently the diff viewer highlights entire lines as added or deleted. Users need to see which specific words or characters changed within a line to quickly understand modifications.

### Research Findings
- `DiffViewer.php:402-434` has `getSplitLines()` which pairs deletions with additions — perfect anchor point for word diffing
- `HunkLine` DTO has `type` (context/addition/deletion) and `content` — word diff needs to annotate content with highlight ranges
- `resources/css/app.css` has `.diff-line-addition` and `.diff-line-deletion` with `rgba()` backgrounds — word highlights need stronger variants
- Two approaches: (a) PHP-side word diffing adding highlight metadata to HunkLine data, or (b) JavaScript-side diffing in the browser using Alpine.js
- PHP-side is preferred for consistency with server-rendered Livewire approach
- Standard algorithm: longest common subsequence (LCS) on word tokens split by whitespace/punctuation

---

## Work Objectives

### Core Objective
Highlight specific changed words/characters within modified lines in the diff viewer.

### Concrete Deliverables
- `app/Services/Git/DiffService.php` — new `computeWordDiff(string $oldLine, string $newLine): array` method
- Updated diff data pipeline to include word-level highlight ranges
- Updated `diff-viewer.blade.php` with `<span>` elements for word highlights
- New CSS classes: `.diff-word-addition`, `.diff-word-deletion`
- Tests for word diff algorithm

### Must Have
- Word-level highlights for paired addition/deletion lines
- Works in both unified and split diff view modes
- Visual distinction between line-level and word-level highlighting (stronger color for words)

### Must NOT Have (Guardrails)
- No character-level diffing of binary files
- No external JS diff library — keep it PHP-side
- Don't break existing hunk staging/unstaging functionality
- No syntax-aware diffing (just word/token boundaries)

---

## Verification Strategy

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after
- **Framework**: Pest

---

## Execution Strategy

```
Wave 1 (Algorithm + CSS):
├── Task 1: Implement word diff algorithm in DiffService [deep]
├── Task 2: Add CSS classes for word-level highlights [quick]

Wave 2 (Integration + tests):
├── Task 3: Update diff-viewer Blade template to render word highlights [visual-engineering]
├── Task 4: Pest tests for word diff algorithm [unspecified-high]
```

---

## TODOs

- [ ] 1. Implement word diff algorithm in DiffService

  **What to do**:
  - Add `computeWordDiff(string $oldContent, string $newContent): array` to `DiffService`
  - Tokenize lines by whitespace and punctuation boundaries
  - Use LCS (longest common subsequence) algorithm to find common tokens
  - Return arrays of segments: `[['text' => '...', 'highlighted' => bool], ...]` for both old and new lines
  - Add a helper method to DiffViewer that processes paired deletion/addition lines and enriches them with word diff data
  - Handle edge cases: empty lines, lines with only whitespace changes, very long lines (>500 chars skip word diff)

  **Recommended Agent Profile**:
  - **Category**: `deep`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 2)
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 3, 4
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/DiffService.php` — Where to add the method
  - `app/DTOs/HunkLine.php` — Line DTO (type, content, lineNumbers)
  - `app/Livewire/DiffViewer.php:402-434` — `getSplitLines()` which pairs deletion/addition lines

  **Acceptance Criteria**:
  - [ ] `computeWordDiff('hello world', 'hello earth')` returns segments with "world" highlighted in old, "earth" highlighted in new
  - [ ] Empty string inputs handled without error
  - [ ] Lines >500 chars return unhighlighted content (performance guard)

  **QA Scenarios**:

  ```
  Scenario: Word diff correctly identifies changed words
    Tool: Bash (php artisan tinker)
    Steps:
      1. Run: php artisan tinker --execute="$s = new \App\Services\Git\DiffService('/tmp/test'); $r = $s->computeWordDiff('the quick brown fox', 'the slow brown cat'); var_export($r);"
      2. Assert "quick" is highlighted in old, "slow" is highlighted in new
      3. Assert "fox" is highlighted in old, "cat" is highlighted in new
      4. Assert "the" and "brown" are NOT highlighted
    Expected Result: Only changed words have highlighted=true
    Evidence: .sisyphus/evidence/task-1-word-diff-algorithm.txt
  ```

  **Commit**: YES
  - Message: `feat(backend): implement word-level diff algorithm in DiffService`
  - Files: `app/Services/Git/DiffService.php`

- [ ] 2. Add CSS classes for word-level highlights

  **What to do**:
  - Add `.diff-word-addition` and `.diff-word-deletion` classes to `resources/css/app.css`
  - Use stronger opacity than line-level: `rgba(64, 160, 43, 0.3)` for additions (vs 0.1 for lines), `rgba(210, 15, 57, 0.3)` for deletions
  - Add `border-radius: 2px` for inline highlight segments
  - Ensure highlights are visible in both unified and split modes

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: YES (with Task 1)
  - **Parallel Group**: Wave 1
  - **Blocks**: Task 3
  - **Blocked By**: None

  **References**:
  - `resources/css/app.css` — Existing `.diff-line-addition` and `.diff-line-deletion` (lines 57-68 area)

  **Acceptance Criteria**:
  - [ ] `.diff-word-addition` and `.diff-word-deletion` classes defined in app.css
  - [ ] Visually distinct from line-level background (stronger opacity)

  **Commit**: YES (groups with Task 1)
  - Message: `design(tokens): add word-level diff highlight CSS classes`
  - Files: `resources/css/app.css`

- [ ] 3. Update diff-viewer Blade template to render word highlights

  **What to do**:
  - In `diff-viewer.blade.php`, when rendering paired addition/deletion lines, call word diff computation
  - Replace plain text content with `<span>` segments: `<span class="diff-word-addition">changed</span>` for highlighted parts
  - Apply to both unified view (lines rendered sequentially) and split view (paired columns)
  - Use Blade `@foreach` on word diff segments instead of raw `{{ $line['content'] }}`
  - Ensure existing hunk stage/unstage buttons still work (don't break `wire:click` handlers)

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Tasks 1, 2

  **References**:
  - `resources/views/livewire/diff-viewer.blade.php` — Line rendering loop, both unified and split modes
  - `app/Livewire/DiffViewer.php:402-434` — `getSplitLines()` for split mode pairing
  - `app/Livewire/DiffViewer.php:161-191` — How `$this->files` is structured (hunks → lines arrays)

  **Acceptance Criteria**:
  - [ ] Changed words within modified lines have colored background highlights
  - [ ] Context lines (unchanged) are not affected
  - [ ] Both unified and split view modes show word highlights
  - [ ] Hunk staging/unstaging still works

  **QA Scenarios**:

  ```
  Scenario: Word-level highlights visible in diff viewer
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app with a repo that has modified files
      2. Click a modified file in staging panel
      3. Wait for diff viewer to show content
      4. Look for `.diff-word-addition` or `.diff-word-deletion` spans
      5. Assert at least one word-level highlight span exists
      6. Take screenshot
    Expected Result: Individual words highlighted within changed lines
    Failure Indicators: No word-level spans, only line-level highlighting
    Evidence: .sisyphus/evidence/task-3-word-diff-visual.png
  ```

  **Commit**: YES
  - Message: `feat(panels): render word-level diff highlights in diff viewer`
  - Files: `resources/views/livewire/diff-viewer.blade.php`, `app/Livewire/DiffViewer.php`

- [ ] 4. Pest tests for word diff algorithm

  **What to do**:
  - Add tests to `tests/Feature/Services/DiffServiceTest.php` for `computeWordDiff()`
  - Test cases: identical lines (no highlights), single word change, multiple word changes, empty lines, whitespace-only changes, long lines (>500 chars skipped)
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2 (last)
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 3

  **References**:
  - `tests/Feature/Services/DiffServiceTest.php` — Existing DiffService tests

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=DiffService` → all pass
  - [ ] `vendor/bin/pint --dirty --format agent` → no issues

  **Commit**: YES
  - Message: `test(backend): add tests for word-level diff algorithm`
  - Files: `tests/Feature/Services/DiffServiceTest.php`

---

## Final Verification Wave

- [ ] F1. **Plan Compliance Audit** — `oracle`
- [ ] F2. **Code Quality Review** — `unspecified-high`
- [ ] F3. **Real Manual QA** — `unspecified-high` + `playwright` skill
- [ ] F4. **Scope Fidelity Check** — `deep`

---

## Commit Strategy

| After Task | Message | Verification |
|------------|---------|--------------|
| 1 | `feat(backend): implement word-level diff algorithm` | tinker test |
| 2 | `design(tokens): add word-level diff CSS classes` | CSS inspection |
| 3 | `feat(panels): render word-level diff highlights` | Playwright |
| 4 | `test(backend): add tests for word-level diff` | php artisan test --filter=DiffService |

---

## Success Criteria

```bash
php artisan test --compact --filter=DiffService  # Expected: all pass
vendor/bin/pint --dirty --format agent  # Expected: no issues
```
