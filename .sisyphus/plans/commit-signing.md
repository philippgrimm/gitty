# Commit Signing

## TL;DR

> **Quick Summary**: Add commit signing support (GPG/SSH) with a toggle in settings and visual indicators on signed commits in the history.
> 
> **Deliverables**:
> - Settings toggle for commit signing (off/GPG/SSH)
> - `CommitService` modified to pass `-S` flag when signing enabled
> - Signed commit indicator in history panel
> - Pest tests
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES — 2 waves
> **Critical Path**: Task 1 → Task 2 → Task 3

---

## Context

### Research Findings
- `CommitService::commit()` runs `git commit -m` — needs `-S` flag for signing
- Git config `commit.gpgsign=true` and `gpg.format=ssh` control signing globally
- Can detect existing config: `git config --get commit.gpgsign`
- `Commit` DTO could include signature verification status from `git log --format='%G?'`
- Settings already exist via `SettingsService` — add signing preference

---

## Work Objectives

### Must Have
- Settings toggle: Off / GPG / SSH signing
- Detect if signing keys are configured (`git config user.signingkey`)
- Pass `-S` to commit commands when enabled
- Show lock/shield icon on signed commits in history
- Warn if signing enabled but no key configured

### Must NOT Have
- No key generation or management
- No key upload to GitHub/GitLab
- No signature verification of others' commits (just visual indicator)

---

## TODOs

- [ ] 1. Add signing support to CommitService and settings

  **What to do**:
  - Add `commit_signing` setting to `SettingsService` (values: 'off', 'gpg', 'ssh')
  - Modify `CommitService::commit()` and `commitAmend()` to check setting and add `-S` flag
  - Add `getSigningKeyStatus(): array` to `GitService` — checks `commit.gpgsign`, `user.signingkey`, `gpg.format`
  - Add `isCommitSigned(string $sha): bool` method — checks `git log -1 --format='%G?' {sha}`
  - Add `commitSigningEnabled` property to SettingsModal

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: [`livewire-development`, `pest-testing`]

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1
  - **Blocks**: Tasks 2, 3
  - **Blocked By**: None

  **References**:
  - `app/Services/Git/CommitService.php:11-17` — `commit()` to modify
  - `app/Livewire/SettingsModal.php` — Settings UI to extend
  - `app/Services/SettingsService.php` — Settings storage
  - `app/Services/Git/GitService.php:160-169` — `getConfigValue()` for reading git config

  **Acceptance Criteria**:
  - [ ] `-S` flag added to commit when signing enabled
  - [ ] `getSigningKeyStatus()` detects key configuration
  - [ ] New setting saved/loaded properly

  **Commit**: YES
  - Message: `feat(backend): add commit signing support`
  - Files: `app/Services/Git/CommitService.php`, `app/Services/Git/GitService.php`, `app/Livewire/SettingsModal.php`, `app/Services/SettingsService.php`

- [ ] 2. Add signing indicator to history and settings UI

  **What to do**:
  - Add signing toggle to `settings-modal.blade.php` using `<flux:select>` with Off/GPG/SSH options
  - Show warning when signing enabled but no key detected
  - Add lock icon to signed commits in `history-panel.blade.php`
  - Use `<x-phosphor-shield-check class="w-3.5 h-3.5 text-[#40a02b]" />` for signed commits

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
  - **Skills**: [`fluxui-development`, `tailwindcss-development`, `livewire-development`]

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 2
  - **Blocks**: Task 3
  - **Blocked By**: Task 1

  **References**:
  - `resources/views/livewire/settings-modal.blade.php` — Settings form
  - `resources/views/livewire/history-panel.blade.php` — Commit rendering

  **Acceptance Criteria**:
  - [ ] Signing toggle in settings with Off/GPG/SSH
  - [ ] Warning shown when no key configured
  - [ ] Shield icon on signed commits in history

  **QA Scenarios**:

  ```
  Scenario: Signing toggle in settings
    Tool: Playwright (playwright skill)
    Steps:
      1. Open settings modal
      2. Find commit signing select
      3. Assert options: Off, GPG, SSH
      4. Select "GPG"
      5. Save settings
      6. Reopen settings
      7. Assert GPG is still selected
    Expected Result: Setting persists across modal open/close
    Evidence: .sisyphus/evidence/task-2-commit-signing-settings.png
  ```

  **Commit**: YES
  - Message: `feat(panels): add commit signing UI indicators and settings`

- [ ] 3. Pest tests for commit signing

  **What to do**:
  - Add tests to `tests/Feature/Services/CommitServiceTest.php` for signing flag
  - Add tests to `tests/Feature/Livewire/SettingsModalTest.php` for signing setting
  - Run `vendor/bin/pint --dirty`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
  - **Skills**: [`pest-testing`]

  **Acceptance Criteria**:
  - [ ] All related tests pass

  **Commit**: YES
  - Message: `test(backend): add tests for commit signing`

---

## Final Verification Wave

- [ ] F1-F4: Standard verification wave

---

## Success Criteria

```bash
php artisan test --compact --filter=CommitService  # Expected: all pass
php artisan test --compact --filter=SettingsModal  # Expected: all pass
```
