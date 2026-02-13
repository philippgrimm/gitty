# Issues

## 2026-02-13: Pest Browser Plugin Runtime Issue

### Problem
Browser tests fail during initialization with error: `Target class [config] does not exist`

### Context
- Pest v4.3.2 + Pest Browser Plugin v4.2.1 + Laravel 12
- Error occurs in `Illuminate\Container\Container::build()` during browser test bootstrap
- Infrastructure is correctly installed and configured
- Test is detected by Pest (`php artisan test --list-tests` shows browser test)
- Error happens before test code executes (during `Browsable::__markAsBrowserTest()`)

### Root Cause
The Pest Browser plugin's HTTP server bootstrap (`ServerManager::instance()->http()->bootstrap()`) attempts to resolve Laravel's 'config' binding, but it's not properly registered in the test container during browser test initialization.

This appears to be a compatibility issue between:
- Pest Browser Plugin v4.2.1 (released 2026-01-11)
- Laravel 12 (very new, released recently)
- PHPUnit 12 (required by Pest v4)

### Workaround Status
No immediate workaround found. This is likely a plugin compatibility issue that will be resolved in a future update.

### Infrastructure Status
✅ All infrastructure correctly set up:
- pestphp/pest-plugin-browser v4.2.1 installed
- Playwright 1.58.2 installed with Chromium
- tests/Browser/Pest.php configured
- tests/Browser/Helpers/BrowserTestHelper.php created
- tests/Browser/screenshots/ directory created and gitignored
- phpunit.xml Browser test suite added
- .env.testing with APP_URL configured
- Smoke test created

### Next Steps
- Monitor Pest Browser plugin releases for Laravel 12 compatibility fix
- Consider downgrading to Laravel 11 if browser testing is critical
- Alternative: Use Laravel Dusk until Pest Browser plugin is updated
