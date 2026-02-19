# Remote Branch Management

## TL;DR

> **Quick Summary**: Add remote branch management capabilities — delete remote branches, prune stale remote-tracking branches, and manage remotes (add/remove/edit).
> 
> **Deliverables**:
> - `RemoteService` extended with deleteRemoteBranch, prune, addRemote, removeRemote
> - Remote branch actions in branch manager (delete remote, prune)
> - Remote management section in settings or dedicated modal
> - Pest tests
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3 → Task 4

---

## Context

### Research Findings
- `RemoteService.php` has `push()`, `pull()`, `fetch()`, `fetchAll()`, `forcePushWithLease()` — but no delete, prune, or remote management
- Git supports: `git push origin --delete {branch}`, `git remote prune origin`, `git remote add/remove`
- `BranchManager.php` shows remote branches but has no actions on them (only local branch actions)
- `Remote` DTO has `name` and `url` properties

---

## Work Objectives

### Core Objective
Enable users to manage remote branches (delete, prune) and remotes (add, remove) from the UI.

### Must Have
- Delete remote branch (`git push origin --delete branch-name`)
- Prune stale remote-tracking references (`git remote prune origin`)
- Confirmation for destructive remote operations
- Error handling with user-friendly messages

### Must NOT Have
- No remote URL editing (too risky)
- No force-delete protection bypass
- No multi-remote push configuration

---

## TODOs

- [ ] 1. Extend RemoteService with branch and remote management

  **What to do**:
  - Add `deleteRemoteBranch(string $remote, string $branch): void` — runs `git push {remote} --delete {branch}`
  - Add `pruneRemote(string $remote): string` — runs `git remote prune {remote}`
  - Add `addRemote(string $name, string $url): void` — runs `git remote add {name} {url}`
  - Add `removeRemote(string $name): void` — runs `git remote remove {name}`
  - Invalidate appropriate cache groups

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3, 4
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/RemoteService.php` — Existing methods pattern
  - `app/DTOs/Remote.php` — Remote DTO

  **Acceptance Criteria**:
  - [ ] All four methods execute correct git commands
  - [ ] Cache invalidated properly
  - [ ] Error messages propagated

  **Commit**: YES
  - Message: `feat(backend): extend RemoteService with branch and remote management`
  - Files: `app/Services/Git/RemoteService.php`

- [ ] 2. Add remote branch actions to BranchManager

  **What to do**:
  - Add "Delete Remote" action to remote branch items in branch manager
  - Add "Prune Remote" button in remote section header
  - Confirmation dialog for remote branch deletion (always confirm — destructive)
  - Show success/error notifications

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`livewire-development`, `fluxui-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/BranchManager.php:105-126` — `deleteBranch()` pattern for local
  - `resources/views/livewire/branch-manager.blade.php` — Remote branch rendering

  **Acceptance Criteria**:
  - [ ] "Delete Remote" action on remote branches
  - [ ] "Prune" button in remote section
  - [ ] Confirmation before destructive actions

  **QA Scenarios**:

  ```
  Scenario: Prune stale remote references
    Tool: Playwright (playwright skill)
    Steps:
      1. Navigate to app
      2. Open branch manager
      3. Click "Prune" button in remote branches section
      4. Assert success notification appears
      5. Assert branch list refreshes
    Expected Result: Stale remote references removed
    Evidence: .sisyphus/evidence/task-2-prune-remote.png
  ```

  **Commit**: YES
  - Message: `feat(header): add remote branch management to branch manager`
  - Files: `app/Livewire/BranchManager.php`, `resources/views/livewire/branch-manager.blade.php`

- [ ] 3. Add remote management UI (add/remove remotes)

  **What to do**:
  - Add "Manage Remotes" section accessible from settings modal or branch manager
  - List current remotes with name and URL
  - "Add Remote" form with name + URL inputs
  - "Remove" button per remote (with confirmation)
  - Use `<flux:modal>` for the management dialog

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`livewire-development`, `fluxui-development`, `tailwindcss-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 4
  - **Blocked By**: Task 1

  **References**:
  - `app/Livewire/SettingsModal.php` — Modal pattern
  - `resources/views/livewire/settings-modal.blade.php` — Settings UI pattern

  **Acceptance Criteria**:
  - [ ] Remotes listed with name and URL
  - [ ] Can add new remote with name + URL
  - [ ] Can remove existing remote with confirmation

  **Commit**: YES
  - Message: `feat(header): add remote management UI`

- [ ] 4. Pest tests for remote management

  **What to do**:
  - Extend `tests/Feature/Services/RemoteServiceTest.php`
  - Add Livewire tests for remote branch actions
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Blocks**: None
  - **Blocked By**: Tasks 1, 2, 3

  **Acceptance Criteria**:
  - [ ] `php artisan test --compact --filter=RemoteService` → all pass
  - [ ] `php artisan test --compact --filter=BranchManager` → all pass

  **Commit**: YES
  - Message: `test(header): add tests for remote management`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=RemoteService  # Expected: all pass
php artisan test --compact --filter=BranchManager  # Expected: all pass
```
