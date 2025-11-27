# Test Infrastructure Fix - Laravel 12 Migration Confirmations

**Date:** 2025-11-16  
**Issue:** BadMethodCallException during test runs due to interactive console confirmations  
**Status:** ✅ Resolved

## Problem

Laravel 12 introduced stricter production safety confirmations that triggered even in testing environments when using `RefreshDatabase` trait. This caused all tests to fail with:

```
BadMethodCallException   
Received Mockery_1_Illuminate_Console_OutputStyle::askQuestion(), but no expectations were specified

at vendor\symfony\console\Style\SymfonyStyle.php:234
```

The root cause: `RefreshDatabase` trait's `migrate:fresh` command was triggering interactive confirmation prompts, but Mockery (test double library) had no expectations configured for these prompts.

## Solution

Created a custom `RefreshDatabaseSilently` trait that overrides Laravel's default behavior:

### 1. Created Custom Trait

**File:** `fwber-backend/tests/Traits/RefreshDatabaseSilently.php`

```php
protected function refreshDatabase(): void
{
    // Run migrations programmatically without console confirmation
    $this->artisan('migrate:fresh', ['--force' => true])->run();
}
```

This forces non-interactive migration execution by passing `--force` flag directly to Artisan.

### 2. Updated All Test Files

Replaced all occurrences of:
```php
use Illuminate\Foundation\Testing\RefreshDatabase;
// ...
use RefreshDatabase;
```

With:
```php
use Tests\Traits\RefreshDatabaseSilently;
// ...
use RefreshDatabaseSilently;
```

**Automation Script:** `update-test-database-trait.ps1`  
**Files Updated:** 23 test files across Feature/ directory

## Results

### Before Fix
- ❌ 173 failed tests (BadMethodCallException)
- ✅ 7 passed tests
- Duration: ~57s

### After Fix
- ❌ 124 failed tests (legitimate test failures, e.g., 404s, missing endpoints)
- ⏭️ 10 skipped tests
- ✅ 46 passed tests
- Duration: ~745s (longer due to actual test execution)

The fix successfully resolved the infrastructure issue. Remaining failures are functional test issues that need to be addressed individually (missing routes, incomplete implementations, etc.).

## Files Modified

1. `fwber-backend/tests/Traits/RefreshDatabaseSilently.php` - Created
2. `update-test-database-trait.ps1` - Created automation script
3. `fwber-backend/tests/Feature/*.php` - 23 files updated to use new trait

## Related Documentation

- `docs/implementations/MODERATION_TEST_SUITE_READY_2025-11-16.md` - Phase 2 moderation tests prepared
- `fwber-backend/tests/Feature/Phase2/ModerationControllerTest.php` - Moved to Phase 2 directory (not blocking MVP)

## Notes

- This fix applies to Laravel 12's stricter environment detection
- Earlier Laravel versions may not require this workaround
- The `--force` flag bypasses production safety checks in test environment
- `phpunit.xml` already sets `APP_ENV=testing`, but Laravel 12 requires explicit `--force` for migrate:fresh

## MVP Impact

✅ Zero negative impact - test infrastructure now works correctly  
✅ MVP tests can now run without infrastructure failures  
✅ Phase 2 tests isolated to avoid interference with MVP test suite  
✅ Future tests can use `RefreshDatabaseSilently` trait out of the box
