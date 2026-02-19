# Fix: Remove APP_KEY from .env.testing

## TL;DR

> **Quick Summary**: Remove the hardcoded `APP_KEY` from `.env.testing` to resolve GitLab's secret detection warning.
> 
> **Deliverables**:
> - `.env.testing` with empty `APP_KEY=`
> 
> **Estimated Effort**: Quick
> **Parallel Execution**: NO — single task
> **Critical Path**: Task 1 → done

---

## Context

### Original Request
GitLab flags a security warning because `.env.testing` contains a committed `APP_KEY`. Remove it so the warning goes away and the key is no longer in plaintext in the repo.

---

## Work Objectives

### Core Objective
Remove the hardcoded `APP_KEY` value from `.env.testing` so it's no longer committed to the repository.

### Concrete Deliverables
- `.env.testing` with `APP_KEY=` (empty value)

### Definition of Done
- [ ] `.env.testing` contains `APP_KEY=` with no value
- [ ] Change is committed

### Must Have
- `APP_KEY=` line remains (just empty — don't delete the line)

### Must NOT Have (Guardrails)
- Do NOT delete `.env.testing` entirely
- Do NOT add `.env.testing` to `.gitignore` (it's intentionally tracked)
- Do NOT rewrite git history
- Do NOT touch any other files

---

## Verification Strategy

> **ZERO HUMAN INTERVENTION**

### Test Decision
- **Automated tests**: None needed — this is a config-only change

---

## Execution Strategy

### Parallel Execution Waves

```
Wave 1 (single task):
└── Task 1: Remove APP_KEY value from .env.testing [quick]

Wave FINAL: N/A — too trivial for a verification wave
```

---

## TODOs

- [ ] 1. Remove APP_KEY value from .env.testing

  **What to do**:
  - Open `.env.testing`
  - Change `APP_KEY=base64:J1DdTkCmTzS1pAgbsOb9DJV+/rl9Si5xme2LIAF0A/w=` to `APP_KEY=`
  - That's it.

  **Must NOT do**:
  - Do not delete the file
  - Do not remove the `APP_KEY=` line entirely
  - Do not touch `APP_URL` or any other lines

  **Recommended Agent Profile**:
  - **Category**: `quick`
  - **Skills**: []

  **Parallelization**:
  - **Can Run In Parallel**: NO (only task)
  - **Blocked By**: None

  **References**:
  - `.env.testing` — the only file to edit

  **Acceptance Criteria**:
  - [ ] `.env.testing` line 1 reads exactly `APP_KEY=`
  - [ ] No other lines changed

  **QA Scenarios**:

  ```
  Scenario: APP_KEY is empty after edit
    Tool: Bash (grep)
    Steps:
      1. Run: grep 'APP_KEY=' .env.testing
    Expected Result: Output is exactly `APP_KEY=` with no value after the equals sign
    Evidence: .sisyphus/evidence/task-1-appkey-empty.txt
  ```

  **Commit**: YES
  - Message: `fix(backend): remove hardcoded APP_KEY from .env.testing`
  - Files: `.env.testing`

---

## Success Criteria

### Final Checklist
- [ ] `APP_KEY=` has no value in `.env.testing`
- [ ] Change committed
