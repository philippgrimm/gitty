## Force-Delete Modal Implementation (2026-02-19)

Successfully added force-delete confirmation modal to BranchManager following the auto-stash modal pattern.

### Changes Made
1. **BranchManager.php** (lines 36-38):
   - Added `public bool $showForceDeleteModal = false;`
   - Added `public string $branchToForceDelete = '';`

2. **BranchManager.php** (lines 127-130):
   - Modified `deleteBranch()` catch block to detect `isNotFullyMergedError()`
   - Shows modal instead of error toast when branch is not fully merged

3. **BranchManager.php** (lines 207-228):
   - Added `forceDeleteBranch()` method (calls `deleteBranch($name, true)`)
   - Added `cancelForceDelete()` method

4. **branch-manager.blade.php** (lines 224-242):
   - Added force-delete modal after auto-stash modal
   - Uses `variant="danger"` for destructive action (NOT primary)
   - Matches auto-stash modal structure exactly

### Pattern Consistency
The implementation mirrors the auto-stash modal pattern 1:1:
- Error detection in catch block → set properties → show modal
- Confirm method → close modal → execute action → refresh → finally cleanup
- Cancel method → close modal → reset properties
- Modal markup → heading + subheading + cancel/confirm buttons

### Key Details
- Used `variant="danger"` for force-delete button (destructive action)
- Modal placed right after auto-stash modal (line 222)
- Pint formatting passed
- No test modifications (as instructed)
