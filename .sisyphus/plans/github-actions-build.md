# GitHub Actions Build Pipeline for Gitty

## TL;DR

> **Quick Summary**: Set up a GitHub Actions CI/CD pipeline that builds the gitty NativePHP macOS desktop app (arm64 + x64 DMGs) on git tag push, runs tests first, and publishes build artifacts to GitHub Releases for auto-updates.
> 
> **Deliverables**:
> - `.github/workflows/build.yml` — tag-triggered build workflow
> - NativePHP updater config switched to GitHub Releases provider
> - `.env.example` updated with GitHub updater variables
> - `.gitignore` updated for build artifacts
> 
> **Estimated Effort**: Short
> **Parallel Execution**: YES - 2 waves
> **Critical Path**: Task 1 + Task 2 (parallel) → Task 3 (verification)

---

## Context

### Original Request
User wants to deploy gitty by connecting to GitHub and having a GitHub Action that builds the app.

### Interview Summary
**Key Discussions**:
- **Repo status**: Not yet on GitHub — user will create public repo manually
- **Target**: macOS only (arm64 + x64 DMGs)
- **Trigger**: Git tags matching `v*` pattern
- **Distribution**: GitHub Releases (NativePHP updater)
- **Code signing**: Skipped for now (unsigned builds, Gatekeeper will warn)
- **Tests**: Run Pest tests before building (fail fast if tests fail)

**Research Findings**:
- NativePHP Desktop v2.1 uses electron-builder under the hood
- Build command: `php artisan native:build mac` (builds for current platform)
- `php artisan native:publish mac` builds AND uploads to configured updater provider
- Pre-build hooks already configured in `config/nativephp.php`: `npm run build`
- Community examples exist: clueless app and larajobs-desktop have working workflows
- Tests use SQLite in-memory — no external services needed in CI
- PHP 8.4, Node 22+, Composer required for builds
- `php artisan native:install --no-interaction` must run before building
- macOS runners needed (no cross-compilation from Linux for macOS builds)
- Public repo = unlimited free GitHub Actions minutes

### Metis Review
**Identified Gaps** (addressed):
- Version management: Resolved — extract from git tag dynamically, set as `NATIVEPHP_APP_VERSION`
- Release type: Resolved — use `draft` releases (matches NativePHP default, user reviews before publishing)
- Architecture matrix: Resolved — parallel arm64 + x64 builds with `fail-fast: true`
- `GITHUB_TOKEN` permissions: Resolved — use default `secrets.GITHUB_TOKEN` with `contents: write` permission
- Tests database: Resolved — SQLite in-memory per `phpunit.xml`
- Gatekeeper UX: Resolved — README section with bypass instructions

---

## Work Objectives

### Core Objective
Create a fully automated build pipeline that produces signed-ready macOS DMGs on every version tag, with test validation before building.

### Concrete Deliverables
- `.github/workflows/build.yml` — GitHub Actions workflow file
- Updated `config/nativephp.php` — updater provider switched to `github`
- Updated `.env.example` — GitHub updater env vars documented
- Updated `.gitignore` — build artifact exclusions

### Definition of Done
- [ ] Pushing a `v*` tag triggers the workflow
- [ ] Tests run and pass before build starts
- [ ] Two DMG files (arm64 + x64) are attached to a GitHub Release draft
- [ ] NativePHP updater config points to GitHub Releases

### Must Have
- Tag-triggered workflow (`v*` pattern)
- Pest test execution before build
- Parallel arm64 + x64 macOS builds
- GitHub Release draft with DMG artifacts attached
- NativePHP updater configured for GitHub provider
- Version extracted from git tag (no manual version syncing)

### Must NOT Have (Guardrails)
- NO Windows or Linux builds (macOS only)
- NO code signing or notarization (deferred — no Apple credentials yet)
- NO auto-tagging or semantic-release automation (manual tags only)
- NO Slack/Discord/email notifications
- NO build caching optimizations (functional first, optimize later)
- NO changelog generation
- NO branch protection rules setup
- NO Bifrost integration

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION** — ALL verification is agent-executed. No exceptions.

### Test Decision
- **Infrastructure exists**: YES
- **Automated tests**: Tests-after (verify workflow syntax, validate YAML)
- **Framework**: Pest (PHP tests run inside the workflow; YAML linting for workflow file)

### QA Policy
Every task MUST include agent-executed QA scenarios.
Evidence saved to `.sisyphus/evidence/task-{N}-{scenario-slug}.{ext}`.

| Deliverable Type | Verification Tool | Method |
|------------------|-------------------|--------|
| YAML workflow | Bash (yq/yamllint) | Parse YAML, validate structure |
| PHP config | Bash (php -r) | Parse PHP config, verify values |
| .env.example | Bash (grep) | Verify expected keys present |
| .gitignore | Bash (grep) | Verify expected patterns present |

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (Start Immediately — all independent):
├── Task 1: Create GitHub Actions build workflow [unspecified-high]
├── Task 2: Update NativePHP config + .env.example [quick]
└── Task 3: Update .gitignore for build artifacts [quick]

Wave FINAL (After ALL tasks — independent review, 4 parallel):
├── Task F1: Plan compliance audit (oracle)
├── Task F2: Code quality review (unspecified-high)
├── Task F3: Real manual QA (unspecified-high)
└── Task F4: Scope fidelity check (deep)

Critical Path: Tasks 1-3 (parallel) → F1-F4 (parallel)
Parallel Speedup: All implementation tasks run simultaneously
Max Concurrent: 3 (Wave 1)
```

### Dependency Matrix

| Task | Depends On | Blocks | Wave |
|------|------------|--------|------|
| 1 | — | F1-F4 | 1 |
| 2 | — | F1-F4 | 1 |
| 3 | — | F1-F4 | 1 |

### Agent Dispatch Summary

| Wave | # Parallel | Tasks → Agent Category |
|------|------------|----------------------|
| 1 | **3** | T1 → `unspecified-high`, T2 → `quick`, T3 → `quick` |
| FINAL | **4** | F1 → `oracle`, F2 → `unspecified-high`, F3 → `unspecified-high`, F4 → `deep` |

---

## TODOs

- [ ] 1. Create GitHub Actions Build Workflow

  **What to do**:
  - Create `.github/workflows/build.yml` with the following structure:
    - **Trigger**: `push: tags: ['v*']`
    - **Permissions**: `contents: write` (needed for creating releases)
    - **Job: test** — runs on `ubuntu-latest` (cheaper than macOS, tests don't need macOS):
      1. `actions/checkout@v4`
      2. `shivammathur/setup-php@v2` with `php-version: '8.4'` and extensions: `dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite`
      3. `actions/setup-node@v4` with `node-version: '22'`
      4. `composer install --no-dev --optimize-autoloader --no-interaction`
      5. `npm ci`
      6. `npm run build`
      7. Copy `.env.example` to `.env` and run `php artisan key:generate`
      8. `php artisan test --compact`
    - **Job: build** — runs on `macos-latest`, `needs: [test]`:
      - **Strategy matrix**: `arch: [arm64, x64]` with `fail-fast: true`
      - **Steps**:
        1. `actions/checkout@v4`
        2. `shivammathur/setup-php@v2` with `php-version: '8.4'`
        3. `actions/setup-node@v4` with `node-version: '22'`
        4. `composer install --no-dev --optimize-autoloader --no-interaction`
        5. `npm ci`
        6. Copy `.env.example` to `.env`, run `php artisan key:generate`
        7. Extract version from tag: `echo "VERSION=${GITHUB_REF#refs/tags/v}" >> $GITHUB_OUTPUT`
        8. Set `NATIVEPHP_APP_VERSION` env var from extracted version
        9. Set updater env vars: `NATIVEPHP_UPDATER_PROVIDER=github`, `GITHUB_REPO`, `GITHUB_OWNER`, `GITHUB_TOKEN`
        10. `php artisan native:install --no-interaction`
        11. `php artisan native:build mac --no-interaction` (NativePHP handles architecture based on runner)
        12. Upload build artifacts using `actions/upload-artifact@v4`
    - **Job: release** — runs on `ubuntu-latest`, `needs: [build]`:
      1. Download all build artifacts from previous job
      2. Use `softprops/action-gh-release@v2` to create a draft GitHub Release
      3. Attach DMG files + blockmap files + latest-mac.yml to the release
      4. Tag the release with the version from the trigger
  - **Important nuances**:
    - NativePHP's `native:build` detects the architecture from the runner, but we may need to pass architecture explicitly. Check if `php artisan native:build mac arm64` or `php artisan native:build mac x64` works. If not, use `--arch` flag or set environment variable.
    - The `prebuild` hook in `config/nativephp.php` already runs `npm run build`, so frontend assets will be compiled during the build step automatically.
    - `GITHUB_TOKEN` is auto-provided by GitHub Actions as `secrets.GITHUB_TOKEN`.
    - For the GitHub updater provider, set: `GITHUB_OWNER=${{ github.repository_owner }}`, `GITHUB_REPO=${{ github.event.repository.name }}`, `GITHUB_TOKEN=${{ secrets.GITHUB_TOKEN }}`.

  **Must NOT do**:
  - Do NOT add code signing env vars or certificate handling (no Apple credentials yet)
  - Do NOT add Windows or Linux build jobs
  - Do NOT add notification steps (Slack, Discord, etc.)
  - Do NOT add dependency caching (optimize later)
  - Do NOT add auto-tagging or semantic-release
  - Do NOT use `native:publish` — use `native:build` since we handle release creation separately via `softprops/action-gh-release`

  **Recommended Agent Profile**:
  - **Category**: `unspecified-high`
    - Reason: YAML workflow creation requires understanding GitHub Actions syntax, NativePHP build process, and matrix strategies. Not a quick task, but not deep algorithmic work either.
  - **Skills**: []
    - No project-specific skills needed — this is infrastructure/DevOps work
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — no Livewire component work
    - `pest-testing`: Tests run inside the workflow, but the task itself isn't writing tests

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 2, 3)
  - **Blocks**: F1, F2, F3, F4
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL):

  **Pattern References** (existing code to follow):
  - `config/nativephp.php:165-171` — Pre-build hooks already configured (`npm run build`), shows build pipeline integration
  - `config/nativephp.php:94-148` — Updater configuration with GitHub provider settings, shows which env vars are needed
  - `config/nativephp.php:61-76` — `cleanup_env_keys` array shows which env vars get stripped from production builds (secrets are auto-removed)
  - `phpunit.xml:29-30` — Tests use SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), confirming no external DB needed in CI

  **API/Type References** (contracts to implement against):
  - NativePHP build commands: `php artisan native:build mac` (builds for macOS), `php artisan native:install --no-interaction` (sets up Electron runtime)
  - GitHub Actions: `softprops/action-gh-release@v2` for creating releases, `actions/upload-artifact@v4` for intermediate artifacts

  **External References** (libraries and frameworks):
  - NativePHP v2 Building docs: https://nativephp.com/docs/desktop/2/publishing/building — build command reference, versioning, cross-compilation
  - NativePHP v2 Publishing docs: https://nativephp.com/docs/desktop/2/publishing/publishing — `native:publish` command, GitHub Releases integration
  - NativePHP v2 Updating docs: https://nativephp.com/docs/desktop/2/publishing/updating — updater configuration, GitHub provider setup
  - Community example (clueless app): https://github.com/vijaythecoder/clueless — working NativePHP GitHub Actions workflow
  - Community example (larajobs desktop): https://github.com/LukeTowers/larajobs-desktop — another working workflow with `native:publish`
  - `softprops/action-gh-release`: https://github.com/softprops/action-gh-release — GitHub Release action docs

  **WHY Each Reference Matters**:
  - `config/nativephp.php` is the source of truth for build configuration — the workflow must match these settings
  - `phpunit.xml` confirms test environment needs — no MySQL/Redis service containers needed
  - Community examples show proven patterns for NativePHP CI (PHP setup, native:install, build steps)
  - NativePHP docs clarify `native:build` vs `native:publish` distinction and versioning requirements

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: Workflow file is valid YAML with correct trigger
    Tool: Bash
    Preconditions: .github/workflows/build.yml exists
    Steps:
      1. Run: cat .github/workflows/build.yml | head -20
      2. Assert: file starts with `name:` key
      3. Run: grep -A3 "on:" .github/workflows/build.yml
      4. Assert: output contains "tags:" with "v*" pattern
      5. Run: php -r "echo yaml_parse_file('.github/workflows/build.yml') !== false ? 'VALID' : 'INVALID';" || python3 -c "import yaml; yaml.safe_load(open('.github/workflows/build.yml')); print('VALID')"
      6. Assert: output is "VALID"
    Expected Result: Workflow file is syntactically valid YAML with `push: tags: ['v*']` trigger
    Failure Indicators: YAML parse error, missing trigger, wrong tag pattern
    Evidence: .sisyphus/evidence/task-1-yaml-validation.txt

  Scenario: Workflow has correct job structure and dependencies
    Tool: Bash
    Preconditions: .github/workflows/build.yml exists
    Steps:
      1. Run: grep "runs-on:" .github/workflows/build.yml
      2. Assert: output contains both "ubuntu-latest" (test job) and "macos-latest" (build job)
      3. Run: grep "needs:" .github/workflows/build.yml
      4. Assert: build job has "needs: [test]", release job has "needs: [build]"
      5. Run: grep "permissions:" .github/workflows/build.yml
      6. Assert: output contains "contents: write"
      7. Run: grep -c "matrix" .github/workflows/build.yml
      8. Assert: count > 0 (matrix strategy present for architectures)
    Expected Result: Three jobs (test → build → release) with correct runners and dependencies
    Failure Indicators: Missing job, wrong runner, missing needs dependency, no permissions block
    Evidence: .sisyphus/evidence/task-1-job-structure.txt

  Scenario: Workflow references secrets correctly (no hardcoded values)
    Tool: Bash
    Preconditions: .github/workflows/build.yml exists
    Steps:
      1. Run: grep -n "secrets\." .github/workflows/build.yml
      2. Assert: GITHUB_TOKEN referenced as ${{ secrets.GITHUB_TOKEN }}
      3. Run: grep -n "NATIVEPHP_APPLE" .github/workflows/build.yml
      4. Assert: NO matches found (code signing is excluded)
      5. Run: grep -rn "ghp_\|gho_\|password\|secret" .github/workflows/build.yml
      6. Assert: NO hardcoded tokens or passwords found
    Expected Result: Only `secrets.GITHUB_TOKEN` referenced, no code signing vars, no hardcoded secrets
    Failure Indicators: Hardcoded token, Apple signing vars present, raw passwords
    Evidence: .sisyphus/evidence/task-1-secrets-check.txt
  ```

  **Evidence to Capture:**
  - [ ] task-1-yaml-validation.txt
  - [ ] task-1-job-structure.txt
  - [ ] task-1-secrets-check.txt

  **Commit**: YES
  - Message: `chore(backend): add GitHub Actions build workflow for macOS`
  - Files: `.github/workflows/build.yml`
  - Pre-commit: YAML validation check

- [ ] 2. Switch NativePHP Updater Config to GitHub Releases

  **What to do**:
  - In `config/nativephp.php`, change the default updater provider from `spaces` to `github`:
    - Line 107: Change `env('NATIVEPHP_UPDATER_PROVIDER', 'spaces')` → `env('NATIVEPHP_UPDATER_PROVIDER', 'github')`
  - In `.env.example`, add the GitHub updater environment variables:
    ```
    # NativePHP Updater (GitHub Releases)
    NATIVEPHP_UPDATER_PROVIDER=github
    GITHUB_REPO=gitty
    GITHUB_OWNER=
    GITHUB_TOKEN=
    NATIVEPHP_APP_VERSION=1.0.0
    ```
  - Ensure `NATIVEPHP_APP_AUTHOR` and `NATIVEPHP_APP_COPYRIGHT` are also in `.env.example` for completeness

  **Must NOT do**:
  - Do NOT remove S3 or Spaces provider configs (they should remain as alternatives)
  - Do NOT add actual secret values to `.env.example`
  - Do NOT modify updater behavior or events
  - Do NOT change `app_id`, `description`, or other NativePHP config values

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Simple config value change + adding env vars to example file. Two files, <10 lines changed.
  - **Skills**: []
    - No specialized skills needed for config edits
  - **Skills Evaluated but Omitted**:
    - `livewire-development`: Not relevant — config file, not component

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 3)
  - **Blocks**: F1, F2, F3, F4
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL):

  **Pattern References** (existing code to follow):
  - `config/nativephp.php:107` — Current default: `env('NATIVEPHP_UPDATER_PROVIDER', 'spaces')` — change `'spaces'` to `'github'`
  - `config/nativephp.php:110-120` — GitHub provider config block showing all required env vars (`GITHUB_REPO`, `GITHUB_OWNER`, `GITHUB_TOKEN`, etc.)
  - `.env.example` — Current env template (check existing structure and formatting conventions)

  **External References**:
  - NativePHP Updating docs: https://nativephp.com/docs/desktop/2/publishing/updating — updater configuration reference

  **WHY Each Reference Matters**:
  - Line 107 is the exact line to change — the executor must match the existing pattern of `env('KEY', 'default')`
  - Lines 110-120 show which env vars the GitHub provider reads — `.env.example` must include all of them
  - `.env.example` structure determines formatting convention (comments style, grouping, ordering)

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: NativePHP config defaults to GitHub updater
    Tool: Bash
    Preconditions: config/nativephp.php exists
    Steps:
      1. Run: grep "NATIVEPHP_UPDATER_PROVIDER" config/nativephp.php
      2. Assert: output contains "'github'" as default value (not 'spaces')
      3. Run: php -l config/nativephp.php
      4. Assert: output is "No syntax errors detected"
    Expected Result: Default updater provider is `github`, PHP syntax is valid
    Failure Indicators: Still says 'spaces', PHP syntax error
    Evidence: .sisyphus/evidence/task-2-config-check.txt

  Scenario: .env.example contains GitHub updater variables
    Tool: Bash
    Preconditions: .env.example exists
    Steps:
      1. Run: grep "NATIVEPHP_UPDATER_PROVIDER" .env.example
      2. Assert: line exists with value "github"
      3. Run: grep "GITHUB_REPO" .env.example
      4. Assert: line exists
      5. Run: grep "GITHUB_OWNER" .env.example
      6. Assert: line exists
      7. Run: grep "GITHUB_TOKEN" .env.example
      8. Assert: line exists (empty value, no actual token)
      9. Run: grep "NATIVEPHP_APP_VERSION" .env.example
      10. Assert: line exists
    Expected Result: All 5 GitHub updater env vars present in .env.example with no actual secrets
    Failure Indicators: Missing env var, contains actual token value
    Evidence: .sisyphus/evidence/task-2-env-check.txt

  Scenario: S3 and Spaces providers still exist as alternatives
    Tool: Bash
    Preconditions: config/nativephp.php exists
    Steps:
      1. Run: grep "'s3'" config/nativephp.php
      2. Assert: S3 provider config block still present
      3. Run: grep "'spaces'" config/nativephp.php
      4. Assert: Spaces provider config block still present (only the default changed, not the provider definitions)
    Expected Result: All 3 provider configs (github, s3, spaces) remain intact
    Failure Indicators: S3 or Spaces provider block deleted
    Evidence: .sisyphus/evidence/task-2-providers-check.txt
  ```

  **Evidence to Capture:**
  - [ ] task-2-config-check.txt
  - [ ] task-2-env-check.txt
  - [ ] task-2-providers-check.txt

  **Commit**: YES
  - Message: `chore(backend): switch NativePHP updater to GitHub Releases provider`
  - Files: `config/nativephp.php`, `.env.example`
  - Pre-commit: `php -l config/nativephp.php`

- [ ] 3. Update .gitignore for Build Artifacts

  **What to do**:
  - Add NativePHP build artifact directories to `.gitignore`:
    ```
    # NativePHP build artifacts
    /nativephp/
    ```
  - Verify `vendor/` and `node_modules/` are already excluded (they should be in a standard Laravel `.gitignore`)
  - The `/nativephp/` directory contains Electron build output (`dist/` with DMGs, blockmaps, etc.) and should never be committed

  **Must NOT do**:
  - Do NOT remove any existing `.gitignore` entries
  - Do NOT add `.env` to gitignore if it's already there (it should be in standard Laravel gitignore)

  **Recommended Agent Profile**:
  - **Category**: `quick`
    - Reason: Single file, appending 2-3 lines. Trivial task.
  - **Skills**: []
  - **Skills Evaluated but Omitted**: None relevant

  **Parallelization**:
  - **Can Run In Parallel**: YES
  - **Parallel Group**: Wave 1 (with Tasks 1, 2)
  - **Blocks**: F1, F2, F3, F4
  - **Blocked By**: None (can start immediately)

  **References** (CRITICAL):

  **Pattern References** (existing code to follow):
  - `.gitignore` — Check existing file for formatting conventions (comment style, grouping)
  - `nativephp/electron/dist/` — Directory that contains build artifacts (DMGs, blockmaps, yml files)

  **WHY Each Reference Matters**:
  - `.gitignore` formatting must match existing style (some projects use comments, some don't)
  - The `nativephp/` directory is where NativePHP stores all Electron-related files during build

  **Acceptance Criteria**:

  **QA Scenarios (MANDATORY):**

  ```
  Scenario: .gitignore excludes NativePHP build artifacts
    Tool: Bash
    Preconditions: .gitignore exists
    Steps:
      1. Run: grep "nativephp" .gitignore
      2. Assert: output contains "/nativephp/" or "nativephp/"
      3. Run: grep "vendor" .gitignore
      4. Assert: output contains "/vendor" (already present)
      5. Run: grep "node_modules" .gitignore
      6. Assert: output contains "/node_modules" (already present)
    Expected Result: Build artifacts directory excluded, standard Laravel exclusions intact
    Failure Indicators: Missing nativephp entry, vendor/node_modules accidentally removed
    Evidence: .sisyphus/evidence/task-3-gitignore-check.txt

  Scenario: No build artifacts would be committed
    Tool: Bash
    Preconditions: .gitignore updated
    Steps:
      1. Run: git status --short | grep "nativephp/" || echo "CLEAN"
      2. Assert: output is "CLEAN" (no nativephp files in staging)
    Expected Result: No NativePHP build artifacts appear in git status
    Failure Indicators: Build artifact files showing as untracked/modified
    Evidence: .sisyphus/evidence/task-3-clean-check.txt
  ```

  **Evidence to Capture:**
  - [ ] task-3-gitignore-check.txt
  - [ ] task-3-clean-check.txt

  **Commit**: YES
  - Message: `chore(backend): update .gitignore for NativePHP build artifacts`
  - Files: `.gitignore`
  - Pre-commit: `grep nativephp .gitignore`

---

## Final Verification Wave

> 4 review agents run in PARALLEL. ALL must APPROVE. Rejection → fix → re-run.

- [ ] F1. **Plan Compliance Audit** — `oracle`
  Read the plan end-to-end. For each "Must Have": verify implementation exists (read file, check YAML structure, verify config values). For each "Must NOT Have": search codebase for forbidden patterns — reject with file:line if found. Check evidence files exist in .sisyphus/evidence/. Compare deliverables against plan.
  Output: `Must Have [N/N] | Must NOT Have [N/N] | Tasks [N/N] | VERDICT: APPROVE/REJECT`

- [ ] F2. **Code Quality Review** — `unspecified-high`
  Validate YAML syntax of workflow file. Check PHP config for correct syntax. Verify `.env.example` has no actual secrets. Check `.gitignore` patterns are correct. Look for hardcoded values that should be configurable.
  Output: `YAML [PASS/FAIL] | PHP [PASS/FAIL] | Env [PASS/FAIL] | VERDICT`

- [ ] F3. **Real Manual QA** — `unspecified-high`
  Parse the workflow YAML and verify: triggers are correct (`push: tags: v*`), jobs have correct `runs-on`, steps are in correct order (checkout → setup → install → test → build → release), env vars reference `secrets.*` correctly, matrix strategy is correct for arm64/x64, permissions block includes `contents: write`.
  Output: `Workflow Structure [PASS/FAIL] | Secrets [PASS/FAIL] | Matrix [PASS/FAIL] | VERDICT`

- [ ] F4. **Scope Fidelity Check** — `deep`
  For each task: read "What to do", read actual files created/modified. Verify 1:1 — everything in spec was built (no missing), nothing beyond spec was built (no creep). Check "Must NOT do" compliance. Flag unaccounted changes.
  Output: `Tasks [N/N compliant] | Unaccounted [CLEAN/N files] | VERDICT`

---

## Commit Strategy

| After Task | Message | Files | Verification |
|------------|---------|-------|--------------|
| 1 | `chore(backend): add GitHub Actions build workflow for macOS` | `.github/workflows/build.yml` | `yamllint .github/workflows/build.yml` |
| 2 | `chore(backend): switch NativePHP updater to GitHub Releases provider` | `config/nativephp.php`, `.env.example` | `php -l config/nativephp.php` |
| 3 | `chore(backend): update .gitignore for NativePHP build artifacts` | `.gitignore` | `cat .gitignore \| grep nativephp` |

---

## Success Criteria

### Verification Commands
```bash
# Workflow file exists and is valid YAML
cat .github/workflows/build.yml | head -5  # Expected: name: Build macOS App

# NativePHP updater config points to github
grep "NATIVEPHP_UPDATER_PROVIDER" .env.example  # Expected: NATIVEPHP_UPDATER_PROVIDER=github

# .gitignore excludes build artifacts
grep "nativephp" .gitignore  # Expected: /nativephp/

# Workflow triggers on tags
grep -A2 "push:" .github/workflows/build.yml  # Expected: tags: ['v*']
```

### Final Checklist
- [ ] All "Must Have" present
- [ ] All "Must NOT Have" absent
- [ ] Workflow file is valid YAML
- [ ] Config changes are syntactically correct PHP
