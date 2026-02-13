# Learnings

## 2026-02-13 Session Start
- Plan has 6 tasks across 3 waves
- Wave 1: Task 1 (infrastructure) — sequential, must be first
- Wave 2: Tasks 2-5 (component browser tests) — parallelizable
- Wave 3: Task 6 (integration tests) — sequential, final

## 2026-02-13: Infrastructure Setup Complete

### Installed Components
1. **Composer Packages**
   - pestphp/pest v4.3.2 (upgraded from v3.8.5)
   - phpunit/phpunit v12.5.8 (upgraded from v11.5.50)
   - pestphp/pest-plugin-browser v4.2.1

2. **Node.js Packages**
   - playwright v1.58.2
   - Chromium browser binary installed via `npx playwright install chromium`

### Configuration Files Created/Modified
1. **tests/Browser/Pest.php** - Browser test configuration with `pest()->extend(TestCase::class)->browser()->in('Browser')`
2. **tests/Browser/Helpers/BrowserTestHelper.php** - Shared helper with:
   - `MOCK_REPO_PATH` constant (`/tmp/gitty-test-repo`)
   - `SCREENSHOTS_PATH` constant
   - `setupMockRepo()` method
   - `getCommonProcessFakes()` method
   - `ensureScreenshotsDirectory()` method
3. **tests/Browser/SmokeTest.php** - Basic smoke test
4. **phpunit.xml** - Added Browser test suite
5. **.env.testing** - Added APP_KEY and APP_URL=http://localhost:8001
6. **.gitignore** - Added `/tests/Browser/screenshots/`

### Directory Structure
```
tests/
├── Browser/
│   ├── Pest.php
│   ├── Helpers/
│   │   └── BrowserTestHelper.php
│   ├── screenshots/
│   │   └── .gitkeep
│   └── SmokeTest.php
```

### Key Patterns
- Browser tests extend TestCase via `pest()->extend(TestCase::class)->browser()->in('Browser')`
- Use `RefreshDatabase` trait for database isolation
- Mock Git commands with `Process::fake()` + `GitOutputFixtures`
- Screenshots saved to `tests/Browser/screenshots/` (gitignored)
- Tests run against `php artisan serve --port=8001`

### Pest v3 → v4 Upgrade Notes
- Upgraded PHPUnit v11 → v12 (required by Pest v4)
- Existing Feature tests have LSP errors for `$testRepoPath` property (Pest v4 type inference changes)
- Service tests all pass (core functionality intact)
- Livewire tests fail due to missing APP_KEY (fixed in .env.testing)
