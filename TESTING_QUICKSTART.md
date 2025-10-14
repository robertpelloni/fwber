# FWBer E2E Testing - Quick Start Guide

**Status:** Ready to Execute
**Prerequisites:** ✅ All testing infrastructure complete
**Estimated Time:** 4-5 hours for complete testing

---

## Overview

This guide walks through executing end-to-end tests for the FWBer profile form system. Follow these steps in order.

---

## Step 1: Set Up Test Environment (5 minutes)

### 1.1 Check Prerequisites
```bash
# Verify PHP is installed
php --version
# Should show PHP 8.x

# Verify MySQL is running
mysql --version

# Verify you can connect to database
php -r "require '_db.php'; echo 'DB connection OK';"
```

### 1.2 Create Test Users
```bash
cd C:\Users\mrgen\fwber
php db/generate-test-users.php
```

**Expected Output:**
```
FWBer Test User Generator
=========================

Test Password: TestPass123!
Password Hash: [long hash string]
Password Salt: [long hex string]

Creating test user: JohnTest (john.test@example.com)
  ✅ Created user ID: X

Creating test user: JaneTest (jane.test@example.com)
  ✅ Created user ID: Y

Creating test user: AlexTest (alex.test@example.com)
  ✅ Created user ID: Z

✅ Test users created successfully!
```

**Test Credentials (all users):**
- Password: `TestPass123!`

---

## Step 2: Test Persona 1 - John Doe (Male User) [60-90 minutes]

### 2.1 Sign In
1. Open browser: `http://localhost/fwber/signin.php`
2. Email: `john.test@example.com`
3. Password: `TestPass123!`
4. Click "Sign In"

**✅ Expected:** Successfully signed in, redirected to profile or matches page

### 2.2 Navigate to Profile Form
- Go to: `http://localhost/fwber/profile-form.php` OR `edit-profile.php`

### 2.3 Follow Test Checklist
Open: `db/test-personas.md` → **Persona 1: John Doe** section

**Key Tests:**
- [ ] Penis size field visible
- [ ] Body hair field visible
- [ ] Breast size field NOT visible
- [ ] Fill ALL 150+ fields
- [ ] Submit form
- [ ] Reload page - verify data persists

### 2.4 Document Results
Create file: `test-results/2025-10-11-john-test.md`

Use template from `test-results/README.md`

**Take screenshots of:**
- Profile form (showing male-specific fields)
- Any errors encountered
- Database verification results

### 2.5 Verify Database
```bash
php db/verify-test-data.php
```

**Expected Output:**
```
Testing: john.test@example.com
--------------------------------------------------
✅ User found (ID: X)
  - Username: JohnTest
  - Age: 28
  - Gender: male
  - Pubic Hair: Trimmed
  - Penis Size: Average
  - Body Hair: Some
  ✅ Breast Size: NULL (correct for male)
  - Preferences saved: 15
  ✅ User has preferences saved
```

---

## Step 3: Test Persona 2 - Jane Smith (Female User) [60-90 minutes]

### 3.1 Sign Out and Sign In as Jane
1. Sign out John
2. Sign in with:
   - Email: `jane.test@example.com`
   - Password: `TestPass123!`

### 3.2 Navigate to Profile Form
- Go to: `http://localhost/fwber/profile-form.php`

### 3.3 Follow Test Checklist
Open: `db/test-personas.md` → **Persona 2: Jane Smith** section

**Key Tests:**
- [ ] Breast size field visible
- [ ] Penis size field NOT visible
- [ ] Body hair field NOT visible
- [ ] Fill ALL applicable fields
- [ ] Submit form
- [ ] Reload page - verify data persists

### 3.4 Document Results
Create file: `test-results/2025-10-11-jane-test.md`

### 3.5 Verify Database
```bash
php db/verify-test-data.php
```

**Expected Output:**
```
Testing: jane.test@example.com
--------------------------------------------------
✅ User found (ID: Y)
  - Gender: female
  - Breast Size: C-cup
  ✅ Penis Size: NULL (correct for female)
  ✅ Body Hair: NULL (correct for female)
```

---

## Step 4: Test Persona 3 - Alex Taylor (Non-Binary User) [60-90 minutes]

### 4.1 Sign Out and Sign In as Alex
1. Sign out Jane
2. Sign in with:
   - Email: `alex.test@example.com`
   - Password: `TestPass123!`

### 4.2 Navigate to Profile Form
- Go to: `http://localhost/fwber/profile-form.php`

### 4.3 Follow Test Checklist
Open: `db/test-personas.md` → **Persona 3: Alex Taylor** section

**Key Tests:**
- [ ] ALL gender-specific fields visible (penis size, body hair, breast size)
- [ ] Can fill any combination of fields
- [ ] Data leakage prevention still works
- [ ] Submit form
- [ ] Reload page - verify data persists

### 4.4 Document Results
Create file: `test-results/2025-10-11-alex-test.md`

### 4.5 Verify Database
```bash
php db/verify-test-data.php
```

**Expected Output:**
```
Testing: alex.test@example.com
--------------------------------------------------
✅ User found (ID: Z)
  - Gender: non-binary
  - Penis Size: [value or NOT SET]
  - Body Hair: Minimal
  - Breast Size: [value or NOT SET]
  ✅ Non-binary user can have any combination
```

---

## Step 5: Cross-Persona Tests [30 minutes]

### 5.1 Data Leakage Prevention Test

**Test with John (male user):**
1. Open browser developer console (F12)
2. Navigate to profile form
3. Try to inject female-specific field via console:
   ```javascript
   // Try to add hidden breastSize field
   let input = document.createElement('input');
   input.type = 'hidden';
   input.name = 'breastSize';
   input.value = 'D-cup';
   document.querySelector('form').appendChild(input);
   ```
4. Submit form
5. Check database - breastSize should be NULL (rejected by backend)

**Expected:** ✅ Backend validation prevents data leakage

### 5.2 CSRF Token Test
1. Save CSRF token from form
2. Try to submit form with old/invalid token
3. Should be rejected

### 5.3 Validation Tests
Test with any user:
- [ ] Submit with age < 18 → Rejected
- [ ] Submit with missing required fields → Error messages shown
- [ ] Submit with invalid email → Rejected

---

## Step 6: Matching Algorithm Integration [30 minutes]

After all profiles are complete:

### 6.1 Test Matches Display
1. Sign in as John
2. Navigate to: `http://localhost/fwber/matches.php`
3. Verify matches are shown
4. Check that preferences are being respected

### 6.2 Test Matching Logic
```bash
# Check matching algorithm processes new preference fields
php -r "require '_getMatches.php'; echo 'Matching algorithm OK';"
```

---

## Step 7: Venue Check-In Smoke Test [30 minutes]

### 7.1 Navigate to Venue Check-In
- URL: `http://localhost/fwber/api/venue-checkin.php`
  OR find venue check-in link in UI

### 7.2 Basic Functionality Check
- [ ] Page loads without errors
- [ ] Can select a venue (if any exist)
- [ ] Check-in button works
- [ ] Check-in is recorded

**Note:** This is a basic smoke test, not comprehensive venue testing

---

## Step 8: Collect and Review Results [30 minutes]

### 8.1 Run Final Database Verification
```bash
php db/verify-test-data.php
```

**Expected:** ✅ ALL TESTS PASSED

### 8.2 Review Test Result Files
Check these files exist and are complete:
- `test-results/2025-10-11-john-test.md`
- `test-results/2025-10-11-jane-test.md`
- `test-results/2025-10-11-alex-test.md`
- `test-results/errors.log` (if any errors occurred)
- `test-results/screenshots/` (screenshots of any issues)

### 8.3 Create Summary Report
Create: `test-results/2025-10-11-SUMMARY.md`

**Template:**
```markdown
# E2E Test Summary - 2025-10-11

## Overall Status
- ✅ PASS / ❌ FAIL / ⚠️ PARTIAL

## Personas Tested
- John Doe (male): ✅/❌
- Jane Smith (female): ✅/❌
- Alex Taylor (non-binary): ✅/❌

## Critical Issues Found
1. [Issue description if any]

## Non-Critical Issues
1. [Issue description if any]

## Recommendations
- [ ] Ready for production
- [ ] Fix issues and retest
- [ ] Needs major rework

## Next Steps
1. [What to do next]
```

---

## Step 9: Cleanup (Optional)

### 9.1 Remove Test Users
```bash
php db/cleanup-test-users.php
# Type 'DELETE' to confirm
```

### 9.2 Create Database Snapshot
```bash
# Save current state for reference
mysqldump -u root -p fwber > test-results/database-snapshots/after-test.sql
```

---

## Step 10: Report to Team

### 10.1 Update AI_TASKS.md
Mark E2E testing task as complete or document blockers

### 10.2 Update Project Status
If all tests pass:
- Update PROJECT_STATE.md: "E2E Testing: ✅ COMPLETE"
- Update AI_TASKS.md: Move to security audit phase

If tests fail:
- Create tickets for issues found
- Prioritize critical bugs
- Retest after fixes

---

## Troubleshooting

### Issue: Test users not created
**Solution:** Check database connection in `_db.php`

### Issue: CSRF token errors
**Solution:** Check `SecurityManager.php` is loaded

### Issue: Fields not saving
**Solution:** Check `edit-profile.php` and `ProfileManager.php` logs

### Issue: Gender-specific fields not showing/hiding
**Solution:** Check JavaScript console for errors in `profile-form.php`

---

## Success Criteria

All tests must pass:
- ✅ All 3 personas complete profile successfully
- ✅ Data persists correctly in database
- ✅ Gender-specific fields show/hide correctly
- ✅ No data leakage between genders
- ✅ CSRF protection working
- ✅ Form validation catches errors
- ✅ No console errors
- ✅ Mobile responsive (basic check)

**If all criteria met:** 🎉 Profile system is launch-ready!

---

## Time Budget

| Task | Estimated Time |
|------|----------------|
| Setup | 5 min |
| John persona test | 60-90 min |
| Jane persona test | 60-90 min |
| Alex persona test | 60-90 min |
| Cross-persona tests | 30 min |
| Matching integration | 30 min |
| Venue check-in | 30 min |
| Results review | 30 min |
| **TOTAL** | **4-5 hours** |

---

## Next Phase: Security Audit

After E2E tests pass, proceed to security audit:
- OWASP Top 10 review
- Rate limiting tests
- SQL injection attempts
- XSS vulnerability checks
- File upload security
- Session security validation

**See:** Security audit checklist (to be created)

---

## Questions or Issues?

- Check `db/test-personas.md` for detailed test cases
- Check `test-results/README.md` for result templates
- Review `MCP_TEST_RESULTS.md` and `CLI_TEST_RESULTS.md` for tool usage
- Consult AI collaboration log for context

**Last Updated:** 2025-10-11
**Created By:** Claude Code CLI (Sonnet 4.5)
**Based On:** Codex strategic recommendation + comprehensive test infrastructure
