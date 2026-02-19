# In-App Update System for Gitty

## TL;DR

> **Quick Summary**: Add in-app auto-update UX to gitty using NativePHP's built-in AutoUpdater infrastructure. The backend plumbing (config, facade, events) already exists — this plan wires up an UpdateService, event listeners, a "Check for Updates" menu item, and a subtle toast notification to inform users when updates are available.
> 
> **Deliverables**:
> - `UpdateService` singleton tracking update state via Cache
> - 7 Laravel event listeners for NativePHP AutoUpdater events
> - 1 Laravel event listener for `MenuItemClicked` (Check for Updates menu action)
> - `UpdateNotification` Livewire component with toast UI
> - Config and .env.example changes for GitHub Releases provider
> - Pest tests for all new code
> 
> **Estimated Effort**: Medium
> **Parallel Execution**: YES — 3 waves
> **Critical Path**: UpdateService → Event Listeners → Livewire Component

---

## Context

### Original Request
User wants in-app update support for gitty. NativePHP v2.1 includes a full AutoUpdater system (facade, events, providers) but there's no in-app UX — no menu item, no notifications, no update flow.

### Interview Summary
**Key Discussions**:
- **Provider**: GitHub Releases — natural fit for a git client, free, simple
- **Check strategy**: Auto-check on launch + manual "Check for Updates..." menu item
- **UX**: Subtle banner/toast (non-intrusive, bottom-right, matches ErrorBanner pattern)
- **Scope**: In-app UX only. CI/CD pipeline and code signing deferred to separate session.
- **Toast behavior**: User can dismiss or click "Restart" when update is ready

**Research Findings**:
- NativePHP wraps `electron-updater` and fires 7 events: `CheckingForUpdate`, `UpdateAvailable`, `DownloadProgress`, `UpdateDownloaded`, `UpdateNotAvailable`, `UpdateCancelled`, `Error`
- Updates auto-download when `UpdateAvailable` fires (cannot be disabled)
- `UpdateDownloaded` event includes `version`, `releaseDate`, `releaseNotes`, `releaseName`
- `MenuItemClicked` is a Laravel event with `$item` array containing the event name
- The app already uses `wire:poll.Xs.visible` extensively (5s, 15s, 30s intervals)
- ErrorBanner is the existing toast pattern: bottom-right, Alpine.js transitions, Catppuccin colors
- AutoFetchService is the existing Cache-based state management pattern
- `app/Listeners/` directory does not exist yet — needs to be created
- Updater only runs in production mode — tests must mock everything

### Metis Review
**Identified Gaps** (addressed):
- Menu events are defined but have no handlers — addressed by creating `MenuItemClicked` listener
- Race condition on multiple rapid checks — addressed with `Cache::lock()` in UpdateService
- Network errors on auto-check should be silent — addressed in event listener logic
- Polling performance concerns — addressed with `wire:poll.10s.visible` (lightweight Cache read)
- Stale state after crash — addressed by clearing transient states on component mount
- Toast needs restart action — added "Restart" button for `ready` state

---

## Work Objectives

### Core Objective
Wire up NativePHP's existing AutoUpdater infrastructure to a user-facing update notification system with auto-check on launch and manual check via menu.

### Concrete Deliverables
- `app/Services/UpdateService.php` — state machine singleton
- `app/Listeners/HandleUpdateCheckingForUpdate.php`
- `app/Listeners/HandleUpdateAvailable.php`
- `app/Listeners/HandleUpdateDownloadProgress.php`
- `app/Listeners/HandleUpdateDownloaded.php`
- `app/Listeners/HandleUpdateNotAvailable.php`
- `app/Listeners/HandleUpdateCancelled.php`
- `app/Listeners/HandleUpdateError.php`
- `app/Listeners/HandleMenuItemClicked.php`
- `app/Livewire/UpdateNotification.php`
- `resources/views/livewire/update-notification.blade.php`
- Updated `config/nativephp.php` (default provider → github)
- Updated `.env.example` (GitHub provider env vars)
- Updated `app/Providers/AppServiceProvider.php` (register UpdateService singleton)
- Updated `app/Providers/NativeAppServiceProvider.php` (add menu item)
- Updated `resources/views/livewire/app-layout.blade.php` (include component)
- `tests/Feature/Services/UpdateServiceTest.php`
- `tests/Feature/Listeners/UpdateListenersTest.php`
- `tests/Feature/Livewire/UpdateNotificationTest.php`

### Definition of Done
- [ ] `php artisan test --compact --filter=Update` → all tests pass
- [ ] UpdateService state machine handles all 7 NativePHP events correctly
- [ ] "Check for Updates..." appears in Help menu
- [ ] UpdateNotification component renders in app layout
- [ ] Toast follows ErrorBanner visual pattern (Catppuccin, bottom-right, Alpine transitions)

### Must Have
- Auto-check on app launch (triggers `AutoUpdater::checkForUpdates()`)
- "Check for Updates..." menu item in Help menu
- Toast notification for: downloading, ready (with Restart button), error (manual only), up-to-date (manual only)
- Silent behavior for auto-check (no toast for checking, up-to-date, or network errors)
- Race condition prevention (`Cache::lock`)
- Minimum check interval enforcement (1 hour)
- Pest tests for all new code (mocked, no real API calls)

### Must NOT Have (Guardrails)
- ❌ Settings UI for toggling auto-updates (defer)
- ❌ Release notes viewer or changelog display (defer)
- ❌ Download progress bar with percentage (simple "Downloading..." message only)
- ❌ CI/CD pipeline, GitHub Actions, or publishing workflow (separate session)
- ❌ Code signing setup (separate session)
- ❌ Update channels (beta/stable switcher)
- ❌ Rollback mechanism
- ❌ Network retry logic (user must manually re-check)
- ❌ System notifications via NotificationService (toast only)
- ❌ Modification of existing ErrorBanner component
- ❌ Over-documentation (no JSDoc blocks on simple getters)

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES (Pest v4)
- **Automated tests**: Tests-after (each task includes its own tests)
- **Framework**: Pest via `php artisan test --compact`

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| PHP Service | Bash (php artisan tinker) | Instantiate, call methods, verify state |
| Event Listeners | Bash (php artisan test) | Fire events, assert state changes |
| Livewire Component | Bash (php artisan test) | Livewire::test(), assert rendering |
| Config/Env | Bash (grep) | Verify keys present in files |
| Menu Item | Bash (grep) | Verify menu definition in provider |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately — foundation, 3 parallel):
├── Task 1: Config changes + .env.example [quick]
├── Task 2: UpdateService + Pest tests [unspecified-high]
└── Task 3: Menu item in NativeAppServiceProvider [quick]

Wave 2 (After Wave 1 — event wiring, 2 parallel):
├── Task 4: NativePHP update event listeners + tests (depends: 2) [unspecified-high]
└── Task 5: MenuItemClicked listener + test (depends: 2, 3) [quick]

Wave 3 (After Wave 2 — UI layer, 1 task):
└── Task 6: UpdateNotification Livewire component + toast UI + layout wiring + tests (depends: 2, 4, 5) [visual-engineering]

Wave FINAL (After ALL — verification, 4 parallel):
├── Task F1: Plan compliance audit [oracle]
├── Task F2: Code quality review [unspecified-high]
├── Task F3: Real manual QA [unspecified-high]
└── Task F4: Scope fidelity check [deep]

Critical Path: Task 2 → Task 4 → Task 6 → F1-F4
Parallel Speedup: ~50% faster than sequential
Max Concurrent: 3 (Wave 1)
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | — | 1 |
| 2 | — | 4, 5, 6 | 1 |
| 3 | — | 5 | 1 |
| 4 | 2 | 6 | 2 |
| 5 | 2, 3 | 6 | 2 |
| 6 | 2, 4, 5 | F1-F4 | 3 |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 1 | **3** | T1 → `quick`, T2 → `unspecified-high`, T3 → `quick` |
| 2 | **2** | T4 → `unspecified-high`, T5 → `quick` |
| 3 | **1** | T6 → `visual-engineering` |
| FINAL | **4** | F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep` |

---

## TODOs

- [ ] 1. Config Changes + Environment Variables

  **What to do**:
  - In `config/nativephp.php`, change the updater default provider from `'spaces'` to `'github'`:
    - Line 107: `'default' => env('NATIVEPHP_UPDATER_PROVIDER', 'spaces')` → `'default' => env('NATIVEPHP_UPDATER_PROVIDER', 'github')`
  - In `.env.example`, add a new section at the end for NativePHP updater configuration:
    ```
    NATIVEPHP_APP_VERSION=1.0.0
    NATIVEPHP_UPDATER_ENABLED=true
    NATIVEPHP_UPDATER_PROVIDER=github
    GITHUB_REPO=
    GITHUB_OWNER=
    GITHUB_TOKEN=
    GITHUB_AUTOUPDATE_TOKEN=
    ```
  - Do NOT modify any other config values or add env vars for S3/Spaces providers (those already have defaults)

  **Must NOT do**:
  - Do NOT add DigitalOcean Spaces or S3 env vars (not the chosen provider)
  - Do NOT change the `app_id`, `version`, or other non-updater config values
  - Do NOT modify `.env` (only `.env.example`)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Two simple file edits — change one default value and append env vars
  - **Skills**: []
    - No specialized skills needed for config edits
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — no Livewire components
    - `tailwindcss-development`: Not relevant — no styling

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: None
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References**:
  - `config/nativephp.php:94-148` — Current updater config block. Line 107 has the default provider setting to change. The GitHub provider block at lines 110-120 shows what env vars are needed.

  **API/Type References**:
  - `.env.example:1-66` — Current env file. New vars should be appended at the end, after the existing `VITE_APP_NAME` line.

  **WHY Each Reference Matters**:
  - `config/nativephp.php`: The executor needs to find line 107 and change `'spaces'` to `'github'` — no other changes.
  - `.env.example`: The executor needs to see the existing format/style (no comments between vars, blank line between sections) to match conventions.

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Default provider changed to github
    Tool: Bash (grep)
    Preconditions: config/nativephp.php exists
    Steps:
      1. Run: grep "NATIVEPHP_UPDATER_PROVIDER.*github" config/nativephp.php
      2. Assert output contains: 'github'
      3. Run: grep "NATIVEPHP_UPDATER_PROVIDER.*spaces" config/nativephp.php
      4. Assert: no output (spaces is no longer the default)
    Expected Result: Default provider is 'github', not 'spaces'
    Failure Indicators: grep returns 'spaces' as default, or 'github' not found
    Evidence: .sisyphus/evidence/task-1-config-provider.txt

  Scenario: GitHub env vars present in .env.example
    Tool: Bash (grep)
    Preconditions: .env.example exists
    Steps:
      1. Run: grep "NATIVEPHP_APP_VERSION" .env.example
      2. Run: grep "NATIVEPHP_UPDATER_PROVIDER=github" .env.example
      3. Run: grep "GITHUB_REPO=" .env.example
      4. Run: grep "GITHUB_OWNER=" .env.example
      5. Run: grep "GITHUB_AUTOUPDATE_TOKEN=" .env.example
      6. Assert: all 5 greps return matches
    Expected Result: All required env vars present
    Failure Indicators: Any grep returns empty
    Evidence: .sisyphus/evidence/task-1-env-vars.txt

  Scenario: No unwanted changes to config
    Tool: Bash (grep)
    Preconditions: config/nativephp.php exists
    Steps:
      1. Run: grep "app_id.*com.gitty.app" config/nativephp.php
      2. Assert: app_id unchanged
      3. Run: grep "version.*NATIVEPHP_APP_VERSION.*1.0.0" config/nativephp.php
      4. Assert: version unchanged
    Expected Result: Only the default provider value changed, all other config intact
    Failure Indicators: app_id or version values differ from original
    Evidence: .sisyphus/evidence/task-1-no-side-effects.txt
  ```

  **Evidence to Capture:**
  - [ ] task-1-config-provider.txt
  - [ ] task-1-env-vars.txt
  - [ ] task-1-no-side-effects.txt

  **Commit**: YES
  - Message: `chore(backend): configure GitHub Releases as default update provider`
  - Files: `config/nativephp.php`, `.env.example`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [ ] 2. UpdateService — State Machine Singleton + Tests

  **What to do**:
  - Create `app/Services/UpdateService.php` — a singleton service that tracks auto-update state via Cache
  - State machine with these states: `idle`, `checking`, `downloading`, `ready`, `error`, `up_to_date`
  - State transitions:
    - `idle` → `checking` (when check is triggered)
    - `checking` → `downloading` (when update available, auto-download starts)
    - `checking` → `up_to_date` (when no update available)
    - `checking` → `error` (when check fails)
    - `downloading` → `ready` (when download completes)
    - `downloading` → `error` (when download fails)
    - Any state → `idle` (when dismissed/reset)
  - Cache keys (global, not repo-specific):
    - `app-update:status` — current state string
    - `app-update:version` — available version string (e.g., "1.2.3")
    - `app-update:message` — human-readable message for the UI
    - `app-update:last-check` — timestamp of last check (for rate limiting)
    - `app-update:manual` — boolean, whether the current check was manually triggered
  - Methods:
    - `check(bool $manual = false): bool` — triggers update check if allowed. Returns false if already checking or rate-limited. Uses `Cache::lock('app-update-check', 10)` to prevent race conditions. Enforces 1-hour minimum between auto-checks (manual checks bypass this). Calls `AutoUpdater::checkForUpdates()`.
    - `getState(): array` — returns `['status' => string, 'version' => string, 'message' => string, 'manual' => bool]`
    - `setStatus(string $status, ?string $version = null, ?string $message = null): void` — updates Cache state (called by event listeners)
    - `isManualCheck(): bool` — returns whether the current check was manually triggered
    - `reset(): void` — clears all state back to `idle`
    - `clearTransientStates(): void` — clears `checking` and `downloading` states (called on app boot to recover from crashes)
    - `canCheck(): bool` — returns whether a check is allowed (not currently checking, not rate-limited for auto)
  - Register as singleton in `app/Providers/AppServiceProvider.php`:
    - Add `use App\Services\UpdateService;` import
    - Add `$this->app->singleton(UpdateService::class);` in `register()` method
  - Create `tests/Feature/Services/UpdateServiceTest.php` with Pest:
    - Test state transitions: idle → checking → downloading → ready
    - Test state transitions: idle → checking → up_to_date
    - Test state transitions: idle → checking → error
    - Test `reset()` clears all state
    - Test `clearTransientStates()` clears checking/downloading but not ready/error
    - Test `canCheck()` returns false when already checking
    - Test rate limiting: auto-check blocked within 1 hour, manual check always allowed
    - Test `Cache::lock` prevents race conditions (mock `Cache::lock`)
    - Test `check()` calls `AutoUpdater::checkForUpdates()` (mock the facade)
    - Test `check()` returns false when rate-limited

  **Must NOT do**:
  - Do NOT make HTTP requests directly — use `AutoUpdater` facade only
  - Do NOT store state in database — use Cache only
  - Do NOT add settings or preferences — just a service with state
  - Do NOT handle NativePHP events in this service — event listeners do that (Task 4)
  - Do NOT add system notification calls

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Core service with state machine logic, Cache interaction, race condition handling, and comprehensive test suite — needs careful implementation
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Required for writing comprehensive Pest test suite for the service
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — pure PHP service, no Livewire
    - `fluxui-development`: Not relevant — no UI components

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: Tasks 4, 5, 6
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References**:
  - `app/Services/AutoFetchService.php:1-157` — **Primary pattern to follow.** Cache-based state management service. Copy the Cache key pattern (`getCacheKey()`), the constructor pattern, and the general service structure. The `shouldFetch()` method (line 71-90) shows how to implement rate limiting with timestamps.
  - `app/Providers/AppServiceProvider.php:13-33` — Where to register the singleton. Follow the existing pattern: add import at top, add `$this->app->singleton()` in `register()`.

  **API/Type References**:
  - `vendor/nativephp/desktop/src/Facades/AutoUpdater.php` — The facade to call. Use `AutoUpdater::checkForUpdates()` to trigger checks.
  - `vendor/nativephp/desktop/src/AutoUpdater.php` — Underlying class behind the facade. Has `checkForUpdates()`, `downloadUpdate()`, `quitAndInstall()` methods.

  **Test References**:
  - `tests/Feature/Services/AutoFetchServiceTest.php` — **Primary test pattern.** Shows how to test a Cache-based service with Pest. Follow the same file structure, `beforeEach`/`afterEach` patterns, and assertion style.
  - `tests/Feature/Services/NotificationServiceTest.php` — Shows how to mock NativePHP facades in tests.

  **WHY Each Reference Matters**:
  - `AutoFetchService`: This IS the pattern — Cache keys, state checking, rate limiting. The new UpdateService should feel like a sibling.
  - `AppServiceProvider`: Singleton registration must match existing style exactly.
  - `AutoFetchServiceTest`: Test structure must match existing conventions (Pest, feature tests, Cache interactions).
  - `NotificationServiceTest`: Shows how to handle NativePHP facade mocking (line 43+).

  **Acceptance Criteria**:

  - [ ] File exists: `app/Services/UpdateService.php`
  - [ ] File exists: `tests/Feature/Services/UpdateServiceTest.php`
  - [ ] `php artisan test --compact --filter=UpdateService` → PASS (all tests, 0 failures)
  - [ ] UpdateService registered as singleton in `AppServiceProvider`

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: UpdateService can be instantiated and returns idle state
    Tool: Bash (php artisan tinker)
    Preconditions: Application boots successfully
    Steps:
      1. Run: php artisan tinker --execute="echo json_encode(app(App\Services\UpdateService::class)->getState());"
      2. Assert output contains: {"status":"idle"
    Expected Result: Service returns idle state with empty version and message
    Failure Indicators: Class not found, or status is not "idle"
    Evidence: .sisyphus/evidence/task-2-service-init.txt

  Scenario: State transitions work correctly
    Tool: Bash (php artisan tinker)
    Preconditions: UpdateService is registered
    Steps:
      1. Run: php artisan tinker --execute="
          \$s = app(App\Services\UpdateService::class);
          \$s->setStatus('checking');
          echo \$s->getState()['status'] . PHP_EOL;
          \$s->setStatus('downloading', '1.2.3', 'Downloading update...');
          echo \$s->getState()['status'] . ' ' . \$s->getState()['version'] . PHP_EOL;
          \$s->setStatus('ready', '1.2.3', 'Update ready');
          echo \$s->getState()['status'] . PHP_EOL;
          \$s->reset();
          echo \$s->getState()['status'];
          "
      2. Assert output lines: "checking", "downloading 1.2.3", "ready", "idle"
    Expected Result: State transitions follow expected flow
    Failure Indicators: Any state value is unexpected
    Evidence: .sisyphus/evidence/task-2-state-transitions.txt

  Scenario: All Pest tests pass
    Tool: Bash
    Preconditions: Test file exists
    Steps:
      1. Run: php artisan test --compact --filter=UpdateService
      2. Assert: output contains "PASS" and "0 failed"
    Expected Result: All tests pass with 0 failures
    Failure Indicators: Any test failure
    Evidence: .sisyphus/evidence/task-2-tests.txt

  Scenario: clearTransientStates preserves terminal states
    Tool: Bash (php artisan tinker)
    Preconditions: UpdateService is registered
    Steps:
      1. Run: php artisan tinker --execute="
          \$s = app(App\Services\UpdateService::class);
          \$s->setStatus('ready', '1.2.3', 'Update ready');
          \$s->clearTransientStates();
          echo \$s->getState()['status'];
          "
      2. Assert output: "ready"
      3. Run: php artisan tinker --execute="
          \$s = app(App\Services\UpdateService::class);
          \$s->setStatus('checking');
          \$s->clearTransientStates();
          echo \$s->getState()['status'];
          "
      4. Assert output: "idle"
    Expected Result: Terminal states preserved, transient states cleared
    Failure Indicators: ready state cleared, or checking state not cleared
    Evidence: .sisyphus/evidence/task-2-transient-clear.txt
  ```

  **Evidence to Capture:**
  - [ ] task-2-service-init.txt
  - [ ] task-2-state-transitions.txt
  - [ ] task-2-tests.txt
  - [ ] task-2-transient-clear.txt

  **Commit**: YES
  - Message: `feat(backend): add UpdateService for tracking app update state`
  - Files: `app/Services/UpdateService.php`, `app/Providers/AppServiceProvider.php`, `tests/Feature/Services/UpdateServiceTest.php`
  - Pre-commit: `php artisan test --compact --filter=UpdateService && vendor/bin/pint --dirty --format agent`

- [ ] 3. Add "Check for Updates..." Menu Item

  **What to do**:
  - In `app/Providers/NativeAppServiceProvider.php`, add a new menu item to the Help menu:
    - Before the existing `Menu::label('About Gitty')` line, add:
      ```php
      Menu::label('Check for Updates...')->event('menu:help:check-updates'),
      Menu::separator(),
      ```
    - This places "Check for Updates..." above a separator, then "About Gitty" below
  - The event name `menu:help:check-updates` follows the existing naming convention: `menu:{menu}:{action}`

  **Must NOT do**:
  - Do NOT add keyboard shortcuts to this menu item (updates are infrequent, no hotkey needed)
  - Do NOT add any other menu items
  - Do NOT modify any other menus (File, Git, Branch)
  - Do NOT create event listeners here (that's Task 5)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single 2-line addition to an existing file
  - **Skills**: []
    - No specialized skills needed
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — NativeAppServiceProvider is not a Livewire component

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: Task 5
  - **Blocked By**: None (can start immediately)

  **References**:

  **Pattern References**:
  - `app/Providers/NativeAppServiceProvider.php:47-49` — The Help menu block. Currently has only `Menu::label('About Gitty')->event('menu:help:about')`. New item goes BEFORE this line, with a separator between them.
  - `app/Providers/NativeAppServiceProvider.php:20-27` — File menu pattern showing how separators are used between logical groups.

  **WHY Each Reference Matters**:
  - Line 47-49: Exact insertion point. The executor must add 2 lines before `Menu::label('About Gitty')`.
  - Lines 20-27: Shows `Menu::separator()` usage pattern — separators stand alone as their own menu entries.

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Menu item exists in Help menu
    Tool: Bash (grep)
    Preconditions: NativeAppServiceProvider.php exists
    Steps:
      1. Run: grep "Check for Updates" app/Providers/NativeAppServiceProvider.php
      2. Assert: output contains "Check for Updates..."
      3. Run: grep "menu:help:check-updates" app/Providers/NativeAppServiceProvider.php
      4. Assert: output contains the event name
    Expected Result: Menu item defined with correct label and event name
    Failure Indicators: grep returns empty for either query
    Evidence: .sisyphus/evidence/task-3-menu-item.txt

  Scenario: Menu item appears before About Gitty with separator
    Tool: Bash (grep)
    Preconditions: NativeAppServiceProvider.php exists
    Steps:
      1. Run: grep -n "Check for Updates\|separator\|About Gitty" app/Providers/NativeAppServiceProvider.php
      2. Assert: "Check for Updates" line number < separator line number < "About Gitty" line number
    Expected Result: Correct ordering: Check for Updates → separator → About Gitty
    Failure Indicators: Items in wrong order or separator missing
    Evidence: .sisyphus/evidence/task-3-menu-order.txt

  Scenario: No other menus modified
    Tool: Bash (diff)
    Preconditions: Git repo with clean state before changes
    Steps:
      1. Run: git diff app/Providers/NativeAppServiceProvider.php
      2. Assert: only changes are within the Help menu block (around lines 47-50)
      3. Assert: File, Git, Branch menus unchanged
    Expected Result: Only Help menu modified
    Failure Indicators: Changes outside Help menu block
    Evidence: .sisyphus/evidence/task-3-no-side-effects.txt
  ```

  **Evidence to Capture:**
  - [ ] task-3-menu-item.txt
  - [ ] task-3-menu-order.txt
  - [ ] task-3-no-side-effects.txt

  **Commit**: YES
  - Message: `feat(header): add Check for Updates menu item`
  - Files: `app/Providers/NativeAppServiceProvider.php`
  - Pre-commit: `vendor/bin/pint --dirty --format agent`

- [ ] 4. NativePHP AutoUpdater Event Listeners + Tests

  **What to do**:
  - Create `app/Listeners/` directory (does not exist yet)
  - Create 7 event listener classes, one per NativePHP AutoUpdater event. Each listener updates the `UpdateService` state:

  | Listener File | Event | Action |
  |---------------|-------|--------|
  | `HandleUpdateCheckingForUpdate.php` | `Native\Desktop\Events\AutoUpdater\CheckingForUpdate` | `setStatus('checking')` |
  | `HandleUpdateAvailable.php` | `Native\Desktop\Events\AutoUpdater\UpdateAvailable` | `setStatus('downloading', $event->version, 'Downloading update...')` — note: NativePHP auto-downloads immediately |
  | `HandleUpdateDownloadProgress.php` | `Native\Desktop\Events\AutoUpdater\DownloadProgress` | `setStatus('downloading', null, 'Downloading update...')` — keeps status as downloading, optionally log progress |
  | `HandleUpdateDownloaded.php` | `Native\Desktop\Events\AutoUpdater\UpdateDownloaded` | `setStatus('ready', $event->version, 'Gitty ' . $event->version . ' is ready — restart to update')` |
  | `HandleUpdateNotAvailable.php` | `Native\Desktop\Events\AutoUpdater\UpdateNotAvailable` | `setStatus('up_to_date', null, 'You\'re on the latest version')` |
  | `HandleUpdateCancelled.php` | `Native\Desktop\Events\AutoUpdater\UpdateCancelled` | `setStatus('idle')` |
  | `HandleUpdateError.php` | `Native\Desktop\Events\AutoUpdater\Error` | If manual check: `setStatus('error', null, 'Unable to check for updates')`. If auto-check: `setStatus('idle')` and log the error via `Log::warning()`. Check `$service->isManualCheck()` to determine behavior. |

  - Each listener should:
    - Accept the event via constructor injection
    - Resolve `UpdateService` from the container: `app(UpdateService::class)`
    - Have a `handle()` method that updates state
    - Use `declare(strict_types=1)` and proper namespace `App\Listeners`
  - Register all 7 listeners in `app/Providers/AppServiceProvider.php` `boot()` method using `Event::listen()`:
    ```php
    use Illuminate\Support\Facades\Event;
    
    Event::listen(CheckingForUpdate::class, HandleUpdateCheckingForUpdate::class);
    // ... repeat for all 7
    ```
  - Create `tests/Feature/Listeners/UpdateListenersTest.php` with Pest:
    - Test that firing `CheckingForUpdate` event sets status to `checking`
    - Test that firing `UpdateAvailable` event sets status to `downloading` with version
    - Test that firing `UpdateDownloaded` event sets status to `ready` with version and message
    - Test that firing `UpdateNotAvailable` event sets status to `up_to_date`
    - Test that firing `UpdateCancelled` event sets status to `idle`
    - Test that firing `Error` event during manual check sets status to `error`
    - Test that firing `Error` event during auto-check sets status to `idle` (silent)
    - Use `Event::fake()` sparingly — we want to test actual listener execution, not just event dispatch
    - Instead, fire events directly: `event(new CheckingForUpdate())` and assert UpdateService state

  **Must NOT do**:
  - Do NOT add business logic beyond state updates — listeners should be thin
  - Do NOT send notifications or dispatch Livewire events from listeners
  - Do NOT modify the NativePHP event classes
  - Do NOT create a separate EventServiceProvider file — register in existing AppServiceProvider

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: Multiple files with event handling logic, error handling differentiation (manual vs auto), and test coverage for all 7 events
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Required for testing event listener behavior with Pest
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — pure Laravel event listeners

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Task 5)
  - **Blocks**: Task 6
  - **Blocked By**: Task 2 (requires UpdateService to exist)

  **References**:

  **Pattern References**:
  - `app/Services/UpdateService.php` — (created in Task 2) The service these listeners will call. Use `app(UpdateService::class)` to resolve, then call `setStatus()`.
  - `app/Providers/AppServiceProvider.php` — Where to register event listeners in `boot()` method. Currently empty, add `Event::listen()` calls.

  **API/Type References**:
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/CheckingForUpdate.php` — Event class. No public properties.
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/UpdateAvailable.php` — Has `$version` property (check exact property names by reading the file).
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/UpdateDownloaded.php` — Has `$version`, `$releaseDate`, `$releaseNotes`, `$releaseName`, `$downloadedFile` properties.
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/DownloadProgress.php` — Has `$percent`, `$total`, `$transferred`, `$delta`, `$bytesPerSecond` properties.
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/UpdateNotAvailable.php` — No public properties.
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/UpdateCancelled.php` — No public properties.
  - `vendor/nativephp/desktop/src/Events/AutoUpdater/Error.php` — Has `$error` property.

  **Test References**:
  - `tests/Feature/Services/UpdateServiceTest.php` — (created in Task 2) Shows how UpdateService is tested. Listener tests should complement, not duplicate.
  - `tests/Feature/Services/NotificationServiceTest.php` — Shows testing patterns for NativePHP-related code.

  **WHY Each Reference Matters**:
  - NativePHP event classes: The executor MUST read these files to get exact property names (e.g., is it `$event->version` or `$event->data['version']`?). Do NOT assume — read the source.
  - UpdateService: Listeners call its `setStatus()` and `isManualCheck()` methods.
  - AppServiceProvider: Registration point. Must not break existing singleton registrations.

  **Acceptance Criteria**:

  - [ ] 7 listener files exist in `app/Listeners/`
  - [ ] All listeners registered in `AppServiceProvider::boot()`
  - [ ] `php artisan test --compact --filter=UpdateListeners` → PASS

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: All 7 listener files exist
    Tool: Bash (ls)
    Preconditions: app/Listeners/ directory exists
    Steps:
      1. Run: ls app/Listeners/HandleUpdate*.php app/Listeners/HandleMenuItemClicked.php 2>/dev/null | wc -l
      2. Note: HandleMenuItemClicked is Task 5, so expect 7 files from this task
      3. Run: ls app/Listeners/HandleUpdate*.php | wc -l
      4. Assert: output is 7
    Expected Result: Exactly 7 HandleUpdate*.php files
    Failure Indicators: Fewer than 7 files
    Evidence: .sisyphus/evidence/task-4-listener-files.txt

  Scenario: Listeners are registered in AppServiceProvider
    Tool: Bash (grep)
    Preconditions: AppServiceProvider.php exists
    Steps:
      1. Run: grep "Event::listen" app/Providers/AppServiceProvider.php | wc -l
      2. Assert: at least 7 Event::listen calls (Task 5 may add 1 more)
      3. Run: grep "CheckingForUpdate" app/Providers/AppServiceProvider.php
      4. Assert: found
      5. Run: grep "UpdateDownloaded" app/Providers/AppServiceProvider.php
      6. Assert: found
    Expected Result: All 7 NativePHP update events have listeners registered
    Failure Indicators: Any event missing from registration
    Evidence: .sisyphus/evidence/task-4-listener-registration.txt

  Scenario: Firing UpdateDownloaded sets ready state
    Tool: Bash (php artisan tinker)
    Preconditions: Listeners registered, UpdateService exists
    Steps:
      1. Run: php artisan tinker --execute="
          event(new Native\Desktop\Events\AutoUpdater\UpdateDownloaded('1.5.0', '/tmp/update', '2026-02-19', 'Bug fixes', 'v1.5.0'));
          echo json_encode(app(App\Services\UpdateService::class)->getState());
          "
      2. Assert: output status is "ready" and version is "1.5.0"
    Expected Result: UpdateDownloaded event correctly sets ready state
    Failure Indicators: Status not "ready", version not "1.5.0"
    Evidence: .sisyphus/evidence/task-4-event-flow.txt

  Scenario: All Pest tests pass
    Tool: Bash
    Preconditions: Test file exists
    Steps:
      1. Run: php artisan test --compact --filter=UpdateListeners
      2. Assert: output contains "PASS" and "0 failed"
    Expected Result: All listener tests pass
    Failure Indicators: Any test failure
    Evidence: .sisyphus/evidence/task-4-tests.txt
  ```

  **Evidence to Capture:**
  - [ ] task-4-listener-files.txt
  - [ ] task-4-listener-registration.txt
  - [ ] task-4-event-flow.txt
  - [ ] task-4-tests.txt

  **Commit**: YES
  - Message: `feat(backend): add event listeners for NativePHP auto-updater`
  - Files: `app/Listeners/HandleUpdate*.php`, `app/Providers/AppServiceProvider.php`, `tests/Feature/Listeners/UpdateListenersTest.php`
  - Pre-commit: `php artisan test --compact --filter=UpdateListeners && vendor/bin/pint --dirty --format agent`

- [ ] 5. MenuItemClicked Listener for "Check for Updates" + Test

  **What to do**:
  - Create `app/Listeners/HandleMenuItemClicked.php`:
    - Listens to `Native\Desktop\Events\Menu\MenuItemClicked`
    - In `handle()`, check if `$event->item` matches the "Check for Updates" event name
    - The `$item` array from NativePHP contains event data — inspect it to find the event name field. It likely has an `id` or `event` key matching `'menu:help:check-updates'`.
    - When matched, resolve `UpdateService` and call `check(manual: true)` to trigger a manual update check
    - For any other menu event names, do nothing (return early)
  - Register in `AppServiceProvider::boot()` alongside the other event listeners:
    ```php
    Event::listen(MenuItemClicked::class, HandleMenuItemClicked::class);
    ```
  - Create `tests/Feature/Listeners/HandleMenuItemClickedTest.php` with Pest:
    - Test that firing `MenuItemClicked` with `menu:help:check-updates` triggers `UpdateService::check(manual: true)`
    - Test that firing `MenuItemClicked` with `menu:git:commit` does NOT trigger update check
    - Mock `AutoUpdater` facade to prevent actual API calls

  **Must NOT do**:
  - Do NOT handle other menu events in this listener (only `menu:help:check-updates`)
  - Do NOT add complex logic — delegate entirely to UpdateService
  - Do NOT create a catch-all menu event router (keep it focused)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single listener file with simple conditional logic + small test file
  - **Skills**: [`pest-testing`]
    - `pest-testing`: Needed for the test file
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — pure event listener

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 2 (with Task 4)
  - **Blocks**: Task 6
  - **Blocked By**: Tasks 2 (requires UpdateService), 3 (requires menu item event name)

  **References**:

  **Pattern References**:
  - `app/Listeners/HandleUpdateCheckingForUpdate.php` — (created in Task 4) Follow the same listener structure: constructor, `handle()` method, resolve UpdateService.

  **API/Type References**:
  - `vendor/nativephp/desktop/src/Events/Menu/MenuItemClicked.php` — **MUST READ THIS FILE.** The event has `public array $item` and `public array $combo`. The executor needs to inspect `$item` to find which key holds the event name (likely `$item['id']` or `$item['event']`). Do NOT guess — read the source.
  - `app/Providers/NativeAppServiceProvider.php:48` — Shows the event name string: `'menu:help:check-updates'` (as added in Task 3).

  **WHY Each Reference Matters**:
  - `MenuItemClicked.php`: The `$item` array structure is critical. Without reading this, the executor might check the wrong key and the listener would never trigger.
  - Task 4 listeners: Ensures consistent code style across all listeners.

  **Acceptance Criteria**:

  - [ ] File exists: `app/Listeners/HandleMenuItemClicked.php`
  - [ ] Listener registered in `AppServiceProvider::boot()`
  - [ ] `php artisan test --compact --filter=MenuItemClicked` → PASS

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Listener file exists and is registered
    Tool: Bash (grep)
    Preconditions: app/Listeners/ directory exists
    Steps:
      1. Run: test -f app/Listeners/HandleMenuItemClicked.php && echo "exists"
      2. Assert: output is "exists"
      3. Run: grep "MenuItemClicked" app/Providers/AppServiceProvider.php
      4. Assert: found
    Expected Result: Listener exists and is registered
    Failure Indicators: File missing or not registered
    Evidence: .sisyphus/evidence/task-5-listener-exists.txt

  Scenario: Listener only responds to check-updates event
    Tool: Bash (grep)
    Preconditions: HandleMenuItemClicked.php exists
    Steps:
      1. Run: grep "check-updates" app/Listeners/HandleMenuItemClicked.php
      2. Assert: found (listener checks for this event name)
      3. Run: grep "manual.*true\|true.*manual\|manual: true" app/Listeners/HandleMenuItemClicked.php
      4. Assert: found (passes manual=true to UpdateService)
    Expected Result: Listener filters for correct event and triggers manual check
    Failure Indicators: Wrong event name or missing manual flag
    Evidence: .sisyphus/evidence/task-5-listener-logic.txt

  Scenario: All Pest tests pass
    Tool: Bash
    Preconditions: Test file exists
    Steps:
      1. Run: php artisan test --compact --filter=MenuItemClicked
      2. Assert: output contains "PASS" and "0 failed"
    Expected Result: All tests pass
    Failure Indicators: Any test failure
    Evidence: .sisyphus/evidence/task-5-tests.txt
  ```

  **Evidence to Capture:**
  - [ ] task-5-listener-exists.txt
  - [ ] task-5-listener-logic.txt
  - [ ] task-5-tests.txt

  **Commit**: YES
  - Message: `feat(backend): handle Check for Updates menu click`
  - Files: `app/Listeners/HandleMenuItemClicked.php`, `app/Providers/AppServiceProvider.php`, `tests/Feature/Listeners/HandleMenuItemClickedTest.php`
  - Pre-commit: `php artisan test --compact --filter=MenuItemClicked && vendor/bin/pint --dirty --format agent`

- [ ] 6. UpdateNotification Livewire Component + Toast UI + Layout Wiring + Tests

  **What to do**:
  
  **Part A — Livewire Component (`app/Livewire/UpdateNotification.php`)**:
  - Create a Livewire component that manages update notification visibility and state
  - Public properties:
    - `string $status = 'idle'` — mirrors UpdateService state
    - `string $version = ''` — available/downloaded version
    - `string $message = ''` — human-readable message for the toast
    - `bool $visible = false` — whether the toast is shown
    - `bool $isManualCheck = false` — whether the current check was manually triggered
  - `mount()` method:
    - Call `app(UpdateService::class)->clearTransientStates()` — recover from crashes
    - Call `app(UpdateService::class)->check(manual: false)` — auto-check on launch
  - `pollUpdateState()` method (called by `wire:poll`):
    - Read state from `app(UpdateService::class)->getState()`
    - Update local properties (`status`, `version`, `message`, `isManualCheck`)
    - Set `visible = true` when:
      - Status is `downloading` (always show)
      - Status is `ready` (always show)
      - Status is `error` AND `isManualCheck` is true
      - Status is `up_to_date` AND `isManualCheck` is true
    - Set `visible = false` when:
      - Status is `idle`
      - Status is `checking` (silent — no toast while checking)
      - Status is `error` AND `isManualCheck` is false (silent fail)
      - Status is `up_to_date` AND `isManualCheck` is false (silent)
  - `restartAndUpdate()` method:
    - Calls `AutoUpdater::quitAndInstall()` to quit the app and install the update
  - `dismiss()` method:
    - Sets `visible = false`
    - Calls `app(UpdateService::class)->reset()` for transient states (error, up_to_date)
    - Does NOT reset `ready` state (update is still downloaded, will install on next launch)

  **Part B — Blade Template (`resources/views/livewire/update-notification.blade.php`)**:
  - Follow the ErrorBanner pattern exactly for: position, transitions, colors, structure
  - Position: `fixed bottom-4 right-4 z-50` (same as ErrorBanner)
  - Note: Since ErrorBanner is also bottom-right, offset this component slightly: `fixed bottom-20 right-4 z-50` so they don't overlap if both are visible
  - Use Alpine.js transitions matching ErrorBanner: `ease-out 200ms` enter, `ease-in 150ms` leave, `translate-x-4 + scale-95` animation
  - Toast visual structure (following ErrorBanner's card pattern):
    - White background with `border-l-4` accent (use `#084CCF` Zed Blue for update toasts)
    - Left border: `border-l-[#084CCF]`, other borders: `border-[#084CCF]/30`
    - Icon: Blue circle with arrow-up icon — use `<x-phosphor-arrow-circle-up class="w-3 h-3 text-white" />` inside a `w-5 h-5 rounded-full bg-[#084CCF]` container
    - Title: "UPDATE" in uppercase tracking-wider, color `#084CCF` — follows ErrorBanner's title pattern (e.g., "ERROR", "WARNING")
    - Message: `$message` text in `text-sm text-[var(--text-primary)]`
    - Close button: Same as ErrorBanner (pixelarticons-close, top-right)
    - **Restart button** (only visible when `$status === 'ready'`):
      - Small `<flux:button>` with `variant="primary" size="xs"` text "Restart"
      - `wire:click="restartAndUpdate"`
      - Positioned below the message text
  - Auto-dismiss behavior (via Alpine.js):
    - For `up_to_date` and `error`: auto-dismiss after 5 seconds (same as ErrorBanner)
    - For `ready` and `downloading`: NO auto-dismiss (persistent — user must click close or restart)
  - `wire:poll.10s.visible="pollUpdateState"` on the outer div — polls every 10 seconds while visible. This is the bridge between async NativePHP events and the Livewire component.
  - The poll also runs when NOT visible, which is how the component detects state changes from menu clicks. Use `wire:poll.10s="pollUpdateState"` (without `.visible`) to ensure polling even when toast is hidden.

  **Part C — Layout Wiring (`resources/views/livewire/app-layout.blade.php`)**:
  - Add `@livewire('update-notification', key('update-notification'))` right after the existing `@livewire('error-banner', ...)` line (line 19)
  - This ensures the component is present on every page load

  **Part D — Tests (`tests/Feature/Livewire/UpdateNotificationTest.php`)**:
  - Use `Livewire::test(UpdateNotification::class)` patterns
  - Test that component renders without errors
  - Test that `mount()` calls `AutoUpdater::checkForUpdates()` (mock the facade)
  - Test that `pollUpdateState()` reads from UpdateService and sets visibility correctly
  - Test that `ready` status makes toast visible with version text
  - Test that `error` status with manual=true makes toast visible
  - Test that `error` status with manual=false keeps toast hidden
  - Test that `dismiss()` hides the toast and resets transient states
  - Test that `restartAndUpdate()` calls `AutoUpdater::quitAndInstall()` (mock)
  - Mock `AutoUpdater` facade in all tests to prevent actual API calls

  **Must NOT do**:
  - Do NOT modify the existing ErrorBanner component or its template
  - Do NOT add system notifications (toast only)
  - Do NOT add a download progress bar (just "Downloading update..." text)
  - Do NOT add release notes display
  - Do NOT add settings or preferences
  - Do NOT use `wire:poll` faster than 10 seconds
  - Do NOT add action buttons beyond "Restart" (no "Install Later", no "View Release Notes")

  **Recommended Agent Profile**:
  - **Category**: `visual-engineering`
    - Reason: Significant Blade template work with precise Catppuccin styling, Alpine.js transitions, and Livewire reactive behavior
  - **Skills**: [`livewire-development`, `tailwindcss-development`, `fluxui-development`, `pest-testing`]
    - `livewire-development`: Core skill — building a Livewire component with polling, events, and state management
    - `tailwindcss-development`: Styling the toast with Tailwind v4 utilities matching Catppuccin palette
    - `fluxui-development`: Using `<flux:button>` for the Restart button
    - `pest-testing`: Writing Livewire component tests with `Livewire::test()`
  - **Skills Evaluated but Omitted**:
    - None — all relevant skills activated

  **Parallelization**:
  - **Can Run In Parallel**: NO
  - **Parallel Group**: Wave 3 (solo)
  - **Blocks**: F1-F4
  - **Blocked By**: Tasks 2 (UpdateService), 4 (event listeners), 5 (menu listener)

  **References**:

  **Pattern References**:
  - `app/Livewire/ErrorBanner.php:1-39` — **Primary Livewire pattern.** Copy the component structure: public properties, `#[On()]` attribute pattern, `dismiss()` method, `render()` method. The UpdateNotification should feel like a sibling.
  - `resources/views/livewire/error-banner.blade.php:1-86` — **Primary Blade pattern.** Copy: Alpine.js `x-data`/`x-show`/`x-transition` structure, the card layout (icon → content → close button), color application pattern with `:class` bindings, auto-dismiss timer via `setTimeout`.
  - `app/Livewire/AutoFetchIndicator.php:26-29` — Shows `mount()` → `checkAndFetch()` pattern for triggering action on component mount.
  - `resources/views/livewire/auto-fetch-indicator.blade.php:2` — Shows `wire:poll.30s.visible` usage pattern.

  **API/Type References**:
  - `app/Services/UpdateService.php` — (created in Task 2) The `getState()` method returns `['status', 'version', 'message', 'manual']`. The `check()`, `reset()`, `clearTransientStates()` methods.
  - `vendor/nativephp/desktop/src/Facades/AutoUpdater.php` — For `AutoUpdater::checkForUpdates()` in mount and `AutoUpdater::quitAndInstall()` in restart action.

  **Test References**:
  - `tests/Feature/Livewire/` — Check if this directory exists. If it has existing Livewire tests, follow their patterns. If not, create it and follow Pest Livewire testing conventions.
  - `tests/Feature/Services/NotificationServiceTest.php:43` — Shows how to handle NativePHP facade mocking: `test('handles missing NativePHP gracefully', ...)`.

  **External References**:
  - ErrorBanner color values: `#084CCF` (Zed Blue) for update-specific toasts. ErrorBanner uses: `#D91440` (error), `#E05800` (warning), `#4040B0` (info), `#1E8C0A` (success). Updates get the accent color.

  **WHY Each Reference Matters**:
  - `ErrorBanner.php` + template: The toast MUST look visually consistent with the existing error banner. Same card shape, same transition, same position zone, same close button. Only the color and content differ.
  - `AutoFetchIndicator`: Shows the polling + mount pattern that this component needs.
  - `UpdateService`: The component reads from this service — must match its API exactly.
  - Color values: Using `#084CCF` (accent/Zed Blue) for updates distinguishes them from error/warning/info/success toasts.

  **Acceptance Criteria**:

  - [ ] File exists: `app/Livewire/UpdateNotification.php`
  - [ ] File exists: `resources/views/livewire/update-notification.blade.php`
  - [ ] Component included in `app-layout.blade.php`
  - [ ] `php artisan test --compact --filter=UpdateNotification` → PASS

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Component renders without errors
    Tool: Bash (php artisan tinker)
    Preconditions: All dependencies (UpdateService, listeners) exist
    Steps:
      1. Run: php artisan tinker --execute="
          \Native\Desktop\Facades\AutoUpdater::shouldReceive('checkForUpdates')->once();
          echo 'Mock set up';
          "
      2. Note: Direct Livewire rendering test via Pest is more reliable
      3. Run: php artisan test --compact --filter="UpdateNotification.*renders"
      4. Assert: test passes
    Expected Result: Component renders without throwing
    Failure Indicators: Class not found, template error, or missing dependency
    Evidence: .sisyphus/evidence/task-6-component-renders.txt

  Scenario: Component is wired into app layout
    Tool: Bash (grep)
    Preconditions: app-layout.blade.php exists
    Steps:
      1. Run: grep "update-notification" resources/views/livewire/app-layout.blade.php
      2. Assert: output contains "@livewire('update-notification'"
    Expected Result: Component is included in the main layout
    Failure Indicators: grep returns empty
    Evidence: .sisyphus/evidence/task-6-layout-wiring.txt

  Scenario: Toast template uses correct Catppuccin colors
    Tool: Bash (grep)
    Preconditions: Template file exists
    Steps:
      1. Run: grep "#084CCF" resources/views/livewire/update-notification.blade.php
      2. Assert: Zed Blue accent color used for update toast border/icon
      3. Run: grep "fixed.*bottom.*right.*z-50" resources/views/livewire/update-notification.blade.php
      4. Assert: positioned at bottom-right like ErrorBanner
      5. Run: grep "wire:poll" resources/views/livewire/update-notification.blade.php
      6. Assert: polling is configured
    Expected Result: Toast follows design system and has polling
    Failure Indicators: Wrong colors, wrong position, missing polling
    Evidence: .sisyphus/evidence/task-6-toast-styling.txt

  Scenario: Restart button present for ready state
    Tool: Bash (grep)
    Preconditions: Template file exists
    Steps:
      1. Run: grep "restartAndUpdate" resources/views/livewire/update-notification.blade.php
      2. Assert: found (restart action wired)
      3. Run: grep "Restart" resources/views/livewire/update-notification.blade.php
      4. Assert: found (button label)
      5. Run: grep "ready" resources/views/livewire/update-notification.blade.php
      6. Assert: found (conditional rendering for ready state)
    Expected Result: Restart button exists, conditionally shown for ready state
    Failure Indicators: Missing restart action or button
    Evidence: .sisyphus/evidence/task-6-restart-button.txt

  Scenario: All Pest tests pass
    Tool: Bash
    Preconditions: Test file exists
    Steps:
      1. Run: php artisan test --compact --filter=UpdateNotification
      2. Assert: output contains "PASS" and "0 failed"
    Expected Result: All component tests pass
    Failure Indicators: Any test failure
    Evidence: .sisyphus/evidence/task-6-tests.txt
  ```

  **Evidence to Capture:**
  - [ ] task-6-component-renders.txt
  - [ ] task-6-layout-wiring.txt
  - [ ] task-6-toast-styling.txt
  - [ ] task-6-restart-button.txt
  - [ ] task-6-tests.txt

  **Commit**: YES
  - Message: `feat(layout): add update notification toast component`
  - Files: `app/Livewire/UpdateNotification.php`, `resources/views/livewire/update-notification.blade.php`, `resources/views/livewire/app-layout.blade.php`, `tests/Feature/Livewire/UpdateNotificationTest.php`
  - Pre-commit: `php artisan test --compact --filter=UpdateNotification && vendor/bin/pint --dirty --format agent`

---

## Final Verification Wave

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists (read file, run command). For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Code Quality Review** — `unspecified-high`
  Run `vendor/bin/pint --dirty --format agent` + `php artisan test --compact`. Review all changed files for: `as any`/`@ts-ignore` equivalents, empty catches, commented-out code, unused imports. Check AI slop: excessive comments, over-abstraction, generic names. Verify all PHP files have `declare(strict_types=1)`.
  Output: `Pint [PASS/FAIL] | Tests [N pass/N fail] | Files [N clean/N issues] | VERDICT`

- [ ] F3. **Real Manual QA** — `unspecified-high`
  Start from clean state. Verify: UpdateService can be instantiated as singleton. Fire mock NativePHP events via `php artisan tinker` and verify state transitions. Check UpdateNotification component renders. Check toast template has correct Catppuccin colors. Verify menu item exists in NativeAppServiceProvider. Verify `.env.example` has all required GitHub env vars.
  Output: `Service [OK/FAIL] | Events [N/N] | Component [OK/FAIL] | Config [OK/FAIL] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual diff. Verify 1:1 — everything in spec was built, nothing beyond spec was built. Check "Must NOT do" compliance: no settings UI, no release notes viewer, no progress bar, no CI/CD, no ErrorBanner modifications. Flag unaccounted changes.
  Output: `Tasks [N/N compliant] | Scope [CLEAN/N issues] | VERDICT`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `chore(backend): configure GitHub Releases as default update provider` | config/nativephp.php, .env.example | grep for env vars |
| 2 | `feat(backend): add UpdateService for tracking app update state` | app/Services/UpdateService.php, app/Providers/AppServiceProvider.php, tests/Feature/Services/UpdateServiceTest.php | php artisan test --filter=UpdateService |
| 3 | `feat(header): add Check for Updates menu item` | app/Providers/NativeAppServiceProvider.php | grep for menu item |
| 4 | `feat(backend): add event listeners for NativePHP auto-updater` | app/Listeners/Handle*.php, tests/Feature/Listeners/UpdateListenersTest.php | php artisan test --filter=UpdateListeners |
| 5 | `feat(backend): handle Check for Updates menu click` | app/Listeners/HandleMenuItemClicked.php, tests/Feature/Listeners/HandleMenuItemClickedTest.php | php artisan test --filter=MenuItemClicked |
| 6 | `feat(layout): add update notification toast component` | app/Livewire/UpdateNotification.php, resources/views/livewire/update-notification.blade.php, resources/views/livewire/app-layout.blade.php, tests/Feature/Livewire/UpdateNotificationTest.php | php artisan test --filter=UpdateNotification |

---

## Success Criteria

### Verification Commands
```bash
php artisan test --compact --filter=Update  # Expected: all tests pass
php artisan test --compact --filter=MenuItemClicked  # Expected: all tests pass
grep -q "NATIVEPHP_UPDATER_PROVIDER=github" .env.example  # Expected: found
grep -q "Check for Updates" app/Providers/NativeAppServiceProvider.php  # Expected: found
grep -q "update-notification" resources/views/livewire/app-layout.blade.php  # Expected: found
vendor/bin/pint --dirty --test --format agent  # Expected: no fixable issues
```

### Final Checklist
- [ ] All "Must Have" present
- [ ] All "Must NOT Have" absent
- [ ] All tests pass
- [ ] Pint formatting clean
