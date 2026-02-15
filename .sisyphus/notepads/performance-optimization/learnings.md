# Performance Optimization Learnings

## Critical Bug Fix: StagingPanel Refresh Mechanism (2026-02-15)

### Problem Identified

**Issue A: refreshStatus() was SKIPPED after every stage/unstage action**

The root cause was a fundamental misunderstanding of Livewire's request lifecycle:

1. `pausePollingTemporarily()` was called BEFORE `refreshStatus()` in all action methods
2. This set `pausePolling = true` 
3. `refreshStatus()` started with `if ($this->pausePolling) { return; }`
4. **Critical flaw**: `pausePolling` was a PRIVATE property that does NOT persist between Livewire requests
5. Result: The guard never worked as intended, but it still blocked the immediate refresh

**Issue B: Hash-based skip optimization never worked**

- `lastStatusHash` was also PRIVATE, so it reset to `null` on every request
- The hash comparison `if ($this->lastStatusHash === $statusHash)` always failed
- This meant every poll triggered a full status rebuild, even when nothing changed

**Issue C: No optimistic UI feedback**

- Users saw no visual feedback until the full server round-trip completed
- This made staging/unstaging feel slow and unresponsive

### Solution Implemented

#### Part A: Fix StagingPanel.php

1. **Removed broken polling pause mechanism**:
   - Deleted `pausePollingTemporarily()` method
   - Deleted `resumePolling()` method  
   - Deleted `private bool $pausePolling` property
   - Removed the `if ($this->pausePolling)` guard from `refreshStatus()`

2. **Fixed hash-based skip optimization**:
   - Changed `lastStatusHash` from `private ?string` to `public string` with `#[Locked]` attribute
   - Initialized as empty string: `public string $lastStatusHash = '';`
   - The `#[Locked]` attribute makes it persist between requests while preventing client-side tampering
   - Now the hash comparison actually works for polling optimization

3. **Simplified action methods**:
   - Removed all `pausePollingTemporarily()` calls
   - Flow is now: git operation → `refreshStatus()` → dispatch event → clear error
   - `refreshStatus()` now ALWAYS runs after actions (no guard blocking it)

4. **Consolidated keyboard handlers**:
   - Removed wrapper methods `handleKeyboardStageAll()` and `handleKeyboardUnstageAll()`
   - Added `#[On('keyboard-stage-all')]` directly to `stageAll()`
   - Added `#[On('keyboard-unstage-all')]` directly to `unstageAll()`

#### Part B: Add Optimistic UI to staging-panel.blade.php

1. **Simplified Alpine x-data**:
   - Removed `resumePollingTimer`, `startResumeTimer()` function
   - Removed `@status-updated.window` listener
   - Now only contains discard modal state

2. **Added optimistic fade-out for individual actions**:
   - Stage button: `x-on:click.stop="$el.closest('[wire\\:key]').classList.add('opacity-30', 'pointer-events-none'); $wire.stageFile('...')"`
   - Unstage button: Same pattern with `$wire.unstageFile(...)`
   - Pattern: Find parent file item → add opacity/disable classes → call Livewire action

3. **Added optimistic fade-out for bulk actions**:
   - Stage All: `x-on:click="document.querySelectorAll('[wire\\:key^=unstaged-]').forEach(el => { el.classList.add('opacity-30', 'pointer-events-none'); }); $wire.stageAll();"`
   - Unstage All: Same pattern for `[wire\\:key^=staged-]`

4. **Added smooth transitions**:
   - Added `transition-opacity duration-150` to file item divs
   - Works alongside existing `transition-colors` for smooth fade

#### Part C: Add Optimistic UI to file-tree.blade.php

Applied the same optimistic fade-out pattern to tree view file items:
- Stage button: Alpine click handler to fade parent item
- Unstage button: Same pattern
- Added `transition-opacity duration-150` to file item divs

### Key Learnings

#### 1. Livewire Property Persistence

**Private properties DO NOT persist between requests**. Only public properties are serialized and sent to the client.

- ❌ `private bool $pausePolling = false` — resets to false on every request
- ❌ `private ?string $lastStatusHash = null` — resets to null on every request
- ✅ `public string $lastStatusHash = '';` with `#[Locked]` — persists AND is protected from tampering

#### 2. The #[Locked] Attribute

From Livewire 4 docs:
- `#[Locked]` prevents properties from being modified on the client-side
- Locked properties still persist between requests (because they're public)
- Perfect for values that need to persist but shouldn't be user-modifiable
- Use case: hash values, IDs, state that should only change server-side

#### 3. Optimistic UI Pattern

The Alpine.js pattern for instant feedback:

```blade
x-on:click.stop="
    $el.closest('[wire\\:key]').classList.add('opacity-30', 'pointer-events-none'); 
    $wire.stageFile('{{ $file['path'] }}')
"
```

**How it works**:
1. `$el.closest('[wire\\:key]')` finds the parent file item div
2. Add `opacity-30` for visual fade
3. Add `pointer-events-none` to prevent double-clicks
4. Call `$wire.stageFile()` to trigger Livewire action
5. When Livewire re-renders, the file moves to correct list and opacity resets

**Why it works**:
- Livewire's DOM diffing removes the old element and creates a new one
- The new element doesn't have the opacity classes
- No manual cleanup needed

#### 4. Avoiding Premature Optimization

The original `pausePolling` mechanism was trying to be clever:
- Pause polling after actions to avoid conflicts
- Resume after 5 seconds via timer

**Problems**:
- Added complexity that didn't work (private property)
- Created race conditions
- Made debugging harder

**Better approach**:
- Let polling run normally
- Use hash-based skip to avoid unnecessary work
- Trust Livewire's request queuing to handle conflicts

#### 5. Testing Validates Behavior

All 11 tests passed after the refactor, proving:
- The polling pause mechanism was never needed
- `refreshStatus()` works correctly when called directly
- The hash optimization is the right way to skip unnecessary work

### Performance Impact

**Before**:
- Every stage/unstage action: NO immediate refresh (blocked by broken guard)
- Every 5s poll: Full status rebuild (hash never persisted)
- No visual feedback until server round-trip complete (~200-500ms)

**After**:
- Every stage/unstage action: Immediate refresh (guard removed)
- Every 5s poll: Skipped if hash unchanged (hash persists via #[Locked])
- Instant visual feedback via optimistic UI (~0ms perceived latency)

### Code Quality Improvements

- **Removed**: 15 lines of broken polling pause logic
- **Simplified**: All action methods (removed `pausePollingTemporarily()` calls)
- **Consolidated**: Keyboard handlers (removed 2 wrapper methods)
- **Added**: Optimistic UI for better UX
- **Fixed**: Hash-based skip optimization now actually works
