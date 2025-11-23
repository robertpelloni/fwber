# Test Results Directory

This directory contains end-to-end test results for the FWBer.me profile form and venue check-in features.

## Directory Structure

```
test-results/
├── README.md (this file)
├── YYYY-MM-DD-john-test.md       # Test results for male persona
├── YYYY-MM-DD-jane-test.md       # Test results for female persona
├── YYYY-MM-DD-alex-test.md       # Test results for non-binary persona
├── YYYY-MM-DD-venue-checkin.md   # Venue check-in test results
├── errors.log                     # PHP errors encountered during testing
├── screenshots/                   # Screenshots of issues/errors
│   ├── persona-field-visibility-error.png
│   ├── csrf-token-failure.png
│   └── validation-error-example.png
└── database-snapshots/            # Database state before/after tests
    ├── before-test.sql
    └── after-test.sql
```

## Test Result Template

Use this template for each persona test file:

```markdown
# E2E Test Results: [Persona Name]

**Date:** YYYY-MM-DD
**Tester:** [AI/Human Name]
**Test Duration:** X minutes
**Overall Status:** ✅ PASS / ❌ FAIL / ⚠️ PARTIAL

---

## Test Environment
- **Browser:** Chrome/Firefox/Safari
- **PHP Version:** X.X.X
- **MySQL Version:** X.X.X
- **Test Database:** fwber_test

---

## Checklist Results

### Visibility Tests
- [ ] ✅/❌ Gender-specific fields show/hide correctly
- [ ] ✅/❌ Preference fields visible
- [ ] ✅/❌ Dynamic field updates work

### Data Entry Tests
- [ ] ✅/❌ All required fields accept input
- [ ] ✅/❌ Dropdowns populate correctly
- [ ] ✅/❌ Checkboxes functional

### Validation Tests
- [ ] ✅/❌ Missing required fields caught
- [ ] ✅/❌ Age < 18 rejected
- [ ] ✅/❌ CSRF token validated

### Persistence Tests
- [ ] ✅/❌ Data saves to database
- [ ] ✅/❌ Data reloads correctly
- [ ] ✅/❌ No data leakage between genders

---

## Issues Found

### Issue #1: [Short Description]
- **Severity:** Critical / High / Medium / Low
- **Location:** file.php:line_number
- **Description:** Detailed description of the issue
- **Expected:** What should happen
- **Actual:** What actually happened
- **Screenshot:** screenshots/issue-1.png
- **Reproduction Steps:**
  1. Step one
  2. Step two
  3. Step three

### Issue #2: [Short Description]
...

---

## Database Verification

\`\`\`sql
SELECT * FROM users WHERE email = 'test@example.com';
-- Results:
-- [paste results here]

SELECT * FROM user_preferences WHERE user_id = X;
-- Results:
-- [paste results here]
\`\`\`

---

## Console Errors

\`\`\`
[paste JavaScript console errors here]
\`\`\`

---

## PHP Error Log

\`\`\`
[paste relevant PHP errors here]
\`\`\`

---

## Test Summary

**Passed:** X / Y tests
**Failed:** Z tests
**Blocked:** 0 tests

**Recommendation:**
- [ ] Ready for production
- [ ] Fix critical issues first
- [ ] Needs major rework

**Next Steps:**
1. Fix Issue #1 (critical)
2. Retest after fixes
3. Proceed to venue check-in testing
```

---

## Running Tests

1. **Setup test environment:**
   ```bash
   php db/generate-test-users.php
   ```

2. **Run tests manually** using test-personas.md checklist

3. **Verify database:**
   ```bash
   php db/verify-test-data.php
   ```

4. **Document results** using template above

5. **Cleanup:**
   ```bash
   php db/cleanup-test-users.php
   ```

---

## Automation (Future)

Consider creating automated E2E tests using:
- Selenium WebDriver
- PHPUnit for backend tests
- Playwright for browser automation

For now, manual testing with this checklist is sufficient for MVP launch.
